<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use App\Services\CustomerCreditScoringService;
use Carbon\Carbon;

class CustomerCreditScoringTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Setup configuration for sales edit window
        \App\Models\Configuration::create([
            'business_name' => 'Test Business',
            'sales_edit_timeout' => 1800,
            'global_credit_limit' => 500,
            'global_allow_credit' => true,
        ]);
        
        $user = User::firstOrCreate(
            ['id' => 1],
            ['name' => 'Admin', 'email' => 'admin@example.com', 'password' => bcrypt('password')]
        );
    }

    public function test_new_customer_has_new_status_and_zero_recommended_limit()
    {
        $customer = Customer::create([
            'name' => 'Test Customer',
            'phone' => '12345678',
            'created_at' => now(), // Registered today
        ]);

        $result = CustomerCreditScoringService::evaluate($customer);

        $this->assertEquals('new', $result['credit_status']);
        $this->assertEquals(0.00, $result['credit_limit_recommended']);
        $this->assertEquals(100, $result['credit_score']); // Default score
    }

    public function test_customer_with_history_gets_active_status_and_recommended_limit()
    {
        $customer = Customer::create([
            'name' => 'History Customer',
            'phone' => '12345678',
        ]);
        $customer->created_at = now()->subDays(40);
        $customer->saveQuietly();

        // Create 3 cash purchases
        for ($i = 0; $i < 3; $i++) {
            Sale::create([
                'reference' => 'SALE-' . $i,
                'customer_id' => $customer->id,
                'type' => 'cash',
                'status' => 'paid',
                'total_usd' => 100, // Average will be 100
                'total' => 100,
                'items' => 1,
                'user_id' => 1,
                'created_at' => now()->subDays(10),
            ]);
        }

        $result = CustomerCreditScoringService::evaluate($customer);

        $this->assertEquals('active', $result['credit_status']);
        $this->assertEquals(100, $result['credit_score']); // No credit sales yet, so perfect score
        $this->assertEquals(30.00, $result['credit_limit_recommended']); // 30% of average (100)
    }

    public function test_customer_with_late_payments_gets_lower_score_and_limit()
    {
        $customer = Customer::create([
            'name' => 'Late Customer',
            'phone' => '12345678',
        ]);
        $customer->created_at = now()->subDays(40);
        $customer->saveQuietly();

        // Bootstrapping cash purchases
        for ($i = 0; $i < 3; $i++) {
            Sale::create([
                'reference' => 'SALE-L-' . $i,
                'customer_id' => $customer->id,
                'type' => 'cash',
                'status' => 'paid',
                'total_usd' => 100,
                'total' => 100,
                'items' => 1,
                'user_id' => 1,
            ]);
        }

        // Credit purchase paid late
        $creditSale = Sale::create([
            'reference' => 'CR-1',
            'customer_id' => $customer->id,
            'type' => 'credit',
            'status' => 'paid',
            'credit_days' => 15,
            'total_usd' => 50,
            'total' => 50,
            'items' => 1,
            'user_id' => 1,
            'created_at' => now()->subDays(30),
        ]);
        
        \App\Models\Payment::create([
            'reference' => 'PAY-1',
            'sale_id' => $creditSale->id,
            'amount' => 50,
            'user_id' => 1,
            'created_at' => now()->subDays(5), // Paid after 25 days (10 days late)
        ]);

        // Credit purchase paid on time
        $creditSale2 = Sale::create([
            'reference' => 'CR-2',
            'customer_id' => $customer->id,
            'type' => 'credit',
            'status' => 'paid',
            'credit_days' => 15,
            'total_usd' => 50,
            'total' => 50,
            'items' => 1,
            'user_id' => 1,
            'created_at' => now()->subDays(10),
        ]);
        
        \App\Models\Payment::create([
            'reference' => 'PAY-2',
            'sale_id' => $creditSale2->id,
            'amount' => 50,
            'user_id' => 1,
            'created_at' => now()->subDays(9), // Paid 1 day later (on time)
        ]);

        $result = CustomerCreditScoringService::evaluate($customer);

        // 1 out of 2 is on time (50%)
        $this->assertEquals(50, $result['credit_score']);
        $this->assertEquals('defaulted', $result['credit_status']); // Score < 60
        $this->assertEquals(0.00, $result['credit_limit_recommended']); 
    }
}
