<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\CustomerConfig;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderPdfFooterCodeTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_pdf_footer_code_contains_customer_freight_and_surcharge()
    {
        Configuration::create([
            'business_name' => 'Test Store',
            'taxpayer_id' => 'J-12345678-0',
            'address' => 'Main St',
            'city' => 'Caracas',
        ]);

        $user = User::factory()->create(['name' => 'Javier Ramirez']);
        $customer = Customer::create([
            'name' => 'Pedro Luis Olivar Angarita',
            'taxpayer_id' => 'V-12345678',
            'address' => 'Calle 1',
            'city' => 'Caracas',
            'phone' => '04121234567',
            'email' => 'pedro@example.com',
            'seller_id' => $user->id,
        ]);

        CustomerConfig::create([
            'customer_id' => $customer->id,
            'seller_id' => $user->id,
            'commission_percent' => 0,
            'freight_percent' => 6,
            'exchange_diff_percent' => 0,
            'base_markup_percent' => 4,
            'credit_days' => 8,
            'credit_limit' => 0,
            'created_by' => $user->id,
        ]);

        $category = Category::create(['name' => 'General']);
        $supplier = Supplier::create(['name' => 'General Supplier']);

        $product = Product::create([
            'name' => 'Test Item',
            'sku' => 'TI-001',
            'cost' => 10,
            'price' => 50,
            'stock_qty' => 100,
            'low_stock' => 5,
            'category_id' => $category->id,
            'supplier_id' => $supplier->id,
        ]);

        $order = Order::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'total' => 100,
            'items' => 1,
            'status' => 'PENDING',
        ]);

        OrderDetail::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'sale_price' => 50,
            'regular_price' => 50,
            'discount' => 0,
            'subtotal' => 100,
        ]);

        $traitClass = new class {
            use \App\Traits\PdfOrderInvoiceTrait;
        };

        $footerData = $traitClass->getOrderInvoiceFooterData($order);
        $code = $footerData['footer_code'];

        // Code should contain F6 (Freight 6%) and RC4 (Recargo 4%)
        $this->assertStringContainsString('F6', $code);
        $this->assertStringContainsString('RC4', $code);
    }
}
