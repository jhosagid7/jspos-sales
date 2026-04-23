<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\Customer::find(115);
$token = $user->createToken('test-app')->plainTextToken;

$ch = curl_init('http://jspos-sales.test/api/vip/sales/pending');
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Accept: application/json', 'Authorization: Bearer ' . $token]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$res = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
if (curl_errno($ch)) {
    echo curl_error($ch);
}
echo "\nHTTP Code: " . $http_code . "\n";
echo "Response: " . $res . "\n";
