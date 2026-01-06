<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\Customer;
use App\Models\Product;
use Livewire\Livewire;
use App\Livewire\PartialPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class PaymentDateTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_date_is_saved_correctly_in_partial_payment()
    {
        $user = User::factory()->create();
        $customer = Customer::factory()->create();
        $product = Product::factory()->create(['price' => 100]);
        
        $sale = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total' => 100,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit'
        ]);

        $pastDate = Carbon::now()->subDays(5)->format('Y-m-d');

        Livewire::actingAs($user)
            ->test(PartialPayment::class)
            ->call('initPay', $sale->id, $customer->name, 100)
            ->set('amount', 50)
            ->set('paymentDate', $pastDate)
            ->call('doPayment');

        $this->assertDatabaseHas('payments', [
            'sale_id' => $sale->id,
            'amount' => 50,
            // 'payment_date' => $pastDate . ' 00:00:00' // Flexible check
        ]);
        
        $payment = Payment::where('sale_id', $sale->id)->first();
        $this->assertStringStartsWith($pastDate, $payment->payment_date);
    }
}
