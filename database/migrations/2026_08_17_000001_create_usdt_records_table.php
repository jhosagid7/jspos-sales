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
        if (!Schema::hasTable('usdt_records')) {
            Schema::create('usdt_records', function (Blueprint $table) {
                $table->id();
                $table->string('sender_name');
                $table->string('reference');
                $table->date('usdt_date')->nullable();
                $table->decimal('amount', 15, 2);
                $table->decimal('remaining_balance', 15, 2)->default(0);
                $table->string('image_path')->nullable();
                $table->enum('status', ['unused', 'partial', 'used'])->default('unused');
                $table->unsignedBigInteger('customer_id')->nullable();
                $table->unsignedBigInteger('sale_id')->nullable();
                $table->unsignedBigInteger('debit_note_id')->nullable();
                $table->decimal('invoice_total', 15, 2)->nullable();
                $table->timestamps();
            });
        }

        // Add usdt_record_id to payments table if not exists and ensure pay_way column accepts 'usdt'
        if (Schema::hasTable('payments')) {
            if (!Schema::hasColumn('payments', 'usdt_record_id')) {
                Schema::table('payments', function (Blueprint $table) {
                    $table->unsignedBigInteger('usdt_record_id')->nullable()->after('zelle_record_id');
                });
            }
            try {
                \Illuminate\Support\Facades\DB::statement("ALTER TABLE payments MODIFY COLUMN pay_way VARCHAR(50) DEFAULT 'cash'");
            } catch (\Throwable $e) {}
        }

        // Add usdt_record_id to sale_payment_details table if not exists
        if (Schema::hasTable('sale_payment_details') && !Schema::hasColumn('sale_payment_details', 'usdt_record_id')) {
            Schema::table('sale_payment_details', function (Blueprint $table) {
                $table->unsignedBigInteger('usdt_record_id')->nullable()->after('zelle_record_id');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'usdt_record_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropColumn('usdt_record_id');
            });
        }

        if (Schema::hasTable('sale_payment_details') && Schema::hasColumn('sale_payment_details', 'usdt_record_id')) {
            Schema::table('sale_payment_details', function (Blueprint $table) {
                $table->dropColumn('usdt_record_id');
            });
        }

        Schema::dropIfExists('usdt_records');
    }
};
