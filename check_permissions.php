<?php

use App\Models\User;
use Spatie\Permission\Models\Permission;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$users = User::whereIn('name', ['Jhonny Pirela', 'Elizabeth Hernandez'])->get();

foreach ($users as $user) {
    echo "User: " . $user->name . " (ID: " . $user->id . ")\n";
    echo "Roles: " . $user->getRoleNames()->implode(', ') . "\n";
    echo "Can 'gestionar_comisiones': " . ($user->can('gestionar_comisiones') ? 'YES' : 'NO') . "\n";
    echo "--------------------------------\n";
}
