<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$token = \Laravel\Sanctum\PersonalAccessToken::where('name', 'vip-mobile-app')->latest()->first();
if ($token) {
    echo "Found token for: " . $token->tokenable_type . " ID: " . $token->tokenable_id . "\n";
    $request = \Illuminate\Http\Request::create('/api/vip/sales/pending', 'GET');
    $request->headers->set('Authorization', 'Bearer ' . explode('|', $token->token)[0]); // Wait, we don't have the plain text token here!
}
