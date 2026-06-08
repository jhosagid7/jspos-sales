<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Services\ConfigurationService;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Settings;
use Carbon\Carbon;

class SequentialCutOffSettingTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed currencies
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Reset ConfigurationService static cache
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Create Configuration
        Configuration::create([
            'business_name' => 'Test Business',
            'taxpayer_id' => '12345678',
            'address' => 'Test Address 123',
            'city' => 'Caracas',
            'phone' => '0212-5555555',
            'decimals' => 2,
            'vat' => 16,
            'printer_name' => 'EPSON',
            'credit_days' => 15,
            'sequential_cut_off_date' => '2026-06-03 00:00:00'
        ]);

        $this->adminUser = User::factory()->create();
    }

    private function resetConfigCache()
    {
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);
    }

    public function test_settings_livewire_component_loads_and_saves_cut_off_date()
    {
        $this->actingAs($this->adminUser);

        // 1. Assert initial load
        Livewire::test(Settings::class)
            ->assertSet('sequentialCutOffDate', '2026-06-03T00:00')
            // 2. Modify and Save
            ->set('sequentialCutOffDate', '2026-06-10T14:30')
            ->call('saveConfig')
            ->assertHasNoErrors();

        // 3. Assert database update
        $config = Configuration::first();
        $this->assertEquals('2026-06-10 14:30:00', $config->sequential_cut_off_date);

        // 4. Reset Cache and assert ConfigurationService returns the new date
        $this->resetConfigCache();
        $this->assertEquals('2026-06-10 14:30:00', ConfigurationService::getSequentialCutOffDate());
    }

    public function test_settings_livewire_validation_rejects_invalid_date()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(Settings::class)
            ->set('sequentialCutOffDate', 'not-a-date')
            ->call('saveConfig')
            ->assertHasErrors(['sequentialCutOffDate']);
    }

    public function test_commission_service_respects_dynamic_date_cutoff()
    {
        // Set cutoff to 2026-06-10 00:00:00
        $config = Configuration::first();
        $config->update([
            'sequential_cut_off_date' => '2026-06-10 00:00:00'
        ]);
        $this->resetConfigCache();

        $customer = Customer::create([
            'name' => 'Test Customer',
            'taxpayer_id' => '123',
            'address' => 'Test Address',
            'city' => 'Test City',
            'type' => 'Consumidor Final'
        ]);

        // Scenario 1: Sale created BEFORE cutoff (e.g. 2026-06-08) -> Additive Formula
        // Total = Base * (1 + (Comm% + Freight% + Diff%)/100)
        // With Base = 10, Comm = 8%, Freight = 6%, Diff = 45% -> Total = 10 * 1.59 = 15.90
        $historicSale = Sale::create([
            'total' => 15.90,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 12:00:00',
            'invoice_number' => 'HIST-999',
            'status' => 'paid',
            'type' => 'cash',
            'applied_commission_percent' => 8.00,
            'applied_freight_percent' => 6.00,
            'applied_exchange_diff_percent' => 45.00,
            'seller_tier_1_days' => 15,
            'seller_tier_1_percent' => 8.00
        ]);

        CommissionService::calculateCommission($historicSale, '2026-06-08 12:00:00');
        // Base should be parsed as 10.00, so commission (8%) is 0.80
        $this->assertEquals(0.80, round($historicSale->final_commission_amount, 2));

        // Scenario 2: Sale created AFTER cutoff (e.g. 2026-06-12) -> Sequential Formula
        // Total = (Base * (1 + (Comm% + Freight%)/100)) * (1 + Diff%/100)
        // With Base = 10, Comm = 8%, Freight = 6%, Diff = 45% -> Total = (10 * 1.14) * 1.45 = 16.53
        $newSale = Sale::create([
            'total' => 16.53,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-12 12:00:00',
            'invoice_number' => 'NEW-999',
            'status' => 'paid',
            'type' => 'cash',
            'applied_commission_percent' => 8.00,
            'applied_freight_percent' => 6.00,
            'applied_exchange_diff_percent' => 45.00,
            'seller_tier_1_days' => 15,
            'seller_tier_1_percent' => 8.00
        ]);

        CommissionService::calculateCommission($newSale, '2026-06-12 12:00:00');
        // Base should be parsed as 10.00, so commission (8%) is 0.80
        $this->assertEquals(0.80, round($newSale->final_commission_amount, 2));
    }

    public function test_sales_report_renders_correct_surcharge_percentages()
    {
        $this->actingAs($this->adminUser);

        // Set cutoff to 2026-06-10 00:00:00
        $config = Configuration::first();
        $config->update([
            'sequential_cut_off_date' => '2026-06-10 00:00:00'
        ]);
        $this->resetConfigCache();

        $customer = Customer::create([
            'name' => 'Test Customer',
            'taxpayer_id' => '123',
            'address' => 'Test Address',
            'city' => 'Test City',
            'type' => 'Consumidor Final'
        ]);

        // Scenario 1: Sale created BEFORE cutoff (e.g. 2026-06-08) -> Additive
        // Comm: 8.0%, Freight: 6.0%, Diff: 45.0% -> Additive sum is 59.0%
        $historicSale = Sale::create([
            'total' => 15.90,
            'total_usd' => 15.90,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 12:00:00',
            'invoice_number' => 'HIST-999',
            'status' => 'paid',
            'type' => 'cash',
            'applied_commission_percent' => 8.00,
            'applied_freight_percent' => 6.00,
            'applied_exchange_diff_percent' => 45.00,
            'base_amount' => 10.00,
            'commission_amount' => 0.80,
            'freight_amount' => 0.60,
            'exchange_diff_amount' => 4.50,
        ]);

        // Scenario 2: Sale created AFTER cutoff (e.g. 2026-06-12) -> Sequential
        // Comm: 8.0%, Freight: 0.0%, Diff: 45.0% -> Compound percent is 56.6%
        $newSale = Sale::create([
            'total' => 22.20,
            'total_usd' => 22.20,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-12 12:00:00',
            'invoice_number' => 'NEW-999',
            'status' => 'paid',
            'type' => 'cash',
            'applied_commission_percent' => 8.00,
            'applied_freight_percent' => 0.00,
            'applied_exchange_diff_percent' => 45.00,
            'base_amount' => 14.18,
            'commission_amount' => 1.13,
            'freight_amount' => 0.00,
            'exchange_diff_amount' => 6.38,
        ]);

        Livewire::test(\App\Livewire\SalesReport::class)
            ->set('dateFrom', '2026/06/01')
            ->set('dateTo', '2026/06/30')
            ->call('searchData')
            ->assertSee('59.0%') // Historic additive surcharge percent
            ->assertSee('56.6%') // New compound surcharge percent
            ->assertSee('(8.0%)') // Configuration Commission percent
            ->assertSee('(6.0%)') // Configuration Freight percent for historic
            ->assertSee('(45.0%)'); // Configuration Diff percent
    }
}
