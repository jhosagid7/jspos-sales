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

    public function mount()
    {
        session(['map' => 'Reportes', 'child' => 'Reporte de Comisiones por Metas', 'rest' => '', 'pos' => 'Comisiones por Metas']);
        $this->referenceDate = Carbon::now()->format('Y-m-d');
    }

    public function render()
    {
        $sellersQuery = User::eligibleSellers();
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
            $evaluations[] = $eval;
            $totalCommissionEarned += $eval['total_earned'];

            foreach ($eval['goals'] as $goalEval) {
                $totalGoalsEvaluated++;
                if ($goalEval['achieved']) {
                    $totalGoalsAchieved++;
                }
            }
        }

        $allSellers = User::eligibleSellers()->get();

        return view('livewire.reports.goal-commission-report', [
            'evaluations' => $evaluations,
            'allSellers' => $allSellers,
            'totalCommissionEarned' => $totalCommissionEarned,
            'totalGoalsAchieved' => $totalGoalsAchieved,
            'totalGoalsEvaluated' => $totalGoalsEvaluated,
        ]);
    }
}
