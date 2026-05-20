<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Limpiar cache de permisos
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        Permission::firstOrCreate(['name' => 'reports.audit']);
        Permission::firstOrCreate(['name' => 'products.edit.inventory']);
        Permission::firstOrCreate(['name' => 'products.edit.categories']);
        Permission::firstOrCreate(['name' => 'products.edit.price_rules']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Permission::whereIn('name', [
            'reports.audit',
            'products.edit.inventory',
            'products.edit.categories',
            'products.edit.price_rules'
        ])->delete();
    }
};
