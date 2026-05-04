<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use App\Models\Configuration;
use App\Models\CreditDiscountRule;
use App\Services\ConfigurationService;

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
        $globalConfig = ConfigurationService::getConfig();

        // 1. Resolver Reglas de Descuento (Estrategia de Fallback: Cliente -> Vendedor -> Global)
        // Si el cliente no tiene reglas específicas, hereda las del vendedor o globales.
        $discountRules = self::getDiscountRules('customer', $customer->id);
        
        if ($discountRules->isEmpty()) {
            if ($seller) {
                $discountRules = self::getDiscountRules('seller', $seller->id);
            }
            if ($discountRules->isEmpty()) {
                $discountRules = self::getDiscountRules('global', $globalConfig->id);
            }
        }

        // 2. Resolver Descuento USD (Fallback similar)
        // null significa "heredar", 0.00 es un valor explícito (si el usuario puso 0)
        // Asumiendo que null es el default cuando no se ha configurado.
        $usdPaymentDiscount = $customer->usd_payment_discount;
        $usdPaymentDiscountTag = $customer->usd_payment_discount_tag;
        if ($usdPaymentDiscount === null) {
            if ($seller && $seller->seller_usd_payment_discount !== null) {
                $usdPaymentDiscount = $seller->seller_usd_payment_discount;
                $usdPaymentDiscountTag = $seller->seller_usd_payment_discount_tag;
            } else {
                $usdPaymentDiscount = $globalConfig->global_usd_payment_discount;
                $usdPaymentDiscountTag = $globalConfig->global_usd_payment_discount_tag;
            }
        }

        // 3. Determinar Configuración de Crédito (Límites y Permisos)
        // Aquí SÍ respetamos la jerarquía estricta para allow_credit y credit_limit
        
        // A. Configuración de Cliente
        if ($customer->allow_credit !== null && $customer->allow_credit !== false) {
            return [
                'allow_credit' => $customer->allow_credit,
                'credit_days' => $customer->credit_days,
                'credit_limit' => $customer->credit_limit,
                'usd_payment_discount' => $usdPaymentDiscount, 
                'usd_payment_discount_tag' => $usdPaymentDiscountTag,
                'discount_rules' => $discountRules, 
                'source' => 'customer',
                'source_name' => $customer->name
            ];
        }

        // B. Configuración de Vendedor
        if ($seller && $seller->seller_allow_credit !== null && $seller->seller_allow_credit !== false) {
            return [
                'allow_credit' => $seller->seller_allow_credit,
                'credit_days' => $seller->seller_credit_days,
                'credit_limit' => $seller->seller_credit_limit,
                'usd_payment_discount' => $usdPaymentDiscount, 
                'usd_payment_discount_tag' => $usdPaymentDiscountTag,
                'discount_rules' => $discountRules, 
                'source' => 'seller',
                'source_name' => $seller->name
            ];
        }

        // C. Configuración Global
        return [
            'allow_credit' => $globalConfig->global_allow_credit ?? true,
            'credit_days' => $globalConfig->global_credit_days ?? 30,
            'credit_limit' => $globalConfig->global_credit_limit,
            'usd_payment_discount' => $usdPaymentDiscount, 
            'usd_payment_discount_tag' => $usdPaymentDiscountTag,
            'discount_rules' => $discountRules, 
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
                    'rule_type' => $rule->rule_type,
                    'tag' => $rule->tag ?? null
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
                'percentage' => $creditConfig['usd_payment_discount'],
                'tag' => $creditConfig['usd_payment_discount_tag'] ?? 'PD'
            ];
        }

        return null;
    }
    /**
     * Parsea el snapshot de reglas de crédito almacenado en la venta
     * Maneja tanto la estructura antigua (solo reglas) como la nueva (reglas + usd_payment_discount)
     *
     * @param array|null $snapshot
     * @return array ['discount_rules' => Collection, 'usd_payment_discount' => float|null]
     */
    public static function parseCreditSnapshot($snapshot): array
    {
        if (empty($snapshot)) {
            return [
                'discount_rules' => collect([]),
                'usd_payment_discount' => null
            ];
        }

        // Normalizar a array si viene como objeto
        if (is_object($snapshot)) {
            $snapshot = (array) $snapshot;
        }

        $rules = collect([]);
        $usdPaymentDiscount = null;

        if (isset($snapshot['discount_rules'])) {
            // Estructura Nueva (con usd_payment_discount)
            $rules = collect(json_decode(json_encode($snapshot['discount_rules'])))->map(function($item) { return (object)$item; });
            $usdPaymentDiscount = $snapshot['usd_payment_discount'] ?? 0;
        } else {
            // Estructura Antigua (solo array de reglas o array de objetos)
            $rules = collect(json_decode(json_encode($snapshot)))->map(function($item) { return (object)$item; });
            $usdPaymentDiscount = 0; // Legacy snapshot didn't have USD discount
        }

        return [
            'discount_rules' => $rules,
            'usd_payment_discount' => $usdPaymentDiscount,
            'usd_payment_discount_tag' => $snapshot['usd_payment_discount_tag'] ?? 'PD'
        ];
    }
}
