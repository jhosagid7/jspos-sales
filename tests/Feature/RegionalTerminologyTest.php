<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Configuration;
use App\Models\User;
use App\Services\TerminologyService;
use App\Livewire\Settings;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class RegionalTerminologyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        TerminologyService::clearCache();
    }

    /** @test */
    public function default_terminology_returns_deposito_and_socio()
    {
        Configuration::create([
            'business_name' => 'Empresa Test',
            'custom_labels' => null,
        ]);

        TerminologyService::clearCache();

        $this->assertEquals('Depósito', term('warehouse'));
        $this->assertEquals('Depósitos', term('warehouses'));
        $this->assertEquals('depósito', term('warehouse_lower'));
        $this->assertEquals('depósitos', term('warehouses_lower'));
        $this->assertEquals('del depósito', term('warehouse_of'));
        $this->assertEquals('de los depósitos', term('warehouses_of'));

        $this->assertEquals('Socio', term('partner'));
        $this->assertEquals('Socios', term('partners'));
        $this->assertEquals('socio', term('partner_lower'));
        $this->assertEquals('socios', term('partners_lower'));
        $this->assertEquals('del socio', term('partner_of'));
        $this->assertEquals('de los socios', term('partners_of'));
    }

    /** @test */
    public function custom_terminology_supports_bodega_and_proveedor()
    {
        Configuration::create([
            'business_name' => 'Empresa Test',
            'custom_labels' => [
                'warehouse_term' => 'bodega',
                'partner_term' => 'proveedor',
            ],
        ]);

        TerminologyService::clearCache();

        $this->assertEquals('Bodega', term('warehouse'));
        $this->assertEquals('Bodegas', term('warehouses'));
        $this->assertEquals('bodega', term('warehouse_lower'));
        $this->assertEquals('bodegas', term('warehouses_lower'));
        $this->assertEquals('de la bodega', term('warehouse_of'));
        $this->assertEquals('de las bodegas', term('warehouses_of'));

        $this->assertEquals('Proveedor', term('partner'));
        $this->assertEquals('Proveedores', term('partners'));
        $this->assertEquals('proveedor', term('partner_lower'));
        $this->assertEquals('proveedores', term('partners_lower'));
        $this->assertEquals('del proveedor', term('partner_of'));
        $this->assertEquals('de los proveedores', term('partners_of'));
    }

    /** @test */
    public function custom_terminology_supports_almacen_and_aliado()
    {
        Configuration::create([
            'business_name' => 'Empresa Test',
            'custom_labels' => [
                'warehouse_term' => 'almacen',
                'partner_term' => 'aliado',
            ],
        ]);

        TerminologyService::clearCache();

        $this->assertEquals('Almacén', term('warehouse'));
        $this->assertEquals('Almacenes', term('warehouses'));
        $this->assertEquals('del almacén', term('warehouse_of'));
        $this->assertEquals('de los almacenes', term('warehouses_of'));

        $this->assertEquals('Aliado', term('partner'));
        $this->assertEquals('Aliados', term('partners'));
        $this->assertEquals('del aliado', term('partner_of'));
        $this->assertEquals('de los aliados', term('partners_of'));
    }

    /** @test */
    public function term_returns_custom_fallback_for_unknown_key()
    {
        $this->assertEquals('Valor Personalizado', term('non_existent_key', 'Valor Personalizado'));
    }

    /** @test */
    public function livewire_settings_saves_and_updates_regional_terminology()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Configuration::create([
            'business_name' => 'Empresa Test',
            'address' => 'Calle Principal 123',
            'city' => 'Caracas',
            'taxpayer_id' => 'J-12345678-9',
            'vat' => 16,
            'decimals' => 2,
            'printer_name' => 'POS-58',
            'credit_days' => 15,
            'currency_symbol' => '$',
        ]);

        TerminologyService::clearCache();
        $this->assertEquals('Depósito', term('warehouse'));

        Livewire::test(Settings::class)
            ->set('warehouseTerm', 'bodega')
            ->set('partnerTerm', 'proveedor')
            ->call('saveConfig')
            ->assertHasNoErrors();

        $config = Configuration::first();
        $this->assertIsArray($config->custom_labels);
        $this->assertEquals('bodega', $config->custom_labels['warehouse_term']);
        $this->assertEquals('proveedor', $config->custom_labels['partner_term']);

        $this->assertEquals('Bodega', term('warehouse'));
        $this->assertEquals('Proveedor', term('partner'));
    }
}
