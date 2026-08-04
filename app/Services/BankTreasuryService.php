<?php

namespace App\Services;

use App\Models\Bank;
use App\Models\BankRecord;
use App\Models\BankExpense;
use App\Models\BankTransfer;
use App\Models\BankDailyClosure;
use App\Models\Currency;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BankTreasuryService
{
    /**
     * Recalculates the current balance for a bank account from its initial balance.
     */
    public static function recalculateBalance(int $bankId): float
    {
        $bank = Bank::find($bankId);
        if (!$bank || !$bank->is_tracked) {
            return 0.0;
        }

        $startDate = $bank->initial_balance_date ? Carbon::parse($bank->initial_balance_date)->startOfDay() : null;
        $initialBalance = (float) $bank->initial_balance;

        // 1. Sum incoming payments (BankRecord)
        $queryRecords = BankRecord::where('bank_id', $bankId);
        if ($startDate) {
            $queryRecords->where('payment_date', '>=', $startDate);
        }
        $totalIncome = (float) $queryRecords->sum('amount');

        // 2. Sum incoming transfers
        $queryTransfersIn = BankTransfer::where('to_bank_id', $bankId);
        if ($startDate) {
            $queryTransfersIn->where('transfer_date', '>=', $startDate);
        }
        $totalTransfersIn = (float) $queryTransfersIn->sum('amount_to');

        // 3. Sum expenses
        $queryExpenses = BankExpense::where('bank_id', $bankId);
        if ($startDate) {
            $queryExpenses->where('expense_date', '>=', $startDate);
        }
        $totalExpenses = (float) $queryExpenses->sum('amount');

        // 4. Sum outgoing transfers
        $queryTransfersOut = BankTransfer::where('from_bank_id', $bankId);
        if ($startDate) {
            $queryTransfersOut->where('transfer_date', '>=', $startDate);
        }
        $totalTransfersOut = (float) $queryTransfersOut->sum('amount_from');

        $currentBalance = $initialBalance + $totalIncome + $totalTransfersIn - $totalExpenses - $totalTransfersOut;

        $bank->update(['current_balance' => $currentBalance]);

        return $currentBalance;
    }

    /**
     * Get income for a bank on a specific date.
     */
    public static function getIncomeForBank(int $bankId, string $date): float
    {
        $parsedDate = Carbon::parse($date)->format('Y-m-d');

        $income = (float) BankRecord::where('bank_id', $bankId)
            ->whereDate('payment_date', $parsedDate)
            ->sum('amount');

        $transfers = (float) BankTransfer::where('to_bank_id', $bankId)
            ->whereDate('transfer_date', $parsedDate)
            ->sum('amount_to');

        return $income + $transfers;
    }

    /**
     * Get expenses for a bank on a specific date.
     */
    public static function getExpensesForBank(int $bankId, string $date): float
    {
        $parsedDate = Carbon::parse($date)->format('Y-m-d');

        $expenses = (float) BankExpense::where('bank_id', $bankId)
            ->whereDate('expense_date', $parsedDate)
            ->sum('amount');

        $transfers = (float) BankTransfer::where('from_bank_id', $bankId)
            ->whereDate('transfer_date', $parsedDate)
            ->sum('amount_from');

        return $expenses + $transfers;
    }

    /**
     * Performs daily opening for a bank on a specific date.
     */
    public static function performOpening(int $bankId, string $date, ?float $manualOpeningBalance = null, ?string $openingProofImage = null, ?int $userId = null): BankDailyClosure
    {
        $bank = Bank::find($bankId);
        $parsedDate = Carbon::parse($date)->format('Y-m-d');
        $prevDate = Carbon::parse($date)->subDay()->format('Y-m-d');

        $prevClosure = BankDailyClosure::where('bank_id', $bankId)
            ->whereDate('closure_date', $prevDate)
            ->first();

        if ($prevClosure) {
            $openingBalance = (float) $prevClosure->closing_balance;
        } else {
            $lastClosure = BankDailyClosure::where('bank_id', $bankId)
                ->whereDate('closure_date', '<', $parsedDate)
                ->orderBy('closure_date', 'desc')
                ->first();

            $openingBalance = $lastClosure ? (float) $lastClosure->closing_balance : (float) $bank->initial_balance;
        }

        $openingDiff = ($manualOpeningBalance !== null) ? ($manualOpeningBalance - $openingBalance) : 0.00;

        $data = [
            'opening_balance' => $openingBalance,
            'manual_opening_balance' => $manualOpeningBalance,
            'opening_difference' => $openingDiff,
            'opened_at' => now(),
            'opened_by' => $userId,
            'status' => 'open',
        ];

        if ($openingProofImage) {
            $data['opening_proof_image'] = $openingProofImage;
        }

        $closure = BankDailyClosure::updateOrCreate(
            ['bank_id' => $bankId, 'closure_date' => $parsedDate],
            $data
        );

        return $closure;
    }

    /**
     * Records Other Income for a bank account (manual income).
     */
    public static function recordOtherIncome(int $bankId, string $date, float $amount, string $category, ?string $description = null, ?string $reference = null, ?string $imagePath = null, ?int $userId = null): BankRecord
    {
        $parsedDate = Carbon::parse($date)->format('Y-m-d');

        $record = BankRecord::create([
            'bank_id' => $bankId,
            'payment_date' => $parsedDate,
            'amount' => $amount,
            'reference' => $reference,
            'image_path' => $imagePath,
            'note' => $description,
            'income_type' => 'other',
            'income_category' => $category,
            'created_by' => $userId,
            'status' => 'confirmed',
            'remaining_balance' => 0.00,
        ]);

        self::recalculateBalance($bankId);

        return $record;
    }

    /**
     * Performs daily closure for a bank on a specific date.
     */
    public static function performDailyClosure(int $bankId, string $date, ?int $userId = null, ?string $notes = null, ?float $manualClosingBalance = null, ?string $closingProofImage = null): BankDailyClosure
    {
        $bank = Bank::find($bankId);
        $parsedDate = Carbon::parse($date)->format('Y-m-d');
        $prevDate = Carbon::parse($date)->subDay()->format('Y-m-d');

        $existingClosure = BankDailyClosure::where('bank_id', $bankId)
            ->whereDate('closure_date', $parsedDate)
            ->first();

        if ($existingClosure && $existingClosure->opening_balance !== null) {
            $openingBalance = (float) $existingClosure->opening_balance;
            $manualOpeningBalance = $existingClosure->manual_opening_balance;
            $openingProofImage = $existingClosure->opening_proof_image;
            $openingDiff = (float) $existingClosure->opening_difference;
        } else {
            $prevClosure = BankDailyClosure::where('bank_id', $bankId)
                ->whereDate('closure_date', $prevDate)
                ->first();

            if ($prevClosure) {
                $openingBalance = (float) $prevClosure->closing_balance;
            } else {
                $lastClosure = BankDailyClosure::where('bank_id', $bankId)
                    ->whereDate('closure_date', '<', $parsedDate)
                    ->orderBy('closure_date', 'desc')
                    ->first();

                $openingBalance = $lastClosure ? (float) $lastClosure->closing_balance : (float) $bank->initial_balance;
            }
            $manualOpeningBalance = null;
            $openingProofImage = null;
            $openingDiff = 0.00;
        }

        $incomeRecordsCount = BankRecord::where('bank_id', $bankId)->whereDate('payment_date', $parsedDate)->count();
        $incomeTransfersCount = BankTransfer::where('to_bank_id', $bankId)->whereDate('transfer_date', $parsedDate)->count();
        $totalIncomeCount = $incomeRecordsCount + $incomeTransfersCount;
        $totalIncome = self::getIncomeForBank($bankId, $parsedDate);

        $expenseRecordsCount = BankExpense::where('bank_id', $bankId)->whereDate('expense_date', $parsedDate)->count();
        $expenseTransfersCount = BankTransfer::where('from_bank_id', $bankId)->whereDate('transfer_date', $parsedDate)->count();
        $totalExpensesCount = $expenseRecordsCount + $expenseTransfersCount;
        $totalExpenses = self::getExpensesForBank($bankId, $parsedDate);

        $closingBalance = $openingBalance + $totalIncome - $totalExpenses;
        $closingDiff = ($manualClosingBalance !== null) ? ($manualClosingBalance - $closingBalance) : 0.00;

        $closure = BankDailyClosure::updateOrCreate(
            ['bank_id' => $bankId, 'closure_date' => $parsedDate],
            [
                'opening_balance' => $openingBalance,
                'manual_opening_balance' => $manualOpeningBalance,
                'opening_proof_image' => $openingProofImage,
                'opening_difference' => $openingDiff,
                'total_income' => $totalIncome,
                'total_income_count' => $totalIncomeCount,
                'total_expenses' => $totalExpenses,
                'total_expenses_count' => $totalExpensesCount,
                'closing_balance' => $closingBalance,
                'manual_closing_balance' => $manualClosingBalance,
                'closing_proof_image' => $closingProofImage ?: ($existingClosure ? $existingClosure->closing_proof_image : null),
                'closing_difference' => $closingDiff,
                'status' => 'closed',
                'closed_at' => now(),
                'closed_by' => $userId,
                'notes' => $notes,
            ]
        );

        self::recalculateBalance($bankId);

        return $closure;
    }

    /**
     * Get expense analysis by category for a specific bank.
     */
    public static function getExpenseAnalysis(int $bankId, string $dateFrom, string $dateTo): array
    {
        $expenses = BankExpense::where('bank_id', $bankId)
            ->whereBetween('expense_date', [$dateFrom, $dateTo])
            ->join('bank_expense_categories', 'bank_expenses.category_id', '=', 'bank_expense_categories.id')
            ->select(
                'bank_expense_categories.name as category_name',
                'bank_expense_categories.color as category_color',
                'bank_expense_categories.icon as category_icon',
                'bank_expense_categories.is_essential',
                DB::raw('SUM(bank_expenses.amount) as total_amount')
            )
            ->groupBy('bank_expense_categories.id', 'bank_expense_categories.name', 'bank_expense_categories.color', 'bank_expense_categories.icon', 'bank_expense_categories.is_essential')
            ->orderByDesc('total_amount')
            ->get();

        $totalAmount = $expenses->sum('total_amount');

        return [
            'categories' => $expenses->map(function ($item) use ($totalAmount) {
                $item->percentage = $totalAmount > 0 ? round(($item->total_amount / $totalAmount) * 100, 2) : 0;
                return $item;
            }),
            'total_amount' => $totalAmount,
            'essential_total' => $expenses->where('is_essential', true)->sum('total_amount'),
            'discretionary_total' => $expenses->where('is_essential', false)->sum('total_amount'),
        ];
    }

    /**
     * Get global expense analysis unified to the primary currency.
     */
    public static function getGlobalExpenseAnalysis(string $dateFrom, string $dateTo): array
    {
        $currencies = Currency::all();
        $primaryCurrency = $currencies->where('is_primary', 1)->first() ?? $currencies->first();
        $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'USD';

        $expenses = BankExpense::whereBetween('expense_date', [$dateFrom, $dateTo])
            ->with(['bank', 'category'])
            ->get();

        $transfersOut = BankTransfer::whereBetween('transfer_date', [$dateFrom, $dateTo])
            ->with(['fromBank', 'toBank'])
            ->get();

        // Convert amounts to primary currency
        $processedExpenses = [];
        $totalGlobal = 0.0;
        $essentialTotal = 0.0;
        $discretionaryTotal = 0.0;

        foreach ($expenses as $expense) {
            $bank = $expense->bank;
            $category = $expense->category;
            
            // Get exchange rate relative to primary currency
            $rate = 1.0;
            if ($bank->currency_code !== $primaryCode) {
                $curr = $currencies->where('code', $bank->currency_code)->first();
                $rate = $curr && $curr->exchange_rate > 0 ? $curr->exchange_rate : 1.0;
            }

            // In our system, usually rate is primary_currency / local_currency. 
            // If primary is USD and bank is VES, exchange_rate is rate of VES per USD (e.g. 36.5).
            // To convert VES to USD, we divide: amount / rate.
            $amountInPrimary = ($rate > 0) ? ($expense->amount / $rate) : $expense->amount;

            $totalGlobal += $amountInPrimary;
            if ($category->is_essential) {
                $essentialTotal += $amountInPrimary;
            } else {
                $discretionaryTotal += $amountInPrimary;
            }

            $catId = $category->id;
            if (!isset($processedExpenses[$catId])) {
                $processedExpenses[$catId] = [
                    'category_name' => $category->name,
                    'category_color' => $category->color,
                    'category_icon' => $category->icon,
                    'is_essential' => $category->is_essential,
                    'total_amount' => 0.0,
                ];
            }
            $processedExpenses[$catId]['total_amount'] += $amountInPrimary;
        }

        // Add percentages
        $categoriesList = collect($processedExpenses)->map(function ($item) use ($totalGlobal) {
            $item['percentage'] = $totalGlobal > 0 ? round(($item['total_amount'] / $totalGlobal) * 100, 2) : 0;
            return $item;
        })->sortByDesc('total_amount')->values()->all();

        return [
            'categories' => $categoriesList,
            'total_amount' => $totalGlobal,
            'essential_total' => $essentialTotal,
            'discretionary_total' => $discretionaryTotal,
            'currency_code' => $primaryCode,
        ];
    }
}
