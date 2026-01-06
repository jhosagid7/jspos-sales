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
    // use RefreshDatabase; // Commented out to avoid wiping local db, will use manual cleanup or transaction if needed, but for now just creating temp data

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
        $sale = new Sale();
        $sale->created_at = Carbon::now()->subDays(10); // 10 days elapsed
        $sale->customer = new Customer(); // No config
        $sale->user = new User(); // No config
        
        // Test Tier 1
        $commission = $service->calculateCommission($sale);
        $this->assertEquals(8, $commission, "Global Tier 1 failed");

        // Test Tier 2
        $sale->created_at = Carbon::now()->subDays(20); // 20 days elapsed
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
        $seller = new User();
        $seller->seller_commission_1_threshold = 10;
        $seller->seller_commission_1_percentage = 10;
        $seller->seller_commission_2_threshold = 20;
        $seller->seller_commission_2_percentage = 5;

        $service = new CommissionService();

        // Create Sale
        $sale = new Sale();
        $sale->created_at = Carbon::now()->subDays(5); // 5 days elapsed
        $sale->customer = new Customer(); // No config
        $sale->user = $seller;

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
        $seller = new User();
        $seller->seller_commission_1_percentage = 10;

        // Setup Customer
        $customer = new Customer();
        $customer->customer_commission_1_threshold = 5;
        $customer->customer_commission_1_percentage = 12;
        $customer->customer_commission_2_threshold = 10;
        $customer->customer_commission_2_percentage = 6;

        $service = new CommissionService();

        // Create Sale
        $sale = new Sale();
        $sale->created_at = Carbon::now()->subDays(2); // 2 days elapsed
        $sale->customer = $customer;
        $sale->user = $seller;

        // Test Customer Tier 1 (Should be 12%, overriding Seller 10% and Global 8%)
        $commission = $service->calculateCommission($sale);
        $this->assertEquals(12, $commission, "Customer Override Tier 1 failed");
    }
}
