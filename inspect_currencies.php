<?php

use App\Models\Currency;

$currencies = Currency::all();

foreach ($currencies as $currency) {
    echo "Code: {$currency->code}, Symbol: {$currency->symbol}, Rate: {$currency->exchange_rate}, Primary: {$currency->is_primary}\n";
}
