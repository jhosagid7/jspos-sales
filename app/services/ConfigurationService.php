<?php

namespace App\Services;

use App\Models\Configuration;

class ConfigurationService
{
    public static function getDecimalPlaces()
    {
        // Suponiendo que la columna clave se llama 'key' y el valor 'value'
        return Configuration::first()?->decimals ?? 0;
    }

    public static function getVat()
    {

        // Suponiendo que la columna clave se llama 'key' y el valor 'value'
        return Configuration::first()?->vat ?? 0;
    }
}
