<?php

namespace App\Livewire\Workshop;

use App\Models\User;
use App\Services\WorkshopMechanicCommissionService;
use Carbon\Carbon;
use Livewire\Component;

class MechanicCommissions extends Component
{
    public $dateFrom;
    public $dateTo;
    public $mechanicId = 0;
    public $statusFilter = 'all';

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function render(WorkshopMechanicCommissionService $service)
    {
        $mechanics = User::mechanics()->orderBy('name')->get();

        $report = $service->getCommissionReport(
            $this->mechanicId > 0 ? (int)$this->mechanicId : null,
            $this->dateFrom,
            $this->dateTo,
            $this->statusFilter
        );

        return view('livewire.workshop.mechanic-commissions', [
            'mechanics' => $mechanics,
            'report' => $report,
        ])->layout('layouts.theme.app');
    }
}