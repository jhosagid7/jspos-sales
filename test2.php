<?php
$user = App\Models\User::first();
Auth::login($user);
echo Livewire\Livewire::mount('welcome.page')->html();
