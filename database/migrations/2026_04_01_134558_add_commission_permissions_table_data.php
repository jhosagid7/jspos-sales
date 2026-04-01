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
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'commissions.view_all']);
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'commissions.view_own']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Spatie\Permission\Models\Permission::whereIn('name', ['commissions.view_all', 'commissions.view_own'])->delete();
    }
};
