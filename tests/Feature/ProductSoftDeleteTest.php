<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Products;

class ProductSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_soft_deleted_and_restored()
    {
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $user = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::findOrCreate('Admin');
        $permission1 = \Spatie\Permission\Models\Permission::findOrCreate('products.delete');
        $permission2 = \Spatie\Permission\Models\Permission::findOrCreate('products.edit');
        $role->givePermissionTo([$permission1, $permission2]);
        $user->assignRole($role);

        $category = Category::create(['name' => 'Cat Test']);
        $supplier = Supplier::create([
            'name' => 'Supplier Test',
            'taxpayer_id' => 'J-1234',
            'address' => 'Test Address',
            'phone' => '123',
        ]);

        $product = Product::create([
            'sku' => 'SKU-TEST',
            'name' => 'Test Product',
            'cost' => 10,
            'price' => 15,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'manage_stock' => false,
            'stock_qty' => 10,
            'low_stock' => 1,
        ]);

        $this->actingAs($user);

        // Verify loaded initially in livewire
        Livewire::test(Products::class)
            ->assertSee('Test Product')
            ->call('Destroy', $product->id);

        // Verify product is soft-deleted in DB
        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);

        // Verify it is not visible in the normal list
        Livewire::test(Products::class)
            ->assertDontSee('Test Product');

        // Verify it is visible when showDeleted is true
        Livewire::test(Products::class)
            ->set('showDeleted', true)
            ->assertSee('Test Product')
            ->call('Restore', $product->id);

        // Verify it is restored in DB
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'deleted_at' => null,
        ]);

        // Verify it is visible in the normal list again
        Livewire::test(Products::class)
            ->assertSee('Test Product');
    }

    public function test_product_with_sales_can_be_soft_deleted_and_relationship_works()
    {
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $user = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::findOrCreate('Admin');
        $permission1 = \Spatie\Permission\Models\Permission::findOrCreate('products.delete');
        $role->givePermissionTo([$permission1]);
        $user->assignRole($role);

        $category = Category::create(['name' => 'Cat Test']);
        $supplier = Supplier::create([
            'name' => 'Supplier Test',
            'taxpayer_id' => 'J-1234',
            'address' => 'Test Address',
            'phone' => '123',
        ]);

        $product = Product::create([
            'sku' => 'SKU-TEST',
            'name' => 'Test Product',
            'cost' => 10,
            'price' => 15,
            'status' => 'available',
            'show_in_sales' => true,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'manage_stock' => false,
            'stock_qty' => 10,
            'low_stock' => 1,
        ]);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'address' => 'Test Address',
            'city' => 'Test City',
            'phone' => '12345678',
        ]);

        $sale = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total' => 100,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit'
        ]);

        $detail = SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'warehouse_id' => 1,
            'regular_price' => 15,
            'quantity' => 1,
            'sale_price' => 15,
            'discount' => 0,
        ]);

        $this->actingAs($user);

        // Soft delete the product
        Livewire::test(Products::class)
            ->call('Destroy', $product->id);

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);

        // Verify that the sale detail relationship still works using withTrashed()
        $detailRelation = SaleDetail::find($detail->id)->product;
        $this->assertNotNull($detailRelation);
        $this->assertEquals('Test Product', $detailRelation->name);
    }
}
