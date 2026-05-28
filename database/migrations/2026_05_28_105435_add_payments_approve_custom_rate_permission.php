<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $permissionName = 'payments.approve_custom_rate';
        
        if (!Permission::where('name', $permissionName)->exists()) {
            Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($permissionName);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionName = 'payments.approve_custom_rate';
        $permission = Permission::where('name', $permissionName)->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
