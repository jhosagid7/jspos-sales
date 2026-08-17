<?php

namespace App\Helpers;

use App\Models\Currency;
use Illuminate\Support\Facades\Cache;

class CurrencyHelper
{
    /**
     * Obtener la moneda principal (para visualización)
     * Sin caché para asegurar que siempre obtiene la configuración actual
     */
    public static function getPrimaryCurrency()
    {
        return Currency::where('is_primary', true)->first();
    }

    /**
     * Convertir de USD (base) a moneda principal
     */
    public static function toPrimary($amountInUSD)
    {
        $primary = self::getPrimaryCurrency();
        if (!$primary) {
            return $amountInUSD;
        }
        return $amountInUSD * $primary->exchange_rate;
    }

    /**
     * Convertir de moneda principal a USD (base)
     */
    public static function toUSD($amountInPrimary)
    {
        $primary = self::getPrimaryCurrency();
        if (!$primary || $primary->exchange_rate == 0) {
            return $amountInPrimary;
        }
        return $amountInPrimary / $primary->exchange_rate;
    }

    /**
     * Convertir entre dos monedas usando USD como intermediario
     */
    public static function convert($amount, $fromCurrencyCode, $toCurrencyCode)
    {
        if ($fromCurrencyCode === $toCurrencyCode) {
            return $amount;
        }

        $fromCurrency = Currency::where('code', $fromCurrencyCode)->first();
        $toCurrency = Currency::where('code', $toCurrencyCode)->first();

        if (!$fromCurrency || !$toCurrency) {
            return $amount;
        }

        // Convertir a USD primero
        $amountInUSD = $amount / $fromCurrency->exchange_rate;
        
        // Luego convertir a la moneda destino
        return $amountInUSD * $toCurrency->exchange_rate;
    }

    /**
     * Formatear monto con símbolo de moneda
     */
    public static function formatWithSymbol($amount, $currencyCode = null, $decimals = 2)
    {
        if (!$currencyCode) {
            $currency = self::getPrimaryCurrency();
        } else {
            $currency = Currency::where('code', $currencyCode)->first();
        }

        if (!$currency) {
            return '$' . number_format($amount, $decimals);
        }

        return $currency->symbol . number_format($amount, $decimals);
    }

    /**
     * Obtener símbolo de moneda
     */
    public static function getSymbol($currencyCode = null)
    {
        if (!$currencyCode) {
            $currency = self::getPrimaryCurrency();
        } else {
            $currency = Currency::where('code', $currencyCode)->first();
        }

        return $currency ? $currency->symbol : '$';
    }

    /**
     * Obtener etiqueta formateada para USDT BINANCE incluyendo ID si está configurado en bancos
     */
    public static function getUsdtLabel($bankName = null)
    {
        try {
            $query = \App\Models\Bank::where(function($q) {
                $q->where('name', 'like', '%usdt%')
                  ->orWhere('name', 'like', '%binance%')
                  ->orWhere('name', 'like', '%cripto%');
            });

            if (!empty($bankName)) {
                $normalizedInput = preg_replace('/[^a-zA-Z0-9]/', '', strtolower(trim($bankName)));
                $banks = $query->get();
                $matched = $banks->first(function($b) use ($normalizedInput) {
                    return preg_replace('/[^a-zA-Z0-9]/', '', strtolower(trim($b->name))) === $normalizedInput;
                });
                if ($matched) {
                    $name = strtoupper($matched->name);
                    return !empty($matched->account_number) ? "{$name} (ID: {$matched->account_number})" : $name;
                }
            }

            $first = $query->first();
            if ($first) {
                $name = strtoupper($first->name);
                return !empty($first->account_number) ? "{$name} (ID: {$first->account_number})" : $name;
            }
        } catch (\Exception $e) {
            // Fallback en caso de error
        }

        return 'USDT BINANCE';
    }

    /**
     * Limpiar caché de moneda principal
     */
    public static function clearCache()
    {
        Cache::forget('primary_currency');
    }
}
