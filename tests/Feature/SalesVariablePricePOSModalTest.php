<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Currency;
use App\Models\Customer;
use App\Models\Product;
use App\Models\SaleDetail;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class SalesVariablePricePOSModalTest extends TestCase
{
    use RefreshDatabase;

    public function test_variable_price_product_prompts_and_adds_with_custom_price_and_name()
    {
        $perm = Permission::firstOrCreate(['name' => 'sales.manage_adjustments']);

        $user = User::factory()->create();
        $user->givePermissionTo($perm);
        $this->actingAs($user);

        $warehouse = Warehouse::create(['name' => 'Almacen Principal', 'status' => 'active']);
        $supplier = Supplier::create(['name' => 'Proveedor']);
        $category = Category::create(['name' => 'Servicios']);
        $customer = Customer::create(['name' => 'Cliente General', 'status' => 1]);

        $currencyUsd = Currency::create(['name' => 'Dolar', 'label' => 'USD', 'code' => 'USD', 'symbol' => '$', 'exchange_rate' => 1.0, 'is_primary' => true]);
        $currencyVed = Currency::create(['name' => 'Bolivar', 'label' => 'Bs', 'code' => 'VED', 'symbol' => 'Bs', 'exchange_rate' => 50.0, 'is_primary' => false]);

        $product = Product::create([
            'sku' => 'SERV-DISEÑO',
            'name' => 'Servicio de Diseño Gráfico',
            'price' => 0.00,
            'cost' => 0.00,
            'stock_qty' => 100,
            'low_stock' => 0,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'manage_stock' => 0,
            'is_variable_price' => true,
        ]);

        $component = Livewire::test(\App\Livewire\Sales::class)
            ->set('warehouse_id', $warehouse->id)
            ->set('customer', $customer)
            ->call('AddProduct', $product->id);

        // 1. Assert event 'prompt-variable-price' was dispatched with productName
        $component->assertDispatched('prompt-variable-price');

        // 2. Assert pending properties stored product info
        $this->assertEquals($product->id, $component->get('pendingProductToAdd'));

        // 3. Dispatch 'set-variable-price-and-add' with custom price (45.00) and custom description ("Pendón 2x1m")
        $component->call('setVariablePriceAndAdd', ['price' => 45.00, 'customName' => 'Pendón 2x1m']);

        // 4. Verify item was added to cart with custom price and custom name
        $cart = $component->get('cart');
        $this->assertNotEmpty($cart);
        
        $addedItem = collect($cart)->firstWhere('pid', $product->id);
        $this->assertNotNull($addedItem);
        $this->assertEquals('Pendón 2x1m', $addedItem['name']);
        $this->assertEquals(45.00, $addedItem['sale_price']);

        // 5. Update quantity to 2 and verify custom price is retained (not reset to 0)
        $component->call('updateQty', $addedItem['id'], 2);
        $cartAfterUpdate = $component->get('cart');
        $updatedItem = collect($cartAfterUpdate)->firstWhere('id', $addedItem['id']);
        $this->assertEquals(45.00, $updatedItem['sale_price']);
        $this->assertEquals(90.00, $updatedItem['total']);

        // 6. Change currency to VED via set('invoiceCurrency_id') and verify price is recalculated correctly from base without resetting to 0
        $component->set('invoiceCurrency_id', $currencyVed->id);
        $cartAfterCurrencyChange = $component->get('cart');
        $currencyItem = collect($cartAfterCurrencyChange)->firstWhere('id', $addedItem['id']);
        $this->assertEquals(45.00, $currencyItem['sale_price']); // base sale_price in primary USD
        $this->assertEquals(90.00, $currencyItem['total']); // base total in primary USD

        // 7. Verify SaleDetail accessor returns custom_name when metadata has custom_name
        $detail = new SaleDetail([
            'product_id' => $product->id,
            'metadata' => json_encode(['custom_name' => 'Pendón 2x1m'])
        ]);
        $this->assertEquals('Pendón 2x1m', $detail->custom_name);
    }
}
