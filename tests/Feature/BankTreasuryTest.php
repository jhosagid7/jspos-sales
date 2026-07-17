<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankRecord;
use App\Models\BankExpense;
use App\Models\BankExpenseCategory;
use App\Models\BankTransfer;
use App\Models\BankDailyClosure;
use App\Models\User;
use App\Services\BankTreasuryService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BankTreasuryTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $bank1;
    protected $bank2;
    protected $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        
        $this->category = BankExpenseCategory::create([
            'name' => 'Servicios Públicos',
            'is_essential' => true,
        ]);

        $this->bank1 = Bank::create([
            'name' => 'Banesco USD',
            'currency_code' => 'USD',
            'account_holder' => 'John Doe',
            'account_number' => '123456',
            'cedula' => 'V-12345',
            'phone' => '12345',
            'is_tracked' => true,
            'initial_balance' => 1000.00,
            'initial_balance_date' => Carbon::today()->format('Y-m-d'),
            'current_balance' => 1000.00,
            'state' => true,
        ]);

        $this->bank2 = Bank::create([
            'name' => 'Mercantil USD',
            'currency_code' => 'USD',
            'account_holder' => 'Jane Doe',
            'account_number' => '654321',
            'cedula' => 'V-54321',
            'phone' => '54321',
            'is_tracked' => true,
            'initial_balance' => 500.00,
            'initial_balance_date' => Carbon::today()->format('Y-m-d'),
            'current_balance' => 500.00,
            'state' => true,
        ]);
    }

    public function test_initial_balance_is_set(): void
    {
        $this->assertEquals(1000.00, $this->bank1->current_balance);
        $this->assertEquals(500.00, $this->bank2->current_balance);
    }

    public function test_bank_record_income_increases_balance(): void
    {
        $record = BankRecord::create([
            'bank_id' => $this->bank1->id,
            'payment_date' => Carbon::today()->format('Y-m-d'),
            'amount' => 250.00,
            'reference' => 'REF001',
            'status' => 'unused',
        ]);

        $this->bank1->refresh();
        $this->assertEquals(1250.00, $this->bank1->current_balance);
    }

    public function test_bank_expense_decreases_balance(): void
    {
        $expense = BankExpense::create([
            'bank_id' => $this->bank1->id,
            'category_id' => $this->category->id,
            'amount' => 150.00,
            'expense_date' => Carbon::today()->format('Y-m-d'),
            'user_id' => $this->user->id,
        ]);

        $this->bank1->refresh();
        $this->assertEquals(850.00, $this->bank1->current_balance);
    }

    public function test_bank_transfer_affects_both_balances(): void
    {
        $transfer = BankTransfer::create([
            'from_bank_id' => $this->bank1->id,
            'to_bank_id' => $this->bank2->id,
            'amount_from' => 300.00,
            'amount_to' => 300.00,
            'exchange_rate' => 1.0,
            'transfer_date' => Carbon::today()->format('Y-m-d'),
            'user_id' => $this->user->id,
        ]);

        $this->bank1->refresh();
        $this->bank2->refresh();

        $this->assertEquals(700.00, $this->bank1->current_balance);
        $this->assertEquals(800.00, $this->bank2->current_balance);

        // Delete transfer should undo the balance changes
        $transfer->delete();

        $this->bank1->refresh();
        $this->bank2->refresh();

        $this->assertEquals(1000.00, $this->bank1->current_balance);
        $this->assertEquals(500.00, $this->bank2->current_balance);
    }

    public function test_daily_closure_calculations(): void
    {
        // 1. Add some income
        BankRecord::create([
            'bank_id' => $this->bank1->id,
            'payment_date' => Carbon::today()->format('Y-m-d'),
            'amount' => 400.00,
            'reference' => 'REF100',
        ]);

        // 2. Add some expense
        BankExpense::create([
            'bank_id' => $this->bank1->id,
            'category_id' => $this->category->id,
            'amount' => 100.00,
            'expense_date' => Carbon::today()->format('Y-m-d'),
            'user_id' => $this->user->id,
        ]);

        $closure = BankTreasuryService::performDailyClosure($this->bank1->id, Carbon::today()->format('Y-m-d'), $this->user->id, 'Cierre de prueba');

        $this->assertEquals(1000.00, $closure->opening_balance);
        $this->assertEquals(400.00, $closure->total_income);
        $this->assertEquals(100.00, $closure->total_expenses);
        $this->assertEquals(1300.00, $closure->closing_balance);
        $this->assertEquals('closed', $closure->status);
    }
}
