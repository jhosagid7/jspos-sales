<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetail;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreditNotePdfTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_generate_credit_note_pdf_with_null_custom_name_or_product()
    {
        Configuration::create([
            'business_name' => 'Test Store',
            'taxpayer_id' => 'J-12345678-0',
            'address' => 'Main St',
            'city' => 'Caracas',
        ]);
        
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'John Doe',
            'taxpayer_id' => 'V-12345678',
            'address' => 'Calle 1',
            'city' => 'Caracas',
            'phone' => '04121234567',
            'email' => 'john@example.com',
        ]);

        $category = Category::create(['name' => 'General']);
        $supplier = Supplier::create(['name' => 'General Supplier']);

        $product = Product::create([
            'name' => 'Bolsa Marplast',
            'sku' => 'BMP-001',
            'cost' => 10,
            'price' => 50,
            'stock_qty' => 100,
            'low_stock' => 5,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
        ]);

        $sale = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total' => 100,
            'items' => 1,
            'type' => 'sale',
            'cash_register_id' => 1,
            'status' => 'PAID',
            'currency_code' => 'USD',
            'exchange_rate' => 1.0,
        ]);

        $saleDetail = SaleDetail::create([
            'sale_id' => $sale->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'sale_price' => 50,
            'regular_price' => 50,
            'discount' => 0,
            'subtotal' => 100,
        ]);

        $saleReturn = SaleReturn::create([
            'sale_id' => $sale->id,
            'user_id' => $user->id,
            'return_number' => 'NC-001',
            'refund_method' => 'debt_reduction',
            'total_returned' => 50,
            'reason' => 'Test Return Null Custom Name',
        ]);

        SaleReturnDetail::create([
            'sale_return_id' => $saleReturn->id,
            'sale_detail_id' => $saleDetail->id,
            'product_id' => null, // Deleted product / null
            'custom_name' => null, // Null custom name
            'quantity_returned' => 1,
            'unit_price' => 50,
            'subtotal' => 50,
            'subtotal_returned' => 50,
        ]);

        $response = (new \App\Livewire\Sales())->generateCreditNotePdf($saleReturn);

        $this->assertNotNull($response);
    }
}
