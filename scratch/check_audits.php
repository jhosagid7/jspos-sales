<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

config(['database.connections.mysql.database' => 'pruebabderrorcobranza']);
\DB::purge('mysql');

$logs = \DB::table('activity_log')
    ->where('subject_type', 'App\Models\Payment')
    ->whereIn('subject_id', [1275, 1295, 1296, 1297, 1305, 1310])
    ->get();

echo "Activity logs for payments:\n";
foreach ($logs as $l) {
    echo "ID: {$l->id}, Subject ID: {$l->subject_id}, Description: {$l->description}, Created At: {$l->created_at}, Properties: {$l->properties}\n";
}
