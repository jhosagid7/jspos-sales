<?php

namespace App\Livewire\Soplados;

use Livewire\Component;
use App\Models\Shift;
use Livewire\WithPagination;
use Carbon\Carbon;

class ShiftList extends Component
{
    use WithPagination;

    public $dateFrom, $dateTo;
    public $search = '';
    private $pagination = 15;

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $query = Shift::with(['user', 'warehouse'])
            ->orderBy('id', 'desc');

        if ($this->search) {
            $query->whereHas('user', function($q) {
                $q->where('name', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->dateFrom) {
            $query->whereDate('start_time', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('start_time', '<=', $this->dateTo);
        }

        return view('livewire.soplados.shift-list', [
            'shifts' => $query->paginate($this->pagination)
        ])->extends('layouts.theme.app')->section('content');
    }
}
