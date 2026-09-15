<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DeviceAuthorization;
use App\Models\Configuration;
use App\Services\PrinterDiscoveryService;
use App\Livewire\Settings\DeviceManager;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class PrinterDiscoveryAndTestPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.installed' => true]);

        Configuration::create([
            'business_name' => 'JSPOS Test Store',
            'device_access_mode' => 'open',
        ]);

        Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'ADMIN']);

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('checkLicense')->andReturn([
                'status' => 'active',
                'days_remaining' => 30,
                'modules' => [],
                'max_devices' => 10,
            ]);
            $mock->shouldReceive('getClientId')->andReturn('test-client-id');
        });
    }

    /** @test */
    public function printer_discovery_service_returns_list_of_printers()
    {
        $printers = PrinterDiscoveryService::discoverPrinters();

        $this->assertIsArray($printers);
        foreach ($printers as $printer) {
            $this->assertArrayHasKey('name', $printer);
            $this->assertArrayHasKey('unc', $printer);
            $this->assertArrayHasKey('type', $printer);
            $this->assertArrayHasKey('label', $printer);
            $this->assertArrayHasKey('is_thermal', $printer);
        }
    }

    /** @test */
    public function printer_discovery_service_tests_connection_with_latency()
    {
        // 1. Test empty printer name
        $emptyResult = PrinterDiscoveryService::testConnection('');
        $this->assertFalse($emptyResult['success']);
        $this->assertStringContainsString('vacío', $emptyResult['message']);

        // 2. Test valid or non-existent printer
        $testResult = PrinterDiscoveryService::testConnection('NonExistentPrinter12345');
        $this->assertIsArray($testResult);
        $this->assertArrayHasKey('success', $testResult);
        $this->assertArrayHasKey('latency_ms', $testResult);
        $this->assertArrayHasKey('message', $testResult);
    }

    /** @test */
    public function device_manager_livewire_can_scan_and_select_printers()
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        $device = DeviceAuthorization::create([
            'uuid' => 'dev-1111',
            'name' => 'Caja Test',
            'ip_address' => '192.168.20.50',
            'user_agent' => 'Mozilla/5.0 Test',
            'status' => 'approved',
            'last_accessed_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(DeviceManager::class)
            ->call('editPrinter', $device->id)
            ->assertSet('selected_device_id', $device->id)
            ->call('scanPrinters')
            ->assertSet('is_scanning', false)
            ->call('selectDiscoveredPrinter', '\\\\192.168.20.115\\POS-80-Series')
            ->assertSet('printer_name', '\\\\192.168.20.115\\POS-80-Series')
            ->assertSet('is_network', true)
            ->assertSet('printer_host', '192.168.20.115')
            ->assertSet('printer_share', 'POS-80-Series')
            ->call('updatePrinter')
            ->assertDispatched('noty');

        $this->assertEquals('\\\\192.168.20.115\\POS-80-Series', $device->fresh()->printer_name);
        $this->assertTrue((bool)$device->fresh()->is_network);
    }

    /** @test */
    public function device_manager_livewire_can_test_connection_and_print_test_ticket()
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        $device = DeviceAuthorization::create([
            'uuid' => 'dev-2222',
            'name' => 'Caja Test 2',
            'ip_address' => '192.168.20.51',
            'user_agent' => 'Mozilla/5.0 Test',
            'status' => 'approved',
            'last_accessed_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(DeviceManager::class)
            ->call('editPrinter', $device->id)
            ->set('printer_name', 'Microsoft Print to PDF')
            ->call('testPrinterConnection')
            ->assertDispatched('noty');
    }

    /** @test */
    public function purge_duplicates_removes_old_pending_and_duplicate_approved_devices()
    {
        $admin = User::factory()->create();
        $admin->assignRole('ADMIN');

        // Current active device
        $currentDevice = DeviceAuthorization::create([
            'uuid' => 'current-dev-active',
            'name' => 'Current Active Box',
            'ip_address' => '192.168.20.100',
            'user_agent' => 'Chrome Windows',
            'status' => 'approved',
            'last_accessed_at' => now(),
        ]);

        // Duplicate approved device with same IP/UA (older)
        $duplicateOld = DeviceAuthorization::create([
            'uuid' => 'old-dup-uuid',
            'name' => 'Dispositivo Va44',
            'ip_address' => '192.168.20.100',
            'user_agent' => 'Chrome Windows',
            'status' => 'approved',
            'last_accessed_at' => now()->subHours(5),
        ]);

        // Stale pending device (> 14 days old)
        $stalePending = new DeviceAuthorization();
        $stalePending->timestamps = false;
        $stalePending->forceFill([
            'uuid' => 'stale-pending-uuid',
            'name' => 'Dispositivo l6Ne',
            'ip_address' => '192.168.20.200',
            'user_agent' => 'Unknown',
            'status' => 'pending',
            'created_at' => now()->subDays(20),
            'last_accessed_at' => now()->subDays(20),
        ]);
        $stalePending->save();

        // Generic auto-generated device inactive > 14 days
        $staleGeneric = new DeviceAuthorization();
        $staleGeneric->timestamps = false;
        $staleGeneric->forceFill([
            'uuid' => 'stale-generic-uuid',
            'name' => 'Dispositivo KUXd',
            'ip_address' => '192.168.194.127',
            'user_agent' => 'Android Mobile',
            'status' => 'approved',
            'created_at' => now()->subDays(30),
            'last_accessed_at' => now()->subDays(30),
        ]);
        $staleGeneric->save();

        // Stale localhost duplicate inactive > 2 days
        $staleLocalhost = new DeviceAuthorization();
        $staleLocalhost->timestamps = false;
        $staleLocalhost->forceFill([
            'uuid' => 'stale-localhost-uuid',
            'name' => 'Dispositivo B0b3',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Chrome Windows',
            'status' => 'approved',
            'created_at' => now()->subDays(50),
            'last_accessed_at' => now()->subDays(50),
        ]);
        $staleLocalhost->save();

        // Distinct approved device on another IP (should NOT be deleted)
        $otherDevice = DeviceAuthorization::create([
            'uuid' => 'other-device-uuid',
            'name' => 'Caja 2',
            'ip_address' => '192.168.20.101',
            'user_agent' => 'Firefox Windows',
            'status' => 'approved',
            'last_accessed_at' => now(),
        ]);

        Livewire::actingAs($admin)
            ->test(DeviceManager::class)
            ->set('current_token', $currentDevice->uuid)
            ->call('purgeDuplicates')
            ->assertDispatched('noty');

        // Verify that stalePending, duplicateOld, staleGeneric, and staleLocalhost were removed
        $this->assertDatabaseMissing('device_authorizations', ['id' => $stalePending->id]);
        $this->assertDatabaseMissing('device_authorizations', ['id' => $duplicateOld->id]);
        $this->assertDatabaseMissing('device_authorizations', ['id' => $staleGeneric->id]);
        $this->assertDatabaseMissing('device_authorizations', ['id' => $staleLocalhost->id]);

        // Verify that currentDevice and otherDevice remain
        $this->assertDatabaseHas('device_authorizations', ['id' => $currentDevice->id]);
        $this->assertDatabaseHas('device_authorizations', ['id' => $otherDevice->id]);
    }
}
