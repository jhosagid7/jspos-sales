<?php

namespace Tests\Unit\Fiscal;

use App\Models\SalePaymentDetail;
use App\Services\Fiscal\FiscalPrinterFactory;
use App\Services\Fiscal\ValueObjects\FiscalPayment;
use PHPUnit\Framework\TestCase;

class FiscalPrinterFactoryTest extends TestCase
{
    public function test_resolves_correct_media_codes_for_ves(): void
    {
        $this->assertSame(FiscalPayment::MEDIA_EFECTIVO_VES, FiscalPrinterFactory::resolveMediaCode('cash', 'VES'));
        $this->assertSame(FiscalPayment::MEDIA_DEBITO_VES, FiscalPrinterFactory::resolveMediaCode('punto_de_venta', 'VES'));
        $this->assertSame(FiscalPayment::MEDIA_TRANSFERENCIA_VES, FiscalPrinterFactory::resolveMediaCode('pago_movil', 'VES'));
        $this->assertSame(FiscalPayment::MEDIA_TRANSFERENCIA_VES, FiscalPrinterFactory::resolveMediaCode('bank_transfer', 'VED'));
    }

    public function test_resolves_correct_media_codes_for_foreign_currencies(): void
    {
        $this->assertSame(FiscalPayment::MEDIA_EFECTIVO_USD, FiscalPrinterFactory::resolveMediaCode('cash', 'USD'));
        $this->assertSame(FiscalPayment::MEDIA_ZELLE, FiscalPrinterFactory::resolveMediaCode('zelle', 'USD'));
        $this->assertSame(FiscalPayment::MEDIA_CRIPTO, FiscalPrinterFactory::resolveMediaCode('usdt', 'USDT'));
        $this->assertSame(FiscalPayment::MEDIA_TARJETA_USD, FiscalPrinterFactory::resolveMediaCode('tarjeta_intl', 'EUR'));
    }

    public function test_maps_sale_payment_details_to_fiscal_payments(): void
    {
        $p1 = new SalePaymentDetail([
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 10.0,
            'exchange_rate' => 50.0,
        ]);

        $p2 = new SalePaymentDetail([
            'payment_method' => 'pago_movil',
            'currency_code' => 'VES',
            'amount' => 100.0,
            'exchange_rate' => 1.0,
        ]);

        $fiscalPayments = FiscalPrinterFactory::mapPayments([$p1, $p2]);

        $this->assertCount(2, $fiscalPayments);
        $this->assertSame(FiscalPayment::MEDIA_EFECTIVO_USD, $fiscalPayments[0]->mediaCode);
        $this->assertSame(500.0, $fiscalPayments[0]->amountVes);

        $this->assertSame(FiscalPayment::MEDIA_TRANSFERENCIA_VES, $fiscalPayments[1]->mediaCode);
        $this->assertSame(100.0, $fiscalPayments[1]->amountVes);
    }
}
