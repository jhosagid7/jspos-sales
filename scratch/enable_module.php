<?php
use App\Models\Configuration;

$c = Configuration::first();
$c->module_production = 1;
$c->save();
echo "module_production enabled\n";
