<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use App\Livewire\Products;

class ProductImageUploadTest extends TestCase
{
    public function test_product_creation_stores_gallery_images_with_correct_product_id()
    {
        $user = User::factory()->create();
        $user->givePermissionTo('products.create');
        $this->actingAs($user);

        $category = Category::create(['name' => 'Test Category ' . uniqid()]);
        $supplier = Supplier::create(['name' => 'Test Supplier ' . uniqid()]);

        $file1 = UploadedFile::fake()->image('coca_cola.jpg');

        Livewire::test(Products::class)
            ->set('form.name', 'Producto Prueba Coca Cola ' . uniqid())
            ->set('form.sku', 'SKU-' . rand(1000, 9999))
            ->set('form.cost', 10.00)
            ->set('form.price', 15.00)
            ->set('form.category_id', $category->id)
            ->set('form.supplier_id', $supplier->id)
            ->set('form.gallery', [$file1])
            ->call('Store');

        $product = Product::where('category_id', $category->id)->first();
        $this->assertNotNull($product, 'Product should be created');

        $this->assertCount(1, $product->images, 'Product should have 1 image linked to its ID');
        $image = $product->images->first();
        $this->assertEquals($product->id, $image->model_id, 'Image model_id must match product ID');

        $this->assertTrue(Storage::disk('public')->exists('products/' . $image->file), 'File must exist on public storage disk');

        // Cleanup
        Storage::disk('public')->delete('products/' . $image->file);
        $product->forceDelete();
        $category->delete();
        $supplier->delete();
    }
}
