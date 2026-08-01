<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ProductVariablePriceTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_saves_is_variable_price_field_correctly()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $supplier = Supplier::create(['name' => 'Proveedor Test']);
        $category = Category::create(['name' => 'Categoria Test']);

        $product = Product::create([
            'sku' => 'TEST-VAR-PRICE',
            'name' => 'Producto Servicio Test',
            'price' => 10.00,
            'cost' => 5.00,
            'stock_qty' => 0,
            'low_stock' => 0,
            'supplier_id' => $supplier->id,
            'category_id' => $category->id,
            'is_variable_price' => false,
        ]);

        $this->assertFalse((bool) $product->is_variable_price);

        // Test updating via model fillable
        $product->update([
            'is_variable_price' => true,
        ]);

        $product->refresh();
        $this->assertTrue((bool) $product->is_variable_price);

        // Test updating via Livewire Products component
        Livewire::test(\App\Livewire\Products::class)
            ->call('Edit', $product->id)
            ->set('form.is_variable_price', true)
            ->call('Update');

        $product->refresh();
        $this->assertTrue((bool) $product->is_variable_price);
    }
}
