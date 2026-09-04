<?php

namespace Tests\Feature;

use Tests\TestCase;

class PromptV2FormulasAndRawMaterialsTest extends TestCase
{
    /** @test */
    public function it_validates_the_17_raw_materials_pricing_with_logistics()
    {
        $materials = [
            '7000F'             => ['base' => 2.55, 'expected' => 2.81],
            '3003'              => ['base' => 2.70, 'expected' => 2.96],
            '0348'              => ['base' => 2.70, 'expected' => 2.96],
            '11PG4'             => ['base' => 2.55, 'expected' => 2.81],
            '11PG1'             => ['base' => 2.55, 'expected' => 2.81],
            'AGUA PANELO'       => ['base' => 1.40, 'expected' => 1.66],
            'RECUPERADO COLOR'  => ['base' => 1.40, 'expected' => 1.66],
            'CHICLE'            => ['base' => 1.40, 'expected' => 1.66],
            'PIGMENTO'          => ['base' => 4.00, 'expected' => 4.26],
            'CRISTALINO'        => ['base' => 1.80, 'expected' => 2.06],
            'LINEAL'            => ['base' => 2.55, 'expected' => 2.81],
            'ORIGINAL BAJA'     => ['base' => 2.70, 'expected' => 2.96],
            'LINEAL DE BAJA'    => ['base' => 2.55, 'expected' => 2.81],
            'BAJA 0348'         => ['base' => 2.70, 'expected' => 2.96],
            'ORIGINAL ALTA'     => ['base' => 2.55, 'expected' => 2.81],
            'LINEAL DE ALTA'    => ['base' => 2.55, 'expected' => 2.81],
            'PIGMENTO BOUTIQUE' => ['base' => 6.24, 'expected' => 6.50],
        ];

        foreach ($materials as $name => $d) {
            $final = round($d['base'] + 0.13 + 0.13, 4);
            $this->assertEquals($d['expected'], $final, "Materia prima {$name} no coincide con el cálculo logístico.");
        }
    }

    /** @test */
    public function it_validates_the_7_excel_production_formulas_weighted_averages()
    {
        // 1. Vivero y Basura: 25kg REC_COLOR (1.66) + 2kg PIGMENTO (4.26) + 4kg CHICLE (1.66) -> $56.66 / 31kg = $1.8277/kg (~1.83)
        $vivero = (25.0 * 1.66 + 2.0 * 4.26 + 4.0 * 1.66) / (25.0 + 2.0 + 4.0);
        $this->assertEquals(1.83, round($vivero, 2));

        // 2. Hielo: 25kg 3003 (2.96) + 12.5kg LINEAL (2.81) -> $109.125 / 37.5kg = $2.9100/kg (~2.91)
        $hielo = (25.0 * 2.96 + 12.5 * 2.81) / (25.0 + 12.5);
        $this->assertEquals(2.91, round($hielo, 2));

        // 3. Original de Baja: 25kg ORIGINAL BAJA (2.96) + 12.5kg LINEAL DE BAJA (2.81) + 4kg 3003 (2.96) -> $120.965 / 41.5kg = $2.9148/kg (~2.91)
        $origBaja = (25.0 * 2.96 + 12.5 * 2.81 + 4.0 * 2.96) / (25.0 + 12.5 + 4.0);
        $this->assertEquals(2.91, round($origBaja, 2));

        // 4. Bobinas de PET: 25kg 3003 (2.96) + 12.5kg BAJA 0348 (2.96) + 12.5kg LINEAL (2.81) -> $146.125 / 50kg = $2.9225/kg (~2.92)
        $bobinasPet = (25.0 * 2.96 + 12.5 * 2.96 + 12.5 * 2.81) / (25.0 + 12.5 + 12.5);
        $this->assertEquals(2.92, round($bobinasPet, 2));

        // 5. Original de Alta: 25kg ORIGINAL ALTA (2.81) + 12.5kg LINEAL DE ALTA (2.81) -> $105.375 / 37.5kg = $2.8100/kg (~2.81)
        $origAlta = (25.0 * 2.81 + 12.5 * 2.81) / (25.0 + 12.5);
        $this->assertEquals(2.81, round($origAlta, 2));

        // 6. Bolsa de Plátano: 25kg AGUA PANELO (1.66) + 4kg CHICLE (1.66) + 4kg CRISTALINO (2.06) -> $56.38 / 33kg = $1.7085/kg (~1.71)
        $platano = (25.0 * 1.66 + 4.0 * 1.66 + 4.0 * 2.06) / (25.0 + 4.0 + 4.0);
        $this->assertEquals(1.71, round($platano, 2));

        // 7. Bolsa de Boutique: 25kg AGUA PANELO (1.66) + 4kg CHICLE (1.66) + 4kg CRISTALINO (2.06) + 3kg PIGMENTO BOUTIQUE (6.50) -> $75.88 / 36kg = $2.1078/kg (~2.11)
        $boutique = (25.0 * 1.66 + 4.0 * 1.66 + 4.0 * 2.06 + 3.0 * 6.50) / (25.0 + 4.0 + 4.0 + 3.0);
        $this->assertEquals(2.11, round($boutique, 2));
    }
}
