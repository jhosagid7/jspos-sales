<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\BagProduct;

class TheoreticalWeightAttributeTest extends TestCase
{
    /** @test */
    public function theoretical_weight_accessor_calculates_millar_weight()
    {
        $product = new BagProduct([
            'name' => 'Bolsa Basura 24x36 Calibre 35',
            'sku' => 'BOL-BAS-2436',
            'sale_unit' => 'BULTO',
            'width_inch' => 24.00,
            'length_inch' => 36.00,
            'gauge_caliber' => 0.0035,
            'millar_per_bulto' => 20.0000,
            'cost' => 0.00,
            'price' => 0.00,
            'is_variable_quantity' => false,
            'is_active' => true,
        ]);

        // Peso teorico del millar = 24 * 36 * 0.0035 = 3.0240
        $this->assertEquals(3.0240, $product->theoretical_weight_kg);
        
        // Peso real de la unidad de venta (bulto de 20 millares) = 3.0240 * 20 = 60.4800
        $this->assertEquals(60.4800, $product->real_total_weight_kg);
    }

    /** @test */
    public function theoretical_weight_for_fractional_100_bags_package()
    {
        $product = new BagProduct([
            'name' => 'Bolsa Jardineria 30x50 Calibre 87 (100 und)',
            'sku' => 'BOL-JARD-100',
            'sale_unit' => 'MILLAR/G',
            'width_inch' => 30.00,
            'length_inch' => 50.00,
            'gauge_caliber' => 0.0087,
            'millar_per_bulto' => 0.1000, // 100 unidades = 0.1 millar
            'cost' => 0.00,
            'price' => 0.00,
            'is_variable_quantity' => false,
            'is_active' => true,
        ]);

        // Peso teorico del millar = 30 * 50 * 0.0087 = 13.0500 kg
        $this->assertEquals(13.0500, $product->theoretical_weight_kg);

        // Peso real del paquete de 100 bolsas = 13.0500 * 0.1 = 1.3050 kg
        $this->assertEquals(1.3050, $product->real_total_weight_kg);
    }

    /** @test */
    public function theoretical_weight_for_bobina_variable()
    {
        $bobina = new BagProduct([
            'name' => 'Bobina Film Termoencogible',
            'sku' => 'BOB-TERMO-01',
            'sale_unit' => 'KG',
            'cost' => 1.80,
            'price' => 2.45,
            'is_variable_quantity' => true,
            'is_active' => true,
        ]);

        $this->assertEquals(1.0000, $bobina->theoretical_weight_kg);
        $this->assertEquals(1.0000, $bobina->real_total_weight_kg);
    }
}