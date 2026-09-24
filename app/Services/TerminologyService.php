<?php

namespace App\Services;

use App\Models\Configuration;

class TerminologyService
{
    protected static ?Configuration $config = null;

    /**
     * Get the configured terminology label.
     */
    public static function get(string $key, ?string $default = null): string
    {
        if (self::$config === null) {
            try {
                self::$config = Configuration::first();
            } catch (\Throwable $th) {
                self::$config = null;
            }
        }

        if (self::$config) {
            return self::$config->getCustomLabel($key, $default);
        }

        $fallbacks = [
            'warehouse' => 'Depósito',
            'warehouses' => 'Depósitos',
            'warehouse_lower' => 'depósito',
            'warehouses_lower' => 'depósitos',
            'partner' => 'Socio',
            'partners' => 'Socios',
            'partner_lower' => 'socio',
            'partners_lower' => 'socios',
        ];

        return $fallbacks[$key] ?? ($default ?? $key);
    }

    /**
     * Clear cached configuration instance.
     */
    public static function clearCache(): void
    {
        self::$config = null;
    }
}
