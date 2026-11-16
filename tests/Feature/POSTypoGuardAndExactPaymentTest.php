<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Configuration;
use App\Livewire\Sales;
use Livewire\Livewire;
use Illuminate\Foundation\Testing\RefreshDatabase;

class POSTypoGuardAndExactPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $customer;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.installed' => false,
            'tenant.modules' => ['module_credits', 'module_commissions', 'module_advanced_payments'],
        ]);

        \App\Services\ConfigurationService::clearCache();

        Configuration::create([
            'business_name' => 'Typo Guard Test Business',
            'decimals' => 4,
            'vat' => 0,
            'bcv_rate' => 900.00,
            'binance_rate' => 920.00,
            'binance_markup_points' => 0.00,
        ]);

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        \Illuminate\Support\Facades\DB::table('currencies')
            ->whereIn('code', ['VES', 'VED'])
            ->update([
                'exchange_rate' => 920.00,
                'updated_at' => now()
            ]);

        $this->user = User::factory()->create();
        
        \App\Models\CashRegister::create([
            'user_id' => $this->user->id,
            'status' => 'open',
            'opening_date' => now(),
            'total_opening_amount' => 1000.00,
        ]);

        $this->customer = Customer::create([
            'name' => 'General Customer',
            'type' => 'Consumidor Final',
            'allow_credit' => false,
            'taxpayer_id' => 'V12345678',
            'address' => 'Caracas',
            'city' => 'Caracas',
            'phone' => '0412-0000000',
            'seller_id' => $this->user->id,
        ]);

        \App\Models\CustomerConfig::create([
            'customer_id' => $this->customer->id,
            'commission_percent' => 0.00,
            'freight_percent' => 0.00,
            'exchange_diff_percent' => 0.00,
        ]);

        $category = \App\Models\Category::create(['name' => 'Stationery']);
        $warehouse = \App\Models\Warehouse::create(['name' => 'Tienda']);
        $supplier = \App\Models\Supplier::create([
            'name' => 'Main Supplier',
            'taxpayer_id' => 'J12345678',
            'address' => 'Address',
            'phone' => '0212-0000000',
        ]);

        $this->product = Product::create([
            'name' => 'Impresión Color',
            'sku' => 'IMP-COL-001',
            'barcode' => 'IMP-COL-001',
            'cost' => 0.10,
            'price' => 2.16,
            'price1' => 2.16,
            'price_usd' => 2.16,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
            'manage_stock' => 0,
            'stock_qty' => 100,
            'low_stock' => 5,
        ]);
    }

    protected function getCartItem($product, $qty = 1, $price = 2.16): array
    {
        return [
            'id' => $product->id,
            'pid' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
            'qty' => $qty,
            'price' => $price,
            'base_price' => $price,
            'sale_price' => $price,
            'tax' => 0.00,
            'total' => $price * $qty,
            'pricelist' => [],
        ];
    }

    public function test_anti_typo_guard_blocks_missing_decimal_point_in_usd(): void
    {
        $item = $this->getCartItem($this->product, 1, 2.16);

        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->set('cart', collect([$item]))
            ->set('totalCart', 2.16)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'USD')
            ->set('paymentAmount', '21568') // Typo: omitted dot for 2.16 or 2.1568
            ->call('addPayment')
            ->assertDispatched('noty')
            ->assertCount('payments', 0); // Must be rejected
    }

    public function test_anti_typo_guard_blocks_three_digit_missing_decimal_in_usd(): void
    {
        $item = $this->getCartItem($this->product, 1, 3.54);

        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->set('cart', collect([$item]))
            ->set('totalCart', 3.54)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'USD')
            ->set('paymentAmount', '354') // Typo: 354 instead of 3.54
            ->call('addPayment')
            ->assertDispatched('noty')
            ->assertCount('payments', 0); // Must be rejected
    }

    public function test_anti_typo_guard_allows_legitimate_cash_bills_like_10_and_100_usd(): void
    {
        $item = $this->getCartItem($this->product, 1, 2.16);

        // Paying $2.16 with a $10 bill
        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->set('cart', collect([$item]))
            ->set('totalCart', 2.16)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'USD')
            ->set('paymentAmount', '10.00')
            ->call('addPayment')
            ->assertCount('payments', 1);

        session()->forget(['payments', 'remainingAmount', 'change']);

        // Paying $2.16 with a $100 bill
        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->set('cart', collect([$item]))
            ->set('totalCart', 2.16)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'USD')
            ->set('paymentAmount', '100.00')
            ->call('addPayment')
            ->assertCount('payments', 1);
    }

    public function test_anti_typo_guard_blocks_extra_zeros_in_ved(): void
    {
        // Product of 4,140 Bs ($4.50 USD)
        $vedProduct = Product::create([
            'name' => 'Resma Papel',
            'sku' => 'PAP-001',
            'barcode' => 'PAP-001',
            'cost' => 3.00,
            'price' => 4.50, // at 920 rate = 4,140 Bs
            'price1' => 4.50,
            'price_usd' => 4.50,
            'category_id' => $this->product->category_id,
            'supplier_id' => $this->product->supplier_id,
            'manage_stock' => 0,
            'stock_qty' => 100,
            'low_stock' => 5,
        ]);

        $item = $this->getCartItem($vedProduct, 1, 4.50);

        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->set('cart', collect([$item]))
            ->set('totalCart', 4.50)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'VED')
            ->set('paymentAmount', '4140000') // Typo: 4.14M instead of 4,140
            ->call('addPayment')
            ->assertDispatched('noty')
            ->assertCount('payments', 0); // Must be rejected
    }

    public function test_set_exact_payment_autofills_exact_remaining_amount(): void
    {
        $item = $this->getCartItem($this->product, 1, 2.16);

        Livewire::actingAs($this->user)
            ->test(Sales::class)
            ->set('cart', collect([$item]))
            ->set('totalCart', 2.16)
            ->set('itemsCart', 1)
            ->set('selectedPaymentMethod', 'cash')
            ->set('paymentCurrency', 'USD')
            ->call('setExactPayment')
            ->assertSet('paymentAmount', '2.1600')
            ->call('addPayment')
            ->assertCount('payments', 1)
            ->assertSet('remainingAmount', 0.0);
    }
}
