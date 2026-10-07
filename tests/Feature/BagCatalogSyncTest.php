<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Services\BagCatalogSyncService;
use App\Livewire\Products;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

class BagCatalogSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_service_sends_factory_products_to_cloud()
    {
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $factorySupplier = Supplier::create([
            'id' => 10,
            'name' => 'FABRICA BOLSA',
            'taxpayer_id' => 'J-1000',
            'address' => 'Zona Ind',
            'phone' => '111',
        ]);
        $otherSupplier = Supplier::create([
            'id' => 99,
            'name' => 'OTRO PROVEEDOR',
            'taxpayer_id' => 'J-2000',
            'address' => 'Caracas',
            'phone' => '222',
        ]);

        $bagsCategory = Category::create(['id' => 2, 'name' => 'BOLSAS']);
        $generalCategory = Category::create(['id' => 1, 'name' => 'GENERAL']);

        Product::create([
            'name' => 'BOLSA ROLLO 20X30',
            'sku' => 'FAC-001',
            'cost' => 10.50,
            'price' => 15.00,
            'stock_qty' => 100,
            'low_stock' => 5,
            'category_id' => $generalCategory->id,
            'supplier_id' => $factorySupplier->id,
            'is_variable_quantity' => false,
        ]);

        Product::create([
            'name' => 'BOBINA POLIETILENO',
            'sku' => 'FAC-002',
            'cost' => 20.00,
            'price' => 30.00,
            'stock_qty' => 50,
            'low_stock' => 5,
            'category_id' => $bagsCategory->id,
            'supplier_id' => $otherSupplier->id,
            'is_variable_quantity' => true,
        ]);

        Product::create([
            'name' => 'REFRESCO COCA COLA',
            'sku' => 'BEV-001',
            'cost' => 1.00,
            'price' => 2.00,
            'stock_qty' => 200,
            'low_stock' => 10,
            'category_id' => $generalCategory->id,
            'supplier_id' => $otherSupplier->id,
            'is_variable_quantity' => false,
        ]);

        Http::fake([
            BagCatalogSyncService::CLOUD_URL => Http::response([
                'success' => true,
                'message' => 'Catálogo sincronizado exitosamente',
                'count' => 2,
            ], 200),
        ]);

        $result = BagCatalogSyncService::syncAll();

        $this->assertTrue($result['success']);
        $this->assertEquals(2, $result['count']);

        Http::assertSent(function ($request) {
            $data = $request->data();
            return $request->url() === BagCatalogSyncService::CLOUD_URL &&
                   isset($data['products']) &&
                   count($data['products']) === 2;
        });
    }

    public function test_livewire_products_sync_button_action()
    {
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $user = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::findOrCreate('Admin');
        $permission = \Spatie\Permission\Models\Permission::findOrCreate('products.index');
        $role->givePermissionTo([$permission]);
        $user->assignRole($role);

        Http::fake([
            BagCatalogSyncService::CLOUD_URL => Http::response([
                'success' => true,
                'count' => 0,
            ], 200),
        ]);

        Livewire::actingAs($user)
            ->test(Products::class)
            ->call('syncToJsBolsas')
            ->assertDispatched('noty');
    }
}