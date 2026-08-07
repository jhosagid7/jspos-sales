<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\CommissionGoal;
use App\Models\Configuration;
use App\Services\GoalCommissionService;
use Livewire\Livewire;
use App\Livewire\Settings\CommissionGoalsManager;
use App\Livewire\Reports\GoalCommissionReport;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Carbon\Carbon;

class GoalCommissionSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;
    protected $sellerUser;
    protected $nonSellerUser;

    protected function setUp(): void
    {
        parent::setUp();

        Configuration::create([
            'business_name' => 'Test POS Store',
            'taxpayer_id' => 'J-99999999-0',
            'seller_assignment_mode' => 'both',
            'commission_calculation_mode' => 'tiered_goals',
        ]);

        Permission::firstOrCreate(['name' => 'reports.sales', 'guard_name' => 'web']);
        $roleAdmin = Role::findOrCreate('Admin', 'web');
        $roleAdmin->givePermissionTo(['reports.sales']);

        $roleSeller = Role::findOrCreate('Vendedor', 'web');

        $this->adminUser = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@test.com']);
        $this->adminUser->assignRole($roleAdmin);

        $this->sellerUser = User::factory()->create(['name' => 'Juan Vendedor', 'email' => 'juan@test.com']);
        $this->sellerUser->assignRole($roleSeller);

        $this->nonSellerUser = User::factory()->create(['name' => 'Pedro Operador', 'email' => 'pedro@test.com']);
    }

    public function test_can_create_commission_goal_and_assign_to_user()
    {
        $goal = CommissionGoal::create([
            'name' => 'Meta Mañanera',
            'target_amount' => 100.00,
            'reward_amount' => 2.00,
            'periodicity' => 'diaria',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('commission_goals', [
            'name' => 'Meta Mañanera',
            'target_amount' => 100.00,
            'reward_amount' => 2.00,
            'periodicity' => 'diaria',
        ]);

        $this->nonSellerUser->commissionGoals()->attach($goal->id);
        $this->assertTrue($this->nonSellerUser->commissionGoals()->where('commission_goal_id', $goal->id)->exists());
    }

    public function test_eligible_sellers_scope_filters_correctly()
    {
        $goal = CommissionGoal::create([
            'name' => 'Mini meta',
            'target_amount' => 375.00,
            'reward_amount' => 25.00,
            'periodicity' => 'semanal',
            'is_active' => true,
        ]);

        // nonSellerUser gets assigned a goal
        $this->nonSellerUser->commissionGoals()->attach($goal->id);

        $eligibleIds = User::eligibleSellers()->pluck('id')->toArray();

        // sellerUser has Vendedor role -> eligible
        $this->assertContains($this->sellerUser->id, $eligibleIds);

        // nonSellerUser has active assigned goal -> eligible
        $this->assertContains($this->nonSellerUser->id, $eligibleIds);
    }

    public function test_date_range_for_periodicities()
    {
        $refDate = Carbon::parse('2026-08-07'); // A Friday

        // Daily
        $daily = GoalCommissionService::getDateRangeForPeriodicity('diaria', $refDate);
        $this->assertEquals('2026-08-07 00:00:00', $daily['start']->format('Y-m-d H:i:s'));
        $this->assertEquals('2026-08-07 23:59:59', $daily['end']->format('Y-m-d H:i:s'));

        // Weekly
        $weekly = GoalCommissionService::getDateRangeForPeriodicity('semanal', $refDate);
        $this->assertEquals('2026-08-03 00:00:00', $weekly['start']->format('Y-m-d H:i:s')); // Monday
        $this->assertEquals('2026-08-09 23:59:59', $weekly['end']->format('Y-m-d H:i:s')); // Sunday
    }

    public function test_custom_weekly_start_and_end_days()
    {
        $refDate = Carbon::parse('2026-08-07'); // A Friday

        $goalFridayEnd = CommissionGoal::create([
            'name' => 'Meta Lunes a Viernes',
            'target_amount' => 100.00,
            'reward_amount' => 10.00,
            'periodicity' => 'semanal',
            'start_day_of_week' => 'lunes',
            'end_day_of_week' => 'viernes',
            'is_active' => true,
        ]);

        $range = GoalCommissionService::getDateRangeForPeriodicity('semanal', $refDate, $goalFridayEnd);
        $this->assertEquals('2026-08-03 00:00:00', $range['start']->format('Y-m-d H:i:s')); // Monday
        $this->assertEquals('2026-08-07 23:59:59', $range['end']->format('Y-m-d H:i:s')); // Friday
    }

    public function test_evaluates_goal_rewards_accurately()
    {
        $goal = CommissionGoal::create([
            'name' => 'Mini meta',
            'target_amount' => 375.00,
            'reward_amount' => 25.00,
            'periodicity' => 'semanal',
            'is_active' => true,
        ]);

        $this->sellerUser->commissionGoals()->attach($goal->id);

        $category = Category::create(['name' => 'Cat1']);
        $supplier = Supplier::create(['name' => 'Sup1']);
        $customer = Customer::create([
            'name' => 'Test Customer',
            'taxpayer_id' => 'J-11111111-0',
            'address' => 'Addr',
            'city' => 'Caracas',
            'phone' => '04120000000',
            'seller_id' => $this->sellerUser->id,
        ]);

        // Create a sale of $400 USD assigned to sellerUser
        Sale::create([
            'total' => 400.00,
            'total_usd' => 400.00,
            'customer_id' => $customer->id,
            'user_id' => $this->adminUser->id,
            'seller_id' => $this->sellerUser->id,
            'status' => 'paid',
            'type' => 'invoice',
            'items' => 1,
            'primary_currency_code' => 'USD',
            'primary_exchange_rate' => 1.0,
            'invoice_number' => 'INV-001',
            'created_at' => Carbon::now(),
        ]);

        $evaluation = GoalCommissionService::evaluateGoalForUser($this->sellerUser, $goal, Carbon::now());

        $this->assertTrue($evaluation['achieved']);
        $this->assertEquals(25.00, $evaluation['earned_reward']);
        $this->assertEquals(0.00, $evaluation['remaining_amount']);
        $this->assertEquals(400.00, $evaluation['total_sales']);
    }

    public function test_commission_goals_manager_livewire_component()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(CommissionGoalsManager::class)
            ->set('name', 'Super meta')
            ->set('target_amount', 800)
            ->set('reward_amount', 70)
            ->set('periodicity', 'semanal')
            ->call('saveGoal')
            ->assertDispatched('noty');

        $this->assertDatabaseHas('commission_goals', [
            'name' => 'Super meta',
            'target_amount' => 800.00,
            'reward_amount' => 70.00,
        ]);
    }

    public function test_goal_commission_report_renders()
    {
        $this->actingAs($this->adminUser);

        Livewire::test(GoalCommissionReport::class)
            ->assertSee('Reporte de Comisiones por Metas de Ventas')
            ->assertSee('Metas Evaluadas')
            ->assertSee('Metas Alcanzadas');
    }
}
