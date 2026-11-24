<?php

namespace Tests\Feature;

use App\Models\CashMovement;
use App\Models\CashRegister;
use App\Models\Currency;
use App\Models\Sale;
use App\Models\SaleReturn;
use App\Models\User;
use App\Services\CashRegisterService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class SaleReturnCashMovementTest extends TestCase
{
    use DatabaseTransactions;

    public function test_record_return_movement_creates_valid_cash_movement(): void
    {
        $user = User::first() ?? User::factory()->create();
        $this->actingAs($user);

        $currency = Currency::where('is_primary', true)->first();
        if (!$currency) {
            $currency = Currency::create([
                'code' => 'USD',
                'name' => 'Dollar',
                'label' => 'Dólar Estadounidense',
                'symbol' => '$',
                'exchange_rate' => 1.0,
                'is_primary' => true,
            ]);
        }

        $register = CashRegister::create([
            'user_id' => $user->id,
            'status' => 'open',
            'opening_date' => now(),
            'total_opening_amount' => 1000.0,
        ]);

        // Opening cash movement
        CashMovement::create([
            'cash_register_id' => $register->id,
            'type' => 'opening',
            'currency_code' => $currency->code,
            'amount' => 500.0,
            'amount_in_primary_currency' => 500.0,
            'exchange_rate' => 1.0,
            'balance_after' => 500.0,
            'description' => 'Fondo inicial',
        ]);

        $sale = Sale::first();
        $saleId = $sale ? $sale->id : null;

        $service = app(CashRegisterService::class);
        $movement = $service->recordReturnMovement(
            $register->id,
            $saleId,
            $currency->code,
            34.33,
            'Devolución Factura Test'
        );

        $this->assertNotNull($movement);
        $this->assertSame($register->id, $movement->cash_register_id);
        $this->assertSame('sale_return', $movement->type);
        $this->assertSame($currency->code, $movement->currency_code);
        $this->assertEquals(-34.33, (float) $movement->amount);
        $this->assertEquals(465.67, (float) $movement->balance_after);
        $this->assertSame('Devolución Factura Test', $movement->description);
    }
}
