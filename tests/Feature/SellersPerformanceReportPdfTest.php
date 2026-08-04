<?php

namespace Tests\Feature;

use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class SellersPerformanceReportPdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_sellers_performance_pdf_generates_successfully()
    {
        config(['tenant.modules' => ['module_seller_performance']]);

        Configuration::create([
            'business_name' => 'Test Store',
            'taxpayer_id' => 'J-12345678-0',
            'address' => 'Main St',
            'city' => 'Caracas',
        ]);

        $permission = Permission::firstOrCreate(['name' => 'reports.sales']);
        $role = Role::firstOrCreate(['name' => 'Super Admin']);
        $role->givePermissionTo($permission);

        $seller = User::factory()->create(['name' => 'Seller One']);
        $user = User::factory()->create(['name' => 'Admin User']);
        $user->assignRole($role);

        $customer = Customer::create([
            'name' => 'Test Customer',
            'taxpayer_id' => 'V-87654321',
            'seller_id' => $seller->id,
        ]);

        Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total' => 150.00,
            'total_usd' => 150.00,
            'items' => 1,
            'cashier_id' => $user->id,
            'type' => 'cash',
            'status' => 'paid',
        ]);

        $response = $this->actingAs($user)->get(route('reports.sellers.performance.pdf', [
            'periodType' => 'monthly',
            'metric' => 'amount',
            'invoiceStatus' => 'all',
            'invoiceLimit' => '100',
        ]));

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/pdf');
    }
}
