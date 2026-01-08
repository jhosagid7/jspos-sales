<?php

use App\Models\Sale;
use App\Models\Payment;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sale = Sale::find(4);

if (!$sale) {
    echo "Sale ID 4 not found.\n";
    exit;
}

echo "Resetting Sale ID: " . $sale->id . "\n";

// Delete payments
$count = Payment::where('sale_id', $sale->id)->count();
Payment::where('sale_id', $sale->id)->delete();
echo "Deleted $count payments.\n";

// Reset status
$sale->status = 'pending';
$sale->save();

echo "Sale status reset to PENDING.\n";
