<?php

namespace App\Livewire\Consultation;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\ExchangeRateApproval;
use Carbon\Carbon;

class ExchangeRateApprovalsDashboard extends Component
{
    use WithPagination;

    public $search = '';
    public $status = 'pending';
    public $dateFrom;
    public $dateTo;

    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        // Require permission or Super Admin role
        if (!auth()->user()->can('payments.approve_custom_rate') && !auth()->user()->hasRole('Super Admin')) {
            abort(403, 'No tienes permiso para ver este módulo.');
        }

        // Default date range: current month
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
    }

    public function approveRequest($id)
    {
        if (!auth()->user()->can('payments.approve_custom_rate') && !auth()->user()->hasRole('Super Admin')) {
            $this->dispatch('noty', msg: 'ACCESO DENEGADO: No tienes permisos de aprobación.');
            return;
        }

        $approval = ExchangeRateApproval::find($id);
        if ($approval && $approval->status === 'pending') {
            $approval->update([
                'status' => 'approved',
                'approver_id' => auth()->id(),
            ]);
            $this->dispatch('noty', msg: 'Solicitud APROBADA y tasa especial desbloqueada.');
        }
    }

    public function rejectRequest($id)
    {
        if (!auth()->user()->can('payments.approve_custom_rate') && !auth()->user()->hasRole('Super Admin')) {
            $this->dispatch('noty', msg: 'ACCESO DENEGADO: No tienes permisos de rechazo.');
            return;
        }

        $approval = ExchangeRateApproval::find($id);
        if ($approval && $approval->status === 'pending') {
            $approval->update([
                'status' => 'rejected',
                'approver_id' => auth()->id(),
            ]);
            $this->dispatch('noty', msg: 'Solicitud RECHAZADA.');
        }
    }

    public function render()
    {
        $query = ExchangeRateApproval::query()->with(['user', 'sale.customer']);

        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('user', function($u) {
                    $u->where('name', 'like', '%' . $this->search . '%');
                })->orWhereHas('sale.customer', function($c) {
                    $c->where('name', 'like', '%' . $this->search . '%');
                })->orWhere('reason', 'like', '%' . $this->search . '%')
                  ->orWhere('custom_rate', 'like', '%' . $this->search . '%')
                  ->orWhere('token', 'like', '%' . $this->search . '%')
                  ->orWhere('sale_id', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->dateFrom) {
            $query->whereDate('created_at', '>=', $this->dateFrom);
        }

        if ($this->dateTo) {
            $query->whereDate('created_at', '<=', $this->dateTo);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $records = $query->orderBy('created_at', 'desc')->paginate(10);

        // Fetch counts for summary cards
        $pendingTodayCount = ExchangeRateApproval::where('status', 'pending')
            ->whereDate('created_at', Carbon::today())
            ->count();
        $approvedTodayCount = ExchangeRateApproval::where('status', 'approved')
            ->whereDate('created_at', Carbon::today())
            ->count();
        $usedTodayCount = ExchangeRateApproval::where('status', 'used')
            ->whereDate('created_at', Carbon::today())
            ->count();
        $rejectedTodayCount = ExchangeRateApproval::where('status', 'rejected')
            ->whereDate('created_at', Carbon::today())
            ->count();
        $averageRateToday = ExchangeRateApproval::whereIn('status', ['approved', 'used'])
            ->whereDate('created_at', Carbon::today())
            ->avg('custom_rate') ?? 0;

        return view('livewire.consultation.exchange-rate-approvals-dashboard', [
            'records' => $records,
            'pendingTodayCount' => $pendingTodayCount,
            'approvedTodayCount' => $approvedTodayCount,
            'usedTodayCount' => $usedTodayCount,
            'rejectedTodayCount' => $rejectedTodayCount,
            'averageRateToday' => $averageRateToday,
        ])->layout('layouts.theme.app');
    }
}
