<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasIndex('sales', 'sales_status_index')) {
                $table->index('status', 'sales_status_index');
            }
            if (!Schema::hasIndex('sales', 'sales_created_at_index')) {
                $table->index('created_at', 'sales_created_at_index');
            }
            if (!Schema::hasIndex('sales', 'sales_user_id_index')) {
                $table->index('user_id', 'sales_user_id_index');
            }
            if (!Schema::hasIndex('sales', 'sales_customer_id_index')) {
                $table->index('customer_id', 'sales_customer_id_index');
            }
        });

        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasIndex('products', 'products_name_index')) {
                $table->index('name', 'products_name_index');
            }
            if (!Schema::hasIndex('products', 'products_category_id_index')) {
                $table->index('category_id', 'products_category_id_index');
            }
            if (!Schema::hasIndex('products', 'products_supplier_id_index')) {
                $table->index('supplier_id', 'products_supplier_id_index');
            }
            
            if (Schema::hasColumn('products', 'barcode') && !Schema::hasIndex('products', 'products_barcode_index')) {
                $table->index('barcode', 'products_barcode_index');
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            if (!Schema::hasIndex('customers', 'customers_name_index')) {
                $table->index('name', 'customers_name_index');
            }
            if (!Schema::hasIndex('customers', 'customers_taxpayer_id_index')) {
                $table->index('taxpayer_id', 'customers_taxpayer_id_index');
            }
        });
        
        Schema::table('sale_details', function (Blueprint $table) {
             if (!Schema::hasIndex('sale_details', 'sale_details_sale_id_product_id_index')) {
                 $table->index(['sale_id', 'product_id'], 'sale_details_sale_id_product_id_index');
             }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['status']);
            $table->dropIndex(['created_at']);
            $table->dropIndex(['user_id']);
            $table->dropIndex(['customer_id']);
        });

        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['supplier_id']);
            
            if (Schema::hasColumn('products', 'barcode')) {
                $table->dropIndex(['barcode']);
            }
        });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['name']);
            $table->dropIndex(['taxpayer_id']);
        });
        
        Schema::table('sale_details', function (Blueprint $table) {
             $table->dropIndex(['sale_id', 'product_id']);
        });
    }
};
