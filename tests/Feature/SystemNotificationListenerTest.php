<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Models\Payment;
use App\Models\EmailMessage;
use App\Events\PaymentReceived;
use App\Listeners\SystemNotificationListener;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SystemNotificationListenerTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(\Database\Seeders\CurrencySeeder::class);
        
        Configuration::create([
            'business_name' => 'Test Business',
            'taxpayer_id' => '12345678',
            'address' => 'Test Address 123',
            'city' => 'Caracas',
            'phone' => '0212-5555555',
            'decimals' => 2,
            'vat' => 16,
            'printer_name' => 'EPSON',
            'credit_days' => 15,
            'bcv_rate' => 587.40,
            'binance_rate' => 797.27,
            'binance_markup_points' => 13.59
        ]);

        $this->adminUser = User::factory()->create();
    }

    public function test_payment_received_notification_calculates_correct_debt_with_multiple_currencies()
    {
        $customer = Customer::create([
            'name' => 'Jhonny Pirela',
            'taxpayer_id' => 'V-12345678',
            'address' => 'Urbanizacion Prado Hermoso',
            'city' => 'Maracaibo',
            'phone' => '0414-1234567',
            'email' => 'jhonny@example.com',
            'type' => 'Consumidor Final',
            'email_notify_payments' => true
        ]);

        // Sale of $113.95
        $sale = Sale::create([
            'total' => 113.95,
            'total_usd' => 113.95,
            'items' => 1,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'invoice_number' => 'F00002132',
            'status' => 'pending',
            'type' => 'credit'
        ]);

        // First payment: $10.00 USD (exchange rate 1.00)
        $payment1 = Payment::create([
            'sale_id' => $sale->id,
            'amount' => 10.00,
            'currency' => 'USD',
            'exchange_rate' => 1.00,
            'pay_way' => 'cash',
            'status' => 'approved',
            'user_id' => $this->adminUser->id
        ]);

        // Second payment: 100.00 VED (exchange rate 400.00) -> equals $0.25 USD
        $payment2 = Payment::create([
            'sale_id' => $sale->id,
            'amount' => 100.00,
            'currency' => 'VED',
            'exchange_rate' => 400.00,
            'pay_way' => 'deposit',
            'status' => 'approved',
            'user_id' => $this->adminUser->id
        ]);

        // Dispatch PaymentReceived event for the second payment
        $event = new PaymentReceived($payment2, 100.00, $sale);
        $listener = new SystemNotificationListener();
        $listener->handle($event);

        // Retrieve the created EmailMessage
        $emailMessage = EmailMessage::where('customer_id', $customer->id)->latest('id')->first();
        
        $this->assertNotNull($emailMessage, 'Email message should be created');
        
        // Assert correct debt in notification body: $103.70
        // Expected: $113.95 (total) - $10.00 (payment 1) - $0.25 (payment 2) = $103.70
        $this->assertStringContainsString('103.70', $emailMessage->message_body);
        $this->assertStringNotContainsString('3.95', $emailMessage->message_body);
    }
}
