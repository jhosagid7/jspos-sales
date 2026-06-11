<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Models\ExchangeRateHistory;
use Livewire\Livewire;
use App\Livewire\Common\PaymentComponent;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class PaymentAgreementEnforcementTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.installed' => false,
            'tenant.modules' => ['module_credits'],
        ]);

        Configuration::create([
            'business_name' => 'Test Business',
            'bcv_rate' => 54.50,
            'binance_rate' => 70.00,
            'binance_markup_points' => 5.00,
        ]);

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $this->user = User::factory()->create();
        $this->customer = Customer::create([
            'name' => 'Test Client',
        ]);
    }

    public function test_payment_agreement_usd_blocks_bcv_rate_and_enforces_binance()
    {
        // 1. Create a sale under USD Agreement
        $saleUSD = Sale::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'total' => 100,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'payment_agreement' => 'USD',
            'primary_exchange_rate' => 1.00,
        ]);

        $today = Carbon::now()->format('Y-m-d');
        
        ExchangeRateHistory::create([
            'rate_type' => 'BCV',
            'rate' => 54.50,
            'user_id' => $this->user->id,
        ]);

        ExchangeRateHistory::create([
            'rate_type' => 'BinanceReal',
            'rate' => 70.00,
            'user_id' => $this->user->id,
            'period' => 'AM'
        ]);

        // Start the test for USD agreement
        Livewire::actingAs($this->user)
            ->test(PaymentComponent::class)
            ->call('initPayment', 100, 'USD', $this->customer->name, true, null, false, 0, 0, true, true, $this->customer->id, 0, ['sale_id' => $saleUSD->id])
            ->set('paymentCurrency', 'VED')
            ->set('paymentMethod', 'cash')
            ->set('paymentDate', $today)
            ->call('lookupHistoricalRate')
            ->assertSet('isUSDInvoice', true) // Forces pure USD restriction
            ->assertSet('customExchangeRate', 70.00); // Defaults to Binance rate

        // Verify that BCV rate is NOT present in rateOptions
        $rateOptions = Livewire::actingAs($this->user)
            ->test(PaymentComponent::class)
            ->call('initPayment', 100, 'USD', $this->customer->name, true, null, false, 0, 0, true, true, $this->customer->id, 0, ['sale_id' => $saleUSD->id])
            ->set('paymentCurrency', 'VED')
            ->set('paymentMethod', 'cash')
            ->set('paymentDate', $today)
            ->call('lookupHistoricalRate')
            ->get('rateOptions');

        $hasBCV = collect($rateOptions)->contains('rate', 54.50);
        $this->assertFalse($hasBCV, "USD Payment agreement allowed selecting BCV rate!");
    }

    public function test_payment_agreement_bcv_allows_bcv_rate()
    {
        // 2. Create a sale under BCV Agreement
        $saleBCV = Sale::create([
            'user_id' => $this->user->id,
            'customer_id' => $this->customer->id,
            'total' => 100,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'payment_agreement' => 'BCV',
            'primary_exchange_rate' => 1.00,
        ]);

        $today = Carbon::now()->format('Y-m-d');
        
        ExchangeRateHistory::create([
            'rate_type' => 'BCV',
            'rate' => 54.50,
            'user_id' => $this->user->id,
        ]);

        Livewire::actingAs($this->user)
            ->test(PaymentComponent::class)
            ->call('initPayment', 100, 'USD', $this->customer->name, true, null, false, 0, 0, true, true, $this->customer->id, 0, ['sale_id' => $saleBCV->id])
            ->set('paymentCurrency', 'VED')
            ->set('paymentMethod', 'cash')
            ->set('paymentDate', $today)
            ->call('lookupHistoricalRate')
            ->assertSet('isUSDInvoice', false) // Allows BCV rate
            ->assertSet('customExchangeRate', 54.50); // Defaults to BCV rate
    }
}
