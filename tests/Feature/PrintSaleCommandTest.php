<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\Configuration;
use App\Services\ConfigurationService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class PrintSaleCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\CurrencySeeder::class);

        // Reset ConfigurationService static cache
        $ref = new \ReflectionClass(ConfigurationService::class);
        $prop = $ref->getProperty('config');
        $prop->setAccessible(true);
        $prop->setValue(null);

        Configuration::create([
            'business_name' => 'Test POS',
            'taxpayer_id'   => 'J-12345678-0',
            'address'       => 'Calle Test',
            'phone'         => '04141234567',
            'decimals'      => 2,
            'vat'           => 0,
            'printer_name'  => 'POS-58',
        ]);
    }

    /** @test */
    public function test_pos_print_sale_command_executes_successfully()
    {
        $user = User::factory()->create();
        $customer = Customer::create([
            'name' => 'Test Customer',
            'code' => 'CLI-001',
            'tax_id' => 'J-00000000-0',
            'phone' => '0000000',
        ]);

        $sale = Sale::create([
            'total' => 100.0,
            'items' => 1,
            'cash' => 100.0,
            'change' => 0.0,
            'status' => 'paid',
            'type' => 'cash',
            'user_id' => $user->id,
            'customer_id' => $customer->id,
        ]);

        $this->artisan('pos:print-sale', ['sale_id' => $sale->id])
            ->assertExitCode(0);
    }
}
