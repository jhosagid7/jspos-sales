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
        if (!Schema::hasTable('dispatch_routes')) {
            Schema::create('dispatch_routes', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('route_type')->default('fixed_route'); // fixed_route, local, pickup
                $table->string('dispatch_day')->nullable(); // monday, tuesday, wednesday, thursday, friday, saturday, sunday
                $table->string('cutoff_day')->nullable(); // monday, tuesday, wednesday, thursday, friday, saturday, sunday
                $table->time('cutoff_time')->default('12:00:00');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (Schema::hasTable('customers') && !Schema::hasColumn('customers', 'dispatch_route_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->unsignedBigInteger('dispatch_route_id')->nullable()->after('seller_id');
                $table->foreign('dispatch_route_id')->references('id')->on('dispatch_routes')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('customers') && Schema::hasColumn('customers', 'dispatch_route_id')) {
            Schema::table('customers', function (Blueprint $table) {
                $table->dropForeign(['dispatch_route_id']);
                $table->dropColumn('dispatch_route_id');
            });
        }

        Schema::dropIfExists('dispatch_routes');
    }
};
