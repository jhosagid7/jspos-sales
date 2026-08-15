<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use Livewire\Livewire;
use App\Livewire\Customers;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CustomerSellerIdFallbackTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_creates_customer_successfully_when_seller_id_is_empty_or_invalid()
    {
        $user = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com'
        ]);

        $this->actingAs($user);

        Livewire::test(Customers::class)
            ->set('customer.name', 'Cliente Plan Básico')
            ->set('customer.taxpayer_id', 'V-12345678')
            ->set('customer.address', 'Calle Principal')
            ->set('customer.city', 'Caracas')
            ->set('customer.phone', '04141234567')
            ->set('customer.seller_id', 0)
            ->call('Store')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('customers', [
            'name' => 'Cliente Plan Básico',
            'seller_id' => $user->id
        ]);
    }
}
