<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$d = \App\Models\TransferDetail::find(14);
if ($d) {
    $d->received_quantity = 2;
    $d->rejected_quantity = 1;
    $d->save();
    echo "TransferDetail 14 updated: Received 2, Rejected 1\n";
    
    $t = \App\Models\Transfer::find(13);
    if ($t) {
        $t->received_by_id = 1; // Assuming Admin/Jhonny for this test
        $t->save();
        echo "Transfer 13 updated: Received By User 1\n";
    }
} else {
    echo "TransferDetail 14 not found\n";
}
