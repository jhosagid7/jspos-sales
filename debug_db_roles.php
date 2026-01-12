<?php

use Illuminate\Support\Facades\DB;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

echo "--- ROLES TABLE ---\n";
$roles = DB::table('roles')->get();
foreach ($roles as $role) {
    echo "ID: $role->id | Name: '$role->name' | Level: $role->level\n";
}

echo "\n--- USER ROLES (model_has_roles) ---\n";
$userRoles = DB::table('model_has_roles')->where('model_id', 1)->get(); // Assuming ID 1
foreach ($userRoles as $ur) {
    echo "Role ID: $ur->role_id | Model Type: $ur->model_type | Model ID: $ur->model_id\n";
}
