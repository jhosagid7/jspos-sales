<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$toDelete = [
    'adjustments.approve_cargo',
    'adjustments.approve_descargo',
    'bank_index',
    'bank_print_pdf',
    'bank_view_details',
    'commissions.access',
    'commissions.manage',
    'commissions.view_all',
    'payments.create_credit_note',
    'payments.method_bank',
    'payments.method_cash',
    'payments.method_credit',
    'payments.method_nequi',
    'payments.methods',
    'sales.approve_return',
    'sales.create_customer',
    'sales.select_driver',
    'sales.show_exchange_rate',
    'zelle_index',
    'zelle_print_pdf',
    'zelle_view_details'
];

echo "Cleaning up " . count($toDelete) . " orphan permissions...\n";

foreach ($toDelete as $name) {
    $permission = Spatie\Permission\Models\Permission::where('name', $name)->first();
    if ($permission) {
        $permission->delete();
        echo "[DELETED] $name\n";
    } else {
        echo "[NOT FOUND] $name\n";
    }
}

echo "\nCleaning up other weird names found in DB...\n";
$weirdNames = [
    'aprobar_cargos',
    'aprobar_descargos',
    'gestionar_comisiones',
    'guardar ordenes de ventas',
    'metodos de pago',
    'pago con Banco',
    'pago con credito',
    'pago con efectivo/nequi',
    'pago con Nequi'
];

foreach ($weirdNames as $name) {
    // Only delete if they are NOT in the seeder (double check)
    $seeder = file_get_contents('database/seeders/CreatePermissionsSeeder.php');
    if (strpos($seeder, "'name' => '" . $name . "'") === false) {
        $permission = Spatie\Permission\Models\Permission::where('name', $name)->first();
        if ($permission) {
            $permission->delete();
            echo "[DELETED] $name\n";
        }
    }
}

echo "\nCleanup complete.\n";
