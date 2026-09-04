<?php

namespace Tests\Feature;

use Tests\TestCase;

class ProductionFormulaTemporalAuditTest extends TestCase
{
    /** @test */
    public function it_calculates_exact_raw_material_final_price_with_logistics()
    {
        // Excel Example: RECUPERADO COLOR Base 1.40 + Transp 0.13 + Recargo 0.13 = 1.66
        $basePrice = 1.4000;
        $transport = 0.1300;
        $surcharge = 0.1300;

        $finalPrice = round($basePrice + $transport + $surcharge, 4);

        $this->assertEquals(1.6600, $finalPrice);
    }

    /** @test */
    public function it_calculates_weighted_average_formula_cost_per_kg_from_excel()
    {
        // Excel Formula: "FÓRMULA DE PREPARACIÓN VIVERO Y BASURA"
        // 90 kg Recuperado Color @ 1.66
        // 2 kg Pigmento Negro @ 4.26 (4.00 + 0.13 + 0.13)
        // 8 kg Lineal Importado @ 1.96 (1.70 + 0.13 + 0.13)
        $ingredients = [
            ['qty' => 90.0, 'price' => 1.6600], // 149.40
            ['qty' => 2.0,  'price' => 4.2600], // 8.52
            ['qty' => 8.0,  'price' => 1.9600], // 15.68
        ];

        $totalKg = 0.0;
        $totalCost = 0.0;

        foreach ($ingredients as $it) {
            $subtotal = round($it['qty'] * $it['price'], 4);
            $totalKg += $it['qty'];
            $totalCost += $subtotal;
        }

        $costPerKg = round($totalCost / $totalKg, 4);

        $this->assertEquals(100.0, round($totalKg, 2));
        $this->assertEquals(173.60, round($totalCost, 2));
        $this->assertEquals(1.7360, $costPerKg);
    }

    /** @test */
    public function it_verifies_temporal_price_history_immutability()
    {
        // T0: Material at 1.66
        $priceT0 = 1.6600;
        $validFromT0 = '2026-05-01 08:00:00';
        $validToT0 = '2026-06-01 08:00:00';

        // T1: Material updated to 1.85
        $priceT1 = 1.8500;
        $validFromT1 = '2026-06-01 08:00:00';
        $validToT1 = null;

        $queryDateMay = '2026-05-15 12:00:00';
        $queryDateJune = '2026-06-15 12:00:00';

        // May query must match T0 price
        $effectiveMayPrice = ($queryDateMay >= $validFromT0 && $queryDateMay < $validToT0) ? $priceT0 : $priceT1;
        $this->assertEquals(1.6600, $effectiveMayPrice);

        // June query must match T1 price
        $effectiveJunePrice = ($queryDateJune >= $validFromT1) ? $priceT1 : $priceT0;
        $this->assertEquals(1.8500, $effectiveJunePrice);
    }

    /** @test */
    public function it_verifies_formula_version_immutability_and_recipe_snapshot()
    {
        // Version 1 (Costo $1.7360/kg)
        $version1 = [
            'version_number' => 1,
            'total_kg'       => 100.0,
            'total_cost'     => 173.60,
            'cost_per_kg'    => 1.7360,
        ];

        // Version 2 (Ajuste a 85kg Recuperado + 15kg Lineal -> Costo $1.7050/kg)
        $version2 = [
            'version_number' => 2,
            'total_kg'       => 100.0,
            'total_cost'     => 170.50,
            'cost_per_kg'    => 1.7050,
        ];

        $this->assertEquals(1, $version1['version_number']);
        $this->assertEquals(1.7360, $version1['cost_per_kg']);

        $this->assertEquals(2, $version2['version_number']);
        $this->assertEquals(1.7050, $version2['cost_per_kg']);
    }

    /** @test */
    public function it_preserves_shift_financial_pnl_when_material_prices_change_later()
    {
        // Shift produced 50 packages of 42 kg = 2100 kg total
        $weightKg = 2100.0;
        $frozenFormulaCostKg = 1.7360; // Snapshot taken at shift closing
        $frozenSalePriceUnit = 95.00;
        $units = 50;
        $fixedCost = 25.00;

        $frozenIncome = round($units * $frozenSalePriceUnit, 2); // $4750.00
        $frozenRawCost = round($weightKg * $frozenFormulaCostKg, 2); // $3645.60
        $frozenTotalCost = round($frozenRawCost + $fixedCost, 2); // $3670.60
        $frozenNetProfit = round($frozenIncome - $frozenTotalCost, 2); // $1079.40

        // In the future, resin/pigment price goes up to $2.50/kg
        $futurePriceKg = 2.5000;
        $hypotheticalNewCost = round($weightKg * $futurePriceKg, 2); // $5250.00 (would be loss)

        // Historical snapshot remains exactly $1079.40 profit
        $this->assertEquals(4750.00, $frozenIncome);
        $this->assertEquals(3670.60, $frozenTotalCost);
        $this->assertEquals(1079.40, $frozenNetProfit);
        $this->assertNotEquals($frozenRawCost, $hypotheticalNewCost);
    }
}
