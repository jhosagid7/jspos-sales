<?php

namespace App\Services;

use App\Models\User;
use App\Models\Sale;
use App\Models\CommissionGoal;
use Carbon\Carbon;

class GoalCommissionService
{
    /**
     * Obtiene el rango de fechas [inicio, fin] según la periodicidad y días de corte.
     */
    public static function getDateRangeForPeriodicity(string $periodicity, $referenceDate = null, $goal = null): array
    {
        $date = $referenceDate ? Carbon::parse($referenceDate) : Carbon::now();
        
        switch (strtolower($periodicity)) {
            case 'diaria':
                return [
                    'start' => $date->copy()->startOfDay(),
                    'end' => $date->copy()->endOfDay(),
                ];
            case 'semanal':
                $startDayStr = strtolower($goal->start_day_of_week ?? 'lunes');
                $endDayStr = strtolower($goal->end_day_of_week ?? 'domingo');

                $dayMap = [
                    'lunes' => Carbon::MONDAY,
                    'martes' => Carbon::TUESDAY,
                    'miercoles' => Carbon::WEDNESDAY,
                    'miércoles' => Carbon::WEDNESDAY,
                    'jueves' => Carbon::THURSDAY,
                    'viernes' => Carbon::FRIDAY,
                    'sabado' => Carbon::SATURDAY,
                    'sábado' => Carbon::SATURDAY,
                    'domingo' => Carbon::SUNDAY,
                ];

                $dayIndex = [
                    'lunes' => 1,
                    'martes' => 2,
                    'miercoles' => 3,
                    'miércoles' => 3,
                    'jueves' => 4,
                    'viernes' => 5,
                    'sabado' => 6,
                    'sábado' => 6,
                    'domingo' => 7,
                ];

                $startConst = $dayMap[$startDayStr] ?? Carbon::MONDAY;
                $start = $date->copy()->startOfWeek($startConst);

                $startIndex = $dayIndex[$startDayStr] ?? 1;
                $endIndex = $dayIndex[$endDayStr] ?? 7;

                $daysToAdd = ($endIndex - $startIndex + 7) % 7;
                if ($daysToAdd === 0 && $startDayStr !== $endDayStr) {
                    $daysToAdd = 6;
                }

                $end = $start->copy()->addDays($daysToAdd)->endOfDay();
                return ['start' => $start, 'end' => $end];
            case 'quincenal':
                if ($date->day <= 15) {
                    $start = $date->copy()->startOfMonth();
                    $end = $date->copy()->day(15)->endOfDay();
                } else {
                    $start = $date->copy()->day(16)->startOfDay();
                    $end = $date->copy()->endOfMonth();
                }
                return ['start' => $start, 'end' => $end];
            case 'mensual':
                return [
                    'start' => $date->copy()->startOfMonth(),
                    'end' => $date->copy()->endOfMonth(),
                ];
            case 'trimestral':
                return [
                    'start' => $date->copy()->startOfQuarter(),
                    'end' => $date->copy()->endOfQuarter(),
                ];
            case 'anual':
                return [
                    'start' => $date->copy()->startOfYear(),
                    'end' => $date->copy()->endOfYear(),
                ];
            default:
                return [
                    'start' => $date->copy()->startOfWeek(Carbon::MONDAY),
                    'end' => $date->copy()->endOfWeek(Carbon::SUNDAY),
                ];
        }
    }

    /**
     * Calcula las ventas acumuladas de un vendedor en un período específico.
     */
    public static function getSellerTotalSales(int $sellerId, Carbon $startDate, Carbon $endDate): float
    {
        return (float) Sale::where(function ($q) use ($sellerId) {
            $q->where('seller_id', $sellerId)
              ->orWhere(function ($subQ) use ($sellerId) {
                  $subQ->whereNull('seller_id')
                       ->whereHas('customer', function ($cq) use ($sellerId) {
                           $cq->where('seller_id', $sellerId);
                       });
              });
        })
        ->whereNotIn('status', ['voided', 'cancelled', 'anulated'])
        ->whereBetween('created_at', [$startDate, $endDate])
        ->sum('total_usd');
    }

    /**
     * Evalúa una meta específica para un usuario.
     */
    public static function evaluateGoalForUser(User $user, CommissionGoal $goal, $referenceDate = null): array
    {
        $range = self::getDateRangeForPeriodicity($goal->periodicity, $referenceDate, $goal);
        $totalSales = self::getSellerTotalSales($user->id, $range['start'], $range['end']);
        
        $achieved = $totalSales >= $goal->target_amount;
        $earnedReward = $achieved ? (float) $goal->reward_amount : 0.0;
        $remainingAmount = $achieved ? 0.0 : max(0.0, (float) $goal->target_amount - $totalSales);

        return [
            'goal_id' => $goal->id,
            'goal_name' => $goal->name,
            'target_amount' => (float) $goal->target_amount,
            'reward_amount' => (float) $goal->reward_amount,
            'periodicity' => $goal->periodicity,
            'total_sales' => $totalSales,
            'achieved' => $achieved,
            'earned_reward' => $earnedReward,
            'remaining_amount' => $remainingAmount,
            'period_start' => $range['start']->format('Y-m-d H:i:s'),
            'period_end' => $range['end']->format('Y-m-d H:i:s'),
        ];
    }

    /**
     * Evalúa todas las metas activas asignadas a un usuario.
     */
    public static function evaluateAllGoalsForUser(User $user, $referenceDate = null): array
    {
        $goals = $user->commissionGoals()->where('is_active', true)->orderBy('sort_order')->orderBy('target_amount')->get();
        $evaluations = [];
        $totalEarned = 0.0;

        foreach ($goals as $goal) {
            $result = self::evaluateGoalForUser($user, $goal, $referenceDate);
            $evaluations[] = $result;
            $totalEarned += $result['earned_reward'];
        }

        return [
            'user_id' => $user->id,
            'user_name' => $user->name,
            'total_earned' => $totalEarned,
            'goals' => $evaluations,
        ];
    }
}
