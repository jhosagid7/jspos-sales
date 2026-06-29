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
use App\Mail\SopladosShiftReportMail;
use Illuminate\Support\Facades\Mail;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SopladosShiftNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $operator;
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

        // Create warehouse
        $this->warehouse = Warehouse::create([
            'name' => 'Planta Soplados Central',
            'address' => 'Planta Industrial 1',
            'is_active' => true,
        ]);

        // Create Users
        $this->user = User::factory()->create([
            'name' => 'Supervisor Soplados',
            'email' => 'supervisor@example.com',
            'warehouse_id' => $this->warehouse->id,
        ]);

        $this->operator = User::factory()->create([
            'name' => 'Operador Juan',
            'email' => 'juan@example.com',
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Create Category
        $this->category = Category::create([
            'name' => 'Envases Plásticos',
        ]);

        // Create Supplier
        $supplier = \App\Models\Supplier::create([
            'name' => 'Supplier Test',
            'taxpayer_id' => 'J-11111111-1',
            'address' => 'Supplier Address',
            'phone' => '12345678',
        ]);

        // Create Products
        $this->finishedProduct = Product::create([
            'sku' => 'BOT-5L',
            'name' => 'Botellon 5L',
            'description' => 'Botellón de 5 Litros',
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
            'description' => 'Materia prima PE',
            'cost' => 1.20,
            'price' => 0.00,
            'stock_qty' => 1000,
            'low_stock' => 0,
            'manage_stock' => false,
            'category_id' => $this->category->id,
            'supplier_id' => $supplier->id,
            'status' => 1,
        ]);
    }

    public function test_shift_close_sends_email_notification_with_correct_stats()
    {
        Mail::fake();

        // Create Soplados configuration settings
        Configuration::create([
            'business_name' => 'Soplados SA',
            'address' => 'Zona Industrial',
            'city' => 'Caracas',
            'taxpayer_id' => 'J-12345678-9',
            'decimals' => 2,
            'vat' => 16,
            'credit_days' => 15,
            'printer_name' => 'DummyPrinter',
            'soplados_warehouse_id' => $this->warehouse->id,
            'soplados_email_recipients' => ['boss@soplados.com', 'admin@soplados.com'],
            'soplados_email_subject' => '[SALUDO], Reporte del Turno de Soplado - [FECHA] ([TIPO_TURNO]) - [EMPRESA]',
            'soplados_email_body' => 'Hola [USUARIO], el turno [TIPO_TURNO] en [ALMACEN] operado por [OPERADORES] de la empresa [EMPRESA] ha cerrado. Total producido: [BUENA_CANTIDAD]. Merma: [DESECHADA_CANTIDAD]. Eficiencia: [EFICIENCIA]%. Productos: [RESUMEN_PRODUCCION]. Materiales: [RESUMEN_MATERIALES]. Observaciones: [NOTA]',
        ]);

        // Create shift
        $shift = Shift::create([
            'type' => 'day',
            'start_time' => now()->subHours(8),
            'status' => 'open',
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        // Attach operator to the shift
        $shift->users()->attach([$this->user->id, $this->operator->id]);

        // Add production logs
        $log = ProductionLog::create([
            'shift_id' => $shift->id,
            'user_id' => $this->user->id,
            'notes' => 'Production log notes',
        ]);

        // Create outputs (1st quality & damaged)
        ProductionOutput::create([
            'production_log_id' => $log->id,
            'product_id' => $this->finishedProduct->id,
            'quantity' => 100.00,
            'quality' => '1st',
        ]);

        ProductionOutput::create([
            'production_log_id' => $log->id,
            'product_id' => $this->finishedProduct->id,
            'quantity' => 10.00,
            'quality' => 'damaged',
        ]);

        // Create material consumed
        ProductionMaterial::create([
            'production_log_id' => $log->id,
            'product_id' => $this->rawMaterial->id,
            'quantity' => 50.50,
        ]);

        // Authenticate and hit the shift close API
        $response = $this->actingAs($this->user)
            ->postJson('/api/soplados/shifts/close', [
                'notes' => 'El turno transcurrió sin eventualidades graves.',
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Turno cerrado correctamente',
            ]);

        // Assert shift is closed in DB
        $this->assertDatabaseHas('shifts', [
            'id' => $shift->id,
            'status' => 'closed',
            'notes' => 'El turno transcurrió sin eventualidades graves.',
        ]);

        // Assert email was sent to correct recipients
        Mail::assertQueued(SopladosShiftReportMail::class, function ($mail) {
            $this->assertTrue($mail->hasTo('boss@soplados.com'));
            $this->assertTrue($mail->hasTo('admin@soplados.com'));

            // Check content replacements in subject
            $this->assertStringContainsString('Reporte del Turno de Soplado', $mail->subjectLine);
            $this->assertStringContainsString('Diurno', $mail->subjectLine);
            $this->assertStringContainsString('Soplados SA', $mail->subjectLine);

            // Check content replacements in body
            $this->assertStringContainsString('Supervisor Soplados', $mail->bodyContent);
            $this->assertStringContainsString('Diurno', $mail->bodyContent);
            $this->assertStringContainsString('Planta Soplados Central', $mail->bodyContent);
            $this->assertStringContainsString('Supervisor Soplados, Operador Juan', $mail->bodyContent);
            $this->assertStringContainsString('Soplados SA', $mail->bodyContent);
            
            // Check totals and yield
            $this->assertStringContainsString('100', $mail->bodyContent); // Buena cantidad
            $this->assertStringContainsString('10', $mail->bodyContent);  // Desechada cantidad
            $this->assertStringContainsString('90.91', $mail->bodyContent); // Eficiencia

            // Check detail lists
            $this->assertStringContainsString('Botellon 5L (1ra Calidad): 100 unidades', $mail->bodyContent);
            $this->assertStringContainsString('Polietileno: 50.50 Kg', $mail->bodyContent);

            // Check observations note
            $this->assertStringContainsString('El turno transcurrió sin eventualidades graves.', $mail->bodyContent);

            return true;
        });
    }

    public function test_shift_close_does_not_send_email_if_no_recipients()
    {
        Mail::fake();

        // Create Soplados configuration settings with empty recipients
        Configuration::create([
            'business_name' => 'Soplados SA',
            'address' => 'Zona Industrial',
            'city' => 'Caracas',
            'taxpayer_id' => 'J-12345678-9',
            'decimals' => 2,
            'vat' => 16,
            'credit_days' => 15,
            'printer_name' => 'DummyPrinter',
            'soplados_warehouse_id' => $this->warehouse->id,
            'soplados_email_recipients' => null, // empty
        ]);

        // Create shift
        $shift = Shift::create([
            'type' => 'night',
            'start_time' => now()->subHours(8),
            'status' => 'open',
            'user_id' => $this->user->id,
            'warehouse_id' => $this->warehouse->id,
        ]);

        $shift->users()->attach([$this->user->id]);

        // Authenticate and hit the shift close API
        $response = $this->actingAs($this->user)
            ->postJson('/api/soplados/shifts/close', [
                'notes' => 'Cierre sin correos',
            ]);

        $response->assertStatus(200);

        // Assert no mailable was sent
        Mail::assertNotSent(SopladosShiftReportMail::class);
    }
}
