<?php

namespace App\Services;

use App\Models\Bank;

class IncomeReportService
{
    /**
     * Get the list of categories for income reports.
     * Cash categories first, followed by active configured banks, then standard electronic methods (ZELLE, USDT).
     *
     * @param array $extraBankNames Extra bank names found in transactions to ensure they are represented.
     * @return array
     */
    public static function getCategories(array $extraBankNames = []): array
    {
        $categories = [
            'DOLARES' => 'DOLARES',
            'PESOS' => 'PESOS',
            'EFECTIVO BS' => 'EFECTIVO BS',
        ];

        // 1. Fetch active configured banks
        $banks = Bank::where('state', 1)->orderBy('sort')->get();
        if ($banks->isEmpty()) {
            $banks = Bank::orderBy('sort')->get();
        }

        $hasCommercialBanks = false;
        foreach ($banks as $bank) {
            $nameUpper = strtoupper(trim($bank->name));
            if ($nameUpper === '') {
                continue;
            }

            // Group cryptocurrency / USDT banks into the USDT category
            if (str_contains($nameUpper, 'USDT') || str_contains($nameUpper, 'BINANCE') || str_contains($nameUpper, 'CRIPTO')) {
                continue;
            }

            // Group Zelle banks into ZELLE category
            if (str_contains($nameUpper, 'ZELLE')) {
                continue;
            }

            $categories[$nameUpper] = $nameUpper;
            $hasCommercialBanks = true;
        }

        // If no commercial banks configured at all in database, provide legacy defaults
        if (!$hasCommercialBanks) {
            $categories['BANCO DE VENEZUELA'] = 'BANCO DE VENEZUELA';
            $categories['BANCO PROVINCIAL'] = 'BANCO PROVINCIAL';
            $categories['BANCO MERCANTIL'] = 'BANCO MERCANTIL';
        }

        // Add any extra bank names found in transaction details
        foreach ($extraBankNames as $extra) {
            $extraUpper = strtoupper(trim($extra));
            if ($extraUpper === '') {
                continue;
            }
            if (str_contains($extraUpper, 'USDT') || str_contains($extraUpper, 'BINANCE') || str_contains($extraUpper, 'CRIPTO') || str_contains($extraUpper, 'ZELLE')) {
                continue;
            }
            if (!isset($categories[$extraUpper])) {
                $categories[$extraUpper] = $extraUpper;
            }
        }

        // Always include standard electronic categories
        $categories['ZELLE'] = 'ZELLE';
        $categories['USDT'] = 'USDT';

        return $categories;
    }

    /**
     * Resolve which category a payment belongs to.
     *
     * @param string $paymentMethod
     * @param string|null $currencyCode
     * @param string|null $bankName
     * @param array $categories Reference to categories array so missing banks can be dynamically registered
     * @return string
     */
    public static function resolveCategory(string $paymentMethod, ?string $currencyCode, ?string $bankName, array &$categories): string
    {
        $method = strtolower(trim($paymentMethod));
        $curr = strtoupper(trim($currencyCode ?? ''));
        $bank = strtoupper(trim($bankName ?? ''));

        // 1. Cash payments
        if ($method === 'cash') {
            if ($curr === 'USD') {
                return 'DOLARES';
            } elseif ($curr === 'COP') {
                return 'PESOS';
            } elseif (in_array($curr, ['VES', 'VED']) || $curr === '') {
                return 'EFECTIVO BS';
            }
            return 'DOLARES';
        }

        // 2. USDT / Binance / Crypto
        if ($method === 'usdt' || str_contains($bank, 'USDT') || str_contains($bank, 'BINANCE') || str_contains($bank, 'CRIPTO')) {
            return 'USDT';
        }

        // 3. Zelle
        if ($method === 'zelle' || str_contains($bank, 'ZELLE')) {
            return 'ZELLE';
        }

        // 4. Bank transfers, deposits, or other electronic methods
        if ($bank !== '') {
            // Direct exact match
            if (isset($categories[$bank])) {
                return $bank;
            }

            // Keyword / substring match with existing categories
            foreach ($categories as $catKey => $catVal) {
                if (in_array($catKey, ['DOLARES', 'PESOS', 'EFECTIVO BS', 'ZELLE', 'USDT'])) {
                    continue;
                }
                if ($bank === $catKey || str_contains($bank, $catKey) || str_contains($catKey, $bank)) {
                    return $catKey;
                }
            }

            // Check BDV abbreviation
            if (str_contains($bank, 'BDV') && isset($categories['BANCO DE VENEZUELA'])) {
                return 'BANCO DE VENEZUELA';
            }

            // If it's a new bank not yet in categories, register it dynamically
            $categories[$bank] = $bank;
            return $bank;
        }

        // Fallback if method is bank but bank name was empty:
        foreach ($categories as $catKey => $catVal) {
            if (!in_array($catKey, ['DOLARES', 'PESOS', 'EFECTIVO BS', 'ZELLE', 'USDT'])) {
                return $catKey;
            }
        }

        return 'BANCO DE VENEZUELA';
    }
}
