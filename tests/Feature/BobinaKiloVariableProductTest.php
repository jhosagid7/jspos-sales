<?php

namespace Tests\Feature;

use App\Models\BagProduct;
use Tests\TestCase;

class BobinaKiloVariableProductTest extends TestCase
{
    /** @test */
    public function it_validates_bobina_variable_product_logic_and_costing_per_kg(): void
    {
        // 1. Instanciar un producto tipo Bobina con is_variable_quantity = true
        $product = new BagProduct([
            'name'                   => 'BOBINA DE BAMBI (3.5CM X1KG) C-18',
            'category'               => 'Bobinas',
            'sale_unit'              => 'KG',
            'millar_per_bulto'       => 1.0000,
            'width_inch'             => null,
            'length_inch'            => null,
            'gauge_caliber'          => null,
            'unit_weight_kg'         => 1.0000,
            'real_total_weight_kg'   => 1.0000,
            'sku'                    => 'BOB-BAM-35',
            'target_units_per_shift' => 3, // Meta de 3 bobinas/escalas
            'target_daily_profit'    => 105.00,
            'is_variable_quantity'   => true,
            'is_active'              => true,
        ]);

        // 2. Pesos unitarios y reales deben ser estrictamente 1.0000
        $this->assertEquals(1.0000, $product->calculatePhysicalWeight());
        $this->assertEquals(1.0000, $product->calculateRealTotalWeight());

        // 3. Costo con $/KG de mezcla = $2.9225 (Fórmula Bobinas PET)
        $costoKg = 2.9225;
        $costoCalculado = $product->calculateRawMaterialCost($costoKg);
        $this->assertEquals(2.9225, $costoCalculado);

        // 4. Calculador inverso de precio de fábrica para Bobinas:
        // FABRICA = COSTO + (UtilidadDeseada / PRODUCCION) = 2.9225 + (105 / 3) = $37.92 / KG
        $precioFabrica = $product->simulateFactoryPriceFromDailyTarget(105.00, 3, $costoCalculado);
        $this->assertEquals(37.92, $precioFabrica);

        // 5. Escala de precios por KG:
        // Tier 1 (+10%): 37.92 * 1.10 = 41.71
        // Tier 2 (+17%): 37.92 * 1.17 = 44.37
        // Tier 3 (+21%): 37.92 * 1.21 = 45.88
        $tiers = $product->calculateTiersFromFactoryPrice($precioFabrica);
        $this->assertEquals(41.71, $tiers['tier_1']);
        $this->assertEquals(44.37, $tiers['tier_2']);
        $this->assertEquals(45.88, $tiers['tier_3']);

        // 6. Márgenes de referencia del Excel:
        $margins = $product->calculateReferenceMargins($costoCalculado);
        $this->assertEquals(4.09, $margins['m_40']); // 2.9225 * 1.40
        $this->assertEquals(4.24, $margins['m_45']); // 2.9225 * 1.45
        $this->assertEquals(4.38, $margins['m_50']); // 2.9225 * 1.50
        $this->assertEquals(4.82, $margins['m_60']); // 2.9225 * 1.65
        $this->assertEquals(5.06, $margins['m_2']);  // 2.9225 * 1.73
        $this->assertEquals(5.85, $margins['m_1']);  // 2.9225 * 2.00

        // 7. Utilidad diaria del turno: (37.92 - 2.9225) * 3 = $104.99 ~ $105.00
        $utilDia = $product->calculateDailyProfitFromFactoryPrice($precioFabrica, 3, $costoCalculado);
        $this->assertEquals(104.99, $utilDia);
    }
}
