<?php
use App\Models\License;

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->handle(Illuminate\Http\Request::capture());

$license = License::latest()->first();
if ($license) {
    try {
        $key = $license->license_key;
        $decoded = base64_decode($key);
        $parts = explode('||', $decoded);
        if (count($parts) > 0) {
            $data = json_decode($parts[0], true);
            echo "License Type: " . ($data['type'] ?? 'Unknown') . "\n";
            echo "Modules: " . json_encode($data['modules'] ?? []) . "\n";
        }
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "No license found";
}
