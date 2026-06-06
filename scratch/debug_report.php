<?php

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\CollectionSheet;
use App\Http\Controllers\ReportController;

// Login first user as admin
$admin = \App\Models\User::first();
auth()->login($admin);

$sheet = CollectionSheet::find(71);
$request = new Request([
    'dateFrom' => '2026-06-04',
    'dateTo' => '2026-06-04'
]);

$controller = new ReportController();

$response = $controller->collectionRelationshipPdf($sheet, $request);

echo "Response class: " . get_class($response) . "\n";
