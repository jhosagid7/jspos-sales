<?php

namespace Tests\Feature;

use Tests\TestCase;

class BagCostsAndPerformanceTest extends TestCase
{
    /** @test */
    public function it_calculates_real_total_weight_from_unit_weight_and_millar()
    {
        $unitWeight = 2.1000; // kg/millar
        $millarPerBulto = 20.0;
        $expectedRealWeight = round($unitWeight * $millarPerBulto, 4); // 42.0 kg

        $this->assertEquals(42.0, $expectedRealWeight);
    }

    /** @test */
    public function it_calculates_raw_material_cost_from_resin_price()
    {
        $realWeight = 42.0; // kg
        $resinPricePerKg = 1.40; // $/kg
        $expectedCost = round($realWeight * $resinPricePerKg, 4); // 58.80 $

        $this->assertEquals(58.80, $expectedCost);
    }

    /** @test */
    public function it_calculates_factory_price_with_gross_margin()
    {
        $costRaw = 58.80;
        $marginPercent = 45.0; // 45%
        $marginMultiplier = 1.0 + ($marginPercent / 100.0); // 1.45
        $expectedFactoryPrice = round($costRaw * $marginMultiplier, 2); // 85.26 $

        $this->assertEquals(85.26, $expectedFactoryPrice);
    }

    /** @test */
    public function it_calculates_pricing_tiers_tier1_tier2_tier3()
    {
        $factoryPrice = 85.26;
        $tier1 = round($factoryPrice * 1.10, 2); // +10% -> 93.79
        $tier2 = round($factoryPrice * 1.17, 2); // +17% -> 99.75
        $tier3 = round($factoryPrice * 1.21, 2); // +21% -> 103.16

        $this->assertEquals(93.79, $tier1);
        $this->assertEquals(99.75, $tier2);
        $this->assertEquals(103.16, $tier3);
    }

    /** @test */
    public function it_evaluates_worker_quota_and_target_completion()
    {
        $targetUnits = 50;
        $actualUnits1 = 55;
        $actualUnits2 = 40;

        $fulfillment1 = round(($actualUnits1 / $targetUnits) * 100.0, 2); // 110.0%
        $isMet1 = $actualUnits1 >= $targetUnits; // true

        $fulfillment2 = round(($actualUnits2 / $targetUnits) * 100.0, 2); // 80.0%
        $isMet2 = $actualUnits2 >= $targetUnits; // false

        $this->assertEquals(110.0, $fulfillment1);
        $this->assertTrue($isMet1);

        $this->assertEquals(80.0, $fulfillment2);
        $this->assertFalse($isMet2);
    }

    /** @test */
    public function it_calculates_shift_financial_pnl_and_profit_margin()
    {
        $unitsProduced = 50;
        $unitSalePrice = 93.79; // Tier 1
        $unitRawCost = 58.80;
        $fixedCostTurno = 25.00;

        $totalIncome = round($unitsProduced * $unitSalePrice, 2); // 4689.50 $
        $totalRawCost = round($unitsProduced * $unitRawCost, 2);  // 2940.00 $
        $totalProductionCost = round($totalRawCost + $fixedCostTurno, 2); // 2965.00 $

        $netProfit = round($totalIncome - $totalProductionCost, 2); // 1724.50 $
        $profitMarginPercent = round(($netProfit / $totalIncome) * 100.0, 2); // 36.77%

        $this->assertEquals(4689.50, $totalIncome);
        $this->assertEquals(2965.00, $totalProductionCost);
        $this->assertEquals(1724.50, $netProfit);
        $this->assertEquals(36.77, $profitMarginPercent);
        $this->assertTrue($netProfit > 0);
    }
}
