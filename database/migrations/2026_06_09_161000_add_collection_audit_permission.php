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
        $permissionName = 'collections.audit';
        
        if (!Permission::where('name', $permissionName)->exists()) {
            Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
        }

        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            $superAdminRole->givePermissionTo($permissionName);
        }

        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminRole->givePermissionTo($permissionName);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permissionName = 'collections.audit';
        $permission = Permission::where('name', $permissionName)->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
