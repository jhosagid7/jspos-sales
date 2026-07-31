<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CustomerConfig;
use App\Models\Product;
use App\Models\Configuration;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Sales;

class SalesControlsMutualExclusivityTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $customer;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed currencies
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Reset ConfigurationService static cache
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Create Configuration (0% VAT for clean price math)
        Configuration::create([
            'business_name'              => 'Test Business',
            'taxpayer_id'                => '12345678',
            'address'                    => 'Test Address',
            'city'                       => 'Caracas',
            'phone'                      => '0212-5555555',
            'decimals'                   => 2,
            'vat'                        => 0,
            'sales_show_commissions'     => true,
            'sales_show_freight'         => true,
            'sales_show_breakdown_freight' => true,
        ]);

        $this->adminUser = User::factory()->create();

        // Customer: 5% comm, 6% freight, 4% markup, 30% exchange diff
        $this->customer = Customer::create([
            'name'  => 'Test Client',
            'code'  => 'CLI-001',
            'tax_id' => 'J-12345678-0',
            'phone' => '04141234567',
        ]);

        CustomerConfig::create([
            'customer_id'          => $this->customer->id,
            'commission_percent'   => 5.0,
            'freight_percent'      => 6.0,
            'base_markup_percent'  => 4.0,
            'exchange_diff_percent'=> 30.0,
        ]);

        $category = \App\Models\Category::create(['name' => 'General']);
        $supplier = \App\Models\Supplier::create(['name' => 'Default']);

        // Product base price = 10.00, freight_type=global (uses customer config)
        $this->product = Product::create([
            'name'          => 'Test Product',
            'code'          => 'PROD-001',
            'sku'           => 'SKU-001',
            'cost'          => 5.0,
            'price'         => 10.0,
            'price1'        => 10.0,
            'price_usd'     => 10.0,
            'show_in_sales' => true,
            'manage_stock'  => false,
            'stock_qty'     => 100,
            'low_stock'     => 0,
            'category_id'   => $category->id,
            'supplier_id'   => $supplier->id,
            'freight_type'  => 'global',
            'freight_value' => 0,
            'status'        => 'available',
        ]);
    }

    /**
     * @test
     * Activar applyCommissions debe forzar applyFreight=false y viceversa.
     */
    public function test_apply_commissions_and_apply_freight_are_mutually_exclusive()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(Sales::class)
            ->set('applyCommissions', true)
            ->assertSet('applyCommissions', true)
            ->assertSet('applyFreight', false)
            ->set('applyFreight', true)
            ->assertSet('applyFreight', true)
            ->assertSet('applyCommissions', false);
    }

    /**
     * @test
     * Formula con applyCommissions=true:
     *   base=10, comm=5%=0.50, markup=4%=0.40, freight=6%=0.60
     *   intermediate = 10 + 0.50 + 0.40 + 0.60 = 11.50
     *   diff = 11.50 * 30% = 3.45
     *   sale_price = 11.50 + 3.45 = 14.95
     */
    public function test_apply_commissions_calculates_integral_price_formula()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(Sales::class)
            ->set('customer', $this->customer->toArray())
            ->set('customerConfig', $this->customer->latestCustomerConfig)
            ->set('is_freight_broken_down', false)   // freight included in unit price
            ->set('applyCommissions', true);          // triggers updatedApplyCommissions

        // Call Calculator($price, $qty, $product) — the real method name in Sales.php
        $result = $component->instance()->Calculator(10.0, 1, $this->product);

        $this->assertEqualsWithDelta(14.95, $result['sale_price'], 0.01,
            'Con applyCommissions=true, sale_price debe ser 14.95');
    }

    /**
     * @test
     * Formula con applyFreight=true (solo flete):
     *   base=10, freight=6%=0.60
     *   No comm, no markup, no diff
     *   sale_price = 10 + 0.60 = 10.60
     */
    public function test_apply_solo_freight_calculates_only_freight()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(Sales::class)
            ->set('customer', $this->customer->toArray())
            ->set('customerConfig', $this->customer->latestCustomerConfig)
            ->set('is_freight_broken_down', false)
            ->set('applyFreight', true);              // triggers updatedApplyFreight

        $result = $component->instance()->Calculator(10.0, 1, $this->product);

        $this->assertEqualsWithDelta(10.60, $result['sale_price'], 0.01,
            'Con applyFreight=true (solo flete), sale_price debe ser 10.60');
    }

    /**
     * @test
     * Sin ningún control activo, el precio no tiene recargos.
     */
    public function test_no_controls_active_returns_base_price()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(Sales::class)
            ->set('applyCommissions', false)
            ->set('applyFreight', false);

        $result = $component->instance()->Calculator(10.0, 1, $this->product);

        $this->assertEqualsWithDelta(10.0, $result['sale_price'], 0.01,
            'Sin controles activos, sale_price debe ser igual al precio base');
    }

    /**
     * @test
     * Desactivar applyCommissions NO debe activar automáticamente applyFreight.
     * El precio resultan te debe ser el precio base limpio ($10.00), no $10.60.
     */
    public function test_toggling_off_commissions_does_not_auto_enable_freight()
    {
        $this->actingAs($this->adminUser);

        $component = Livewire::test(Sales::class)
            ->set('customer', $this->customer->toArray())
            ->set('customerConfig', $this->customer->latestCustomerConfig)
            ->set('applyCommissions', true)
            ->assertSet('applyCommissions', true)
            ->assertSet('applyFreight', false)
            ->set('applyCommissions', false)
            ->assertSet('applyCommissions', false)
            ->assertSet('applyFreight', false);

        $result = $component->instance()->Calculator(10.0, 1, $this->product);

        $this->assertEqualsWithDelta(10.0, $result['sale_price'], 0.01,
            'Al desactivar comisiones, applyFreight debe permanecer en false y retornar el precio base limpio (10.00)');
    }
}
