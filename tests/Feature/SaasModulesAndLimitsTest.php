<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DeviceAuthorization;
use App\Models\Configuration;
use App\Services\LicenseService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Livewire;
use App\Livewire\Settings\DeviceManager;

class SaasModulesAndLimitsTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable app.installed config so that middleware executes
        config(['app.installed' => true]);

        // Ensure Configuration exists
        Configuration::create([
            'business_name' => 'Test Business',
            'device_access_mode' => 'open', // Default to open for auto-approval test cases
        ]);

        // Create Roles and Permissions
        Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'ADMIN']);
        Permission::firstOrCreate(['name' => 'reports.sales']);
        Permission::firstOrCreate(['name' => 'settings.devices']);

        // Create Admin user
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole('ADMIN');
        $this->adminUser->givePermissionTo('reports.sales');
        $this->adminUser->givePermissionTo('settings.devices');
    }

    /**
     * Helper to mock license details and set config.
     */
    protected function mockLicense(array $modules, int $maxDevices)
    {
        // Set config directly (useful for Livewire tests that don't go through the CheckLicense middleware)
        config(['tenant.modules' => $modules]);
        config(['tenant.max_devices' => $maxDevices]);

        $this->mock(LicenseService::class, function ($mock) use ($modules, $maxDevices) {
            $mock->shouldReceive('checkLicense')->andReturn([
                'status' => 'active',
                'days_remaining' => 30,
                'modules' => $modules,
                'max_devices' => $maxDevices,
            ]);
            $mock->shouldReceive('getClientId')->andReturn('test-client-id');
        });
    }

    public function test_reports_are_blocked_when_advanced_reports_module_is_missing()
    {
        // Mock license without advanced reports module
        $this->mockLicense([], 5);

        $this->actingAs($this->adminUser);

        // Define routes that require the module
        $routes = [
            'reports.sales.analysis',
            'reports.sellers.performance',
            'reports.operators.precision',
            'reports.exchange.diff',
            'reports.cash.flow.forecast'
        ];

        foreach ($routes as $routeName) {
            $response = $this->get(route($routeName));
            $response->assertRedirect(route('welcome'));
            $response->assertSessionHas('error');
        }
    }

    public function test_reports_are_allowed_when_advanced_reports_module_is_present()
    {
        // Mock license with advanced reports module
        $this->mockLicense(['module_advanced_reports'], 5);

        $this->actingAs($this->adminUser);

        // Define routes that require the module
        $routes = [
            'reports.sales.analysis',
            'reports.sellers.performance',
            'reports.operators.precision',
            'reports.exchange.diff',
            'reports.cash.flow.forecast'
        ];

        // Ensure we have at least one approved device to pass the CheckDeviceAuthorization middleware
        $device = DeviceAuthorization::create([
            'uuid' => 'approved-device-uuid',
            'name' => 'Approved Device',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'status' => 'approved',
        ]);

        foreach ($routes as $routeName) {
            $response = $this->withCookie('device_token', $device->uuid)
                ->get(route($routeName));
            $response->assertStatus(200);
        }
    }

    public function test_device_approval_is_blocked_when_limit_reached()
    {
        // Mock license with max_devices = 1
        $this->mockLicense([], 1);

        // Create an existing approved device
        DeviceAuthorization::create([
            'uuid' => 'device-1',
            'name' => 'Approved Device 1',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'status' => 'approved',
        ]);

        // Create a pending device we want to approve
        $pendingDevice = DeviceAuthorization::create([
            'uuid' => 'device-2',
            'name' => 'Pending Device 2',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser);

        // Test Livewire component
        Livewire::test(DeviceManager::class)
            ->call('approve', $pendingDevice->id)
            ->assertDispatched('msg-error');

        // Assert the device is still pending
        $this->assertEquals('pending', $pendingDevice->fresh()->status);
    }

    public function test_device_approval_succeeds_when_under_limit()
    {
        // Mock license with max_devices = 2
        $this->mockLicense([], 2);

        // Create an existing approved device
        DeviceAuthorization::create([
            'uuid' => 'device-1',
            'name' => 'Approved Device 1',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'status' => 'approved',
        ]);

        // Create a pending device we want to approve
        $pendingDevice = DeviceAuthorization::create([
            'uuid' => 'device-2',
            'name' => 'Pending Device 2',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'status' => 'pending',
        ]);

        $this->actingAs($this->adminUser);

        // Test Livewire component
        Livewire::test(DeviceManager::class)
            ->call('approve', $pendingDevice->id)
            ->assertNotDispatched('msg-error');

        // Assert the device is now approved
        $this->assertEquals('approved', $pendingDevice->fresh()->status);
    }

    public function test_new_device_is_forced_to_pending_when_limit_reached()
    {
        // Mock license with max_devices = 1
        $this->mockLicense([], 1);

        // Create an existing approved device to hit the limit.
        // Use a different IP and UA so the incoming test request (IP: 127.0.0.1, UA: Symfony)
        // doesn't match this device in fingerprint fallback.
        DeviceAuthorization::create([
            'uuid' => 'device-1',
            'name' => 'Approved Device 1',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'DifferentAgent',
            'status' => 'approved',
        ]);

        $this->actingAs($this->adminUser);

        // Connect with a new device (simulate request without a cookie, meaning it will create a new one)
        // Accessing 'welcome' which is a standard route
        $response = $this->get(route('welcome'));

        // Check if a new device was created and its status is forced to pending
        $newDevice = DeviceAuthorization::where('uuid', '!=', 'device-1')->first();
        $this->assertNotNull($newDevice);
        $this->assertEquals('pending', $newDevice->status);
    }

    public function test_new_device_is_approved_when_under_limit_and_mode_is_open()
    {
        // Mock license with max_devices = 2
        $this->mockLicense([], 2);

        // Create an existing approved device (limit is 2, so 1 slot remaining).
        // Use a different IP and UA so the incoming test request (IP: 127.0.0.1, UA: Symfony)
        // doesn't match this device in fingerprint fallback.
        DeviceAuthorization::create([
            'uuid' => 'device-1',
            'name' => 'Approved Device 1',
            'ip_address' => '192.168.1.100',
            'user_agent' => 'DifferentAgent',
            'status' => 'approved',
        ]);

        $this->actingAs($this->adminUser);

        // Connect with a new device
        $response = $this->get(route('welcome'));

        // Check if a new device was created and approved because access mode is open and limit not reached
        $newDevice = DeviceAuthorization::where('uuid', '!=', 'device-1')->first();
        $this->assertNotNull($newDevice);
        $this->assertEquals('approved', $newDevice->status);
    }
}
