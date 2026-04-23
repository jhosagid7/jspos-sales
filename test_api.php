<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

$user = \App\Models\Customer::find(115); // Dalbert
$token = $user->createToken('test-app')->plainTextToken;

$request = \Illuminate\Http\Request::create('/api/vip/sales/pending', 'GET');
$request->headers->set('Authorization', 'Bearer ' . $token);
$request->headers->set('Accept', 'application/json');

$response = $kernel->handle($request);
echo "Status: " . $response->getStatusCode() . "\n";
echo "Content: \n" . $response->getContent() . "\n";
