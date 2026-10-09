<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkOrderItem;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class WorkshopMechanicCommissionService
{
    /**
     * Get commission report summary for all mechanics or a specific mechanic.
     *
     * @param int|null $mechanicId
     * @param string|null $dateFrom
     * @param string|null $dateTo
     * @param string|null $statusFilter
     * @return array
     */
    public function getCommissionReport(?int $mechanicId = null, ?string $dateFrom = null, ?string $dateTo = null, ?string $statusFilter = null): array
    {
        $startDate = $dateFrom ? Carbon::parse($dateFrom)->startOfDay() : Carbon::now()->startOfMonth();
        $endDate = $dateTo ? Carbon::parse($dateTo)->endOfDay() : Carbon::now()->endOfDay();

        $query = WorkOrderItem::with(['workOrder.customer', 'workOrder.vehicle', 'mechanic'])
            ->where('type', 'labor')
            ->whereNotNull('mechanic_id')
            ->whereHas('workOrder', function ($q) use ($startDate, $endDate, $statusFilter) {
                $q->whereBetween('created_at', [$startDate, $endDate]);
                if ($statusFilter && $statusFilter !== 'all') {
                    $q->where('status', $statusFilter);
                }
            });

        if ($mechanicId && $mechanicId > 0) {
            $query->where('mechanic_id', $mechanicId);
        }

        $items = $query->orderBy('created_at', 'desc')->get();

        $totalLaborCharged = round((float)$items->sum('subtotal'), 2);
        $totalCommissionEarned = round((float)$items->sum('mechanic_commission_amount'), 2);
        $totalItemsCount = $items->count();

        // Group by mechanic
        $byMechanic = $items->groupBy('mechanic_id')->map(function (Collection $mechanicItems) {
            $first = $mechanicItems->first();
            $mechanic = $first ? $first->mechanic : null;

            return [
                'mechanic_id' => $first ? $first->mechanic_id : null,
                'mechanic_name' => $mechanic ? $mechanic->name : 'N/A',
                'mechanic_email' => $mechanic ? $mechanic->email : '',
                'total_labor' => round((float)$mechanicItems->sum('subtotal'), 2),
                'total_commission' => round((float)$mechanicItems->sum('mechanic_commission_amount'), 2),
                'items_count' => $mechanicItems->count(),
                'orders_count' => $mechanicItems->pluck('work_order_id')->unique()->count(),
                'items' => $mechanicItems,
            ];
        })->values();

        return [
            'date_from' => $startDate->format('Y-m-d'),
            'date_to' => $endDate->format('Y-m-d'),
            'status_filter' => $statusFilter ?? 'all',
            'total_labor_charged' => $totalLaborCharged,
            'total_commission_earned' => $totalCommissionEarned,
            'total_items_count' => $totalItemsCount,
            'by_mechanic' => $byMechanic,
            'items' => $items,
        ];
    }

    /**
     * Get complete service & repair history for a specific vehicle by ID or license plate.
     *
     * @param string|int $vehicleIdentifier
     * @return array
     */
    public function getVehicleServiceHistory($vehicleIdentifier): array
    {
        $vehicle = is_numeric($vehicleIdentifier)
            ? \App\Models\Vehicle::with('customer')->find($vehicleIdentifier)
            : \App\Models\Vehicle::with('customer')->where('plate', mb_strtoupper(trim((string)$vehicleIdentifier), 'UTF-8'))->first();

        if (!$vehicle) {
            return [
                'vehicle' => null,
                'total_visits' => 0,
                'total_spent' => 0.0,
                'total_parts_spent' => 0.0,
                'total_labor_spent' => 0.0,
                'orders' => collect(),
            ];
        }

        $orders = \App\Models\WorkOrder::with(['items.mechanic', 'customer', 'sale.payments'])
            ->where('vehicle_id', $vehicle->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $totalSpent = round((float)$orders->where('status', '!=', 'cancelled')->sum('total'), 2);
        $totalParts = round((float)$orders->where('status', '!=', 'cancelled')->sum('subtotal_parts'), 2);
        $totalLabor = round((float)$orders->where('status', '!=', 'cancelled')->sum('subtotal_labor'), 2);

        return [
            'vehicle' => $vehicle,
            'total_visits' => $orders->count(),
            'total_spent' => $totalSpent,
            'total_parts_spent' => $totalParts,
            'total_labor_spent' => $totalLabor,
            'orders' => $orders,
        ];
    }
}