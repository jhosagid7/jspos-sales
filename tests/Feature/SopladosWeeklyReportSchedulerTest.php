<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Shift;
use App\Models\Warehouse;
use App\Models\Product;
use App\Models\Category;
use App\Models\Configuration;
use App\Models\ProductionLog;
use App\Models\ProductionOutput;
use App\Models\ProductionMaterial;
use App\Models\SopladosInventory;
use App\Models\SopladosInventoryDetail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Livewire\Livewire;
use Carbon\Carbon;

class SopladosWeeklyReportSchedulerTest extends TestCase
{
    use DatabaseTransactions;

    protected $user;
    protected $warehouse;
    protected $category;
    protected $finishedProduct;
    protected $rawMaterial;

    protected function setUp(): void
    {
        parent::setUp();
        config(['app.installed' => true]);

        // Mock LicenseService
        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('checkLicense')->andReturn([
                'status' => 'active',
                'days_remaining' => 30,
                'modules' => [],
                'max_devices' => 10,
            ]);
            $mock->shouldReceive('getClientId')->andReturn('test-client-id');
        });

        $this->warehouse = Warehouse::create([
            'name' => 'Planta Soplados Central',
            'is_active' => true,
        ]);

        $this->user = User::factory()->create([
            'name' => 'Supervisor Soplados',
            'email' => 'supervisor@example.com',
            'phone' => '1234567890',
            'warehouse_id' => $this->warehouse->id,
        ]);

        $this->category = Category::create([
            'name' => 'Envases Plásticos',
        ]);

        $supplier = \App\Models\Supplier::create([
            'name' => 'Supplier Test',
            'taxpayer_id' => 'J-11111111-1',
            'address' => 'Supplier Address',
            'phone' => '12345678',
        ]);

        $this->finishedProduct = Product::create([
            'sku' => 'BOT-5L',
            'name' => 'Botellon 5L',
            'cost' => 0.50,
            'price' => 1.50,
            'stock_qty' => 0,
            'low_stock' => 0,
            'manage_stock' => false,
            'category_id' => $this->category->id,
            'supplier_id' => $supplier->id,
            'status' => 1,
        ]);

        $this->rawMaterial = Product::create([
            'sku' => 'MAT-PE',
            'name' => 'Polietileno',
            'cost' => 1.20,
            'price' => 0.00,
            'stock_qty' => 1000,
            'low_stock' => 0,
            'manage_stock' => false,
            'category_id' => $this->category->id,
            'supplier_id' => $supplier->id,
            'status' => 1,
        ]);

        // Create initial config
        Configuration::create([
            'business_name' => 'Soplados SA',
            'taxpayer_id' => 'J-12345678-9',
            'decimals' => 2,
            'vat' => 16,
            'credit_days' => 15,
            'printer_name' => 'PDF',
            'soplados_warehouse_id' => $this->warehouse->id,
            'weekly_report_send_day' => 6,
            'weekly_report_send_hour' => '10:00'
        ]);
    }

    public function test_soplados_notifications_configuration_saving()
    {
        $this->actingAs($this->user);

        Livewire::test(\App\Livewire\Settings\WhatsappSettings::class)
            ->set('emailSopladosWeeklyRecipients', 'boss@soplados.com, admin@soplados.com')
            ->set('selectedSopladosShiftGroups', ['group-shift-1'])
            ->set('selectedSopladosWeeklyGroups', ['group-weekly-1'])
            ->set('weeklyReportSendDay', 1) // Monday
            ->set('weeklyReportSendHour', '14:30')
            ->call('save');

        $config = Configuration::first();

        $this->assertEquals(['boss@soplados.com', 'admin@soplados.com'], $config->email_soplados_weekly_recipients);
        $this->assertEquals(['group-shift-1'], $config->whatsapp_soplados_shift_groups);
        $this->assertEquals(['group-weekly-1'], $config->whatsapp_soplados_weekly_groups);
        $this->assertEquals(1, $config->weekly_report_send_day);
        $this->assertEquals('14:30', $config->weekly_report_send_hour);
    }

    public function test_send_soplados_weekly_report_command()
    {
        Mail::fake();

        // Update config to have email recipients
        $config = Configuration::first();
        $config->update([
            'email_soplados_weekly_recipients' => ['boss@soplados.com'],
            'whatsapp_soplados_weekly_groups' => ['group-weekly-1'],
        ]);

        // Create a closed shift in the past week
        $shift = Shift::create([
            'type' => 'day',
            'start_time' => now()->subDays(2),
            'end_time' => now()->subDays(2)->addHours(8),
            'status' => 'closed',
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $log = ProductionLog::create([
            'shift_id' => $shift->id,
            'user_id' => $this->user->id,
        ]);

        ProductionOutput::create([
            'production_log_id' => $log->id,
            'product_id' => $this->finishedProduct->id,
            'quantity' => 150.00,
            'quality' => '1st',
        ]);

        ProductionMaterial::create([
            'production_log_id' => $log->id,
            'product_id' => $this->rawMaterial->id,
            'quantity' => 75.00,
        ]);

        // Create a physical inventory
        $inventory = SopladosInventory::create([
            'warehouse_id' => $this->warehouse->id,
            'supervisor_id' => $this->user->id,
            'operator_id' => $this->user->id,
            'status' => 'accepted',
            'notes' => 'Inventory test',
        ]);

        SopladosInventoryDetail::create([
            'soplados_inventory_id' => $inventory->id,
            'product_id' => $this->finishedProduct->id,
            'type' => 'production',
            'system_stock_primera' => 100,
            'counted_primera' => 110,
            'difference_primera' => 10,
            'system_stock_segunda' => 0,
            'counted_segunda' => 0,
            'difference_segunda' => 0,
            'system_stock_merma' => 0,
            'counted_merma' => 0,
            'difference_merma' => 0,
        ]);

        // Run the artisan command
        $exitCode = Artisan::call('app:send-soplados-weekly-report');

        $this->assertEquals(0, $exitCode);

        // Assert mail was sent to boss
        Mail::assertSent(\App\Mail\GenericNotificationMail::class, function ($mail) {
            $this->assertTrue($mail->hasTo('boss@soplados.com'));
            $this->assertStringContainsString('Reporte Semanal Consolidado de Soplados', $mail->subjectLine);
            $this->assertNotNull($mail->attachmentPath);
            return true;
        });
    }
}
