<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Configuration;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Settings;
use App\Livewire\Customers;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class WorkshopConfigurationToggleTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $this->resetConfigCache();

        Configuration::create([
            'business_name' => 'Comercio General Test',
            'taxpayer_id' => 'J-12345678-9',
            'address' => 'Caracas, Venezuela',
            'city' => 'Caracas',
            'phone' => '0212-1234567',
            'decimals' => 2,
            'vat' => 16,
            'printer_name' => 'EPSON',
            'credit_days' => 15,
            'enable_workshop' => false,
        ]);

        $superAdminRole = Role::findOrCreate('Super Admin');
        $this->adminUser = User::factory()->create();
        $this->adminUser->assignRole($superAdminRole);

        Permission::findOrCreate('customers.index');
        Permission::findOrCreate('sales.index');
        $this->adminUser->givePermissionTo(['customers.index', 'sales.index']);
    }

    private function resetConfigCache(): void
    {
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);
    }

    public function test_workshop_is_disabled_by_default_and_hidden_even_for_super_admin(): void
    {
        $this->resetConfigCache();
        $this->assertFalse(ConfigurationService::isWorkshopEnabled());
        $this->assertFalse(Configuration::first()->hasAddon('workshop'));

        // Sidebar check
        $response = $this->actingAs($this->adminUser)->get(route('welcome'));
        $response->assertOk();
        $response->assertDontSee('TALLER AUTOMOTRIZ');
        $response->assertDontSee('Órdenes de Trabajo');
        $response->assertDontSee('Historial de Vehículos');

        // Customer form check
        $customer = Customer::create([
            'name' => 'Cliente General Test',
            'type' => 'Consumidor Final',
            'address' => 'Caracas',
            'city' => 'Caracas',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(Customers::class)
            ->call('Edit', $customer)
            ->assertDontSee('Vehículos / Flota');
    }

    public function test_settings_can_enable_workshop_module(): void
    {
        $this->resetConfigCache();

        Livewire::actingAs($this->adminUser)
            ->test(Settings::class)
            ->assertSet('enableWorkshop', false)
            ->set('enableWorkshop', true)
            ->call('saveConfig')
            ->assertDispatched('noty', msg: 'Configuración General Actualizada');

        $this->resetConfigCache();
        $config = Configuration::first();
        $this->assertTrue((bool)$config->enable_workshop);
        $this->assertTrue(ConfigurationService::isWorkshopEnabled());
        $this->assertTrue($config->hasAddon('workshop'));
    }

    public function test_sidebar_and_customer_form_displays_workshop_when_enabled(): void
    {
        Configuration::first()->update(['enable_workshop' => true]);
        $this->resetConfigCache();

        $response = $this->actingAs($this->adminUser)->get(route('welcome'));
        $response->assertOk();
        $response->assertSee('TALLER AUTOMOTRIZ');
        $response->assertSee('Órdenes de Trabajo');
        $response->assertSee('Historial de Vehículos');

        $customer = Customer::create([
            'name' => 'Cliente Con Taller',
            'type' => 'Consumidor Final',
            'address' => 'Caracas',
            'city' => 'Caracas',
        ]);

        Livewire::actingAs($this->adminUser)
            ->test(Customers::class)
            ->call('Edit', $customer)
            ->assertSee('Vehículos / Flota');
    }

    public function test_workshop_routes_blocked_when_disabled(): void
    {
        $this->resetConfigCache();
        $this->assertFalse(ConfigurationService::isWorkshopEnabled());

        $response = $this->actingAs($this->adminUser)->get(route('workshop.work-orders'));
        $response->assertRedirect(route('welcome'));
        $response->assertSessionHas('error');
    }

    public function test_workshop_routes_accessible_when_enabled(): void
    {
        Configuration::first()->update(['enable_workshop' => true]);
        $this->resetConfigCache();

        $response = $this->actingAs($this->adminUser)->get(route('workshop.work-orders'));
        $response->assertOk();
    }
}
