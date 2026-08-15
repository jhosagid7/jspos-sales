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
        // 1. Create commission_goals table
        if (!Schema::hasTable('commission_goals')) {
            Schema::create('commission_goals', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->decimal('target_amount', 12, 2);
                $table->decimal('reward_amount', 12, 2);
                $table->string('periodicity', 20)->default('semanal'); // diaria, semanal, quincenal, mensual, trimestral, anual
                $table->boolean('is_active')->default(true);
                $table->integer('sort_order')->default(0);
                $table->timestamps();
            });
        }

        // 2. Create user_commission_goals pivot table
        if (!Schema::hasTable('user_commission_goals')) {
            Schema::create('user_commission_goals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
                $table->foreignId('commission_goal_id')->constrained('commission_goals')->onDelete('cascade');
                $table->timestamps();
                $table->unique(['user_id', 'commission_goal_id']);
            });
        }

        // 3. Add seller_id to sales table
        Schema::table('sales', function (Blueprint $table) {
            if (!Schema::hasColumn('sales', 'seller_id')) {
                $table->foreignId('seller_id')->nullable()->after('customer_id')->constrained('users')->onDelete('set null');
            }
        });

        // 4. Add seller_assignment_mode & commission_calculation_mode to configurations table
        if (Schema::hasTable('configurations')) {
            Schema::table('configurations', function (Blueprint $table) {
                if (!Schema::hasColumn('configurations', 'seller_assignment_mode')) {
                    $table->string('seller_assignment_mode', 30)->default('customer_assigned')->after('business_name');
                }
                if (!Schema::hasColumn('configurations', 'commission_calculation_mode')) {
                    $table->string('commission_calculation_mode', 30)->default('percentage_threshold')->after('seller_assignment_mode');
                }
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configurations', function (Blueprint $table) {
            if (Schema::hasColumn('configurations', 'seller_assignment_mode')) {
                $table->dropColumn('seller_assignment_mode');
            }
            if (Schema::hasColumn('configurations', 'commission_calculation_mode')) {
                $table->dropColumn('commission_calculation_mode');
            }
        });

        Schema::table('sales', function (Blueprint $table) {
            if (Schema::hasColumn('sales', 'seller_id')) {
                $table->dropForeign(['seller_id']);
                $table->dropColumn('seller_id');
            }
        });

        Schema::dropIfExists('user_commission_goals');
        Schema::dropIfExists('commission_goals');
    }
};
