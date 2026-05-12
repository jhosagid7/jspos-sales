<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\ProductionLog;
use App\Models\ProductionMaterial;
use App\Models\ProductionOutput;

$log = ProductionLog::orderBy('id', 'desc')->first();

if (!$log) {
    echo "No production logs found.\n";
    exit;
}

echo "--- Recent Production Log ---\n";
echo "ID: " . $log->id . "\n";
echo "Date: " . $log->created_at . "\n";
echo "Shift ID: " . $log->shift_id . "\n";
echo "User: " . ($log->user->name ?? 'N/A') . "\n";

echo "\n--- Outputs (Finished Products) ---\n";
$outputs = ProductionOutput::where('production_log_id', $log->id)->with('product')->get();
foreach ($outputs as $o) {
    echo "- " . ($o->product->name ?? 'Unknown') . ": " . $o->quantity . " units (Quality: " . $o->quality . ")\n";
}

echo "\n--- Materials (Ingredients consumed) ---\n";
$materials = ProductionMaterial::where('production_log_id', $log->id)->with('product')->get();
foreach ($materials as $m) {
    echo "- " . ($m->product->name ?? 'Unknown') . " (ID: " . $m->product_id . "): " . $m->quantity . " units consumed\n";
}
