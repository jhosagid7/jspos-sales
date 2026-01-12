<?php

use App\Models\User;
use Spatie\Permission\Models\Role;

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernel->handle(
    $request = Illuminate\Http\Request::capture()
);

// Get the user (assuming ID 1 is the one trying to access, or we can look up by email)
$user = User::where('email', 'jhosagid77@gmail.com')->first();

if ($user) {
    echo "User: " . $user->name . " (" . $user->email . ")\n";
    echo "Roles: " . implode(', ', $user->getRoleNames()->toArray()) . "\n";
    echo "Has 'Admin' role? " . ($user->hasRole('Admin') ? 'YES' : 'NO') . "\n";
    
    $adminRole = Role::where('name', 'Admin')->first();
    if ($adminRole) {
        echo "Admin Role Level: " . $adminRole->level . "\n";
    } else {
        echo "Admin Role NOT FOUND in DB\n";
    }
} else {
    echo "User jhosagid77@gmail.com not found.\n";
}
