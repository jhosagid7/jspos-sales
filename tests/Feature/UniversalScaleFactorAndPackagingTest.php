<?php

namespace Tests\Feature;

use App\Models\BagProduct;
use Tests\TestCase;

class UniversalScaleFactorAndPackagingTest extends TestCase
{
    /** @test */
    public function it_calculates_fractional_packaging_like_millar_g_100_units_correctly()
    {
        $product = new BagProduct([
            'name'                   => 'BOLSA MILLAR/G 100 UNIDS',
            'sale_unit'              => 'MILLAR/G',
            'width_inch'             => 29.00,
            'length_inch'            => 45.00,
            'gauge_caliber'          => 0.0100,
            'millar_per_bulto'       => 0.1000, // 0.1 millar = 100 unidades
            'is_variable_quantity'   => false,
            'price'                  => 4.33,
            'target_units_per_shift' => 200,
        ]);

        // 1. Peso Teórico de 1 Millar = 29 * 45 * 0.01 = 13.05 kg
        $pesoTeoricoMillar = $product->calculatePhysicalWeight();
        $this->assertEquals(13.0500, $pesoTeoricoMillar);

        // 2. Peso Real de la Unidad de Venta (Paquete 100 unidades) = 13.05 * 0.1 = 1.305 kg
        $pesoReal = $product->calculateRealTotalWeight(true);
        $this->assertEquals(1.3050, $pesoReal);

        // 3. Costo de Materia Prima con Resina/Fórmula $2.910/kg = 1.305 * 2.910 = 3.79755 ($3.7976)
        $costo = $product->calculateRawMaterialCost(2.9100);
        $this->assertEquals(3.7976, round($costo, 4));

        // 4. Utilidad Unitaria = $4.33 - $3.79755 = $0.53245
        $utilidadUnitaria = 4.33 - 3.79755;
        $this->assertEquals(0.5325, round($utilidadUnitaria, 4));

        // 5. Utilidad Diaria con Cuota de 200 paquetes = $0.53245 * 200 = $106.49 USD
        $utilidadDiaria = $product->calculateDailyProfitFromFactoryPrice(4.33, 200, $costo);
        $this->assertEquals(106.48, round($utilidadDiaria, 2));
    }

    /** @test */
    public function it_calculates_variable_bultos_and_packs_accurately_with_universal_scale()
    {
        // Producto base: 24 x 36 x 0.0035 = 3.0240 kg por millar
        $baseData = [
            'width_inch'           => 24.00,
            'length_inch'          => 36.00,
            'gauge_caliber'        => 0.0035,
            'is_variable_quantity' => false,
        ];

        // Caso A: Bulto de 20 Millares (factor = 20.0) -> 3.024 * 20 = 60.48 kg
        $bulto20 = new BagProduct(array_merge($baseData, [
            'sale_unit'        => 'BULTO',
            'millar_per_bulto' => 20.0,
        ]));
        $this->assertEquals(60.4800, $bulto20->calculateRealTotalWeight(true));

        // Caso B: Bulto de 3 Millares (factor = 3.0) -> 3.024 * 3 = 9.072 kg
        $bulto3 = new BagProduct(array_merge($baseData, [
            'sale_unit'        => 'BULTO',
            'millar_per_bulto' => 3.0,
        ]));
        $this->assertEquals(9.0720, $bulto3->calculateRealTotalWeight(true));

        // Caso C: Paquete de 500 Unidades (factor = 0.5) -> 3.024 * 0.5 = 1.512 kg
        $pack500 = new BagProduct(array_merge($baseData, [
            'sale_unit'        => 'PAQUETE 500',
            'millar_per_bulto' => 0.5,
        ]));
        $this->assertEquals(1.5120, $pack500->calculateRealTotalWeight(true));

        // Caso D: Paquete de 750 Unidades (factor = 0.75) -> 3.024 * 0.75 = 2.268 kg
        $pack750 = new BagProduct(array_merge($baseData, [
            'sale_unit'        => 'PAQUETE 750',
            'millar_per_bulto' => 0.75,
        ]));
        $this->assertEquals(2.2680, $pack750->calculateRealTotalWeight(true));

        // Caso E: Bobina de Kilo Variable -> Siempre 1.0000 kg
        $bobina = new BagProduct([
            'name'                 => 'BOBINA VARIABLE',
            'is_variable_quantity' => true,
        ]);
        $this->assertEquals(1.0000, $bobina->calculateRealTotalWeight(true));
    }
}
