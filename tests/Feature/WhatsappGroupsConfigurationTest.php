<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Models\CollectionSheet;
use App\Models\Payment;
use App\Models\SalePaymentDetail;
use App\Models\Warehouse;
use App\Services\WhatsappService;
use App\Livewire\Settings\WhatsappSettings;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class WhatsappGroupsConfigurationTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed currencies
        DB::table('currencies')->insert([
            ['id' => 1, 'code' => 'USD', 'label' => 'USD', 'symbol' => '$', 'name' => 'USD', 'exchange_rate' => 1.00, 'is_primary' => true, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'code' => 'VES', 'label' => 'Bs', 'symbol' => 'Bs', 'name' => 'VES', 'exchange_rate' => 760.00, 'is_primary' => false, 'created_at' => now(), 'updated_at' => now()],
        ]);

        $this->warehouse = Warehouse::create([
            'id' => 1,
            'name' => 'TIENDA PRINCIPAL',
            'is_active' => 1,
        ]);

        Configuration::create([
            'business_name' => 'Steel Plastics Factory',
            'taxpayer_id' => 'V12345678',
            'address' => 'Factory Lane',
            'city' => 'Caracas',
            'phone' => '0212-0000000',
            'decimals' => 2,
            'vat' => 16,
            'printer_name' => 'PDF',
            'credit_days' => 15,
            'default_warehouse_id' => $this->warehouse->id,
            'bcv_rate' => 600.00,
            'binance_rate' => 700.00,
        ]);

        $this->adminUser = User::factory()->create();
    }

    public function test_whatsapp_settings_can_toggle_and_save_various_group_actions()
    {
        // Mock group list response
        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('checkStatus')->andReturn(true);
            $mock->shouldReceive('getGroups')->andReturn([
                ['id' => '111111@g.us', 'name' => 'Grupo Eliecer'],
                ['id' => '222222@g.us', 'name' => 'Grupo Cierre'],
            ]);
        });

        // Test Livewire component
        Livewire::actingAs($this->adminUser)
            ->test(WhatsappSettings::class)
            ->assertSet('selectedRateGroups', [])
            ->assertSet('selectedClosureGroups', [])
            ->assertSet('selectedWeeklyReportGroups', [])
            ->call('toggleGroup', '111111@g.us', 'rate')
            ->call('toggleGroup', '222222@g.us', 'closure')
            ->call('toggleGroup', '222222@g.us', 'weekly_report')
            ->call('save')
            ->assertHasNoErrors();

        // Verify database configuration has updated
        $config = Configuration::first();
        $this->assertEquals(['111111@g.us'], $config->whatsapp_rate_groups);
        $this->assertEquals(['222222@g.us'], $config->whatsapp_closure_groups);
        $this->assertEquals(['222222@g.us'], $config->whatsapp_weekly_report_groups);
    }

    public function test_send_daily_closure_command_sends_correct_message()
    {
        $config = Configuration::first();
        $config->update([
            'whatsapp_closure_groups' => ['cierre_group_id@g.us']
        ]);

        $monday = '2026-06-08';
        Carbon::setTestNow(Carbon::parse($monday));

        $customer = Customer::create([
            'name' => 'Test Customer',
            'taxpayer_id' => '123456',
            'address' => 'Test',
            'city' => 'Test',
            'type' => 'Consumidor Final'
        ]);

        // Create a cash sale
        $sale = Sale::create([
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 10:00:00',
            'invoice_number' => 'F0001',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        SalePaymentDetail::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 100.00,
            'exchange_rate' => 1.00,
            'amount_in_primary_currency' => 100.00
        ]);

        // Mock WhatsappService
        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('checkStatus')->once()->andReturn(true);
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('cierre_group_id@g.us', \Mockery::on(function($msg) {
                    return str_contains($msg, 'CIERRE DE VENTAS DIARIAS') &&
                           str_contains($msg, 'STEEL PLASTICS FACTORY') &&
                           str_contains($msg, 'Subtotal Contado') &&
                           str_contains($msg, 'Total General');
                }))
                ->andReturn(['success' => true, 'error' => null]);
        });

        // Run the console command
        $exitCode = Artisan::call('app:send-daily-closure', ['date' => $monday]);
        $this->assertEquals(0, $exitCode);

        Carbon::setTestNow(); // Reset
    }

    public function test_manual_daily_closure_via_livewire_component_sends_whatsapp_message()
    {
        $config = Configuration::first();
        $config->update([
            'whatsapp_closure_groups' => ['cierre_group_id@g.us']
        ]);

        $monday = '2026-06-08';
        Carbon::setTestNow(Carbon::parse($monday));

        $customer = Customer::create([
            'name' => 'Test Customer',
            'taxpayer_id' => '123456',
            'address' => 'Test',
            'city' => 'Test',
            'type' => 'Consumidor Final'
        ]);

        // Create a cash sale
        $sale = Sale::create([
            'total' => 100.00,
            'total_usd' => 100.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 10:00:00',
            'invoice_number' => 'F0001',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        SalePaymentDetail::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 100.00,
            'exchange_rate' => 1.00,
            'amount_in_primary_currency' => 100.00
        ]);

        // Mock WhatsappService
        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('checkStatus')->once()->andReturn(true);
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('cierre_group_id@g.us', \Mockery::on(function($msg) {
                    return str_contains($msg, 'CIERRE DE VENTAS DIARIAS') &&
                           str_contains($msg, 'STEEL PLASTICS FACTORY') &&
                           str_contains($msg, 'Subtotal Contado') &&
                           str_contains($msg, 'Total General');
                }))
                ->andReturn(['success' => true, 'error' => null]);
        });

        // Test Livewire component
        Livewire::actingAs($this->adminUser)
            ->test(\App\Livewire\Reports\DailySalesReport::class)
            ->set('dateFrom', '2026/06/08')
            ->call('sendDailyClosureToWhatsapp')
            ->assertHasNoErrors()
            ->assertDispatched('noty', msg: 'CIERRE DIARIO ENVIADO CON ÉXITO A WHATSAPP');

        Carbon::setTestNow(); // Reset
    }

    public function test_send_weekly_report_command_generates_and_sends_pdf()
    {
        $config = Configuration::first();
        $config->update([
            'whatsapp_weekly_report_groups' => ['reporte_semanal_id@g.us']
        ]);

        $monday = '2026-06-08';
        Carbon::setTestNow(Carbon::parse($monday));

        $customer = Customer::create([
            'name' => 'Test Customer',
            'taxpayer_id' => '123456',
            'address' => 'Test',
            'city' => 'Test',
            'type' => 'Consumidor Final'
        ]);

        // Create a cash sale
        $sale = Sale::create([
            'total' => 50.00,
            'total_usd' => 50.00,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'created_at' => '2026-06-08 10:00:00',
            'invoice_number' => 'F0001',
            'status' => 'paid',
            'type' => 'cash',
        ]);

        SalePaymentDetail::create([
            'sale_id' => $sale->id,
            'payment_method' => 'cash',
            'currency_code' => 'USD',
            'amount' => 50.00,
            'exchange_rate' => 1.00,
            'amount_in_primary_currency' => 50.00
        ]);

        // Mock WhatsappService
        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('checkStatus')->once()->andReturn(true);
            $mock->shouldReceive('sendMessage')
                ->once()
                ->with('reporte_semanal_id@g.us', \Mockery::on(function($msg) {
                    return str_contains($msg, 'REPORTE SEMANAL DE INGRESOS') &&
                           str_contains($msg, 'STEEL PLASTICS FACTORY') &&
                           str_contains($msg, 'Semana del 08/06/2026 al 13/06/2026');
                }), \Mockery::on(function($filePath) {
                    return str_contains($filePath, 'Reporte_Ingresos_Semanal_2026-06-08.pdf') &&
                           File::exists($filePath);
                }))
                ->andReturn(['success' => true, 'error' => null]);
        });

        // Run the console command
        $exitCode = Artisan::call('app:send-weekly-report', ['date' => $monday]);
        $this->assertEquals(0, $exitCode);

        // Verify temp file has been cleaned up
        $tempPath = storage_path('app/temp/Reporte_Ingresos_Semanal_2026-06-08.pdf');
        $this->assertFalse(File::exists($tempPath));

        Carbon::setTestNow(); // Reset
    }

    public function test_whatsapp_settings_can_search_and_select_users_and_send_notifications()
    {
        $targetUser = User::factory()->create([
            'name' => 'Eliecer Bermudez',
            'phone' => '584121112233'
        ]);

        $this->mock(WhatsappService::class, function ($mock) {
            $mock->shouldReceive('checkStatus')->andReturn(true);
            $mock->shouldReceive('getGroups')->andReturn([]);
        });

        // Test Livewire component search and select
        Livewire::actingAs($this->adminUser)
            ->test(WhatsappSettings::class)
            ->set('searchRateQuery', 'Eliecer')
            ->assertSet('rateUsersResults', [
                ['id' => $targetUser->id, 'name' => 'Eliecer Bermudez', 'phone' => '584121112233']
            ])
            ->call('selectUser', $targetUser->id, 'rate')
            ->assertSet('selectedRateUsers', [$targetUser->id])
            ->call('removeUser', $targetUser->id, 'rate')
            ->assertSet('selectedRateUsers', [])
            ->call('selectUser', $targetUser->id, 'rate')
            ->call('save')
            ->assertHasNoErrors();

        // Verify database configuration has updated
        $config = Configuration::first();
        $this->assertEquals([$targetUser->id], $config->whatsapp_rate_users);
    }
}
