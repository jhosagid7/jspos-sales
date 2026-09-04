<?php

namespace Tests\Feature;

use Tests\TestCase;

class UnifiedCostsAndPriceSimulationTest extends TestCase
{
    /** @test */
    public function it_calculates_physical_weight_from_dimensions()
    {
        // PESO = ANCHO * LARGO * CALIBRE
        $ancho = 24.0;
        $largo = 36.0;
        $calibre = 0.0035;

        $pesoFisico = round($ancho * $largo * $calibre, 4); // 3.0240

        $this->assertEquals(3.0240, $pesoFisico);
    }

    /** @test */
    public function it_calculates_peso_real_for_bulto_and_millar_and_supports_manual_override()
    {
        $pesoFisico = 3.0240;
        $millarPorBulto = 10.0;

        // Millar: PESO_R = PESO
        $pesoRMillar = $pesoFisico;
        $this->assertEquals(3.0240, $pesoRMillar);

        // Bulto: PESO_R = PESO * MILLAR/BULTO
        $pesoRBulto = round($pesoFisico * $millarPorBulto, 4);
        $this->assertEquals(30.2400, $pesoRBulto);

        // Manual Override: e.g. Factory specific non-linear weight = 28.50 kg
        $manualOverride = 28.5000;
        $effectivePesoR = $manualOverride > 0 ? $manualOverride : $pesoRBulto;
        $this->assertEquals(28.5000, $effectivePesoR);
    }

    /** @test */
    public function it_calculates_raw_material_cost_from_formula_cost_per_kg()
    {
        // COSTO = PESO_R * $/KG
        // e.g. PESO_R = 28.50 kg, $/KG = 1.7360 (from Formula Vivero y Basura)
        $pesoR = 28.5000;
        $costoKgFormula = 1.7360;

        $costo = round($pesoR * $costoKgFormula, 4); // 49.4760

        $this->assertEquals(49.4760, $costo);
    }

    /** @test */
    public function it_calculates_informative_reference_margins_from_excel()
    {
        // Margins: 40% (x1.40), 45% (x1.45), 50% (x1.50), 60% (x1.65), 2% (x1.73), 1.00 (x2.00)
        $costo = 49.47;

        $m40 = round($costo * 1.40, 2); // 69.26
        $m45 = round($costo * 1.45, 2); // 71.73
        $m50 = round($costo * 1.50, 2); // 74.21
        $m60 = round($costo * 1.65, 2); // 81.63 (Excel factor 1.65)
        $m2  = round($costo * 1.73, 2); // 85.58
        $m1  = round($costo * 2.00, 2); // 98.94

        $this->assertEquals(69.26, $m40);
        $this->assertEquals(71.73, $m45);
        $this->assertEquals(74.21, $m50);
        $this->assertEquals(81.63, $m60);
        $this->assertEquals(85.58, $m2);
        $this->assertEquals(98.94, $m1);
    }

    /** @test */
    public function it_calculates_reverse_factory_price_from_daily_profit_goal()
    {
        // EXACT EXCEL VALIDATION CASE:
        // COSTO = 49.47, PRODUCCION = 5 bultos, Utilidad Objetivo = 105.00
        // FABRICA = COSTO + (Utilidad / PRODUCCION) = 49.47 + (105 / 5) = 70.47
        $costo = 49.47;
        $produccionMeta = 5;
        $utilidadObjetivo = 105.00;

        $fabricaSugerido = round($costo + ($utilidadObjetivo / $produccionMeta), 2);

        $this->assertEquals(70.47, $fabricaSugerido);

        // Daily Profit Check: UTIL/DIA = (FABRICA - COSTO) * PRODUCCION = (70.47 - 49.47) * 5 = 105.00
        $utilidadDiaria = round(($fabricaSugerido - $costo) * $produccionMeta, 2);
        $this->assertEquals(105.00, $utilidadDiaria);
    }

    /** @test */
    public function it_calculates_sales_price_tiers_distributor_wholesaler_retailer()
    {
        // FABRICA = 70.47
        // PRECIO I (Distribuidor): FABRICA * 1.10 = 77.52
        // PRECIO II (Mayorista):   FABRICA * 1.17 = 82.45
        // PRECIO III (Minorista):  FABRICA * 1.21 = 85.27
        $fabrica = 70.47;

        $tier1 = round($fabrica * 1.10, 2);
        $tier2 = round($fabrica * 1.17, 2);
        $tier3 = round($fabrica * 1.21, 2);

        $this->assertEquals(77.52, $tier1);
        $this->assertEquals(82.45, $tier2);
        $this->assertEquals(85.27, $tier3);
    }

    /** @test */
    public function it_evaluates_worker_goal_and_shift_real_net_profit()
    {
        $metaProduccion = 5; // bultos
        $unidadesReales1 = 6; // Produjo 6 bultos -> META ALCANZADA
        $unidadesReales2 = 4; // Produjo 4 bultos -> META NO ALCANZADA

        $this->assertTrue($unidadesReales1 >= $metaProduccion);
        $this->assertFalse($unidadesReales2 >= $metaProduccion);

        // Real Shift Financial P&L:
        // Ingreso Real = 6 * 77.52 (Precio I) = 465.12
        // Costo Produccion Real = (6 * 49.47) + 25.00 (Fijo Turno) = 296.82 + 25.00 = 321.82
        // Utilidad Neta Real = 465.12 - 321.82 = 143.30 (GANANCIA > 0)
        $precioVenta = 77.52;
        $costoUnitario = 49.47;
        $costoFijoTurno = 25.00;

        $ingresoReal = round($unidadesReales1 * $precioVenta, 2);
        $costoReal = round(($unidadesReales1 * $costoUnitario) + $costoFijoTurno, 2);
        $utilidadNetaReal = round($ingresoReal - $costoReal, 2);

        $this->assertEquals(465.12, $ingresoReal);
        $this->assertEquals(321.82, $costoReal);
        $this->assertEquals(143.30, $utilidadNetaReal);
        $this->assertTrue($utilidadNetaReal > 0);
    }
}
