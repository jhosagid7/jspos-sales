<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Settings;

class GlobalRatesDifferentialTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed currencies
        $this->seed(\Database\Seeders\CurrencySeeder::class);

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
            'bcv_rate' => 50.00,
            'binance_rate' => 60.00,
            'binance_markup_points' => 5.00
        ]);

        $this->adminUser = User::factory()->create();
    }

    public function test_settings_calculates_and_displays_rate_differentials()
    {
        $this->actingAs($this->adminUser);

        // Test initial values render correctly
        Livewire::test(Settings::class)
            ->assertSet('bcvRate', 50.00)
            ->assertSet('binanceRate', 60.00)
            ->assertSet('binanceMarkupPoints', 5.00)
            ->assertSee('20.00%') // Real: ((60 - 50) / 50) * 100 = 20%
            ->assertSee('30.00%') // Applied: (((60 + 5) - 50) / 50) * 100 = 30%
            
            // Modify properties and check real-time precalculation updates
            ->set('bcvRate', 40.00)
            ->set('binanceRate', 50.00)
            ->set('binanceMarkupPoints', 2.00)
            ->assertSee('25.00%') // Real: ((50 - 40) / 40) * 100 = 25%
            ->assertSee('30.00%'); // Applied: (((50 + 2) - 40) / 40) * 100 = 30%
    }
}
