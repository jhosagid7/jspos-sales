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
        // Permission for viewing order audit history
        $permissionName = 'orders.view_history';

        if (!Permission::where('name', $permissionName)->exists()) {
            Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
        }

        // Assign to Admin and Super Admin roles
        foreach (['Admin', 'Super Admin'] as $roleName) {
            $role = Role::where('name', $roleName)->first();
            if ($role) {
                $role->givePermissionTo($permissionName);
            }
        }

        // Also ensure sales.view_history is assigned to Super Admin (in case it was missed)
        $salesPermission = 'sales.view_history';
        if (Permission::where('name', $salesPermission)->exists()) {
            $superAdmin = Role::where('name', 'Super Admin')->first();
            if ($superAdmin && !$superAdmin->hasPermissionTo($salesPermission)) {
                $superAdmin->givePermissionTo($salesPermission);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $permission = Permission::where('name', 'orders.view_history')->first();
        if ($permission) {
            $permission->delete();
        }
    }
};
