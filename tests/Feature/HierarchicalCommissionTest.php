<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Carbon\Carbon;

class HierarchicalCommissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Configuration::create([
            'business_name' => 'Test Business',
            'bcv_rate' => 54.50,
            'binance_rate' => 70.00,
            'binance_markup_points' => 5.00,
        ]);
    }

    public function test_global_commission_logic()
    {
        // Setup Global Config
        $config = Configuration::first();
        $config->update([
            'global_commission_1_threshold' => 15,
            'global_commission_1_percentage' => 8,
            'global_commission_2_threshold' => 30,
            'global_commission_2_percentage' => 4,
        ]);

        $service = new CommissionService();

        // Create Sale
        $user = User::factory()->create();
        $customer = Customer::create(['name' => 'Test Customer']);
        $sale = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'created_at' => Carbon::now()->subDays(10),
            'applied_commission_percent' => 8,
            'seller_tier_1_days' => 15,
            'seller_tier_1_percent' => 8,
            'seller_tier_2_days' => 30,
            'seller_tier_2_percent' => 4,
        ]);
        $sale->setRelation('customer', $customer);
        $sale->setRelation('user', $user);
        
        // Test Tier 1
        $commission = $service->calculateCommission($sale);
        $this->assertEquals(8, $commission, "Global Tier 1 failed");

        // Test Tier 2
        $sale->created_at = Carbon::now()->subDays(20); // 20 days elapsed
        $sale->save();
        $commission = $service->calculateCommission($sale);
        $this->assertEquals(4, $commission, "Global Tier 2 failed");
    }

    public function test_seller_override_logic()
    {
        // Setup Global Config
        $config = Configuration::first();
        $config->update([
            'global_commission_1_threshold' => 15,
            'global_commission_1_percentage' => 8,
        ]);

        // Setup Seller
        $seller = User::factory()->create([
            'seller_commission_1_threshold' => 10,
            'seller_commission_1_percentage' => 10,
            'seller_commission_2_threshold' => 20,
            'seller_commission_2_percentage' => 5,
        ]);

        $customer = Customer::create(['name' => 'Test Customer']);

        $service = new CommissionService();

        // Create Sale
        $sale = Sale::create([
            'user_id' => $seller->id,
            'customer_id' => $customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'created_at' => Carbon::now()->subDays(5),
            'applied_commission_percent' => 10,
            'seller_tier_1_days' => 10,
            'seller_tier_1_percent' => 10,
            'seller_tier_2_days' => 20,
            'seller_tier_2_percent' => 5,
        ]);
        $sale->setRelation('customer', $customer);
        $sale->setRelation('user', $seller);

        // Test Seller Tier 1 (Should be 10%, overriding Global 8%)
        $commission = $service->calculateCommission($sale);
        $this->assertEquals(10, $commission, "Seller Override Tier 1 failed");
    }

    public function test_customer_override_logic()
    {
        // Setup Global
        $config = Configuration::first();
        $config->update(['global_commission_1_percentage' => 8]);

        // Setup Seller
        $seller = User::factory()->create([
            'seller_commission_1_percentage' => 10,
        ]);

        // Setup Customer
        $customer = Customer::create([
            'name' => 'Test Customer',
            'customer_commission_1_threshold' => 5,
            'customer_commission_1_percentage' => 12,
            'customer_commission_2_threshold' => 10,
            'customer_commission_2_percentage' => 6,
        ]);

        $service = new CommissionService();

        // Create Sale
        $sale = Sale::create([
            'user_id' => $seller->id,
            'customer_id' => $customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'created_at' => Carbon::now()->subDays(2),
            'applied_commission_percent' => 12,
            'seller_tier_1_days' => 5,
            'seller_tier_1_percent' => 12,
            'seller_tier_2_days' => 10,
            'seller_tier_2_percent' => 6,
        ]);
        $sale->setRelation('customer', $customer);
        $sale->setRelation('user', $seller);

        // Test Customer Tier 1 (Should be 12%, overriding Seller 10% and Global 8%)
        $commission = $service->calculateCommission($sale);
        $this->assertEquals(12, $commission, "Customer Override Tier 1 failed");
    }

    public function test_individual_percentage_fallback()
    {
        // Setup Seller Config
        $seller = User::factory()->create();
        $sellerConfig = \App\Models\SellerConfig::create([
            'user_id' => $seller->id,
            'commission_percent' => 10.00,
            'freight_percent' => 6.00,
            'exchange_diff_percent' => 60.00,
        ]);

        // Setup Customer
        $customer = Customer::create([
            'name' => 'Test Customer',
            'seller_id' => $seller->id,
        ]);

        // Setup Customer Config: overrides commission only, leaves freight and diff as 0.00
        $customerConfig = \App\Models\CustomerConfig::create([
            'customer_id' => $customer->id,
            'commission_percent' => 8.00,
            'freight_percent' => 0.00,
            'exchange_diff_percent' => 0.00,
        ]);

        // Create Sale (without applied percents so it resolves dynamically)
        $sale = Sale::create([
            'user_id' => $seller->id,
            'customer_id' => $customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
        ]);
        $sale->setRelation('customer', $customer);
        $sale->setRelation('user', $seller);

        // Verify resolved percentages on Sale
        // Commission should be customer's 8.00%
        $this->assertEquals(8.00, $sale->resolved_commission_percent);
        // Freight should fall back to seller's 6.00%
        $this->assertEquals(6.00, $sale->resolved_freight_percent);
        // Exchange diff should fall back to seller's 60.00%
        $this->assertEquals(60.00, $sale->resolved_exchange_diff_percent);

        // Create Order
        $order = \App\Models\Order::create([
            'user_id' => $seller->id,
            'customer_id' => $customer->id,
            'total' => 100.00,
            'items' => 1,
            'status' => 'pending',
            'type' => 'credit',
            'apply_commissions' => true,
            'apply_freight' => true,
        ]);
        $order->setRelation('customer', $customer);
        $order->setRelation('user', $seller);

        // Verify resolved percentages on Order
        $this->assertEquals(8.00, $order->resolved_commission_percent);
        $this->assertEquals(6.00, $order->resolved_freight_percent);
        $this->assertEquals(60.00, $order->resolved_exchange_diff_percent);
    }
}

