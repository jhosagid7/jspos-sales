<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use App\Livewire\Customers;

class CustomerSoftDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_be_soft_deleted_and_restored()
    {
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $user = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::findOrCreate('Admin');
        $permission1 = \Spatie\Permission\Models\Permission::findOrCreate('customers.view_all');
        $permission2 = \Spatie\Permission\Models\Permission::findOrCreate('customers.delete');
        $permission3 = \Spatie\Permission\Models\Permission::findOrCreate('customers.edit');
        $role->givePermissionTo([$permission1, $permission2, $permission3]);
        $user->assignRole($role);

        $customer = Customer::create([
            'name' => 'Active Customer',
            'address' => 'Test Address',
            'city' => 'Test City',
            'phone' => '12345678',
        ]);

        $this->actingAs($user);

        // Verify loaded initially
        Livewire::test(Customers::class)
            ->assertSee('Active Customer')
            ->call('Destroy', $customer->id);

        // Verify customer is soft-deleted in DB
        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
        ]);

        // Verify it is not visible in the normal list
        Livewire::test(Customers::class)
            ->assertDontSee('Active Customer');

        // Verify it is visible when showDeleted is true
        Livewire::test(Customers::class)
            ->set('showDeleted', true)
            ->assertSee('Active Customer')
            ->call('Restore', $customer->id);

        // Verify it is restored in DB
        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'deleted_at' => null,
        ]);

        // Verify it is visible in the normal list again
        Livewire::test(Customers::class)
            ->assertSee('Active Customer');
    }

    public function test_customer_with_sales_can_be_soft_deleted_and_relationship_works()
    {
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        $user = User::factory()->create();
        $role = \Spatie\Permission\Models\Role::findOrCreate('Admin');
        $permission1 = \Spatie\Permission\Models\Permission::findOrCreate('customers.view_all');
        $permission2 = \Spatie\Permission\Models\Permission::findOrCreate('customers.delete');
        $role->givePermissionTo([$permission1, $permission2]);
        $user->assignRole($role);

        $customer = Customer::create([
            'name' => 'Historic Customer',
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

        $this->actingAs($user);

        // Soft delete the customer
        Livewire::test(Customers::class)
            ->call('Destroy', $customer->id);

        $this->assertSoftDeleted('customers', [
            'id' => $customer->id,
        ]);

        // Verify that the sale relationship to customer still works using withTrashed()
        $saleRelation = Sale::find($sale->id)->customer;
        $this->assertNotNull($saleRelation);
        $this->assertEquals('Historic Customer', $saleRelation->name);
    }
}
