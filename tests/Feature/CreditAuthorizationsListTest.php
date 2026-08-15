<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\CreditAuthorization;
use Livewire\Livewire;
use App\Livewire\CreditAuthorizationsList;

class CreditAuthorizationsListTest extends TestCase
{
    public function test_expired_pending_pins_are_displayed_and_filtered_as_expired()
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Cliente Test ' . rand(1000, 9999),
            'taxpayer_id' => 'V-' . rand(10000000, 99999999),
            'address' => 'Caracas',
            'city' => 'Caracas',
            'seller_id' => $user->id,
        ]);

        // Expired PIN (created days ago, status still 'pending' in DB)
        $expiredAuth = CreditAuthorization::create([
            'customer_id' => $customer->id,
            'pin_code' => '999888',
            'status' => 'pending',
            'action_type' => 'credit',
            'recipient_email' => 'supervisor@test.com',
            'amount_requested' => 100,
            'expires_at' => now()->subDays(5),
            'requested_by_id' => $user->id,
        ]);

        // Active Valid PIN (expires in 10 minutes)
        $validAuth = CreditAuthorization::create([
            'customer_id' => $customer->id,
            'pin_code' => '111222',
            'status' => 'pending',
            'action_type' => 'credit',
            'recipient_email' => 'supervisor@test.com',
            'amount_requested' => 200,
            'expires_at' => now()->addMinutes(10),
            'requested_by_id' => $user->id,
        ]);

        // Test filtering by 'pending' -> should only show validAuth
        Livewire::test(CreditAuthorizationsList::class)
            ->set('status', 'pending')
            ->assertSee('111222')
            ->assertDontSee('999888');

        // Test filtering by 'expired' -> should show expiredAuth
        Livewire::test(CreditAuthorizationsList::class)
            ->set('status', 'expired')
            ->assertSee('999888')
            ->assertDontSee('111222');

        // Clean up test data
        $expiredAuth->delete();
        $validAuth->delete();
        $customer->forceDelete();
    }
}
