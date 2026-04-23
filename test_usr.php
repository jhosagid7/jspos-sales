<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('email', 'dalbert3433@gmail.com')->first();
if ($user) {
    echo "Found in users: " . $user->name;
} else {
    echo "Not in users";
}
