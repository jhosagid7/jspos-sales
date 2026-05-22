<?php
$user = App\Models\User::first();
Auth::login($user);
$c = new App\Livewire\Welcome();
$c->mount();
var_dump($c->salesChartData);
var_dump($c->topSellersChartData);
