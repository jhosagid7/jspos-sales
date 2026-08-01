<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
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
    }
}
