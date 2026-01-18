<?php

namespace App\Livewire;

use Livewire\Component;

class LiveDriverMap extends Component
{
    public $drivers = [];

    public function render()
    {
        $this->loadDrivers();
        return view('livewire.live-driver-map')
            ->layout('layouts.theme.app');
    }

    public function loadDrivers()
    {
        // Get latest location for each driver
        $this->drivers = \App\Models\User::role('Driver')
            ->whereHas('locations') // Only drivers with location history
            ->with(['locations' => function($q) {
                $q->latest()->limit(1);
            }])
            ->get()
            ->map(function($driver) {
                $lastLoc = $driver->locations->first();
                // Get active orders for this driver
                $activeOrders = \App\Models\Sale::where('driver_id', $driver->id)
                    ->whereIn('delivery_status', ['pending', 'in_transit'])
                    ->with('customer')
                    ->orderBy('created_at', 'asc')
                    ->get()
                    ->map(function($sale) {
                        return [
                            'id' => $sale->id,
                            'customer' => $sale->customer->name,
                            'address' => $sale->customer->address,
                            'status' => $sale->delivery_status
                        ];
                    });

                return [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'lat' => $lastLoc ? $lastLoc->latitude : null,
                    'lng' => $lastLoc ? $lastLoc->longitude : null,
                    'last_update' => $lastLoc ? $lastLoc->created_at->diffForHumans() : 'N/A',
                    'active_orders' => $activeOrders
                ];
            });
        
        $this->dispatch('drivers-updated', drivers: $this->drivers);
    }
}
