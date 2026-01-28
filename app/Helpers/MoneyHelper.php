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
