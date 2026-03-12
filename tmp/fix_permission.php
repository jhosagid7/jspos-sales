<?php

use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$permissionName = 'sales.reset_credit_snapshot';

if (!Permission::where('name', $permissionName)->exists()) {
    Permission::create(['name' => $permissionName, 'guard_name' => 'web']);
    echo "Permission created: $permissionName\n";
} else {
    echo "Permission already exists: $permissionName\n";
}

$adminRole = Role::where('name', 'Admin')->first();
if ($adminRole) {
    $adminRole->givePermissionTo($permissionName);
    echo "Permission assigned to Admin role.\n";
}

echo "Done.\n";
