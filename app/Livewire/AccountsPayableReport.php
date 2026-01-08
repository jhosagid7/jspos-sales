<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Bank;
use App\Models\Currency;
use App\Models\Payable;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use App\Traits\PrintTrait;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class AccountsPayableReport extends Component
{
    use PrintTrait;
    use WithPagination;


    public $pagination = 10, $banks = [], $supplier, $supplier_name, $debt, $dateFrom, $dateTo, $showReport = false, $status = 0;
    public $totales = 0, $purchase_id, $details = [], $pays = [];
    public $amount, $acountNumber, $depositNumber, $bank, $phoneNumber;
    public $currencies, $paymentCurrency;
    public $paymentMethod = 'cash'; // cash, nequi, deposit



    function mount()
    {
        session()->forget('account_supplier');
        $this->banks = Bank::orderBy('sort')->get();
        $this->currencies = Currency::orderBy('is_primary', 'desc')->orderBy('id', 'asc')->get();
        $this->paymentCurrency = $this->currencies->firstWhere('is_primary', 1)->code ?? 'COP';
        $this->paymentMethod = 'cash';
        session(['map' => "", 'child' => '', 'pos' => 'Reporte de Cuentas por Pagar']);

        if (request()->has('s')) {
            $supplier = \App\Models\Supplier::find(request()->s);
            if ($supplier) {
                session(['account_supplier' => $supplier]);
                $this->supplier = $supplier;
                $this->showReport = true;
            }
        }
    }

    public function render()
    {
        $this->supplier = session('account_supplier', null);

        return view('livewire.reports.accounts-payable-report', [
            'purchases' => $this->getReport()
        ]);
    }


    #[On('account_supplier')]
    function setSupplier($supplier)
    {
        session(['account_supplier' => $supplier]);
        $this->supplier = $supplier;
    }



    function getReport()
    {
        if (!$this->showReport) return [];

        if ($this->supplier == null && $this->dateFrom == null && $this->dateTo == null) {
            $this->dispatch('noty', msg: 'SELECCIONA EL CLIENTE Y/O LAS FECHAS PARA CONSULTAR LAS COMPRAS');
            return;
        }
        if ($this->dateFrom != null && $this->dateTo == null) {
            $this->dispatch('noty', msg: 'SELECCIONA LA FECHA DESDE Y HASTA');
            return;
        }
        if ($this->dateFrom == null && $this->dateTo != null) {
            $this->dispatch('noty', msg: 'SELECCIONA LA FECHA DESDE Y HASTA');
            return;
        }

        try {



            $query = Purchase::with(['supplier', 'payables'])
                ->where('type', 'credit')
                ->when($this->supplier != null, function ($query) {
                    $query->where('supplier_id', $this->supplier['id']);
                })
                ->when($this->status != 0, function ($query) {
                    $query->where('status', $this->status);
                });

            if ($this->dateFrom != null && $this->dateTo != null) {
                $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
                $dTo = Carbon::parse($this->dateTo)->endOfDay();
                $query->whereBetween('created_at', [$dFrom, $dTo]);
            }

            $purchases = $query->orderBy('id', 'desc')->paginate($this->pagination);


            $this->totales = $purchases->sum(function ($purchase) {
                return $purchase->total;
            });

            return $purchases;

            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar obtener el reporte \n {$th->getMessage()}");
            return [];
        }
    }

    function initPayable(Purchase $purchase, $supplier_name)
    {
        $debt = round($purchase->total - $purchase->payables->sum('amount'), 2);
        $this->debt = $debt;
        $this->supplier_name = $supplier_name;
        $this->purchase_id = $purchase->id;
        
        // For Accounts Payable, we assume the purchase total is in the primary currency or handled similarly
        // If Purchases are multi-currency, we might need conversion logic here similar to Sales.
        // Assuming Purchase->total is in primary currency for now based on existing code.
        
        $primaryCurrency = \App\Models\Currency::where('is_primary', true)->first();
        
        $this->dispatch('initPayment', 
            total: $this->debt, 
            currency: $primaryCurrency->code, 
            customer: $supplier_name, // Reusing 'customer' param for supplier name
            allowPartial: true
        );
    }

    #[On('payment-completed')]
    public function handlePaymentCompleted($payments, $change, $changeDistribution)
    {
        if (!$this->purchase_id) return;

        DB::beginTransaction();
        try {
            $purchase = Purchase::find($this->purchase_id);
            $primaryCurrency = \App\Models\Currency::where('is_primary', true)->first();

            foreach ($payments as $payment) {
                $amount = $payment['amount'];
                $currencyCode = $payment['currency'];
                $exchangeRate = $payment['exchange_rate'];
                
                // Determine type
                // We need to check if this payment settles the debt.
                // Since we might have multiple payments, we should check total paid.
                
                $pay = Payable::create([
                    'user_id' => Auth()->user()->id,
                    'purchase_id' => $this->purchase_id,
                    'amount' => floatval($amount),
                    'currency_code' => $currencyCode,
                    'exchange_rate' => $exchangeRate,
                    'pay_way' => $payment['method'] == 'bank' ? 'deposit' : $payment['method'],
                    'type' => 'pay', // Default to pay
                    'bank' => $payment['bank_name'] ?? null,
                    'account_number' => $payment['account_number'] ?? null,
                    'deposit_number' => $payment['reference'] ?? null,
                    'phone_number' => $payment['phone'] ?? null
                ]);
            }

            // Check if settled
            // Recalculate debt
            $totalPaid = $purchase->payables()->sum('amount'); // Assuming amount is in same currency as total
            // If payables store amount in original currency, we need to normalize.
            // Existing code: $purchase->payables->sum('amount')
            // This implies 'amount' in payables is already normalized or in same currency as Purchase total.
            // Let's assume 'amount' is what matters.
            
            // Wait, in Sales we convert to USD. Here Purchases seem simpler or maybe single currency?
            // The existing code used: $purchase->total - $purchase->payables->sum('amount')
            // So we stick to that logic.
            
            if ($totalPaid >= ($purchase->total - 0.01)) {
                $purchase->update(['status' => 'paid']);
                
                Payable::where('purchase_id', $purchase->id)
                    ->where('created_at', '>=', \Carbon\Carbon::now()->subMinute())
                    ->update(['type' => 'settled']);
            }

            DB::commit();

            $this->printPayable($pay->id);
            $this->dispatch('noty', msg: 'PAGO REGISTRADO CON ÉXITO');
            $this->dispatch('hide-modal-payment'); // Close PaymentComponent modal if needed (it closes itself mostly)
            
            $this->reset('debt', 'purchase_id', 'supplier_name');

        } catch (\Exception $th) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al registrar el pago: {$th->getMessage()} ");
        }
    }

    public function cancelPay()
    {
        $this->reset('debt', 'purchase_id', 'supplier_name');
    }

    function historyPayables(Purchase $purchase)
    {
        $this->pays = $purchase->payables;
        $this->dispatch('show-payablehistory');
    }
}
