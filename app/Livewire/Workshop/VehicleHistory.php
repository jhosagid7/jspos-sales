<?php

namespace App\Livewire\Workshop;

use App\Models\Vehicle;
use App\Services\WorkshopMechanicCommissionService;
use Livewire\Component;

class VehicleHistory extends Component
{
    public $searchPlate = '';
    public $selectedVehicleId = null;

    public function searchVehicle()
    {
        $plate = mb_strtoupper(trim($this->searchPlate), 'UTF-8');
        if (!empty($plate)) {
            $vehicle = Vehicle::where('plate', $plate)->first();
            if ($vehicle) {
                $this->selectedVehicleId = $vehicle->id;
            } else {
                $this->selectedVehicleId = null;
            }
        }
    }

    public function selectVehicle(int $id)
    {
        $this->selectedVehicleId = $id;
        $vehicle = Vehicle::find($id);
        if ($vehicle) {
            $this->searchPlate = $vehicle->plate;
        }
    }

    public function render(WorkshopMechanicCommissionService $service)
    {
        $vehicles = [];
        if (!empty($this->searchPlate)) {
            $vehicles = Vehicle::with('customer')
                ->search($this->searchPlate)
                ->limit(8)
                ->get();
        }

        $history = null;
        if ($this->selectedVehicleId) {
            $history = $service->getVehicleServiceHistory($this->selectedVehicleId);
        }

        return view('livewire.workshop.vehicle-history', [
            'vehicles' => $vehicles,
            'history' => $history,
        ])->layout('layouts.theme.app');
    }
}