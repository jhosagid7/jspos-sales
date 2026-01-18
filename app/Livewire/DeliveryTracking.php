<?php

namespace App\Livewire;

use App\Models\Sale;
use Livewire\Component;

class DeliveryTracking extends Component
{
    public $sale;
    public $locations;

    public function mount(Sale $sale)
    {
        $this->sale = $sale;
        $this->locations = $sale->deliveryLocations()->orderBy('created_at', 'desc')->get();
    }

    public function render()
    {
        return view('livewire.delivery-tracking')
            ->layout('layouts.theme.app');
    }
}
