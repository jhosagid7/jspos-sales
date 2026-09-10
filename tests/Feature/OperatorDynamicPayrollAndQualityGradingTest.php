<?php

namespace Tests\Feature;

use App\Models\BagMachine;
use App\Models\BagProduct;
use App\Models\BagProduction;
use App\Models\BagShift;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OperatorDynamicPayrollAndQualityGradingTest extends TestCase
{
    use RefreshDatabase;

    protected $operator;
    protected $supervisor;
    protected $machine;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();

        config(['app.installed' => true]);

        $this->mock(\App\Services\LicenseService::class, function ($mock) {
            $mock->shouldReceive('checkLicense')->andReturn([
                'status'         => 'active',
                'days_remaining' => 30,
                'modules'        => [],
                'max_devices'    => 10,
            ]);
            $mock->shouldReceive('getClientId')->andReturn('test-client-id');
        });

        $this->operator = User::create([
            'name'                 => 'Pedro Operario',
            'email'                => 'pedro.operario@jsbolsas.test',
            'password'             => bcrypt('password'),
            'role'                 => 'operario',
            'weekly_salary'        => 90.00,
            'work_days_per_week'   => 6,
            'pay_partial_packages' => false,
        ]);

        $this->supervisor = User::create([
            'name'     => 'Carlos Supervisor',
            'email'    => 'carlos.supervisor@jsbolsas.test',
            'password' => bcrypt('password'),
            'role'     => 'supervisor',
        ]);

        $this->machine = BagMachine::create([
            'code'      => 'EXT01',
            'name'      => 'Extrusora Principal',
            'type'      => 'extrusora',
            'is_active' => true,
        ]);

        $this->product = BagProduct::create([
            'name'                   => 'Bolsa Plastica 50x70',
            'sku'                    => 'BP-5070',
            'millar_per_bulto'       => 20.0000,
            'unit_weight_kg'         => 1.1000,
            'real_total_weight_kg'   => 22.0000,
            'target_units_per_shift' => 3,
            'cost'                   => 25.0000,
            'price'                  => 35.0000,
            'is_variable_quantity'   => false,
            'is_active'              => true,
        ]);
    }

    public function test_daily_salary_calculation_based_on_work_days_5_6_7(): void
    {
        // 5 Days (Mon-Fri)
        $this->operator->work_days_per_week = 5;
        $this->assertEquals(18.00, round($this->operator->daily_salary, 2));

        // 6 Days (Mon-Sat)
        $this->operator->work_days_per_week = 6;
        $this->assertEquals(15.00, round($this->operator->daily_salary, 2));

        // 7 Days (Continuous)
        $this->operator->work_days_per_week = 7;
        $this->assertEquals(12.86, round($this->operator->daily_salary, 2));
    }

    public function test_labor_tariffs_by_product_target_and_millar(): void
    {
        // 6 days = $15.00/day. Target = 3 bultos -> $5.00/bulto. Millar/bulto = 20 -> $0.25/millar.
        $this->operator->work_days_per_week = 6;
        $tariffs = $this->operator->calculateLaborTariff($this->product);

        $this->assertEquals(15.00, round($tariffs['daily_salary'], 2));
        $this->assertEquals(5.00, round($tariffs['package_tariff'], 2));
        $this->assertEquals(0.25, round($tariffs['fraction_tariff'], 2));
    }

    public function test_automatic_bulto_and_fraction_breakdown(): void
    {
        $breakdown = $this->product->calculateBreakdown(50.0); // 50 millares
        $this->assertEquals(2.0, $breakdown['completed_packages']);
        $this->assertEquals(10.0, $breakdown['fractional_units']);
        $this->assertFalse($breakdown['is_package_completed']);

        $breakdownClosed = $this->product->calculateBreakdown(60.0); // 60 millares (3 bultos exactos)
        $this->assertEquals(3.0, $breakdownClosed['completed_packages']);
        $this->assertEquals(0.0, $breakdownClosed['fractional_units']);
        $this->assertTrue($breakdownClosed['is_package_completed']);
    }

    public function test_weight_quality_grading_a_b_c(): void
    {
        // Theoretical weight: 1 bulto of 20 millares = 22.00 kg.
        // Grade B: Optimal weight (+/- 3%)
        $qualityB = $this->product->calculateWeightQualityGrade(22.15, 1.0, 0.0);
        $this->assertEquals('B', $qualityB['grade']);
        $this->assertEquals(0.68, $qualityB['deviation_percent']);

        // Grade A: Overweight (> +3%)
        $qualityA = $this->product->calculateWeightQualityGrade(23.50, 1.0, 0.0);
        $this->assertEquals('A', $qualityA['grade']);
        $this->assertEquals(6.82, $qualityA['deviation_percent']);

        // Grade C: Underweight (< -3%)
        $qualityC = $this->product->calculateWeightQualityGrade(20.00, 1.0, 0.0);
        $this->assertEquals('C', $qualityC['grade']);
        $this->assertEquals(-9.09, $qualityC['deviation_percent']);
    }

    public function test_collaborative_fraction_completion_releases_retained_pay(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $prod1 = BagProduction::create([
            'bag_shift_id'             => $shift->id,
            'user_id'                  => $this->operator->id,
            'product_id'               => $this->product->id,
            'quantity'                 => 10, // 10 millares sueltos (0.5 bulto)
            'weight'                   => 11.0000,
            'recorded_at'              => now(),
            'completed_packages_count' => 0,
            'fractional_units'         => 10,
            'is_package_completed'     => false,
            'labor_earned_amount'      => 2.50,
            'labor_retained_amount'    => 2.50,
        ]);

        $operator2 = User::create([
            'name'                 => 'Juan Operario 2',
            'email'                => 'juan2@jsbolsas.test',
            'password'             => bcrypt('password'),
            'role'                 => 'operario',
            'weekly_salary'        => 90.00,
            'work_days_per_week'   => 6,
            'pay_partial_packages' => false,
        ]);

        $prod2 = BagProduction::create([
            'bag_shift_id'             => $shift->id,
            'user_id'                  => $operator2->id,
            'product_id'               => $this->product->id,
            'quantity'                 => 10, // 10 millares que completan el bulto
            'weight'                   => 11.0000,
            'recorded_at'              => now(),
            'completed_packages_count' => 0,
            'fractional_units'         => 10,
            'is_package_completed'     => true,
            'labor_earned_amount'      => 2.50,
            'labor_retained_amount'    => 0.00,
        ]);

        // Complete fraction
        $prod1->completeFractionWith($prod2);

        $this->assertTrue((bool)$prod1->fresh()->is_package_completed);
        $this->assertEquals(0.00, (float)$prod1->fresh()->labor_retained_amount);
        $this->assertEquals($prod2->id, $prod1->fresh()->completed_by_production_id);
    }

    public function test_sync_productions_api_computes_payroll_and_quality_grades(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
            'sync_id'    => 'SHIFT-SYNC-001',
        ]);

        // Sync 50 millares with 55.25 kg
        $payload = [
            'shift_id'    => $shift->id,
            'productions' => [
                [
                    'sync_id'     => 'PROD-SYNC-001',
                    'product_id'  => $this->product->id,
                    'quantity'    => 50.0, // 2 bultos + 10 millares
                    'weight'      => 55.25,
                    'recorded_at' => now()->toDateTimeString(),
                    'status'      => 'pending_review',
                ],
            ],
        ];

        $response = $this->actingAs($this->operator)
            ->postJson('/api/bag-factory/productions/sync', $payload);

        $response->assertStatus(200)
            ->assertJson([
                'success'      => true,
                'synced_count' => 1,
            ]);

        $createdProd = BagProduction::where('sync_id', 'PROD-SYNC-001')->first();
        $this->assertNotNull($createdProd);
        $this->assertEquals(2.0, (float)$createdProd->completed_packages_count);
        $this->assertEquals(10.0, (float)$createdProd->fractional_units);
        $this->assertFalse((bool)$createdProd->is_package_completed);
        $this->assertEquals(12.50, (float)$createdProd->labor_earned_amount);
        $this->assertEquals(2.50, (float)$createdProd->labor_retained_amount);
        $this->assertNotNull($createdProd->weight_quality_grade);
    }

    public function test_operator_earnings_endpoint(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        BagProduction::create([
            'bag_shift_id'             => $shift->id,
            'user_id'                  => $this->operator->id,
            'product_id'               => $this->product->id,
            'quantity'                 => 50,
            'weight'                   => 55.0000,
            'recorded_at'              => now(),
            'completed_packages_count' => 2,
            'fractional_units'         => 10,
            'is_package_completed'     => false,
            'labor_earned_amount'      => 12.50,
            'labor_retained_amount'    => 2.50,
        ]);

        $response = $this->actingAs($this->operator)
            ->getJson('/api/bag-factory/operator/earnings');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'user'    => [
                    'name'               => 'Pedro Operario',
                    'weekly_salary'      => 90.00,
                    'work_days_per_week' => 6,
                    'daily_salary'       => 15.00,
                ],
                'shift'   => [
                    'earned'    => 12.50,
                    'available' => 10.00,
                    'retained'  => 2.50,
                ],
            ]);
    }

    public function test_complete_fraction_api_releases_retained_amount(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $prod1 = BagProduction::create([
            'bag_shift_id'             => $shift->id,
            'user_id'                  => $this->operator->id,
            'product_id'               => $this->product->id,
            'quantity'                 => 10,
            'weight'                   => 11.0000,
            'recorded_at'              => now(),
            'completed_packages_count' => 0,
            'fractional_units'         => 10,
            'is_package_completed'     => false,
            'labor_earned_amount'      => 2.50,
            'labor_retained_amount'    => 2.50,
        ]);

        $prod2 = BagProduction::create([
            'bag_shift_id'             => $shift->id,
            'user_id'                  => $this->operator->id,
            'product_id'               => $this->product->id,
            'quantity'                 => 10,
            'weight'                   => 11.0000,
            'recorded_at'              => now(),
            'completed_packages_count' => 0,
            'fractional_units'         => 10,
            'is_package_completed'     => true,
            'labor_earned_amount'      => 2.50,
            'labor_retained_amount'    => 0.00,
        ]);

        $response = $this->actingAs($this->supervisor)
            ->postJson('/api/bag-factory/supervisor/fractions/complete', [
                'production_id'            => $prod1->id,
                'completing_production_id' => $prod2->id,
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertEquals(0.00, (float)$prod1->fresh()->labor_retained_amount);
        $this->assertTrue((bool)$prod1->fresh()->is_package_completed);
    }

    public function test_ticket_data_api_includes_grade_and_batch_code(): void
    {
        $shift = BagShift::create([
            'user_id'    => $this->operator->id,
            'machine_id' => $this->machine->id,
            'shift_type' => 'diurno',
            'start_time' => now(),
            'status'     => 'open',
        ]);

        $prod = BagProduction::create([
            'bag_shift_id'             => $shift->id,
            'user_id'                  => $this->operator->id,
            'product_id'               => $this->product->id,
            'quantity'                 => 1,
            'weight'                   => 22.0000,
            'weight_quality_grade'     => 'B',
            'weight_deviation_percent' => 0.00,
            'recorded_at'              => now(),
            'status'                   => 'approved',
            'qr_code'                  => 'PKG-QUALITY-001',
        ]);

        $response = $this->actingAs($this->supervisor)
            ->getJson("/api/bag-factory/supervisor/ticket/{$prod->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data'    => [
                    'qr_code'                  => 'PKG-QUALITY-001',
                    'product_name'             => 'Bolsa Plastica 50x70',
                    'weight_quality_grade'     => 'B',
                    'weight_deviation_percent' => 0.00,
                ],
            ]);

        $this->assertStringContainsString('-EXT01-B', $response->json('data.effective_batch_code'));
    }
}
