<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Configuration;
use Spatie\Permission\Models\Role;

class PlanConsistencyTest extends TestCase
{
    use RefreshDatabase;

    protected $superAdmin;
    protected $config;

    protected function setUp(): void
    {
        parent::setUp();

        // Create Super Admin role and user
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $this->superAdmin = User::factory()->create([
            'email' => 'admin@test.com',
            'profile' => 'Super Admin'
        ]);
        $this->superAdmin->assignRole($role);

        // Create base config
        $this->config = Configuration::create([
            'plan_type' => 'basic',
            'business_name' => 'Test Business',
            'vat' => 0,
            'decimals' => 2,
        ]);
    }

    /** @test */
    public function config_plans_file_returns_valid_structure()
    {
        $plansConfig = config('plans');

        $this->assertIsArray($plansConfig);
        $this->assertArrayHasKey('tiers', $plansConfig);
        $this->assertArrayHasKey('dependencies', $plansConfig);
        $this->assertArrayHasKey('available_modules', $plansConfig);

        $this->assertArrayHasKey('basic', $plansConfig['tiers']);
        $this->assertArrayHasKey('pro', $plansConfig['tiers']);
        $this->assertArrayHasKey('premium', $plansConfig['tiers']);
    }

    /** @test */
    public function super_admin_bypasses_check_module_middleware()
    {
        $this->actingAs($this->superAdmin);

        // Define tenant modules as empty array (Plan Básico without modules)
        config(['tenant.modules' => []]);

        // Create a dummy request to test CheckModule handle
        $middleware = new \App\Http\Middleware\CheckModule();
        $request = \Illuminate\Http\Request::create('/test-route', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return response('allowed');
        }, 'module_commissions');

        $this->assertEquals('allowed', $response->getContent());
    }

    /** @test */
    public function super_admin_bypasses_plan_access_middleware()
    {
        $this->actingAs($this->superAdmin);

        $middleware = new \App\Http\Middleware\PlanAccessMiddleware();
        $request = \Illuminate\Http\Request::create('/test-route', 'GET');

        $response = $middleware->handle($request, function ($req) {
            return response('allowed');
        }, 'plan', 'premium');

        $this->assertEquals('allowed', $response->getContent());
    }

    /** @test */
    public function generate_license_command_auto_resolves_module_dependencies()
    {
        if (!file_exists(base_path('private_key.pem'))) {
            $this->markTestSkipped('private_key.pem missing for license generation test.');
        }

        $clientId = 'test-client-12345';

        $this->artisan('license:generate', [
            'client_id' => $clientId,
            '--plan' => 'BASIC',
            '--add' => 'module_production',
        ])
        ->expectsOutput('License generated successfully!')
        ->expectsOutput('Client ID: test-client-12345')
        ->expectsOutput('Plan: BASIC')
        ->expectsOutput('Max Devices: 1')
        ->expectsOutput('Modules (2): module_production, module_multi_warehouse')
        ->expectsOutput('Dependencias agregadas automáticamente:')
        ->expectsOutput('  → module_multi_warehouse (requerido por module_production)')
        ->assertExitCode(0);
    }
}
