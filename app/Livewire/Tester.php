<?php

namespace App\Livewire;

use Livewire\Component;

class Tester extends Component
{
    function mount()
    {
        session(['map' => '', 'child' => '', 'rest' => '', 'pos' => 'Tester']);
    }
    public function render()
    {
        return view('livewire.tester');
    }
}
