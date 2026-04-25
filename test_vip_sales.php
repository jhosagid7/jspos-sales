<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$c = App\Models\Customer::whereNotNull('password')->first();
if (!$c) {
    echo "No customer with password.";
    exit;
}
$token = $c->createToken('test')->plainTextToken;

$request = Illuminate\Http\Request::create('/api/vip/sales', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Accept', 'application/json');

$kernelHttp = $app->make(Illuminate\Contracts\Http\Kernel::class);
$response = $kernelHttp->handle($request);

echo $response->getContent();
