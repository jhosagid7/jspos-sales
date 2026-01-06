<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create the permission
        $permission = Permission::firstOrCreate(['name' => 'gestionar_comisiones', 'guard_name' => 'web']);

        // Assign to Admin role
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permission);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('name', 'gestionar_comisiones')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
