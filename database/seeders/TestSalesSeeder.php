<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Department;
use App\Models\Category;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SalePaymentDetail;
use App\Models\User;
use Carbon\Carbon;

class TestSalesSeeder extends Seeder
{
    public function run()
    {
        // 1. Create or get Departments (local & gravado)
        $deptLocal = Department::firstOrCreate(['name' => 'DEPARTAMENTO LOCAL TEST'], ['report_type' => 'local']);
        $deptGravado = Department::firstOrCreate(['name' => 'DEPARTAMENTO GRAVADO TEST'], ['report_type' => 'gravado']);

        // 2. Create or get Categories
        $catLocal = Category::firstOrCreate(['name' => 'CATEGORIA LOCAL TEST'], ['department_id' => $deptLocal->id]);
        $catGravado = Category::firstOrCreate(['name' => 'CATEGORIA GRAVADA TEST'], ['department_id' => $deptGravado->id]);

        // 3. Create or get Products
        $supplier = \App\Models\Supplier::first();
        $supplierId = $supplier ? $supplier->id : 1;
        
        $prodLocal = Product::firstOrCreate(
            ['sku' => 'PROD-LOC-01'],
            ['name' => 'Producto Prueba LOCAL', 'category_id' => $catLocal->id, 'price' => 10, 'stock_qty' => 100, 'type' => 'physical', 'status' => 'available', 'cost' => 5, 'supplier_id' => $supplierId, 'low_stock' => 10]
        );
        $prodGravado = Product::firstOrCreate(
            ['sku' => 'SERV-GRAV-01'],
            ['name' => 'Servicio Prueba GRAVADO', 'category_id' => $catGravado->id, 'price' => 20, 'stock_qty' => 100, 'type' => 'service', 'status' => 'available', 'cost' => 10, 'supplier_id' => $supplierId, 'low_stock' => 10]
        );

        // 4. Get a user
        $user = User::first();
        if (!$user) {
            $user = User::factory()->create(['name' => 'Vendedor Prueba']);
        }

        // Customer
        $customer = \App\Models\Customer::first();
        if (!$customer) {
            $this->command->error('No hay clientes en la base de datos. Por favor crea uno primero.');
            return;
        }

        $today = Carbon::now();

        // 5. Create Sale 1: Mixed Products (1 Local, 1 Gravado = $30 total)
        // Payments: $15 Efectivo USD, $15 Zelle USD
        $sale1 = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'type' => 'cash',
            'items' => 2,
            'total' => 30,
            'total_usd' => 30,
            'invoice_number' => 'TEST-0001',
            'created_at' => $today,
            'updated_at' => $today,
        ]);
        
        SaleDetail::create(['sale_id' => $sale1->id, 'product_id' => $prodLocal->id, 'quantity' => 1, 'sale_price' => 10, 'regular_price' => 10, 'discount' => 0, 'warehouse_id' => 1]);
        SaleDetail::create(['sale_id' => $sale1->id, 'product_id' => $prodGravado->id, 'quantity' => 1, 'sale_price' => 20, 'regular_price' => 20, 'discount' => 0, 'warehouse_id' => 1]);
        
        SalePaymentDetail::create([
            'sale_id' => $sale1->id, 'payment_method' => 'efectivo', 'currency_code' => 'usd',
            'amount' => 15, 'exchange_rate' => 1, 'amount_in_primary_currency' => 15, 'created_at' => $today
        ]);
        SalePaymentDetail::create([
            'sale_id' => $sale1->id, 'payment_method' => 'zelle', 'currency_code' => 'usd',
            'amount' => 15, 'exchange_rate' => 1, 'amount_in_primary_currency' => 15, 'created_at' => $today
        ]);

        // 6. Create Sale 2: Only Local ($10)
        // Payments: $10 Pagomovil VES (Exchange rate 40 -> 400 VES)
        $sale2 = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'type' => 'cash',
            'items' => 1,
            'total' => 10,
            'total_usd' => 10,
            'invoice_number' => 'TEST-0002',
            'created_at' => $today,
            'updated_at' => $today,
        ]);
        
        SaleDetail::create(['sale_id' => $sale2->id, 'product_id' => $prodLocal->id, 'quantity' => 1, 'sale_price' => 10, 'regular_price' => 10, 'discount' => 0, 'warehouse_id' => 1]);
        
        SalePaymentDetail::create([
            'sale_id' => $sale2->id, 'payment_method' => 'pagomovil', 'currency_code' => 'ves',
            'amount' => 400, 'exchange_rate' => 40, 'amount_in_primary_currency' => 10, 'created_at' => $today
        ]);

        // 7. Create Sale 3: Only Gravado ($40)
        // Payments: $40 Transferencia USD
        $sale3 = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'status' => 'paid',
            'type' => 'cash',
            'items' => 2,
            'total' => 40,
            'total_usd' => 40,
            'invoice_number' => 'TEST-0003',
            'created_at' => $today,
            'updated_at' => $today,
        ]);
        
        SaleDetail::create(['sale_id' => $sale3->id, 'product_id' => $prodGravado->id, 'quantity' => 2, 'sale_price' => 20, 'regular_price' => 20, 'discount' => 0, 'warehouse_id' => 1]);
        
        SalePaymentDetail::create([
            'sale_id' => $sale3->id, 'payment_method' => 'transferencia', 'currency_code' => 'usd',
            'amount' => 40, 'exchange_rate' => 1, 'amount_in_primary_currency' => 40, 'created_at' => $today
        ]);
        
        $this->command->info('Test sales created successfully!');
    }
}

