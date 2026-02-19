<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Product;
use App\Models\Configuration;
use App\Models\Currency;

use Spatie\Permission\Models\Permission;

$permissions = Permission::orderBy('name')->get();

echo "Posibles Permisos Huérfanos (Legacy):\n";
echo str_pad("Permiso", 30) . " | " . str_pad("Roles Asignados", 15) . "\n";
echo str_repeat("-", 50) . "\n";

foreach ($permissions as $p) {
    if (strpos($p->name, '.') === false) {
        $count = $p->roles()->count();
        echo str_pad($p->name, 30) . " | " . $count . "\n";
    }
}


$tiers = $product->priceTiers;
echo "Price Tiers count: " . $tiers->count() . "\n";
foreach ($tiers as $tier) {
    echo " - Min Qty: {$tier->min_qty}, Price: {$tier->price}\n";
}

// Config
$config = Configuration::first();
echo "\nConfiguration:\n";
echo "VAT: {$config->vat}\n";

// Currencies
$currencies = Currency::all();
echo "\nCurrencies:\n";
foreach ($currencies as $c) {
    echo " - {$c->code} ({$c->name}): Rate {$c->exchange_rate}, Is Primary: {$c->is_primary}\n";
}

// Simulation
$primary = $currencies->where('is_primary', 1)->first();
$exchangeRate = $primary ? $primary->exchange_rate : 1;
echo "Exchange Rate used: $exchangeRate\n";

$basePrice = $product->price;
$qty = 1;

if ($tiers && $tiers->count() > 0) {
    $tier = $tiers->where('min_qty', '<=', $qty)->sortByDesc('min_qty')->first();
    if ($tier) {
        $basePrice = $tier->price;
        echo " - Applied Tier Price: $basePrice\n";
    }
}

$basePriceInPrimary = $basePrice * $exchangeRate;
echo "Base Price in Primary: $basePriceInPrimary\n";

// ... previous code ...
$iva = $config->vat / 100;
echo "IVA Rate: $iva\n";

if ($iva > 0) {
    $precioUnitarioSinIva = $basePriceInPrimary / (1 + $iva);
    echo "Price / (1+IVA): $precioUnitarioSinIva\n";
} else {
    echo "Price (No IVA): $basePriceInPrimary\n";
}

// use App\Models\User; // Already imported
$user = User::find(1);
if ($user) {
    echo "\nUser 1 ({$user->name}):\n";
    echo "Roles: " . $user->getRoleNames()->implode(', ') . "\n";
    
    $sellerConfig = $user->latestSellerConfig;
    if ($sellerConfig) {
        echo "Seller Config:\n";
        echo " - Commission: {$sellerConfig->commission_percent}%\n";
        echo " - Freight: {$sellerConfig->freight_percent}%\n";
        echo " - Exchange Diff: {$sellerConfig->exchange_diff_percent}%\n";
        
        $total = $sellerConfig->commission_percent + $sellerConfig->freight_percent + $sellerConfig->exchange_diff_percent;
        echo " - Total Inflation: $total%\n";
        
        $inflatedPrice = $basePriceInPrimary * (1 + ($total/100));
        echo " - Inflated Price: $inflatedPrice\n";

        $inflatedCost = $product->cost * (1 + ($total/100));
        echo " - Inflated Cost: $inflatedCost\n";
    } else {
        echo "No Seller Config found.\n";
    }
} else {
    echo "User 1 not found.\n";
}

