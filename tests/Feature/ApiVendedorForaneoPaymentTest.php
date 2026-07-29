<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Currency;
use App\Models\Configuration;
use App\Models\ExchangeRateHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class ApiVendedorForaneoPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $primaryCurrency;
    protected $vedCurrency;

    protected function setUp(): void
    {
        parent::setUp();

        Configuration::create([
            'business_name' => 'Test Business',
            'decimals' => 2,
            'vat' => 16,
            'bcv_rate' => 50.00,
            'binance_rate' => 60.00,
        ]);

        $this->primaryCurrency = Currency::create([
            'code' => 'USD',
            'name' => 'Dólar',
            'label' => 'Dólar',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'is_primary' => true,
        ]);

        $this->vedCurrency = Currency::create([
            'code' => 'VED',
            'name' => 'Bolívar',
            'label' => 'Bolívar',
            'symbol' => 'Bs.',
            'exchange_rate' => 50.0,
            'is_primary' => false,
        ]);

        $this->user = User::factory()->create();
        $this->customer = Customer::create(['name' => 'Test Customer']);
    }

    public function test_form_data_returns_historical_bcv_rate_for_bcv_invoice()
    {
        $sale = Sale::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'total' => 100,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'payment_agreement' => 'BCV',
        ]);

        $pastDate = Carbon::now()->subDays(3)->format('Y-m-d');
        $history = ExchangeRateHistory::create([
            'rate_type' => 'BCV',
            'rate' => 45.50,
        ]);
        $history->created_at = Carbon::parse($pastDate)->setHour(10);
        $history->save();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/payments/form-data?sale_id={$sale->id}&date={$pastDate}");

        $response->assertStatus(200);
        $response->assertJson([
            'calculated_rate' => 45.50,
            'is_bcv' => true,
            'rate_type' => 'BCV',
        ]);
    }

    public function test_form_data_returns_historical_binance_rate_for_usd_invoice()
    {
        $sale = Sale::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'total' => 100,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'payment_agreement' => 'USD',
        ]);

        $pastDate = Carbon::now()->subDays(2)->format('Y-m-d');
        $history = ExchangeRateHistory::create([
            'rate_type' => 'BinanceReal',
            'rate' => 58.20,
        ]);
        $history->created_at = Carbon::parse($pastDate)->setHour(14);
        $history->save();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/payments/form-data?sale_id={$sale->id}&date={$pastDate}");

        $response->assertStatus(200);
        $response->assertJson([
            'calculated_rate' => 58.20,
            'is_bcv' => false,
            'rate_type' => 'Binance',
        ]);
    }
}
