<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use Illuminate\Foundation\Testing\RefreshDatabase;

class OverdueNotificationHeaderTest extends TestCase
{
    public function test_header_composer_includes_custom_credit_days_overdue_sales()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $config = Configuration::first();
        if (!$config) {
            $config = Configuration::create([
                'business_name' => 'JSPOS Test',
                'credit_days' => 30,
            ]);
        }

        // Customer with 12 credit days
        $customer = Customer::create([
            'name' => 'Test Customer Jean Parra',
            'credit_days' => 12,
            'allow_credit' => true,
            'seller_id' => $user->id,
        ]);

        // Sale created 15 days ago with 12 credit days -> Overdue by 3 days!
        $sale = Sale::create([
            'customer_id' => $customer->id,
            'user_id' => $user->id,
            'type' => 'credit',
            'status' => 'pending',
            'total' => 500.00,
            'items' => 1,
            'credit_days' => 12,
            'invoice_number' => 'F00009999',
            'created_at' => now()->subDays(15),
        ]);

        $composer = new \App\View\Composers\HeaderComposer();
        $view = \Illuminate\Support\Facades\View::make('layouts.theme.header');
        $composer->compose($view);

        $notySales = $view->getData()['noty_sales'] ?? collect();
        $totalReceivables = $view->getData()['total_receivables'] ?? 0;

        $this->assertTrue($notySales->contains('id', $sale->id), 'Overdue sale with custom credit days should be included in noty_sales');
        $this->assertGreaterThan(0, $totalReceivables);

        $sale->delete();
        $customer->delete();
    }
}
