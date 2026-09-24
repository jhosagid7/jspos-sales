<?php

use App\Services\ConfigurationService;

if (!function_exists('formatMoney')) {
    /**
     * Format a number with the configured decimal places
     * 
     * @param float $amount
     * @return string
     */
    function formatMoney($amount)
    {
        $decimals = ConfigurationService::getDecimalPlaces();
        return number_format($amount, $decimals);
    }
}

if (!function_exists('term')) {
    /**
     * Get customized regional terminology label.
     *
     * @param string $key e.g. 'warehouse', 'warehouses', 'partner', 'partners'
     * @param string|null $default
     * @return string
     */
    function term(string $key, ?string $default = null): string
    {
        return \App\Services\TerminologyService::get($key, $default);
    }
}
