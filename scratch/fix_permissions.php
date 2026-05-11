<?php
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

$role = Role::where('name', 'Admin')->first();
if ($role) {
    // Ensure permission exists
    Permission::firstOrCreate(['name' => 'production.index']);
    
    if (!$role->hasPermissionTo('production.index')) {
        $role->givePermissionTo('production.index');
        echo "Permission production.index granted to Admin\n";
    } else {
        echo "Admin already has production.index\n";
    }
} else {
    echo "Admin role not found\n";
}

// Also check supervisor
$sup = Role::where('name', 'Supervisor')->first();
if ($sup && !$sup->hasPermissionTo('production.index')) {
    $sup->givePermissionTo('production.index');
    echo "Permission production.index granted to Supervisor\n";
}
