<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\Sale;
use App\Models\User;
use App\Models\Customer;
use App\Models\Warehouse;
use App\Models\Configuration;
use App\Livewire\PartialPayment;
use App\Livewire\AccountsReceivableReport;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class InvoiceSearchAndCorrelativeSyncTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->warehouse = Warehouse::create([
            'name' => 'TIENDA PRINCIPAL',
            'is_active' => 1,
        ]);

        \App\Models\Currency::create([
            'name' => 'Dólar',
            'code' => 'USD',
            'symbol' => '$',
            'label' => 'USD',
            'exchange_rate' => 1.0,
            'is_primary' => true,
        ]);

        Configuration::create([
            'business_name' => 'JSPOS Test',
            'default_warehouse_id' => $this->warehouse->id,
            'invoice_sequence' => 4827,
        ]);

        $this->user = User::factory()->create([
            'name' => 'Vendedor Test'
        ]);

        $this->customer = Customer::create([
            'name' => 'CLIENTE PRUEBA',
            'taxpayer_id' => 'V12345678',
            'phone' => '04141234567',
            'seller_id' => $this->user->id
        ]);
    }

    /** @test */
    public function partial_payment_finds_invoice_by_correlative_when_id_and_invoice_number_diverge()
    {
        $this->actingAs($this->user);

        // Simulate real scenario:
        // Sale 1: ID 4617, invoice F00004614, paid
        $sale1 = Sale::create([
            'invoice_number' => 'F00004614',
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'items' => 1,
            'total' => 42.57,
            'status' => 'paid',
            'type' => 'cash',
            'created_at' => now(),
        ]);
        \DB::table('sales')->where('id', $sale1->id)->update(['id' => 4617]);

        // Sale 2: ID 4620, invoice F00004617, pending credit debt
        $sale2 = Sale::create([
            'invoice_number' => 'F00004617',
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'items' => 1,
            'total' => 180.06,
            'status' => 'pending',
            'type' => 'credit',
            'created_at' => now(),
        ]);
        \DB::table('sales')->where('id', $sale2->id)->update(['id' => 4620]);

        // 1. Search by full invoice number: "f00004617"
        Livewire::test(PartialPayment::class)
            ->set('search', 'f00004617')
            ->assertSee('F00004617')
            ->assertSee('180.06')
            ->assertDontSee('F00004614');

        // 2. Search by numeric invoice correlative: "4617"
        Livewire::test(PartialPayment::class)
            ->set('search', '4617')
            ->assertSee('F00004617')
            ->assertSee('180.06');

        // 3. Search by upper invoice number: "F00004617"
        Livewire::test(PartialPayment::class)
            ->set('search', 'F00004617')
            ->assertSee('F00004617')
            ->assertSee('180.06');

        // 4. Search by internal ID "4620" also finds and shows F00004617
        Livewire::test(PartialPayment::class)
            ->set('search', '4620')
            ->assertSee('F00004617')
            ->assertSee('180.06');
    }

    /** @test */
    public function accounts_receivable_report_finds_invoice_by_correlative()
    {
        $this->actingAs($this->user);

        Sale::create([
            'id' => 4620,
            'invoice_number' => 'F00004617',
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'items' => 1,
            'total' => 180.06,
            'status' => 'pending',
            'type' => 'credit',
            'created_at' => now(),
        ]);

        Livewire::test(AccountsReceivableReport::class)
            ->set('searchFactura', 'f00004617')
            ->set('dateFrom', now()->subDay()->format('Y-m-d'))
            ->set('dateTo', now()->addDay()->format('Y-m-d'))
            ->call('searchData')
            ->assertSee('F00004617');

        Livewire::test(AccountsReceivableReport::class)
            ->set('searchFactura', '4617')
            ->set('dateFrom', now()->subDay()->format('Y-m-d'))
            ->set('dateTo', now()->addDay()->format('Y-m-d'))
            ->call('searchData')
            ->assertSee('F00004617');
    }

    /** @test */
    public function sales_report_finds_invoice_by_correlative()
    {
        $this->actingAs($this->user);

        Sale::create([
            'id' => 4620,
            'invoice_number' => 'F00004617',
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'items' => 1,
            'total' => 180.06,
            'status' => 'pending',
            'type' => 'credit',
            'created_at' => now(),
        ]);

        Livewire::test(\App\Livewire\SalesReport::class)
            ->set('searchFactura', 'f00004617')
            ->set('dateFrom', now()->subDay()->format('Y-m-d'))
            ->set('dateTo', now()->addDay()->format('Y-m-d'))
            ->call('searchData')
            ->assertSee('F00004617');

        Livewire::test(\App\Livewire\SalesReport::class)
            ->set('searchFactura', '4617')
            ->set('dateFrom', now()->subDay()->format('Y-m-d'))
            ->set('dateTo', now()->addDay()->format('Y-m-d'))
            ->call('searchData')
            ->assertSee('F00004617');
    }

    /** @test */
    public function sales_component_load_from_document_finds_by_invoice_number()
    {
        $this->actingAs($this->user);

        $sale = Sale::create([
            'id' => 4620,
            'invoice_number' => 'F00004617',
            'customer_id' => $this->customer->id,
            'user_id' => $this->user->id,
            'items' => 1,
            'total' => 180.06,
            'status' => 'pending',
            'type' => 'credit',
            'created_at' => now(),
        ]);

        $salesComponent = new \App\Livewire\Sales();
        $salesComponent->loadFromDocument('SALE:F00004617');
        $this->assertNotEmpty($salesComponent->customer, 'Customer should be loaded when document is found by invoice_number');
        $this->assertEquals($this->customer->id, $salesComponent->customer['id']);
    }
}
