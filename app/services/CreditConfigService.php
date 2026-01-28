<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Models\Configuration;
use App\Models\CreditDiscountRule;

class CreditConfigService
{
    /**
     * Obtiene la configuración de crédito aplicable según jerarquía:
     * Cliente > Vendedor > Global
     *
     * @param Customer $customer
     * @param User|null $seller
     * @return array
     */
    public static function getCreditConfig(Customer $customer, ?User $seller = null): array
    {
        // 1. Intentar obtener configuración del Cliente (Prioridad 1)
        if ($customer->allow_credit !== null && $customer->allow_credit !== false) {
            return [
                'allow_credit' => $customer->allow_credit,
                'credit_days' => $customer->credit_days,
                'credit_limit' => $customer->credit_limit,
                'usd_payment_discount' => $customer->usd_payment_discount,
                'discount_rules' => self::getDiscountRules('customer', $customer->id),
                'source' => 'customer',
                'source_name' => $customer->name
            ];
        }

        // 2. Intentar obtener configuración del Vendedor (Prioridad 2)
        if ($seller && $seller->seller_allow_credit !== null && $seller->seller_allow_credit !== false) {
            return [
                'allow_credit' => $seller->seller_allow_credit,
                'credit_days' => $seller->seller_credit_days,
                'credit_limit' => $seller->seller_credit_limit,
                'usd_payment_discount' => $seller->seller_usd_payment_discount,
                'discount_rules' => self::getDiscountRules('seller', $seller->id),
                'source' => 'seller',
                'source_name' => $seller->name
            ];
        }

        // 3. Usar configuración Global (Prioridad 3 - Fallback)
        $config = Configuration::first();
        return [
            'allow_credit' => $config->global_allow_credit ?? true,
            'credit_days' => $config->global_credit_days ?? 30,
            'credit_limit' => $config->global_credit_limit,
            'usd_payment_discount' => $config->global_usd_payment_discount,
            'discount_rules' => self::getDiscountRules('global', null),
            'source' => 'global',
            'source_name' => 'Sistema'
        ];
    }

    /**
     * Obtiene las reglas de descuento/recargo para una entidad específica
     *
     * @param string $entityType ('customer', 'seller', 'global')
     * @param int|null $entityId
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public static function getDiscountRules(string $entityType, ?int $entityId)
    {
        return CreditDiscountRule::where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->orderBy('days_from')
            ->get();
    }

    /**
     * Calcula el descuento o recargo aplicable según los días transcurridos
     *
     * @param float $saleTotal Monto total de la venta/saldo pendiente
     * @param int $paymentDays Días transcurridos desde la fecha de la factura
     * @param \Illuminate\Database\Eloquent\Collection $rules Reglas de descuento
     * @return array|null ['amount', 'percentage', 'reason', 'days'] o null si no aplica
     */
    public static function calculateDiscount(float $saleTotal, int $paymentDays, $rules): ?array
    {
        foreach ($rules as $rule) {
            // Verificar si el pago cae dentro del rango de esta regla
            $inRange = $paymentDays >= $rule->days_from && 
                       ($rule->days_to === null || $paymentDays <= $rule->days_to);

            if ($inRange) {
                $discountAmount = $saleTotal * ($rule->discount_percentage / 100);

                return [
                    'amount' => round($discountAmount, 2),
                    'percentage' => $rule->discount_percentage,
                    'reason' => $rule->description ?? "Ajuste por {$paymentDays} días",
                    'days' => $paymentDays,
                    'rule_type' => $rule->rule_type
                ];
            }
        }

        return null; // No hay descuento/recargo aplicable
    }

    /**
     * Valida si el cliente puede comprar a crédito según su límite
     *
     * @param Customer $customer
     * @param float $newSaleAmount Monto de la nueva venta
     * @return array ['allowed' => bool, 'message' => string, 'current_debt' => float, 'available' => float]
     */
    public static function validateCreditLimit(Customer $customer, float $newSaleAmount): array
    {
        $seller = $customer->seller;
        $creditConfig = self::getCreditConfig($customer, $seller);

        // Si no hay límite configurado, permitir
        if ($creditConfig['credit_limit'] === null) {
            return [
                'allowed' => true,
                'message' => 'Sin límite de crédito configurado',
                'current_debt' => 0,
                'available' => PHP_FLOAT_MAX
            ];
        }

        // Calcular deuda actual (ventas pendientes - pagos realizados)
        // Usamos withSum para ser eficientes y no cargar todos los pagos en memoria
        $sales = $customer->sales()
            ->where('status', 'PENDING')
            ->withSum('payments', 'amount')
            ->get();
            
        $currentDebt = $sales->sum(function($sale) {
            return $sale->total - ($sale->payments_sum_amount ?? 0);
        });

        $availableCredit = $creditConfig['credit_limit'] - $currentDebt;

        if ($newSaleAmount > $availableCredit) {
            return [
                'allowed' => false,
                'message' => "Límite de crédito excedido. Disponible: $" . number_format($availableCredit, 2),
                'current_debt' => $currentDebt,
                'available' => $availableCredit,
                'limit' => $creditConfig['credit_limit']
            ];
        }

        return [
            'allowed' => true,
            'message' => 'Crédito disponible',
            'current_debt' => $currentDebt,
            'available' => $availableCredit,
            'limit' => $creditConfig['credit_limit']
        ];
    }

    /**
     * Calcula descuento por pago en USD (Zelle o Efectivo Dólar)
     *
     * @param Customer $customer
     * @param User|null $seller
     * @param float $amount Monto del pago
     * @return array|null ['amount', 'percentage'] o null si no aplica
     */
    public static function calculateUsdDiscount(Customer $customer, ?User $seller, float $amount): ?array
    {
        $creditConfig = self::getCreditConfig($customer, $seller);

        if ($creditConfig['usd_payment_discount'] && $creditConfig['usd_payment_discount'] > 0) {
            $discountAmount = $amount * ($creditConfig['usd_payment_discount'] / 100);

            return [
                'amount' => round($discountAmount, 2),
                'percentage' => $creditConfig['usd_payment_discount']
            ];
        }

        return null;
    }
}
