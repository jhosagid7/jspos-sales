<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class SellerPortfolioSharingTest extends TestCase
{
    use RefreshDatabase;

    protected $sellerA;
    protected $sellerB;
    protected $customerA;
    protected $customerB;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.installed' => true]);

        Configuration::create([
            'business_name' => 'Test Business',
            'device_access_mode' => 'open',
        ]);

        // Mock LicenseService
        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('checkLicense')->andReturn([
                'status' => 'active',
                'days_remaining' => 30,
                'modules' => [],
                'max_devices' => 10,
            ]);
            $mock->shouldReceive('getClientId')->andReturn('test-client-id');
        });

        // Set up permissions
        Permission::firstOrCreate(['name' => 'customers.view_own']);
        Permission::firstOrCreate(['name' => 'customers.view_all']);
        Permission::firstOrCreate(['name' => 'customer_statement.view_own']);
        Permission::firstOrCreate(['name' => 'customer_statement.index']);
        Permission::firstOrCreate(['name' => 'payments.upload']);

        $roleSeller = Role::firstOrCreate(['name' => 'Seller']);
        $roleSeller->givePermissionTo([
            'customers.view_own',
            'customer_statement.view_own',
            'customer_statement.index',
            'payments.upload'
        ]);

        // Create Sellers
        $this->sellerA = User::factory()->create([
            'profile' => 'Seller'
        ]);
        $this->sellerA->assignRole('Seller');

        $this->sellerB = User::factory()->create([
            'profile' => 'Seller'
        ]);
        $this->sellerB->assignRole('Seller');

        // Create Customers
        $this->customerA = Customer::create([
            'name' => 'Customer Seller A',
            'address' => '123 Street A',
            'city' => 'City A',
            'seller_id' => $this->sellerA->id,
        ]);

        $this->customerB = Customer::create([
            'name' => 'Customer Seller B',
            'address' => '456 Street B',
            'city' => 'City B',
            'seller_id' => $this->sellerB->id,
        ]);
    }

    public function test_seller_cannot_see_other_seller_customers_by_default()
    {
        // Act as Seller A
        $this->actingAs($this->sellerA);

        // Check customer list API
        $response = $this->getJson('/api/customers');
        $response->assertStatus(200);

        // Seller A should see Customer A but not Customer B
        $customerIds = collect($response->json())->pluck('id')->toArray();
        $this->assertContains($this->customerA->id, $customerIds);
        $this->assertNotContains($this->customerB->id, $customerIds);

        // Check customer autocomplete
        $responseAutocomplete = $this->getJson('/data/customers?q=Customer');
        $responseAutocomplete->assertStatus(200);
        $autocompleteIds = collect($responseAutocomplete->json())->pluck('id')->toArray();
        $this->assertContains($this->customerA->id, $autocompleteIds);
        $this->assertNotContains($this->customerB->id, $autocompleteIds);

        // Check payment pending sales visibility
        $responsePending = $this->getJson("/api/sales/pending?customer_id={$this->customerB->id}");
        $responsePending->assertStatus(403);
    }

    public function test_seller_can_see_other_seller_customers_when_portfolio_sharing_is_active()
    {
        // Share Seller B's portfolio with Seller A
        $this->sellerA->sharedSellers()->attach($this->sellerB->id);

        // Act as Seller A
        $this->actingAs($this->sellerA);

        // Check customer list API
        $response = $this->getJson('/api/customers');
        $response->assertStatus(200);

        // Seller A should see both Customer A and Customer B
        $customerIds = collect($response->json())->pluck('id')->toArray();
        $this->assertContains($this->customerA->id, $customerIds);
        $this->assertContains($this->customerB->id, $customerIds);

        // Check customer autocomplete
        $responseAutocomplete = $this->getJson('/data/customers?q=Customer');
        $responseAutocomplete->assertStatus(200);
        $autocompleteIds = collect($responseAutocomplete->json())->pluck('id')->toArray();
        $this->assertContains($this->customerA->id, $autocompleteIds);
        $this->assertContains($this->customerB->id, $autocompleteIds);

        // Check payment pending sales visibility
        $responsePending = $this->getJson("/api/sales/pending?customer_id={$this->customerB->id}");
        $responsePending->assertStatus(200);
    }
}
