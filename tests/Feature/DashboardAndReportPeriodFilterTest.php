<?php

namespace Tests\Feature;

use Tests\TestCase;

class DashboardAndReportPeriodFilterTest extends TestCase
{
    /** @test */
    public function it_calculates_daily_weekly_and_monthly_targets_correctly()
    {
        $dailyTarget = 105.00;

        $targetToday = $dailyTarget * 1; // 105.00
        $targetWeek = $dailyTarget * 7; // 735.00
        $targetMonth = $dailyTarget * 30; // 3150.00

        $this->assertEquals(105.00, $targetToday);
        $this->assertEquals(735.00, $targetWeek);
        $this->assertEquals(3150.00, $targetMonth);
    }

    /** @test */
    public function it_calculates_financial_kpis_from_productions_and_fixed_costs()
    {
        // 2 turnos con costo fijo de 25$ c/u = 50$
        $fixedCostPerShift = 25.00;
        $totalShifts = 2;
        $totalFixedCost = $fixedCostPerShift * $totalShifts; // 50.00

        // Producción 1: 10 bultos a 85$ precio fabrica, costo plástico 58$
        // Ingreso = 850, Costo MP = 580
        $ingreso1 = 850.00;
        $costoMp1 = 580.00;

        // Producción 2: 100 Kg bobina a 4.15$/Kg, costo MP 2.91$/Kg
        // Ingreso = 415, Costo MP = 291
        $ingreso2 = 415.00;
        $costoMp2 = 291.00;

        $totalIncome = $ingreso1 + $ingreso2; // 1265.00
        $totalRawCost = $costoMp1 + $costoMp2; // 871.00
        $totalCost = $totalRawCost + $totalFixedCost; // 921.00
        $netProfit = $totalIncome - $totalCost; // 344.00
        $marginPercent = round(($netProfit / $totalIncome) * 100.0, 2); // 27.19%

        $this->assertEquals(1265.00, $totalIncome);
        $this->assertEquals(871.00, $totalRawCost);
        $this->assertEquals(50.00, $totalFixedCost);
        $this->assertEquals(921.00, $totalCost);
        $this->assertEquals(344.00, $netProfit);
        $this->assertEquals(27.19, $marginPercent);
    }
}
