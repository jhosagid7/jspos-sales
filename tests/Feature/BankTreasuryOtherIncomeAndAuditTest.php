<?php

namespace Tests\Feature;

use App\Models\Bank;
use App\Models\BankRecord;
use App\Models\BankDailyClosure;
use App\Models\User;
use App\Services\BankTreasuryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BankTreasuryOtherIncomeAndAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_record_other_income_and_update_balance(): void
    {
        $user = User::factory()->create();
        $bank = Bank::create([
            'name' => 'Banesco USD',
            'account_holder' => 'Juan Pérez',
            'account_number' => '01340000000000000001',
            'cedula' => 'V-12345678',
            'phone' => '04141234567',
            'account_type' => 'Corriente',
            'currency_code' => 'USD',
            'initial_balance' => 100.00,
            'current_balance' => 100.00,
            'is_tracked' => true,
            'is_active' => true,
        ]);

        $record = BankTreasuryService::recordOtherIncome(
            $bank->id,
            now()->format('Y-m-d'),
            500.00,
            'Aporte de Capital',
            'Inyección inicial de socios',
            'REF-123456',
            null,
            $user->id
        );

        $this->assertInstanceOf(BankRecord::class, $record);
        $this->assertEquals('other', $record->income_type);
        $this->assertEquals('Aporte de Capital', $record->income_category);
        $this->assertEquals(500.00, $record->amount);

        $bank->refresh();
        $this->assertEquals(600.00, (float) $bank->current_balance);
    }

    public function test_can_perform_opening_and_closure_with_audit_and_difference(): void
    {
        Storage::fake('public');
        $user = User::factory()->create();
        $bank = Bank::create([
            'name' => 'Mercantil VED',
            'account_holder' => 'María Gómez',
            'account_number' => '01050000000000000002',
            'cedula' => 'V-87654321',
            'phone' => '04247654321',
            'account_type' => 'Corriente',
            'currency_code' => 'VED',
            'initial_balance' => 1000.00,
            'current_balance' => 1000.00,
            'is_tracked' => true,
            'is_active' => true,
        ]);

        $today = now()->format('Y-m-d');

        // 1. Perform Opening
        $opening = BankTreasuryService::performOpening(
            $bank->id,
            $today,
            1000.00,
            'bank_opening_proofs/sample.jpg',
            $user->id
        );

        $this->assertEquals(1000.00, (float) $opening->opening_balance);
        $this->assertEquals(1000.00, (float) $opening->manual_opening_balance);
        $this->assertEquals(0.00, (float) $opening->opening_difference);

        // Record an income
        BankTreasuryService::recordOtherIncome($bank->id, $today, 500.00, 'Devolución', 'Prueba', 'REF-99', null, $user->id);

        // 2. Perform Closure (Theoretical: 1500, Manual declared: 1550 -> Sobrante: +50)
        $closure = BankTreasuryService::performDailyClosure(
            $bank->id,
            $today,
            $user->id,
            'Cierre auditado con sobrante de $50',
            1550.00,
            'bank_closure_proofs/sample_closure.jpg'
        );

        $this->assertEquals(1500.00, (float) $closure->closing_balance);
        $this->assertEquals(1550.00, (float) $closure->manual_closing_balance);
        $this->assertEquals(50.00, (float) $closure->closing_difference);
    }

    public function test_opening_only_sets_opened_by_and_leaves_manual_closing_null(): void
    {
        $user = User::factory()->create();
        $bank = Bank::create([
            'name' => 'Provincial VED',
            'account_holder' => 'Carlos López',
            'account_number' => '01080000000000000003',
            'cedula' => 'V-99999999',
            'phone' => '04120000000',
            'account_type' => 'Corriente',
            'currency_code' => 'VED',
            'initial_balance' => 2000.00,
            'current_balance' => 2000.00,
            'is_tracked' => true,
            'is_active' => true,
        ]);

        $today = now()->format('Y-m-d');

        $opening = BankTreasuryService::performOpening(
            $bank->id,
            $today,
            1694928.28,
            null,
            $user->id
        );

        $this->assertEquals('open', $opening->status);
        $this->assertEquals(1694928.28, (float) $opening->manual_opening_balance);
        $this->assertNull($opening->manual_closing_balance);
        $this->assertNull($opening->closed_by);
        $this->assertEquals($user->id, $opening->opened_by);
    }
}
