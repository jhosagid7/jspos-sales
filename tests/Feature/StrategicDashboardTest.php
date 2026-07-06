<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Product;
use App\Models\Category;
use App\Models\Supplier;
use App\Models\OperationalExpense;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

class StrategicDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed currencies
        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Reset ConfigurationService static cache
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        // Create Configuration
        \App\Models\Configuration::create([
            'business_name' => 'Test Business',
            'taxpayer_id' => '12345678',
            'address' => 'Test Address 123',
            'city' => 'Caracas',
            'phone' => '0212-5555555',
            'decimals' => 2,
            'vat' => 16,
            'printer_name' => 'EPSON',
            'credit_days' => 15,
            'sequential_cut_off_date' => '2026-06-03 00:00:00'
        ]);

        $this->adminUser = User::factory()->create();
        \Spatie\Permission\Models\Permission::firstOrCreate(['name' => 'reports.sales']);
        $this->adminUser->givePermissionTo('reports.sales');
    }

    public function test_strategic_dashboard_requires_permission()
    {
        $guest = User::factory()->create();
        $this->actingAs($guest);

        $response = $this->get(route('reports.strategic'));
        $response->assertStatus(403);
    }

    public function test_strategic_dashboard_renders_successfully()
    {
        $this->actingAs($this->adminUser);

        $response = $this->get(route('reports.strategic'));
        $response->assertStatus(200);
        $response->assertSee('Análisis Estratégico');
    }

    public function test_can_add_and_delete_opex()
    {
        $this->actingAs($this->adminUser);

        $selectedMonth = now()->format('Y-m');

        Livewire::test(\App\Livewire\Reports\StrategicDashboard::class)
            ->set('selectedMonth', $selectedMonth)
            ->set('opexCategory', 'Nómina')
            ->set('opexAmount', 500)
            ->set('opexDescription', 'Nómina Administrativa')
            ->call('addOpex')
            ->assertSet('opexAmount', null)
            ->assertSet('opexDescription', null);

        $this->assertDatabaseHas('operational_expenses', [
            'year_month' => $selectedMonth,
            'category' => 'Nómina',
            'amount' => 500
        ]);

        $expense = OperationalExpense::first();

        Livewire::test(\App\Livewire\Reports\StrategicDashboard::class)
            ->call('deleteOpex', $expense->id);

        $this->assertDatabaseMissing('operational_expenses', [
            'id' => $expense->id
        ]);
    }
}
