<?php

use App\Models\Sale;
use Carbon\Carbon;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$sale = Sale::latest()->first();

if ($sale) {
    echo "Sale ID: " . $sale->id . "\n";
    echo "Created At: " . $sale->created_at . "\n";
    
    $startOfMonth = Carbon::now()->startOfMonth();
    $endOfMonth = Carbon::now()->endOfMonth();
    
    echo "Current Month Range: " . $startOfMonth . " to " . $endOfMonth . "\n";
    
    if ($sale->created_at->between($startOfMonth, $endOfMonth)) {
        echo "Verdict: Sale IS within current month.\n";
    } else {
        echo "Verdict: Sale is OUTSIDE current month.\n";
    }
}
