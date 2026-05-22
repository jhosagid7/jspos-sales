<?php
$user = App\Models\User::first();
Auth::login($user);
try {
    echo Livewire\Livewire::mount('welcome')->html();
} catch (\Exception $e) {
    echo $e->getMessage() . "\n" . $e->getTraceAsString();
}
