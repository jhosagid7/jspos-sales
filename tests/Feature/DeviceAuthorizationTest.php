<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\DeviceAuthorization;
use App\Models\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

class DeviceAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Enable app.installed config so that middleware executes
        config(['app.installed' => true]);

        // Ensure Configuration exists with restricted or open access
        Configuration::create([
            'business_name' => 'Test Business',
            'device_access_mode' => 'restricted',
        ]);

        // Create Spatie Roles
        Role::firstOrCreate(['name' => 'Super Admin']);
        Role::firstOrCreate(['name' => 'ADMIN']);

        // Mock LicenseService to bypass the license check
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

    public function test_non_super_admin_is_blocked_on_blocked_device()
    {
        // Create an ADMIN user
        $user = User::factory()->create();
        $user->assignRole('ADMIN');

        // Create a blocked device
        $device = DeviceAuthorization::create([
            'uuid' => 'blocked-device-uuid-123',
            'name' => 'Test Blocked Device',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'status' => 'blocked',
            'last_accessed_at' => now(),
        ]);

        // Act as the ADMIN user and request welcome page, with the blocked device cookie
        $response = $this->actingAs($user)
            ->withCookie('device_token', $device->uuid)
            ->get(route('welcome'));

        // Assert redirect to access-denied
        $response->assertRedirect(route('access.denied', ['device_uuid' => $device->uuid]));

        // Assert device remains blocked in database
        $this->assertEquals('blocked', $device->fresh()->status);
    }

    public function test_super_admin_bypasses_blocked_device_and_does_not_approve_it_permanently()
    {
        // Create a Super Admin user
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        // Create a blocked device
        $device = DeviceAuthorization::create([
            'uuid' => 'blocked-device-uuid-123',
            'name' => 'Test Blocked Device',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'status' => 'blocked',
            'last_accessed_at' => now(),
        ]);

        // Act as the Super Admin, with the blocked device cookie
        $response = $this->actingAs($superAdmin)
            ->withCookie('device_token', $device->uuid)
            ->get(route('welcome'));

        // Assert successful access (welcome route is rendered or redirecting but not to access denied)
        $response->assertStatus(200);

        // Assert device status remains 'blocked' in the database! (Extremely important)
        $this->assertEquals('blocked', $device->fresh()->status);
    }

    public function test_super_admin_auto_approves_pending_device()
    {
        // Create a Super Admin user
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole('Super Admin');

        // Create a pending device
        $device = DeviceAuthorization::create([
            'uuid' => 'pending-device-uuid-123',
            'name' => 'Test Pending Device',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Symfony',
            'status' => 'pending',
            'last_accessed_at' => now(),
        ]);

        // Act as the Super Admin, with the pending device cookie
        $response = $this->actingAs($superAdmin)
            ->withCookie('device_token', $device->uuid)
            ->get(route('welcome'));

        $response->assertStatus(200);

        // Assert device status is auto-approved to 'approved' in the database!
        $this->assertEquals('approved', $device->fresh()->status);
    }
}
