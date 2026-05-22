<?php
$user = App\Models\User::first();
Auth::login($user);
file_put_contents('test5.html', Livewire\Livewire::mount('welcome')->html());
