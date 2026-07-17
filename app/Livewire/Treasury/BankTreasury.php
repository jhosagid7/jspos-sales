<?php

namespace App\Livewire\Treasury;

use App\Models\Bank;
use App\Models\BankRecord;
use App\Models\BankExpense;
use App\Models\BankExpenseCategory;
use App\Models\BankTransfer;
use App\Models\BankDailyClosure;
use App\Models\Currency;
use App\Services\BankTreasuryService;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class BankTreasury extends Component
{
    use WithFileUploads, WithPagination;

    protected $paginationTheme = 'bootstrap';

    // Filters
    public $selectedBankId = 'all';
    public $dateFrom;
    public $dateTo;

    // View Control
    public $activeTab = 'dashboard'; // dashboard, expenses, transfers, closures

    // New Expense Form Properties
    public $showExpenseModal = false;
    public $expenseId = null; // For edit if needed
    public $expense_bank_id;
    public $expense_category_id;
    public $expense_amount;
    public $expense_date;
    public $expense_description;
    public $expense_reference;
    public $expense_beneficiary;
    public $expense_receipt;
    public $expense_is_recurring = false;

    // New Transfer Form Properties
    public $showTransferModal = false;
    public $transfer_from_bank_id;
    public $transfer_to_bank_id;
    public $transfer_amount_from;
    public $transfer_amount_to;
    public $transfer_exchange_rate = 1.0;
    public $transfer_date;
    public $transfer_reference;
    public $transfer_notes;

    // New Manual Closure Form Properties
    public $showClosureModal = false;
    public $closure_bank_id;
    public $closure_date;
    public $closure_notes;

    // AI Interpretation & PDF
    public $showInterpretationModal = false;
    public $showPdfModal = false;
    public $pdfUrl = '';

    // Chart Data Cache
    public $chartData = [];

    protected $queryString = [
        'selectedBankId' => ['except' => 'all'],
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'activeTab' => ['except' => 'dashboard'],
    ];

    public function mount()
    {
        session(['map' => 'Tesorería y Bancos', 'child' => 'Auditoría y Flujos', 'rest' => '', 'pos' => 'Treasury']);

        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');

        $this->expense_date = Carbon::today()->format('Y-m-d');
        $this->transfer_date = Carbon::today()->format('Y-m-d');
        $this->closure_date = Carbon::today()->format('Y-m-d');

        $this->loadChartData();
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['selectedBankId', 'dateFrom', 'dateTo'])) {
            $this->loadChartData();
            $this->dispatch('chart-updated');
        }

        if ($propertyName === 'transfer_amount_from' || $propertyName === 'transfer_exchange_rate') {
            $this->calculateTransferAmountTo();
        }
    }

    public function calculateTransferAmountTo()
    {
        $amountFrom = (float) $this->transfer_amount_from;
        $rate = (float) $this->transfer_exchange_rate;
        if ($rate > 0) {
            $this->transfer_amount_to = round($amountFrom * $rate, 2);
        } else {
            $this->transfer_amount_to = $amountFrom;
        }
    }

    public function loadChartData()
    {
        // 1. Expense analysis by category
        if ($this->selectedBankId === 'all') {
            $analysis = BankTreasuryService::getGlobalExpenseAnalysis($this->dateFrom, $this->dateTo);
            $currencyCode = $analysis['currency_code'];
        } else {
            $analysis = BankTreasuryService::getExpenseAnalysis((int) $this->selectedBankId, $this->dateFrom, $this->dateTo);
            $bank = Bank::find($this->selectedBankId);
            $currencyCode = $bank ? $bank->currency_code : 'USD';
        }

        // Prepare Category Chart Data
        $categoriesData = [];
        foreach ($analysis['categories'] as $cat) {
            $categoriesData[] = [
                'name' => $cat['category_name'],
                'y' => (float) $cat['total_amount'],
                'color' => $cat['category_color'] ?? '#cccccc',
            ];
        }

        // 2. Essential vs Discretionary
        $essentialData = [
            ['name' => 'Esenciales', 'y' => (float) $analysis['essential_total'], 'color' => '#28a745'],
            ['name' => 'Discrecionales', 'y' => (float) $analysis['discretionary_total'], 'color' => '#dc3545'],
        ];

        // 3. Flow trend (daily balances)
        $flowData = [];
        $flowLabels = [];
        $days = Carbon::parse($this->dateFrom)->diffInDays(Carbon::parse($this->dateTo)) + 1;
        
        // If query range is too long, group by week/month to avoid cluttering charts
        $format = $days > 31 ? 'Y-W' : 'Y-m-d';
        $closures = BankDailyClosure::query();
        if ($this->selectedBankId !== 'all') {
            $closures->where('bank_id', $this->selectedBankId);
        }
        $closuresData = $closures->whereBetween('closure_date', [$this->dateFrom, $this->dateTo])
            ->orderBy('closure_date', 'asc')
            ->get();

        $trendData = [];
        foreach ($closuresData as $cls) {
            $dateStr = $cls->closure_date->format('d/m');
            if (!isset($trendData[$dateStr])) {
                $trendData[$dateStr] = 0.0;
            }
            $trendData[$dateStr] += (float) $cls->closing_balance;
        }

        $this->chartData = [
            'categories' => $categoriesData,
            'essential' => $essentialData,
            'trend_labels' => array_keys($trendData),
            'trend_values' => array_values($trendData),
            'currency_code' => $currencyCode,
            'total_expenses' => $analysis['total_amount'],
        ];
    }

    public function render()
    {
        $trackedBanks = Bank::tracked()->where('state', 1)->get();
        $allBanks = Bank::where('state', 1)->get();
        $categories = BankExpenseCategory::where('is_active', true)->orderBy('sort')->get();

        // Summary cards
        $balances = [];
        $totalInPrimary = 0.0;
        
        $currencies = Currency::all();
        $primaryCurrency = $currencies->where('is_primary', 1)->first() ?? $currencies->first();
        $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'USD';
        $config = \App\Models\Configuration::first();

        foreach ($trackedBanks as $bank) {
            $rate = 1.0;
            if ($bank->currency_code !== $primaryCode) {
                if (in_array($bank->currency_code, ['VED', 'VES'])) {
                    $rate = $config && floatval($config->binance_rate) > 0 ? floatval($config->binance_rate) : 1.0;
                } else {
                    $curr = $currencies->where('code', $bank->currency_code)->first();
                    $rate = $curr && $curr->exchange_rate > 0 ? $curr->exchange_rate : 1.0;
                }
            }
            
            $balanceInPrimary = $rate > 0 ? ($bank->current_balance / $rate) : $bank->current_balance;
            $totalInPrimary += $balanceInPrimary;

            $balances[$bank->id] = [
                'name' => $bank->name,
                'currency' => $bank->currency_code,
                'balance' => $bank->current_balance,
                'balance_primary' => $balanceInPrimary,
                'rate' => $rate,
                'income_today' => BankTreasuryService::getIncomeForBank($bank->id, Carbon::today()->format('Y-m-d')),
                'expenses_today' => BankTreasuryService::getExpensesForBank($bank->id, Carbon::today()->format('Y-m-d')),
            ];
        }

        // Get Paginated lists depending on tab
        $expensesList = [];
        $transfersList = [];
        $closuresList = [];

        if ($this->activeTab === 'expenses') {
            $q = BankExpense::with(['bank', 'category', 'user']);
            if ($this->selectedBankId !== 'all') {
                $q->where('bank_id', $this->selectedBankId);
            }
            $expensesList = $q->whereBetween('expense_date', [$this->dateFrom, $this->dateTo])
                ->orderBy('expense_date', 'desc')
                ->paginate($this->selectedBankId === 'all' ? 15 : 10);
        } elseif ($this->activeTab === 'transfers') {
            $q = BankTransfer::with(['fromBank', 'toBank', 'user']);
            if ($this->selectedBankId !== 'all') {
                $q->where(function($query) {
                    $query->where('from_bank_id', $this->selectedBankId)
                          ->orWhere('to_bank_id', $this->selectedBankId);
                });
            }
            $transfersList = $q->whereBetween('transfer_date', [$this->dateFrom, $this->dateTo])
                ->orderBy('transfer_date', 'desc')
                ->paginate(15);
        } elseif ($this->activeTab === 'closures') {
            $q = BankDailyClosure::with(['bank', 'closedBy']);
            if ($this->selectedBankId !== 'all') {
                $q->where('bank_id', $this->selectedBankId);
            }
            $closuresList = $q->whereBetween('closure_date', [$this->dateFrom, $this->dateTo])
                ->orderBy('closure_date', 'desc')
                ->paginate(15);
        }

        // Movemenets table for Dashboard Tab
        $movements = [];
        if ($this->activeTab === 'dashboard') {
            $movements = $this->getCombinedMovements();
        }

        return view('livewire.treasury.bank-treasury', [
            'trackedBanks' => $trackedBanks,
            'allBanks' => $allBanks,
            'categories' => $categories,
            'balances' => $balances,
            'totalInPrimary' => $totalInPrimary,
            'primaryCode' => $primaryCode,
            'expensesList' => $expensesList,
            'transfersList' => $transfersList,
            'closuresList' => $closuresList,
            'movements' => $movements,
        ])->layout('layouts.theme.app');
    }

    private function getCombinedMovements()
    {
        $bankIds = $this->selectedBankId === 'all' 
            ? Bank::tracked()->pluck('id')->toArray() 
            : [(int) $this->selectedBankId];

        if (empty($bankIds)) {
            return collect();
        }

        // Fetch Income (BankRecords)
        $incomes = BankRecord::whereIn('bank_id', $bankIds)
            ->whereBetween('payment_date', [$this->dateFrom, $this->dateTo])
            ->select('id', 'bank_id', 'payment_date as date', 'amount', 'reference', 'note as description', DB::raw("'INGRESO' as type"), DB::raw("null as category_name"), DB::raw("null as category_icon"), DB::raw("null as category_color"))
            ->get();

        // Fetch Expenses
        $expenses = BankExpense::whereIn('bank_id', $bankIds)
            ->whereBetween('expense_date', [$this->dateFrom, $this->dateTo])
            ->join('bank_expense_categories', 'bank_expenses.category_id', '=', 'bank_expense_categories.id')
            ->select('bank_expenses.id', 'bank_expenses.bank_id', 'bank_expenses.expense_date as date', 'bank_expenses.amount', 'bank_expenses.reference', 'bank_expenses.description', DB::raw("'GASTO' as type"), 'bank_expense_categories.name as category_name', 'bank_expense_categories.icon as category_icon', 'bank_expense_categories.color as category_color')
            ->get();

        // Fetch Transfers In & Out
        $transfersIn = BankTransfer::whereIn('to_bank_id', $bankIds)
            ->whereBetween('transfer_date', [$this->dateFrom, $this->dateTo])
            ->select('id', 'to_bank_id as bank_id', 'transfer_date as date', 'amount_to as amount', 'reference', DB::raw("CONCAT('Transf. Recibida desde banco ID ', from_bank_id) as description"), DB::raw("'TRANSFER_IN' as type"), DB::raw("null as category_name"), DB::raw("null as category_icon"), DB::raw("null as category_color"))
            ->get();

        $transfersOut = BankTransfer::whereIn('from_bank_id', $bankIds)
            ->whereBetween('transfer_date', [$this->dateFrom, $this->dateTo])
            ->select('id', 'from_bank_id as bank_id', 'transfer_date as date', 'amount_from as amount', 'reference', DB::raw("CONCAT('Transf. Enviada a banco ID ', to_bank_id) as description"), DB::raw("'TRANSFER_OUT' as type"), DB::raw("null as category_name"), DB::raw("null as category_icon"), DB::raw("null as category_color"))
            ->get();

        // Merge and sort
        $combined = collect()
            ->concat($incomes)
            ->concat($expenses)
            ->concat($transfersIn)
            ->concat($transfersOut)
            ->sortByDesc('date')
            ->take(50); // limit to 50 on dashboard

        // Map bank names
        $banksMap = Bank::whereIn('id', $bankIds)->pluck('name', 'id')->toArray();
        $combined->transform(function ($item) use ($banksMap) {
            $item->bank_name = $banksMap[$item->bank_id] ?? 'Banco';
            return $item;
        });

        return $combined;
    }

    // Modal Control
    public function openExpenseModal()
    {
        $this->resetValidation();
        $this->reset(['expenseId', 'expense_bank_id', 'expense_category_id', 'expense_amount', 'expense_description', 'expense_reference', 'expense_beneficiary', 'expense_receipt', 'expense_is_recurring']);
        $this->expense_date = Carbon::today()->format('Y-m-d');
        
        $trackedBanks = Bank::tracked()->get();
        if ($trackedBanks->isNotEmpty()) {
            $this->expense_bank_id = $trackedBanks->first()->id;
        }
        
        $categories = BankExpenseCategory::where('is_active', true)->orderBy('sort')->get();
        if ($categories->isNotEmpty()) {
            $this->expense_category_id = $categories->first()->id;
        }

        $this->showExpenseModal = true;
    }

    public function saveExpense()
    {
        $this->validate([
            'expense_bank_id' => 'required|exists:banks,id',
            'expense_category_id' => 'required|exists:bank_expense_categories,id',
            'expense_amount' => 'required|numeric|min:0.01',
            'expense_date' => 'required|date|before_or_equal:today',
            'expense_beneficiary' => 'nullable|string|max:255',
            'expense_reference' => 'nullable|string|max:255',
            'expense_description' => 'nullable|string',
            'expense_receipt' => 'nullable|image|max:2048', // max 2MB
        ]);

        $receiptPath = null;
        if ($this->expense_receipt) {
            $receiptPath = $this->expense_receipt->store('receipts', 'public');
        }

        BankExpense::create([
            'bank_id' => $this->expense_bank_id,
            'category_id' => $this->expense_category_id,
            'amount' => $this->expense_amount,
            'expense_date' => $this->expense_date,
            'beneficiary' => $this->expense_beneficiary,
            'reference' => $this->expense_reference,
            'description' => $this->expense_description,
            'receipt_path' => $receiptPath,
            'user_id' => auth()->id() ?? 1, // Fallback for tests
            'is_recurring' => $this->expense_is_recurring,
        ]);

        $this->showExpenseModal = false;
        $this->loadChartData();
        $this->dispatch('chart-updated');
        $this->dispatch('noty', msg: 'Gasto registrado correctamente.');
    }

    public function deleteExpense(int $id)
    {
        if (!auth()->user()->can('treasury.config')) {
            $this->dispatch('noty', msg: 'No tiene permisos para eliminar gastos.', type: 'error');
            return;
        }

        $expense = BankExpense::find($id);
        if ($expense) {
            if ($expense->receipt_path) {
                Storage::disk('public')->delete($expense->receipt_path);
            }
            $expense->delete();
            $this->loadChartData();
            $this->dispatch('chart-updated');
            $this->dispatch('noty', msg: 'Gasto eliminado con éxito.');
        }
    }

    public function openTransferModal()
    {
        $this->resetValidation();
        $this->reset(['transfer_from_bank_id', 'transfer_to_bank_id', 'transfer_amount_from', 'transfer_amount_to', 'transfer_exchange_rate', 'transfer_reference', 'transfer_notes']);
        $this->transfer_date = Carbon::today()->format('Y-m-d');
        
        $trackedBanks = Bank::tracked()->get();
        if ($trackedBanks->count() >= 2) {
            $this->transfer_from_bank_id = $trackedBanks->first()->id;
            $this->transfer_to_bank_id = $trackedBanks->skip(1)->first()->id;
        }

        $this->showTransferModal = true;
    }

    public function saveTransfer()
    {
        $this->validate([
            'transfer_from_bank_id' => 'required|exists:banks,id|different:transfer_to_bank_id',
            'transfer_to_bank_id' => 'required|exists:banks,id',
            'transfer_amount_from' => 'required|numeric|min:0.01',
            'transfer_amount_to' => 'required|numeric|min:0.01',
            'transfer_exchange_rate' => 'required|numeric|min:0.000001',
            'transfer_date' => 'required|date|before_or_equal:today',
            'transfer_reference' => 'nullable|string|max:255',
            'transfer_notes' => 'nullable|string',
        ], [
            'transfer_from_bank_id.different' => 'El banco de origen y destino deben ser diferentes.',
        ]);

        BankTransfer::create([
            'from_bank_id' => $this->transfer_from_bank_id,
            'to_bank_id' => $this->transfer_to_bank_id,
            'amount_from' => $this->transfer_amount_from,
            'amount_to' => $this->transfer_amount_to,
            'exchange_rate' => $this->transfer_exchange_rate,
            'transfer_date' => $this->transfer_date,
            'reference' => $this->transfer_reference,
            'notes' => $this->transfer_notes,
            'user_id' => auth()->id() ?? 1,
        ]);

        $this->showTransferModal = false;
        $this->loadChartData();
        $this->dispatch('chart-updated');
        $this->dispatch('noty', msg: 'Transferencia registrada correctamente.');
    }

    public function deleteTransfer(int $id)
    {
        if (!auth()->user()->can('treasury.config')) {
            $this->dispatch('noty', msg: 'No tiene permisos para eliminar transferencias.', type: 'error');
            return;
        }

        $transfer = BankTransfer::find($id);
        if ($transfer) {
            $transfer->delete();
            $this->loadChartData();
            $this->dispatch('chart-updated');
            $this->dispatch('noty', msg: 'Transferencia eliminada con éxito.');
        }
    }

    public function openClosureModal()
    {
        $this->resetValidation();
        $this->reset(['closure_bank_id', 'closure_notes']);
        $this->closure_date = Carbon::today()->format('Y-m-d');

        $trackedBanks = Bank::tracked()->get();
        if ($trackedBanks->isNotEmpty()) {
            $this->closure_bank_id = $trackedBanks->first()->id;
        }

        $this->showClosureModal = true;
    }

    public function saveClosure()
    {
        $this->validate([
            'closure_bank_id' => 'required|exists:banks,id',
            'closure_date' => 'required|date|before_or_equal:today',
            'closure_notes' => 'nullable|string',
        ]);

        BankTreasuryService::performDailyClosure(
            $this->closure_bank_id,
            $this->closure_date,
            auth()->id() ?? 1,
            $this->closure_notes
        );

        $this->showClosureModal = false;
        $this->loadChartData();
        $this->dispatch('chart-updated');
        $this->dispatch('noty', msg: 'Corte de banco realizado con éxito.');
    }

    public function deleteClosure(int $id)
    {
        if (!auth()->user()->can('treasury.config')) {
            $this->dispatch('noty', msg: 'No tiene permisos para eliminar cortes diarios.', type: 'error');
            return;
        }

        $closure = BankDailyClosure::find($id);
        if ($closure) {
            $closure->delete();
            // Recalculate balance to restore it
            BankTreasuryService::recalculateBalance($closure->bank_id);
            $this->loadChartData();
            $this->dispatch('chart-updated');
            $this->dispatch('noty', msg: 'Corte eliminado. Saldo recalculado con éxito.');
        }
    }

    public function toggleInterpretationModal()
    {
        $this->showInterpretationModal = !$this->showInterpretationModal;
    }

    public function openPdfPreview()
    {
        $params = [
            'bank_id' => $this->selectedBankId,
            'date_from' => $this->dateFrom,
            'date_to' => $this->dateTo,
            'type' => $this->activeTab,
        ];

        $this->pdfUrl = route('reports.bank.treasury.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }

    public function getInterpretation()
    {
        // 1. Fetch data
        $currencies = \App\Models\Currency::all();
        $primaryCurrency = $currencies->where('is_primary', 1)->first() ?? $currencies->first();
        $primaryCode = $primaryCurrency ? $primaryCurrency->code : 'USD';

        if ($this->selectedBankId === 'all') {
            $analysis = BankTreasuryService::getGlobalExpenseAnalysis($this->dateFrom, $this->dateTo);
            $bankName = "Todas las Cuentas Bancarias";
            $currencyCode = $primaryCode;
        } else {
            $analysis = BankTreasuryService::getExpenseAnalysis((int) $this->selectedBankId, $this->dateFrom, $this->dateTo);
            $bank = Bank::find($this->selectedBankId);
            $bankName = $bank ? $bank->name : "Cuenta Seleccionada";
            $currencyCode = $bank ? $bank->currency_code : 'USD';
        }

        $totalExpenses = $analysis['total_amount'];
        $essentialTotal = $analysis['essential_total'];
        $discretionaryTotal = $analysis['discretionary_total'];

        $essentialPercent = $totalExpenses > 0 ? round(($essentialTotal / $totalExpenses) * 100, 2) : 0;
        $discretionaryPercent = $totalExpenses > 0 ? round(($discretionaryTotal / $totalExpenses) * 100, 2) : 0;

        // Fetch Tracked Banks Status
        $trackedBanks = Bank::tracked()->where('state', 1)->get();
        $totalCashPrimary = 0.0;
        $bankStatuses = [];

        foreach ($trackedBanks as $b) {
            $rate = 1.0;
            if ($b->currency_code !== $primaryCode) {
                $curr = $currencies->where('code', $b->currency_code)->first();
                $rate = $curr && $curr->exchange_rate > 0 ? $curr->exchange_rate : 1.0;
            }
            $balancePrimary = $rate > 0 ? ($b->current_balance / $rate) : $b->current_balance;
            $totalCashPrimary += $balancePrimary;

            $bankStatuses[] = [
                'name' => $b->name,
                'currency' => $b->currency_code,
                'balance' => $b->current_balance,
                'balance_primary' => $balancePrimary,
                'is_low' => $b->current_balance < ($b->currency_code === 'COP' ? 200000 : 50),
            ];
        }

        // Calculate flow trend
        $closuresQuery = BankDailyClosure::query();
        if ($this->selectedBankId !== 'all') {
            $closuresQuery->where('bank_id', $this->selectedBankId);
        }
        $closures = $closuresQuery->whereBetween('closure_date', [$this->dateFrom, $this->dateTo])
            ->orderBy('closure_date', 'asc')
            ->get();

        $netFlow = 0.0;
        if ($closures->isNotEmpty()) {
            $first = $closures->first()->opening_balance;
            $last = $closures->last()->closing_balance;
            $netFlow = $last - $first;
        }

        $html = '';
        $html .= "<div class='p-2'>";
        $html .= "<h5 class='text-primary mb-3'><i class='fas fa-chart-line mr-2'></i> <b>Análisis Financiero de Tesorería:</b> $bankName</h5>";
        $html .= "<p class='text-muted'>Este informe presenta una interpretación inteligente sobre la salud de tus cuentas bancarias, ingresos, egresos y control de gastos en el período comprendido entre <b>" . Carbon::parse($this->dateFrom)->format('d/m/Y') . "</b> y <b>" . Carbon::parse($this->dateTo)->format('d/m/Y') . "</b>:</p>";

        // Block 1: Balance y Flujo Neto
        $html .= "<div class='row mt-4'>";
        $html .= "<div class='col-md-6 mb-3'>";
        $html .= "<div class='p-3 bg-light rounded border h-100'>";
        $html .= "<h6><i class='fas fa-wallet text-success mr-2'></i> <b>Posición de Caja y Liquidez</b></h6>";
        $html .= "<p class='mb-1'>• Patrimonio Bancario Total: <b>$" . number_format($totalCashPrimary, 2) . " $primaryCode</b></p>";
        
        if ($this->selectedBankId !== 'all') {
            $html .= "<p class='mb-1'>• Saldo Específico en Cuenta: <b>$" . number_format($analysis['total_amount'] ?? 0, 2) . " $currencyCode</b></p>";
        }

        if ($netFlow >= 0) {
            $html .= "<p class='text-success mb-0 font-weight-bold'><i class='fas fa-arrow-up mr-1'></i> Flujo Neto del Período: +" . number_format($netFlow, 2) . " $currencyCode (Incremento)</p>";
        } else {
            $html .= "<p class='text-danger mb-0 font-weight-bold'><i class='fas fa-arrow-down mr-1'></i> Flujo Neto del Período: " . number_format($netFlow, 2) . " $currencyCode (Disminución)</p>";
        }
        $html .= "</div>";
        $html .= "</div>";

        // Block 2: Opex/Gastos
        $html .= "<div class='col-md-6 mb-3'>";
        $html .= "<div class='p-3 bg-light rounded border h-100'>";
        $html .= "<h6><i class='fas fa-receipt text-danger mr-2'></i> <b>Estructura de Egresos</b></h6>";
        $html .= "<p class='mb-1'>• Total Egresado: <b>$" . number_format($totalExpenses, 2) . " $currencyCode</b></p>";
        $html .= "<p class='mb-1'>• Gastos Esenciales: <b>$" . number_format($essentialTotal, 2) . " $currencyCode ($essentialPercent%)</b></p>";
        $html .= "<p class='mb-1'>• Gastos Discrecionales: <b>$" . number_format($discretionaryTotal, 2) . " $currencyCode ($discretionaryPercent%)</b></p>";
        $html .= "</div>";
        $html .= "</div>";
        $html .= "</div>";

        // Block 3: Recommendations / Inteligencia
        $html .= "<div class='p-3 bg-light rounded border mt-2 mb-4'>";
        $html .= "<h6><i class='fas fa-brain text-info mr-2'></i> <b>Interpretación del Consultor de Finanzas IA</b></h6>";
        
        if ($totalExpenses == 0.0) {
            $html .= "<p class='mb-0 text-muted'>No se han registrado salidas en el período seleccionado. El flujo de caja está inactivo o no se han cargado gastos bancarios.</p>";
        } else {
            if ($discretionaryPercent > 35) {
                $html .= "<p class='mb-2 text-warning font-weight-bold'><i class='fas fa-exclamation-triangle mr-1'></i> Alerta de Gastos Discrecionales Altos ($discretionaryPercent%)</p>";
                $html .= "<p class='mb-0 text-dark'>Los gastos no esenciales (discrecionales) representan una porción significativa de las salidas en esta cuenta bancaria. Recomendamos auditar las facturas asociadas a esta cuenta y buscar reducir gastos en categorías no críticas para conservar liquidez.</p>";
            } else {
                $html .= "<p class='mb-2 text-success font-weight-bold'><i class='fas fa-check-circle mr-1'></i> Control de Gastos Saludable ($discretionaryPercent% Discrecional)</p>";
                $html .= "<p class='mb-0 text-dark'>La mayor parte de los egresos de esta cuenta están destinados a categorías esenciales (nómina, alquiler, proveedores). Esto demuestra una estructura de costos operativa controlada y eficiente.</p>";
            }

            // Top categories
            $topCats = collect($analysis['categories'])->take(3);
            if ($topCats->isNotEmpty()) {
                $html .= "<div class='mt-3 font-weight-bold f-12'>Top 3 Categorías con mayor consumo de fondos:</div>";
                $html .= "<ul class='mb-0 pl-3 mt-1'>";
                foreach ($topCats as $tc) {
                    $html .= "<li>" . $tc['category_name'] . ": <b>$" . number_format($tc['total_amount'], 2) . " $currencyCode</b> (" . $tc['percentage'] . "%)</li>";
                }
                $html .= "</ul>";
            }
        }
        $html .= "</div>";

        // Block 4: Alertas de saldos bajos
        $lowBanks = collect($bankStatuses)->where('is_low', true);
        if ($lowBanks->isNotEmpty()) {
            $html .= "<div class='p-3 bg-light-danger border border-danger rounded text-danger mb-2'>";
            $html .= "<h6><i class='fas fa-exclamation-circle mr-2'></i> <b>Alertas de Saldo Mínimo Crítico</b></h6>";
            $html .= "<p class='mb-2 small'>Las siguientes cuentas auditadas tienen saldos por debajo de los límites mínimos recomendados (50 USD o 200,000 COP) para cubrir obligaciones operacionales inmediatas:</p>";
            $html .= "<ul class='mb-0 pl-3'>";
            foreach ($lowBanks as $lb) {
                $html .= "<li>Cuenta <b>" . $lb['name'] . "</b>: Saldo actual de <b>" . number_format($lb['balance'], 2) . " " . $lb['currency'] . "</b></li>";
            }
            $html .= "</ul>";
            $html .= "</div>";
        }

        $html .= "</div>";

        return $html;
    }
}
