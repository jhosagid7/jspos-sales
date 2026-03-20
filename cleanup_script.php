<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$permissions = Spatie\Permission\Models\Permission::pluck('name')->toArray();
$seeder = file_get_contents('database/seeders/CreatePermissionsSeeder.php');

$orphans = [];
foreach($permissions as $p) {
    if (strpos($seeder, "'name' => '" . $p . "'") === false) {
        $orphans[] = $p;
    }
}

echo "Orphans in DB but not in Seeder:\n";
foreach($orphans as $o) {
    echo "- $o\n";
}

if (count($orphans) > 0) {
    echo "\nDeleting " . count($orphans) . " orphan permissions...\n";
    foreach ($orphans as $name) {
        $permission = Spatie\Permission\Models\Permission::where('name', $name)->first();
        if ($permission) {
            $permission->delete();
            echo "[DELETED] $name\n";
        }
    }
}

echo "\nFinal Count: " . Spatie\Permission\Models\Permission::count() . "\n";
