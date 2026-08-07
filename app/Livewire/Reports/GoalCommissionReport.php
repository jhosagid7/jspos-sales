<?php

namespace App\Livewire\Reports;

use Livewire\Component;
use App\Models\User;
use App\Services\GoalCommissionService;
use Carbon\Carbon;

class GoalCommissionReport extends Component
{
    public $sellerId = 'all';
    public $referenceDate = '';
    public $showPdfModal = false;
    public $pdfUrl = '';

    public function mount()
    {
        session(['map' => 'Reportes', 'child' => 'Reporte de Comisiones por Metas', 'rest' => '', 'pos' => 'Comisiones por Metas']);
        $this->referenceDate = Carbon::now()->format('Y-m-d');
    }

    public function setToday()
    {
        $this->referenceDate = Carbon::now()->format('Y-m-d');
    }

    public function setYesterday()
    {
        $this->referenceDate = Carbon::now()->subDay()->format('Y-m-d');
    }

    public function exportPdf()
    {
        $baseQuery = function() {
            return User::whereHas('commissionGoals', function($q) {
                $q->where('is_active', true);
            })->distinct()->orderBy('name');
        };

        $sellersQuery = $baseQuery();
        if ($this->sellerId !== 'all' && !empty($this->sellerId)) {
            $sellersQuery->where('id', $this->sellerId);
        }

        $sellers = $sellersQuery->get();
        $evaluations = [];
        $totalCommissionEarned = 0.0;
        $totalGoalsAchieved = 0;
        $totalGoalsEvaluated = 0;

        foreach ($sellers as $seller) {
            $eval = GoalCommissionService::evaluateAllGoalsForUser($seller, $this->referenceDate);
            if (count($eval['goals']) > 0) {
                $evaluations[] = $eval;
                $totalCommissionEarned += $eval['total_earned'];

                foreach ($eval['goals'] as $goalEval) {
                    $totalGoalsEvaluated++;
                    if ($goalEval['achieved']) {
                        $totalGoalsAchieved++;
                    }
                }
            }
        }

        $config = \App\Models\Configuration::first();
        $user = auth()->user();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('livewire.reports.goal-commission-report-pdf', [
            'evaluations' => $evaluations,
            'totalCommissionEarned' => $totalCommissionEarned,
            'totalGoalsAchieved' => $totalGoalsAchieved,
            'totalGoalsEvaluated' => $totalGoalsEvaluated,
            'referenceDate' => $this->referenceDate,
            'config' => $config,
            'user' => $user,
        ]);

        return response()->streamDownload(function() use ($pdf) {
            echo $pdf->output();
        }, 'reporte_comisiones_metas_' . $this->referenceDate . '.pdf');
    }

    public function openPdfPreview()
    {
        $baseQuery = function() {
            return User::whereHas('commissionGoals', function($q) {
                $q->where('is_active', true);
            })->distinct()->orderBy('name');
        };

        $sellersQuery = $baseQuery();
        if ($this->sellerId !== 'all' && !empty($this->sellerId)) {
            $sellersQuery->where('id', $this->sellerId);
        }

        $sellers = $sellersQuery->get();
        $evaluations = [];
        $totalCommissionEarned = 0.0;
        $totalGoalsAchieved = 0;
        $totalGoalsEvaluated = 0;

        foreach ($sellers as $seller) {
            $eval = GoalCommissionService::evaluateAllGoalsForUser($seller, $this->referenceDate);
            if (count($eval['goals']) > 0) {
                $evaluations[] = $eval;
                $totalCommissionEarned += $eval['total_earned'];

                foreach ($eval['goals'] as $goalEval) {
                    $totalGoalsEvaluated++;
                    if ($goalEval['achieved']) {
                        $totalGoalsAchieved++;
                    }
                }
            }
        }

        $config = \App\Models\Configuration::first();
        $user = auth()->user();

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('livewire.reports.goal-commission-report-pdf', [
            'evaluations' => $evaluations,
            'totalCommissionEarned' => $totalCommissionEarned,
            'totalGoalsAchieved' => $totalGoalsAchieved,
            'totalGoalsEvaluated' => $totalGoalsEvaluated,
            'referenceDate' => $this->referenceDate,
            'config' => $config,
            'user' => $user,
        ]);

        $this->pdfUrl = 'data:application/pdf;base64,' . base64_encode($pdf->output());
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }

    public function render()
    {
        $baseQuery = function() {
            return User::whereHas('commissionGoals', function($q) {
                $q->where('is_active', true);
            })->distinct()->orderBy('name');
        };

        $sellersQuery = $baseQuery();
        if ($this->sellerId !== 'all' && !empty($this->sellerId)) {
            $sellersQuery->where('id', $this->sellerId);
        }

        $sellers = $sellersQuery->get();
        $evaluations = [];
        $totalCommissionEarned = 0.0;
        $totalGoalsAchieved = 0;
        $totalGoalsEvaluated = 0;

        foreach ($sellers as $seller) {
            $eval = GoalCommissionService::evaluateAllGoalsForUser($seller, $this->referenceDate);
            if (count($eval['goals']) > 0) {
                $evaluations[] = $eval;
                $totalCommissionEarned += $eval['total_earned'];

                foreach ($eval['goals'] as $goalEval) {
                    $totalGoalsEvaluated++;
                    if ($goalEval['achieved']) {
                        $totalGoalsAchieved++;
                    }
                }
            }
        }

        $allSellers = $baseQuery()->get();

        return view('livewire.reports.goal-commission-report', [
            'evaluations' => $evaluations,
            'allSellers' => $allSellers,
            'totalCommissionEarned' => $totalCommissionEarned,
            'totalGoalsAchieved' => $totalGoalsAchieved,
            'totalGoalsEvaluated' => $totalGoalsEvaluated,
        ]);
    }
}
