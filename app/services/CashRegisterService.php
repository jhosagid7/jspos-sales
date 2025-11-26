<?php

namespace App\Services;

use App\Models\CashRegister;
use App\Models\CashRegisterDetail;
use App\Models\CashMovement;
use App\Models\Currency;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Exception;

class CashRegisterService
{
    /**
     * Obtener la caja activa del usuario actual
     */
    public function getActiveCashRegister($userId = null)
    {
        $userId = $userId ?? Auth::id();
        return CashRegister::where('user_id', $userId)
            ->where('status', 'open')
            ->first();
    }

    /**
     * Verificar si el usuario tiene caja abierta
     */
    public function hasOpenRegister($userId = null)
    {
        return $this->getActiveCashRegister($userId) !== null;
    }

    /**
     * Abrir una nueva caja
     */
    public function openRegister($userId, array $openingAmounts, $notes = null)
    {
        if ($this->hasOpenRegister($userId)) {
            throw new Exception("El usuario ya tiene una caja abierta.");
        }

        return DB::transaction(function () use ($userId, $openingAmounts, $notes) {
            $primaryCurrency = Currency::where('is_primary', true)->first();
            $totalOpeningAmount = 0;

            // Crear registro de caja
            $register = CashRegister::create([
                'user_id' => $userId,
                'opening_date' => now(),
                'status' => 'open',
                'opening_notes' => $notes,
                'total_opening_amount' => 0 // Se actualizará después
            ]);

            // Procesar montos iniciales
            foreach ($openingAmounts as $currencyCode => $amount) {
                if ($amount <= 0) continue;

                $currency = Currency::where('code', $currencyCode)->firstOrFail();
                
                // Calcular valor en moneda principal
                $amountInPrimary = $amount;
                if (!$currency->is_primary) {
                    // Convertir a USD primero si es necesario (lógica simplificada, ajustar según sistema)
                    $amountInPrimary = $amount / $currency->exchange_rate * $primaryCurrency->exchange_rate;
                }
                
                $totalOpeningAmount += $amountInPrimary;

                // Guardar detalle
                CashRegisterDetail::create([
                    'cash_register_id' => $register->id,
                    'currency_code' => $currencyCode,
                    'type' => 'opening',
                    'amount' => $amount,
                    'amount_in_primary_currency' => $amountInPrimary,
                    'exchange_rate' => $currency->exchange_rate
                ]);

                // Registrar movimiento inicial
                CashMovement::create([
                    'cash_register_id' => $register->id,
                    'type' => 'opening',
                    'currency_code' => $currencyCode,
                    'amount' => $amount,
                    'amount_in_primary_currency' => $amountInPrimary,
                    'balance_after' => $amount,
                    'description' => 'Fondo inicial de caja'
                ]);
            }

            $register->update(['total_opening_amount' => $totalOpeningAmount]);

            return $register;
        });
    }

    /**
     * Obtener saldo actual de una moneda específica
     */
    public function getBalance($cashRegisterId, $currencyCode)
    {
        // Calcular saldo sumando movimientos
        return CashMovement::where('cash_register_id', $cashRegisterId)
            ->where('currency_code', $currencyCode)
            ->sum('amount');
    }

    /**
     * Validar si hay suficiente saldo para dar vuelto
     */
    public function validateChangeAvailability($cashRegisterId, $currencyCode, $amountNeeded)
    {
        $currentBalance = $this->getBalance($cashRegisterId, $currencyCode);
        
        if ($currentBalance < $amountNeeded) {
            return [
                'valid' => false,
                'current_balance' => $currentBalance,
                'shortage' => $amountNeeded - $currentBalance
            ];
        }

        return ['valid' => true, 'current_balance' => $currentBalance];
    }

    /**
     * Registrar movimiento por venta (pago o vuelto)
     */
    public function recordSaleMovement($cashRegisterId, $saleId, $type, $currencyCode, $amount, $description = null)
    {
        $currency = Currency::where('code', $currencyCode)->firstOrFail();
        $primaryCurrency = Currency::where('is_primary', true)->first();
        
        // Calcular valor en moneda principal
        $amountInPrimary = $amount;
        if (!$currency->is_primary) {
            $amountInPrimary = $amount / $currency->exchange_rate * $primaryCurrency->exchange_rate;
        }

        // Calcular saldo anterior
        $currentBalance = $this->getBalance($cashRegisterId, $currencyCode);
        
        return CashMovement::create([
            'cash_register_id' => $cashRegisterId,
            'sale_id' => $saleId,
            'type' => $type, // 'sale_payment' o 'sale_change'
            'currency_code' => $currencyCode,
            'amount' => $amount, // Positivo para pagos, negativo para vueltos
            'amount_in_primary_currency' => $amountInPrimary,
            'balance_after' => $currentBalance + $amount,
            'description' => $description
        ]);
    }

    /**
     * Cerrar caja
     */
    public function closeRegister($cashRegisterId, array $countedAmounts, $notes = null)
    {
        return DB::transaction(function () use ($cashRegisterId, $countedAmounts, $notes) {
            $register = CashRegister::findOrFail($cashRegisterId);
            $primaryCurrency = Currency::where('is_primary', true)->first();
            
            $totalExpected = 0;
            $totalCounted = 0;

            // Obtener todas las monedas que tuvieron movimientos
            $currencies = CashMovement::where('cash_register_id', $cashRegisterId)
                ->select('currency_code')
                ->distinct()
                ->pluck('currency_code');

            foreach ($currencies as $currencyCode) {
                $currency = Currency::where('code', $currencyCode)->first();
                
                // Calcular esperado (saldo del sistema)
                $expectedAmount = $this->getBalance($cashRegisterId, $currencyCode);
                
                // Obtener contado
                $countedAmount = $countedAmounts[$currencyCode] ?? 0;
                
                // Convertir a principal para totales globales
                $rate = $currency->exchange_rate;
                $expectedInPrimary = $currency->is_primary ? $expectedAmount : ($expectedAmount / $rate * $primaryCurrency->exchange_rate);
                $countedInPrimary = $currency->is_primary ? $countedAmount : ($countedAmount / $rate * $primaryCurrency->exchange_rate);
                
                $totalExpected += $expectedInPrimary;
                $totalCounted += $countedInPrimary;

                // Guardar detalle de cierre
                CashRegisterDetail::create([
                    'cash_register_id' => $register->id,
                    'currency_code' => $currencyCode,
                    'type' => 'closing',
                    'amount' => $countedAmount,
                    'amount_in_primary_currency' => $countedInPrimary,
                    'exchange_rate' => $rate
                ]);
            }

            // Actualizar registro principal
            $register->update([
                'closing_date' => now(),
                'status' => 'closed',
                'closing_notes' => $notes,
                'total_expected_amount' => $totalExpected,
                'total_counted_amount' => $totalCounted,
                'difference_amount' => $totalCounted - $totalExpected
            ]);

            // Registrar movimiento de cierre (ajuste si es necesario o solo cierre)
            CashMovement::create([
                'cash_register_id' => $register->id,
                'type' => 'closing',
                'currency_code' => $primaryCurrency->code,
                'amount' => 0,
                'amount_in_primary_currency' => 0,
                'balance_after' => 0, // Referencial
                'description' => 'Cierre de caja'
            ]);

            return $register;
        });
    }
}
