<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Sale;
use Carbon\Carbon;

class CustomerCreditScoringService
{
    /**
     * Evalúa el comportamiento crediticio de un cliente y actualiza su base de datos.
     *
     * @param Customer $customer
     * @return array
     */
    public static function evaluate(Customer $customer): array
    {
        $daysSinceRegistration = $customer->created_at ? $customer->created_at->diffInDays(now()) : 0;

        // Compras de contado (tipo 'cash', estado 'paid', y excluyendo ventas anuladas/devueltas)
        $cashSales = Sale::where('customer_id', $customer->id)
            ->where('type', 'cash')
            ->whereIn('status', ['paid', 'delivered'])
            ->get();

        $cashPurchaseCount = $cashSales->count();
        $totalCashAmount = $cashSales->sum('total_usd');
        $averageCashPurchase = $cashPurchaseCount > 0 ? ($totalCashAmount / $cashPurchaseCount) : 0.00;

        // Si es cliente nuevo y no cumple los requisitos mínimos (30 días de registro y 3 compras de contado)
        if ($daysSinceRegistration < 30 || $cashPurchaseCount < 3) {
            $status = 'new';
            $score = 100;
            $recommendedLimit = 0.00;

            $aiAnalysis = "El cliente es catalogado como **NUEVO**. Aún no cumple con el periodo mínimo de 30 días de registro (lleva {$daysSinceRegistration} días) o el mínimo de 3 compras de contado realizadas (lleva {$cashPurchaseCount}). Por tanto, se sugiere mantener su cupo en $0.00 hasta construir historial.";
        } else {
            // Evaluamos ventas a crédito
            $creditSales = Sale::where('customer_id', $customer->id)
                ->where('type', 'credit')
                ->whereNotIn('status', ['voided', 'cancelled', 'anulated'])
                ->get();

            $totalCreditSales = $creditSales->count();
            $delayedInvoicesCount = 0;
            $totalMoraDays = 0;

            foreach ($creditSales as $sale) {
                $daysOverdue = $sale->days_overdue; // signed difference: positive means overdue
                if ($daysOverdue > 0) {
                    $totalMoraDays += $daysOverdue;
                    $delayedInvoicesCount++;
                }
            }

            // Días de Mora Promedio (DMP)
            $averageMoraDays = $totalCreditSales > 0 ? ($totalMoraDays / $totalCreditSales) : 0;

            // Tasa de Puntualidad (Porcentaje de facturas pagadas/vigentes a tiempo)
            $punctualInvoicesCount = $totalCreditSales - $delayedInvoicesCount;
            $punctualityScore = $totalCreditSales > 0 ? (int) (($punctualInvoicesCount / $totalCreditSales) * 100) : 100;

            // Determinar Score y Estado Crediticio
            $score = $punctualityScore;
            
            if ($score < 60) {
                $status = 'defaulted'; // Moroso / Alto Riesgo
                $recommendedLimit = 0.00;
            } elseif ($score < 85) {
                $status = 'active'; // Riesgo Medio
                $recommendedLimit = round($averageCashPurchase * 0.20, 2);
            } else {
                $status = 'active'; // Riesgo Bajo / Excelente
                $recommendedLimit = round($averageCashPurchase * 0.30, 2);
            }

            // Generar Síntesis Dinámica (AI Analysis)
            if ($totalCreditSales === 0) {
                $aiAnalysis = "El cliente califica para crédito por antigüedad e historial de contado. Ha realizado **{$cashPurchaseCount} compras de contado** con un ticket promedio de **$" . number_format($averageCashPurchase, 2) . "**. Se recomienda iniciar con un **Cupo Semilla de $" . number_format($recommendedLimit, 2) . "** (30% de su promedio de compras).";
            } else {
                $statusText = $score >= 85 ? "Riesgo Bajo (Excelente Pagador)" : ($score >= 60 ? "Riesgo Medio (Pagos Regulares)" : "Riesgo Alto (Moroso)");
                $aiAnalysis = "El cliente posee un perfil de **{$statusText}**. Tiene un Score de Puntualidad de **{$score}/100** con un retraso promedio de **" . number_format($averageMoraDays, 1) . " días** por factura. Su ticket promedio de contado es de **$" . number_format($averageCashPurchase, 2) . "**. Cupo de crédito sugerido: **$" . number_format($recommendedLimit, 2) . "**.";
            }
        }

        // Persistir resultados
        $customer->update([
            'credit_score' => $score,
            'credit_status' => $status,
            'credit_limit_recommended' => $recommendedLimit,
            'last_credit_scoring_at' => now(),
        ]);

        return [
            'credit_score' => $score,
            'credit_status' => $status,
            'credit_limit_recommended' => $recommendedLimit,
            'days_since_registration' => $daysSinceRegistration,
            'cash_purchase_count' => $cashPurchaseCount,
            'average_cash_purchase' => $averageCashPurchase,
            'ai_analysis' => $aiAnalysis,
        ];
    }
}
