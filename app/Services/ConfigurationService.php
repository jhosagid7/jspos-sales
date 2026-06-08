<?php

namespace App\Services;

use App\Models\Configuration;

class ConfigurationService
{
    protected static $config;

    public static function getConfig()
    {
        if (self::$config === null) {
            self::$config = Configuration::first();
        }
        return self::$config;
    }

    public static function getDecimalPlaces()
    {
        return self::getConfig()?->decimals ?? 0;
    }

    public static function getVat()
    {
        return self::getConfig()?->vat ?? 0;
    }

    public static function getSequentialCutOffDate()
    {
        return self::getConfig()?->sequential_cut_off_date ?? '2026-06-03 00:00:00';
    }
}
