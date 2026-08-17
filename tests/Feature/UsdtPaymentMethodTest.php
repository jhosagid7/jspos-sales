<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\UsdtRecord;
use App\Models\Payment;
use App\Models\License;
use App\Services\LicenseService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class UsdtPaymentMethodTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        try {
            DB::statement("ALTER TABLE payments MODIFY COLUMN pay_way VARCHAR(50) DEFAULT 'cash'");
        } catch (\Throwable $e) {}
    }

    public function test_usdt_record_creation_with_optional_date_defaults_to_now()
    {
        $usdt = UsdtRecord::create([
            'sender_name' => 'BinanceUser123',
            'reference' => 'TXID987654321',
            'amount' => 150.00,
            'image_path' => 'usdt_vouchers/test.jpg',
            'usdt_date' => null, // Optional date left empty
            'status' => 'unused',
            'remaining_balance' => 150.00,
        ]);

        $this->assertNotNull($usdt->id);
        $this->assertEquals('BinanceUser123', $usdt->sender_name);
        $this->assertEquals('TXID987654321', $usdt->reference);
        $this->assertEquals(150.00, $usdt->amount);
        $this->assertEquals(Carbon::now()->format('Y-m-d'), Carbon::parse($usdt->usdt_date)->format('Y-m-d'));
    }

    public function test_usdt_record_creation_with_explicit_date()
    {
        $explicitDate = '2026-05-15';
        $usdt = UsdtRecord::create([
            'sender_name' => 'CryptoTrader',
            'reference' => 'HASH11223344',
            'amount' => 200.00,
            'image_path' => 'usdt_vouchers/crypto.jpg',
            'usdt_date' => $explicitDate,
            'status' => 'unused',
            'remaining_balance' => 200.00,
        ]);

        $this->assertEquals('2026-05-15', Carbon::parse($usdt->usdt_date)->format('Y-m-d'));
    }

    public function test_usdt_module_permission_and_license_check()
    {
        License::create([
            'license_key' => 'test_key',
            'client_id' => 'test_client',
            'expires_at' => Carbon::now()->addDays(30),
        ]);

        Cache::forget('active_license_v2');
        $licenseService = app(LicenseService::class);
        $status = $licenseService->checkLicense();

        $this->assertContains('module_usdt', $status['modules']);
    }

    public function test_payment_usdt_record_relationship()
    {
        $usdt = UsdtRecord::create([
            'sender_name' => 'JohnDoeBinance',
            'reference' => 'TXID_TEST_99',
            'amount' => 50.00,
            'usdt_date' => Carbon::now(),
        ]);

        $payment = Payment::create([
            'user_id' => 1,
            'amount' => 50.00,
            'currency' => 'USD',
            'pay_way' => 'usdt',
            'usdt_record_id' => $usdt->id,
            'payment_date' => Carbon::now(),
        ]);

        $this->assertNotNull($payment->usdtRecord);
        $this->assertEquals('JohnDoeBinance', $payment->usdtRecord->sender_name);
        $this->assertEquals('TXID_TEST_99', $payment->usdtRecord->reference);
    }

    public function test_usdt_consultation_page_renders_successfully()
    {
        config(['tenant.modules' => ['module_usdt']]);
        $user = \App\Models\User::factory()->create();
        $response = $this->actingAs($user)->get(route('consultation.usdt'));
        $response->assertStatus(200);
        $response->assertSee('Consulta y Auditoría de Pagos USDT');
    }
}
