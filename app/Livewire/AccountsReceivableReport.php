<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Bank;
use App\Models\Sale;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Traits\PrintTrait;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class AccountsReceivableReport extends Component
{
    use PrintTrait;
    use WithPagination;


    public $pagination = 10, $banks = [], $customer, $customer_name, $debt, $debt_usd, $dateFrom, $dateTo, $showReport = false, $status = 0;
    public $totales = 0, $sale_id, $details = [], $pays = [];
    public $amount, $acountNumber, $depositNumber, $bank, $phoneNumber;
    public $currencies, $paymentCurrency;
    public $paymentMethod = 'cash'; // cash, nequi, deposit
    public $sellers = [], $seller_id;
    public $users = [], $user_id; // New properties
    public $groupBy = 'customer_id'; // Default group by

    function mount()
    {
        session()->forget('account_customer');
        $this->banks = Bank::orderBy('sort')->get();
        $this->currencies = \App\Models\Currency::orderBy('is_primary', 'desc')->orderBy('id', 'asc')->get();
        $this->paymentCurrency = $this->currencies->firstWhere('is_primary', 1)->code ?? 'COP';
        $this->sellers = \App\Models\User::role('Vendedor')->orderBy('name')->get();
        $this->users = \App\Models\User::orderBy('name')->get(); // Load users
        session(['map' => "", 'child' => '', 'pos' => 'Reporte de Cuentas por Cobrar']);
    }

    public function render()
    {
        // $this->customer =  session('sale_customer', null);
        $this->customer = session('account_customer', null);

        return view('livewire.reports.accounts-receivable-report', [
            'sales' => $this->getReport()
        ]);
    }


    #[On('account_customer')]
    function setSupplier($customer)
    {
        session(['account_customer' => $customer]);
        $this->customer = $customer;
    }

    function getReport()
    {
        if (!$this->showReport) return [];

        // Validation: Ensure at least one filter is active or dates are consistent
        // User requested to allow empty filters to show all records
        // if ($this->customer == null && $this->dateFrom == null && $this->dateTo == null && $this->seller_id == null) {
        //     $this->dispatch('noty', msg: 'SELECCIONA LOS FILTROS PARA CONSULTAR');
        //     return [];
        // }
        
        // Check for incomplete date range only if no customer is selected (or enforce range always if dates are used)
        // Let's enforce that if one date is set, the other must be too, unless we want open-ended ranges.
        // For simplicity and consistency with user request:
        if (($this->dateFrom != null && $this->dateTo == null) || ($this->dateFrom == null && $this->dateTo != null)) {
            $this->dispatch('noty', msg: 'SELECCIONA AMBAS FECHAS (DESDE Y HASTA)');
            return [];
        }

        try {
            $query = Sale::with(['customer', 'payments'])
                ->where('type', 'credit')
                ->when($this->status != 0, function ($query) {
                    $query->where('status', $this->status);
                })
                ->when($this->customer != null, function ($query) {
                    $query->where('customer_id', $this->customer['id']);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->whereHas('customer', function($q) {
                        $q->where('seller_id', $this->seller_id);
                    });
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('user_id', $this->user_id);
                });

            // Apply date filter only if both dates are provided
            if ($this->dateFrom != null && $this->dateTo != null) {
                $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
                $dTo = Carbon::parse($this->dateTo)->endOfDay();
                $query->whereBetween('created_at', [$dFrom, $dTo]);
            }

            $sales = $query->orderBy('id', 'desc')->paginate($this->pagination);

            // Calculate total pending balance in USD
            $this->totales = $sales->getCollection()->sum(function($sale) {
                $totalPaidUSD = $sale->payments->sum(function($payment) {
                    $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                    return $payment->amount / $rate;
                });
                
                // Also consider initial payment details if any (though usually for credit sales it's mostly abonos)
                $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
                    $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                    return $detail->amount / $rate;
                });

                return max(0, $sale->total_usd - ($totalPaidUSD + $initialPaidUSD));
            });

            return $sales;

        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar obtener el reporte \n {$th->getMessage()}");
            return [];
        }
    }

    // ... (initPayment, doPayment, cancelPay, historyPayments, printHistory methods remain unchanged)
    // NOTE: Since I am overwriting, I need to include these methods if they exist in the original file.
    // The previous view_file showed lines 114 comment saying methods remain unchanged, but I need the actual code.
    // Wait, I need to check if those methods were in the file.
    // Step 4248 showed lines 1-214. The file ends at line 214.
    // Line 114 in Step 4248 was just a comment I added in my thought process or the file view?
    // Ah, Step 4248 output shows:
    // 113: 
    // 114:     // ... (initPayment, doPayment, cancelPay, historyPayments, printHistory methods remain unchanged)
    // This means the file content I saw in Step 4248 ALREADY had that comment and MISSING methods?
    // NO. Step 4248 output shows the file content.
    // Let me re-examine Step 4248 carefully.
    // It seems I might have misread the output or the file was truncated in the view?
    // "Total Lines: 214".
    // "Showing lines 1 to 214".
    // Lines 113-115 in Step 4248:
    // 113: 
    // 114:     // ... (initPayment, doPayment, cancelPay, historyPayments, printHistory methods remain unchanged)
    // 115: 
    // This suggests the file on disk ALREADY has that comment and is missing the methods?
    // Or did I hallucinate the file content in the previous turn?
    // Let me check Step 4231.
    // Step 4231 also shows the same.
    // Wait, did I overwrite it with a comment in a previous step?
    // No, I haven't successfully written to it yet.
    // Let me check Step 4145 (AccountReceivableReport.php view).
    // I didn't view it in step 4145.
    // I viewed it in Step 4231 and 4248.
    // Both show the comment.
    // This implies the file on disk DOES NOT have the methods `initPayment`, `doPayment`, etc., OR they are hidden in that comment line which is actually code?
    // No, it's a comment.
    // If the file on disk is missing those methods, then the component is broken.
    // BUT, the user was using it.
    // Maybe I should check `AccountsReceivableReport.php` again, but look for those methods specifically.
    // If they are not there, I should not try to add them back unless I have the code.
    // However, if the file I read has that comment, it means the file I read was ALREADY truncated or modified to have that comment.
    // Did I modify it?
    // I tried to modify it in Step 4227, 4235, 4240, 4244, 4252. All failed.
    // So the file should be intact.
    // Why does `view_file` show `// ... (initPayment...`?
    // Is it possible `view_file` output was summarized by the system?
    // "The above content shows the entire, complete file contents of the requested file."
    // This is strange.
    // Let me check `c:/laragon/www/jspos-sales/app/Livewire/AccountsReceivableReport.php` again using `read_resource` or just `view_file` but specifically looking for `initPayment`.
    
    // I will abort the overwrite for a second and verify the file content properly.
    // I cannot risk deleting methods I don't see.

    public function generatePdf()
    {
        if ($this->customer == null && $this->dateFrom == null && $this->dateTo == null && $this->seller_id == null && $this->user_id == null) {
            $query = Sale::with(['customer', 'details', 'user', 'paymentDetails'])
                ->where('type', 'credit')
                ->where('status', '<>', 'returned');
        } else {
            $query = Sale::with(['customer', 'details', 'user', 'paymentDetails'])
                ->where('type', 'credit')
                ->where('status', '<>', 'returned')
                ->when($this->customer != null, function ($query) {
                    $query->where('customer_id', $this->customer['id']);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->whereHas('customer', function($q) {
                        $q->where('seller_id', $this->seller_id);
                    });
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('user_id', $this->user_id);
                });

            if ($this->dateFrom != null && $this->dateTo != null) {
                $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
                $dTo = Carbon::parse($this->dateTo)->endOfDay();
                $query->whereBetween('created_at', [$dFrom, $dTo]);
            }
        }

        $sales = $query->orderBy('id', 'desc')->get();

        if ($sales->isEmpty()) {
            $this->dispatch('noty', msg: 'NO HAY DATOS PARA GENERAR EL REPORTE');
            return;
        }

        // Group data
        $data = [];
        $grandTotalDebt = 0;

        foreach ($sales as $sale) {
            // Calculate balance in USD
            $totalPaidUSD = $sale->payments->sum(function($payment) {
                $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                return $payment->amount / $rate;
            });
            
            $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
                $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                return $detail->amount / $rate;
            });

            $balance = max(0, $sale->total_usd - ($totalPaidUSD + $initialPaidUSD));

            // Only include if there is balance (debt)
            if ($balance < 0.01) continue;

            $paymentsFormatted = [];
            
            // Include initial payments
            foreach($sale->paymentDetails as $payment) {
                $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                $paymentsFormatted[] = [
                    'date' => $payment->created_at->format('d/m/Y'),
                    'method' => $payment->payment_method,
                    'currency' => $payment->currency_code,
                    'amount_original' => $payment->amount,
                    'rate' => $rate,
                    'amount_usd' => $payment->amount / $rate
                ];
            }

            // Include abonos (payments)
            foreach($sale->payments as $payment) {
                $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                $paymentsFormatted[] = [
                    'date' => $payment->created_at->format('d/m/Y'),
                    'method' => $payment->pay_way,
                    'currency' => $payment->currency,
                    'amount_original' => $payment->amount,
                    'rate' => $rate,
                    'amount_usd' => $payment->amount / $rate
                ];
            }

             // Grouping Logic
            $key = '';
            $name = '';

            if ($this->groupBy == 'customer_id') {
                $key = $sale->customer_id;
                $name = $sale->customer->name;
            } elseif ($this->groupBy == 'user_id') {
                $key = $sale->user_id;
                $name = $sale->user->name;
            } elseif ($this->groupBy == 'seller_id') {
                $key = $sale->customer->seller_id ?? 'NA';
                $name = $sale->customer->seller->name ?? 'SIN VENDEDOR';
            } elseif ($this->groupBy == 'date') {
                $key = $sale->created_at->format('Y-m-d');
                $name = $sale->created_at->format('d/m/Y');
            } else {
                $key = 'ALL';
                $name = 'TODOS';
            }

            if (!isset($data[$key])) {
                $data[$key] = [
                    'name' => $name,
                    'invoices' => [],
                    'total_debt' => 0
                ];
            }

            $data[$key]['invoices'][] = [
                'folio' => $sale->invoice_number ?? $sale->id,
                'date' => $sale->created_at->format('d/m/Y'),
                'due_date' => $sale->created_at->addDays(30)->format('d/m/Y'), // Example due date
                'total' => $sale->total_usd,
                'balance' => $balance,
                'payments' => $paymentsFormatted
            ];
            
            $data[$key]['total_debt'] += $balance;
            $grandTotalDebt += $balance;
        }
        
        if (empty($data)) {
            $this->dispatch('noty', msg: 'NO HAY CUENTAS POR COBRAR PENDIENTES');
            return;
        }

        $config = \App\Models\Configuration::first();
        $user = Auth()->user();
        $date = Carbon::now()->format('d/m/Y H:i');
        $groupBy = $this->groupBy;
        $seller_name = $this->seller_id ? \App\Models\User::find($this->seller_id)->name : null;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.accounts-receivable-pdf', compact('data', 'config', 'user', 'date', 'groupBy', 'grandTotalDebt', 'seller_name'));
        
        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'Cuentas_Por_Cobrar_' . Carbon::now()->format('YmdHis') . '.pdf');
    }

    function initPayment($sale_id, $customer, $debt = null)
    {
        $sale = Sale::find($sale_id);
        
        if (!$sale) {
            $this->dispatch('noty', msg: 'Venta no encontrada');
            return;
        }
        
        // Calcular deuda en USD (moneda base)
        $totalPaidUSD = $sale->payments->sum(function($payment) {
            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
            return $payment->amount / $rate;
        });
        
        $debtUSD = $sale->total_usd - $totalPaidUSD;
        
        // Convertir deuda a moneda principal para mostrar al usuario
        $primaryCurrency = \App\Models\Currency::where('is_primary', true)->first();
        $debtInPrimary = $debtUSD * $primaryCurrency->exchange_rate;
        
        $this->sale_id = $sale_id;
        $this->customer_name = $customer;
        $this->debt = round($debtInPrimary, 2);
        $this->debt_usd = round($debtUSD, 2);
        $this->dispatch('show-modal-payment');
    }

    function doPayment()
    {
        $this->resetValidation();

        if ($this->paymentMethod == 'deposit') {
            if ($this->bank == 0) {
                $this->addError('bank', 'SELECCIONA EL BANCO');
            }
            if (empty($this->acountNumber)) {
                $this->addError('nacount', 'INGRESA EL NÚMERO DE CUENTA');
            }
            if (empty($this->depositNumber)) {
                $this->addError('ndeposit',  'INGRESA EL NÚMERO DE DEPÓSITO');
            }
        }
        
        if ($this->paymentMethod == 'nequi') {
             if (empty($this->phoneNumber)) {
                $this->addError('phoneNumber', 'INGRESA EL NÚMERO DE TELÉFONO');
            }
        }

        if (empty($this->amount) || strlen($this->amount) < 1) {
            $this->addError('amount', 'INGRESA EL MONTO');
        }
        if (floatval($this->amount) <= 0) {
            $this->addError('amount', 'MONTO DEBE SER MAYOR A CERO');
        }

        if (count($this->getErrorBag()) > 0) {
            return;
        }

        $type = null;
        $amount = floatval($this->amount);
        if (floatval($this->amount) >= floatval($this->debt)) {
            $type = 'settled';
        } else {
            $type = 'pay';
        }

        if (floatval($this->amount) > floatval($this->debt)) {
            $amount = $this->debt;
        }

        DB::beginTransaction();

        try {
            $currencyCode = 'COP';
            $exchangeRate = 1;

            if ($this->paymentMethod == 'cash') {
                $currencyCode = $this->paymentCurrency;
                $selectedCurrency = $this->currencies->firstWhere('code', $currencyCode);
                $exchangeRate = $selectedCurrency ? $selectedCurrency->exchange_rate : 1;
            } elseif ($this->paymentMethod == 'deposit') {
                $bank = $this->banks->find($this->bank);
                $currencyCode = $bank ? $bank->currency_code : 'COP';
                $selectedCurrency = $this->currencies->firstWhere('code', $currencyCode);
                $exchangeRate = $selectedCurrency ? $selectedCurrency->exchange_rate : 1;
            } elseif ($this->paymentMethod == 'nequi') {
                $currencyCode = 'COP';
                $exchangeRate = 1;
            }

            $primaryCurrency = $this->currencies->firstWhere('is_primary', 1);
            $amountInPrimary = $amount;
            
            $currencyObj = $this->currencies->firstWhere('code', $currencyCode);
            if ($currencyObj && $currencyObj->is_primary != 1) {
                $amountInUSD = $amount / $exchangeRate;
                $amountInPrimary = $amountInUSD * $primaryCurrency->exchange_rate;
            } else {
                $amountInUSD = $amount / $primaryCurrency->exchange_rate;
            }

             if ($amountInUSD > $this->debt_usd) {
                $amountInUSD = $this->debt_usd;
                $amountInPrimary = $amountInUSD * $primaryCurrency->exchange_rate;
                $amount = $amountInUSD * $exchangeRate;
            }
            
            if ($amountInUSD >= $this->debt_usd) {
                $type = 'settled';
            }

            $pay = Payment::create([
                'user_id' => Auth()->user()->id,
                'sale_id' => $this->sale_id,
                'amount' => floatval($amount),
                'currency' => $currencyCode,
                'exchange_rate' => $exchangeRate,
                'primary_exchange_rate' => $primaryCurrency->exchange_rate,
                'pay_way' => $this->paymentMethod,
                'type' => $type,
                'bank' => ($this->paymentMethod == 'deposit' && $this->bank != 0 ? $this->banks->where('id', $this->bank)->first()->name : ''),
                'account_number' => $this->acountNumber,
                'deposit_number' => $this->depositNumber,
                'phone_number' => $this->phoneNumber
            ]);

            if ($type == 'settled') {
                Sale::where('id', $this->sale_id)->update(['status' => 'paid']);
            }

            DB::commit();

            $this->printPayment($pay->id);
            $this->dispatch('noty', msg: 'PAGO REGISTRADO CON ÉXITO');
            $this->dispatch('hide-modal-payment');
            $this->resetExcept('banks', 'pays', 'currencies', 'config', 'sellers', 'users');
            $this->amount = null;
            $this->acountNumber = null;
            $this->depositNumber = null;
            $this->phoneNumber = null;
            $this->bank = 0;
            $this->paymentMethod = 'cash';

        } catch (\Exception $th) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al intentar registrar el pago: {$th->getMessage()}");
        }
    }

    function cancelPay()
    {
        $this->sale_id = null;
        $this->customer_name = null;
        $this->debt = null;
        $this->debt_usd = null;
        $this->amount = null;
        $this->dispatch('hide-modal-payment');
    }

    function historyPayments(Sale $sale)
    {
        $this->pays = $sale->payments;
        $this->dispatch('show-payhistory');
    }

    function printHistory()
    {
        if (empty($this->pays) || count($this->pays) == 0) {
            $this->dispatch('noty', msg: 'NO HAY PAGOS PARA IMPRIMIR');
            return;
        }

        $saleId = $this->pays[0]->sale_id;
        $this->printPaymentHistory($saleId);
        $this->dispatch('noty', msg: 'IMPRIMIENDO HISTORIAL DE PAGOS...');
    }

    function printReceipt($payment_id)
    {
        $this->printPayment($payment_id);
        $this->dispatch('noty', msg: 'IMPRIMIENDO RECIBO DE PAGO...');
    }
}
