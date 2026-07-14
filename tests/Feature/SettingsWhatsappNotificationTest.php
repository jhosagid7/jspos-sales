<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Configuration;
use App\Services\WhatsappService;
use Livewire\Livewire;
use App\Livewire\Settings;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SettingsWhatsappNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        $warehouse = \App\Models\Warehouse::create([
            'id' => 1,
            'name' => 'TIENDA PRINCIPAL',
            'is_active' => 1,
        ]);
        
        // Seed currencies
        DB::table('currencies')->insert([
            ['id' => 1, 'code' => 'USD', 'label' => 'USD', 'symbol' => '$', 'name' => 'USD', 'exchange_rate' => 1.00, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'VES', 'label' => 'Bs', 'symbol' => 'Bs', 'name' => 'VES', 'exchange_rate' => 760.00, 'is_primary' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        Configuration::create([
            'business_name' => 'FABRICA DE PLASTICOS M&M STEEL',
            'default_warehouse_id' => $warehouse->id,
            'bcv_rate' => 639.70,
            'binance_rate' => 735.06,
            'binance_markup_points' => 24.94,
        ]);
    }

    public function test_save_global_rates_sends_whatsapp_notification_to_group()
    {
        // Mock WhatsappService
        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('checkStatus')->once()->andReturn(true);
            
            $expectedMessage = "*FABRICA DE PLASTICOS M&M STEEL*\n" .
                now()->format('d/m/Y') . "\n\n" .
                "*BCV:* 639.70\n" .
                "*MONITOR:* 735.06\n" .
                "*DIFERENCIAL:* 1.1490\n" .
                "*SISTEMA:* 760";
                
            $mock->shouldReceive('sendToGroupByName')
                ->once()
                ->with('Diferencial', $expectedMessage)
                ->andReturn(['success' => true, 'error' => null]);
        });

        $component = Livewire::actingAs($this->user)
            ->test(Settings::class)
            ->set('bcvRate', 639.70)
            ->set('binanceRate', 735.06)
            ->set('binanceMarkupPoints', 24.94)
            ->call('saveGlobalRates')
            ->assertHasNoErrors();
    }

    public function test_save_global_rates_sends_whatsapp_notification_to_multiple_configured_groups()
    {
        $config = Configuration::first();
        $config->update([
            'whatsapp_rate_groups' => ['1111111111@g.us', '2222222222@g.us']
        ]);

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('checkStatus')->once()->andReturn(true);
            
            $expectedMessage = "*FABRICA DE PLASTICOS M&M STEEL*\n" .
                now()->format('d/m/Y') . "\n\n" .
                "*BCV:* 639.70\n" .
                "*MONITOR:* 735.06\n" .
                "*DIFERENCIAL:* 1.1490\n" .
                "*SISTEMA:* 760";
                
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('1111111111@g.us', $expectedMessage)
                ->andReturn(['success' => true, 'error' => null]);

            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('2222222222@g.us', $expectedMessage)
                ->andReturn(['success' => true, 'error' => null]);
        });

        Livewire::actingAs($this->user)
            ->test(Settings::class)
            ->set('bcvRate', 639.70)
            ->set('binanceRate', 735.06)
            ->set('binanceMarkupPoints', 24.94)
            ->call('saveGlobalRates')
            ->assertHasNoErrors();
    }
}
