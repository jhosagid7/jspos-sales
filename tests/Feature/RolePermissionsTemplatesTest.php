<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Configuration;
use App\Models\Currency;
use App\Models\CashRegister;
use App\Services\RoleTemplateService;
use App\Livewire\AsignarPermisos;
use App\Livewire\Sales;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RolePermissionsTemplatesTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $superAdminRole;
    protected $adminRole;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed permissions and roles
        $this->seed(\Database\Seeders\PermissionSeeder::class);
        $this->seed(\Database\Seeders\RoleSeeder::class);

        // System Configuration
        Configuration::create([
            'business_name' => 'JSPOS Sales Test',
            'taxpayer_id' => 'V-12345678-9',
            'address' => 'Test Address',
            'phone' => '1234567',
        ]);

        Currency::create([
            'name' => 'Dolares',
            'code' => 'USD',
            'label' => 'USD',
            'symbol' => '$',
            'exchange_rate' => 1,
            'is_primary' => 1,
            'status' => 1
        ]);

        // Create Admin user
        $this->adminUser = User::firstOrCreate(['email' => 'admin_perm_test@test.com'], [
            'name' => 'Admin Test',
            'status' => 'Active',
            'profile' => 'Admin',
            'password' => bcrypt('password')
        ]);
        $this->adminUser->assignRole('Admin');
    }

    public function test_seeders_create_standard_roles_with_canonical_permissions()
    {
        $this->assertTrue(Role::where('name', 'Admin')->exists());
        $this->assertTrue(Role::where('name', 'Administrador')->exists());
        $this->assertTrue(Role::where('name', 'Supervisor')->exists());
        $this->assertTrue(Role::where('name', 'Cajero')->exists());
        $this->assertTrue(Role::where('name', 'Vendedor')->exists());
        $this->assertTrue(Role::where('name', 'Vendedor Foraneo')->exists());
        $this->assertTrue(Role::where('name', 'Chofer')->exists());

        $cashierRole = Role::findByName('Cajero');
        $this->assertTrue($cashierRole->hasPermissionTo('sales.index'));
        $this->assertTrue($cashierRole->hasPermissionTo('sales.create'));
        $this->assertTrue($cashierRole->hasPermissionTo('cash_register.open'));
        $this->assertTrue($cashierRole->hasPermissionTo('payments.pay'));

        $foreignSellerRole = Role::findByName('Vendedor Foraneo');
        $this->assertTrue($foreignSellerRole->hasPermissionTo('cash_register.bypass'));
        $this->assertTrue($foreignSellerRole->hasPermissionTo('system.is_foreign_seller'));
        $this->assertTrue($foreignSellerRole->hasPermissionTo('sales.index'));
    }

    public function test_role_template_service_returns_all_templates()
    {
        $templates = RoleTemplateService::getTemplates();
        $this->assertArrayHasKey('super_admin', $templates);
        $this->assertArrayHasKey('admin', $templates);
        $this->assertArrayHasKey('supervisor', $templates);
        $this->assertArrayHasKey('cashier', $templates);
        $this->assertArrayHasKey('seller', $templates);
        $this->assertArrayHasKey('foreign_seller', $templates);
        $this->assertArrayHasKey('driver', $templates);
        $this->assertArrayHasKey('soplados', $templates);
    }

    public function test_livewire_asignar_permisos_applies_cashier_template_with_one_click()
    {
        $customRole = Role::create(['name' => 'Cajero Nocturno', 'guard_name' => 'web']);
        $this->assertCount(0, $customRole->permissions);

        Livewire::actingAs($this->adminUser)
            ->test(AsignarPermisos::class)
            ->set('roleSelectedId', $customRole->id)
            ->call('applyTemplate', 'cashier')
            ->assertDispatched('noty');

        $customRole->refresh();
        $this->assertTrue($customRole->hasPermissionTo('sales.index'));
        $this->assertTrue($customRole->hasPermissionTo('sales.create'));
        $this->assertTrue($customRole->hasPermissionTo('cash_register.open'));
        $this->assertTrue($customRole->hasPermissionTo('payments.pay'));
        $this->assertGreaterThanOrEqual(30, $customRole->permissions->count());
    }

    public function test_livewire_asignar_permisos_applies_foreign_seller_template_with_one_click()
    {
        $customRole = Role::create(['name' => 'Preventista Ruta Norte', 'guard_name' => 'web']);

        Livewire::actingAs($this->adminUser)
            ->test(AsignarPermisos::class)
            ->set('roleSelectedId', $customRole->id)
            ->call('applyTemplate', 'foreign_seller')
            ->assertDispatched('noty');

        $customRole->refresh();
        $this->assertTrue($customRole->hasPermissionTo('cash_register.bypass'));
        $this->assertTrue($customRole->hasPermissionTo('system.is_foreign_seller'));
        $this->assertTrue($customRole->hasPermissionTo('sales.index'));
        $this->assertTrue($customRole->hasPermissionTo('orders.save'));
    }

    public function test_livewire_asignar_permisos_applies_driver_template_with_one_click()
    {
        $customRole = Role::create(['name' => 'Chofer Camion 2', 'guard_name' => 'web']);

        Livewire::actingAs($this->adminUser)
            ->test(AsignarPermisos::class)
            ->set('roleSelectedId', $customRole->id)
            ->call('applyTemplate', 'driver')
            ->assertDispatched('noty');

        $customRole->refresh();
        $this->assertTrue($customRole->hasPermissionTo('distribution.map'));
        $this->assertTrue($customRole->hasPermissionTo('driver_monitoring'));
        $this->assertTrue($customRole->hasPermissionTo('sales.index'));
        $this->assertFalse($customRole->hasPermissionTo('sales.create'));
    }

    public function test_livewire_asignar_permisos_clears_all_permissions()
    {
        $customRole = Role::create(['name' => 'Rol Temporal', 'guard_name' => 'web']);
        RoleTemplateService::applyTemplateToRole($customRole, 'cashier');
        $this->assertGreaterThan(0, $customRole->permissions->count());

        Livewire::actingAs($this->adminUser)
            ->test(AsignarPermisos::class)
            ->set('roleSelectedId', $customRole->id)
            ->call('clearRolePermissions')
            ->assertDispatched('noty');

        $customRole->refresh();
        $this->assertCount(0, $customRole->permissions);
    }

    public function test_cashier_can_access_pos_with_open_register()
    {
        $cashier = User::factory()->create([
            'name' => 'Pedro Cajero',
            'email' => 'pedro@test.com',
            'status' => 'Active',
            'profile' => 'Cajero'
        ]);
        $cashier->assignRole('Cajero');

        // Open cash register for cashier
        CashRegister::create([
            'user_id' => $cashier->id,
            'total_opening_amount' => 50,
            'status' => 'open',
            'opening_date' => now(),
        ]);

        $response = $this->actingAs($cashier)->get(route('sales'));
        $response->assertStatus(200);
    }

    public function test_foreign_seller_can_access_pos_without_open_cash_register()
    {
        $foreignSeller = User::factory()->create([
            'name' => 'Ana Vendedora Foranea',
            'email' => 'ana@test.com',
            'status' => 'Active',
            'profile' => 'Vendedor Foraneo'
        ]);
        $foreignSeller->assignRole('Vendedor Foraneo');

        // No cash register opened!
        // Should succeed and NOT redirect because cash_register.bypass is granted
        $response = $this->actingAs($foreignSeller)->get(route('sales'));
        $response->assertStatus(200);
    }

    public function test_user_without_sales_permission_is_forbidden()
    {
        $restrictedUser = User::factory()->create([
            'name' => 'Usuario Restringido',
            'email' => 'restringido@test.com',
            'status' => 'Active',
            'profile' => 'SinVentas'
        ]);
        $emptyRole = Role::create(['name' => 'SinVentas', 'guard_name' => 'web']);
        $restrictedUser->assignRole($emptyRole);

        $response = $this->actingAs($restrictedUser)->get(route('sales'));
        $response->assertStatus(403);
    }

    public function test_cashier_template_includes_seller_flag_currency_change_and_adjustments()
    {
        $cashierPermissions = RoleTemplateService::getCashierPermissions();
        $this->assertContains('system.is_seller', $cashierPermissions);
        $this->assertContains('sales.change_invoice_currency', $cashierPermissions);
        $this->assertContains('sales.manage_adjustments', $cashierPermissions);

        $customRole = Role::create(['name' => 'Operador Principal', 'guard_name' => 'web']);
        RoleTemplateService::applyTemplateToRole($customRole, 'cashier');

        $this->assertTrue($customRole->hasPermissionTo('system.is_seller'));
        $this->assertTrue($customRole->hasPermissionTo('sales.change_invoice_currency'));
        $this->assertTrue($customRole->hasPermissionTo('sales.manage_adjustments'));
    }

    public function test_livewire_asignar_permisos_exports_template_as_json()
    {
        $role = Role::create(['name' => 'Vendedor VIP', 'guard_name' => 'web']);
        $role->givePermissionTo(['sales.index', 'sales.create', 'system.is_seller']);

        $response = Livewire::actingAs($this->adminUser)
            ->test(AsignarPermisos::class)
            ->set('roleSelectedId', $role->id)
            ->call('exportTemplate');

        $this->assertNotNull($response);
    }

    public function test_livewire_asignar_permisos_imports_template_from_json_file()
    {
        $role = Role::create(['name' => 'Cajero Importado', 'guard_name' => 'web']);
        $this->assertCount(0, $role->permissions);

        $templateData = [
            'system' => 'JSPOS-Sales',
            'role_name' => 'Plantilla Test',
            'version' => '1.0',
            'permissions' => [
                'sales.index',
                'sales.create',
                'system.is_seller',
                'sales.change_invoice_currency',
                'sales.manage_adjustments'
            ]
        ];

        $tmpFile = \Illuminate\Http\UploadedFile::fake()->createWithContent('plantilla_cajero.json', json_encode($templateData));

        Livewire::actingAs($this->adminUser)
            ->test(AsignarPermisos::class)
            ->set('roleSelectedId', $role->id)
            ->set('templateFile', $tmpFile)
            ->assertDispatched('noty');

        $role->refresh();
        $this->assertTrue($role->hasPermissionTo('sales.index'));
        $this->assertTrue($role->hasPermissionTo('sales.create'));
        $this->assertTrue($role->hasPermissionTo('system.is_seller'));
        $this->assertTrue($role->hasPermissionTo('sales.change_invoice_currency'));
        $this->assertTrue($role->hasPermissionTo('sales.manage_adjustments'));
    }
}

