<?php

namespace App\Services\Fiscal;

use App\Models\Configuration;
use App\Models\DeviceAuthorization;
use App\Models\SalePaymentDetail;
use App\Services\Fiscal\Drivers\FakeDriver;
use App\Services\Fiscal\Drivers\FiscalPrinterDriverInterface;
use App\Services\Fiscal\Drivers\IntTfhkaDriver;
use App\Services\Fiscal\ValueObjects\FiscalPayment;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

/**
 * Factory & Resolver for fiscal printing configuration, driver resolution,
 * and payment mapping from JSPOS to TFHKA media codes.
 */
class FiscalPrinterFactory
{
    /**
     * Resolves the active fiscal printer driver based on:
     * DeviceAuthorization (per workstation) -> Global Configuration.
     */
    public static function resolveDriver(?Configuration $config = null): ?FiscalPrinterDriverInterface
    {
        $config = $config ?? Configuration::first();
        if (!$config || !$config->fiscal_printer_enabled) {
            return null;
        }

        // Test environment fallback
        if (app()->runningUnitTests()) {
            return new FakeDriver();
        }

        $deviceAuth = self::resolveDevice();

        // Device override: if device explicitly disabled fiscal printing
        if ($deviceAuth && $deviceAuth->fiscal_printer_enabled === false) {
            return null;
        }

        $driverType = $deviceAuth?->fiscal_printer_driver ?? 'intTfhka';
        $port = $deviceAuth?->fiscal_printer_port ?? config('services.fiscal.port', 'COM99');

        if ($driverType === 'fake') {
            return new FakeDriver();
        }

        if ($driverType === 'intTfhka') {
            $exeDir = config('services.fiscal.int_tfhka_dir') 
                ?? base_path('vendor/tfhka/windows') 
                ?? 'C:\\laragon\\www\\jspos-sales\\vendor\\tfhka\\windows';

            if (!is_dir($exeDir)) {
                // Fallback to local storage or standard app path if configured
                $exeDir = public_path('tfhka');
            }

            return new IntTfhkaDriver($exeDir, $port);
        }

        return null;
    }

    /**
     * Build the FiscalPrinterService with driver, composer and settings.
     */
    public static function make(?Configuration $config = null): ?FiscalPrinterService
    {
        $config = $config ?? Configuration::first();
        if (!$config || !$config->fiscal_printer_enabled) {
            return null;
        }

        $driver = self::resolveDriver($config);
        if (!$driver) {
            return null;
        }

        $deviceAuth = self::resolveDevice();
        $model = $deviceAuth?->fiscal_printer_model ?? $config->fiscal_printer_model ?? 'hka80';
        $maxName = in_array(strtolower($model), ['pp9', 'pp9plus']) ? 40 : 20;

        return new FiscalPrinterService(
            driver: $driver,
            igtfEnabled: (bool) $config->is_special_taxpayer,
            maxName: $maxName
        );
    }

    /**
     * Map a collection of SalePaymentDetail to FiscalPayment[] with proper TFHKA media codes.
     * 
     * TFHKA Media:
     * 01: Efectivo VES
     * 02: Tarjeta de Débito VES (Punto)
     * 04: Transferencia VES (Pago Móvil / Banco)
     * 20: Efectivo USD / Divisa
     * 21: Tarjeta / POS Internacional
     * 22: Zelle / Divisa electrónica
     * 23: Cripto / USDT
     * 
     * @param iterable<SalePaymentDetail> $paymentDetails
     * @param float $toVesRate Default exchange rate to convert divisas to VES for fiscal printer
     * @param bool|null $isSpecialTaxpayer When null, automatically loaded from Configuration
     * @return FiscalPayment[]
     */
    public static function mapPayments(iterable $paymentDetails, float $toVesRate = 1.0, ?bool $isSpecialTaxpayer = null): array
    {
        if ($isSpecialTaxpayer === null) {
            $isSpecialTaxpayer = (bool) (\App\Models\Configuration::value('is_special_taxpayer') ?? false);
        }

        $payments = [];

        foreach ($paymentDetails as $detail) {
            $currency = strtoupper(trim($detail->currency_code ?? ''));
            $method = strtolower(trim($detail->payment_method ?? ''));
            $rate = (float) ($detail->exchange_rate ?: $toVesRate);
            $amount = (float) $detail->amount;

            // Compute amount in VES
            $amountVes = in_array($currency, ['VED', 'VES', 'BS', 'BS.']) 
                ? $amount 
                : round($amount * $rate, 2);

            if ($amountVes <= 0) {
                continue;
            }

            $mediaCode = self::resolveMediaCode($method, $currency, $isSpecialTaxpayer);
            $payments[] = new FiscalPayment($mediaCode, $amountVes);
        }

        return $payments;
    }

    /**
     * Resolve TFHKA 2-digit media code from method and currency.
     * When $isSpecialTaxpayer is false (ordinary taxpayer), fiscal printers do not accept
     * foreign media codes (20-24) without Flag 50, so payments must be routed through
     * standard media codes (01 Efectivo, 02 Débito, 04 Transferencia).
     */
    public static function resolveMediaCode(string $method, string $currency, bool $isSpecialTaxpayer = false): string
    {
        $isForeign = !in_array($currency, ['VED', 'VES', 'BS', 'BS.']);

        if ($isForeign && $isSpecialTaxpayer) {
            if (str_contains($method, 'zelle')) return FiscalPayment::MEDIA_ZELLE;
            if (str_contains($method, 'usdt') || str_contains($method, 'crypto')) return FiscalPayment::MEDIA_CRIPTO;
            if (str_contains($method, 'tarjeta') || str_contains($method, 'card')) return FiscalPayment::MEDIA_TARJETA_USD;
            return FiscalPayment::MEDIA_EFECTIVO_USD;
        }

        // Local currency (VES) or ordinary taxpayer processing
        if (str_contains($method, 'tarjeta') || str_contains($method, 'debito') || str_contains($method, 'punto') || str_contains($method, 'card')) {
            return FiscalPayment::MEDIA_DEBITO_VES;
        }
        if (str_contains($method, 'pago_movil') || str_contains($method, 'transfer') || str_contains($method, 'bank') || str_contains($method, 'zelle')) {
            return FiscalPayment::MEDIA_TRANSFERENCIA_VES;
        }

        return FiscalPayment::MEDIA_EFECTIVO_VES;
    }

    /**
     * Helper to resolve the active DeviceAuthorization.
     */
    private static function resolveDevice(): ?DeviceAuthorization
    {
        try {
            $deviceToken = request()?->cookie('device_token') 
                ?? Cookie::get('device_token') 
                ?? session('device_token') 
                ?? request()?->header('X-Device-Token');

            if ($deviceToken) {
                $auth = DeviceAuthorization::where('uuid', $deviceToken)->first();
                if ($auth) return $auth;
            }

            if (request() && request()->ip()) {
                return DeviceAuthorization::where('ip_address', request()->ip())
                    ->where('status', 'approved')
                    ->orderBy('last_accessed_at', 'desc')
                    ->first();
            }
        } catch (\Throwable $e) {
            Log::debug("Device resolution error: {$e->getMessage()}");
        }

        return null;
    }
}
