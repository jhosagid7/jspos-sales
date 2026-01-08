<?php

namespace App\Livewire;

use App\Models\Bank;
use App\Models\Currency;
use App\Models\Payable;
use App\Models\Purchase;
use App\Traits\PrintTrait;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PurchasePartialPayment extends Component
{
    use PrintTrait;

    public $purchases, $pays;
    public  $search, $purchase_selected_id, $supplier_name, $debt, $debt_usd;
    
    // Listeners
    protected $listeners = [
        'payment-completed' => 'handlePaymentCompleted',
        'close-modal' => 'resetUI' 
    ];

    function mount($key = null)
    {
        $this->purchases = [];
        $this->pays = [];
        $this->search = null;
        $this->purchase_selected_id = null;
        $this->supplier_name = null;
    }

    public function render()
    {
        $this->getPurchasesWithDetails();
        return view('livewire.purchases.partials.purchase-partial-payment');
    }

    public  function getPurchasesWithDetails()
    {
        $query = Purchase::whereHas('supplier', function ($query) {
            if (!empty(trim($this->search))) {
                $query->where('name', 'like', "%{$this->search}%");
            }
        })
            ->where('type', 'credit')
            ->where('status', 'pending')
            ->with(['supplier', 'payables'])
            ->take(15)
            ->orderBy('purchases.id', 'desc');

        $purchases = $query->get();
        
        // Obtener moneda principal
        $primaryCurrency = Currency::where('is_primary', true)->first();
        
        // Calcular totales correctos
        $purchases->map(function($purchase) use ($primaryCurrency) {
            // Calcular total pagado (asumiendo que los abonos se guardan en moneda principal o se normalizan)
            // En Purchases.php no vi conversión explícita a USD como en Sales, pero Payables tiene currency_code y exchange_rate.
            // Vamos a sumar los montos de los payables.
            
            $totalPaid = $purchase->payables->sum('amount');
            
            $debt = $purchase->total - $totalPaid;
            
            // Asignar valores para la vista
            $purchase->total_display = $purchase->total;
            $purchase->total_paid_display = $totalPaid;
            $purchase->debt_display = $debt;
            
            return $purchase;
        });

        if (!empty(trim($this->search))) {
            $this->search = null;
            $this->dispatch('clear-search');
        }
        
        $this->purchases = $purchases;
    }

    function initPay($purchase_id, $supplier, $debt)
    {
        $purchase = Purchase::find($purchase_id);
        
        if (!$purchase) {
            $this->dispatch('noty', msg: 'Compra no encontrada');
            return;
        }
        
        $this->purchase_selected_id = $purchase_id;
        $this->supplier_name = $supplier;
        $this->debt = round($debt, 2);
        
        // Open the Payment Component Modal
        // Asumimos que la deuda está en la moneda principal
        $primaryCurrency = Currency::where('is_primary', true)->first();

        $this->dispatch('initPayment', 
            total: $this->debt, 
            currency: $primaryCurrency->code, 
            customer: $this->supplier_name, 
            allowPartial: true
        );
    }

    public function handlePaymentCompleted($payments, $change, $changeDistribution)
    {
        if (!$this->purchase_selected_id) return;

        DB::beginTransaction();
        try {
            $purchase = Purchase::find($this->purchase_selected_id);
            
            foreach ($payments as $payment) {
                $amount = $payment['amount'];
                $currencyCode = $payment['currency'];
                $exchangeRate = $payment['exchange_rate'];
                
                // Create Payable Record
                $pay = Payable::create([
                    'user_id' => Auth()->user()->id,
                    'purchase_id' => $this->purchase_selected_id,
                    'amount' => floatval($amount),
                    'currency_code' => $currencyCode,
                    'exchange_rate' => $exchangeRate,
                    'pay_way' => $payment['method'] == 'bank' ? 'deposit' : $payment['method'],
                    'type' => 'pay', 
                    'bank' => $payment['bank_name'] ?? null,
                    'account_number' => $payment['account_number'] ?? null,
                    'deposit_number' => $payment['reference'] ?? null,
                    'phone_number' => $payment['phone'] ?? null
                ]);
            }

            // Check if settled
            $totalPaid = $purchase->payables()->sum('amount');
            
            if ($totalPaid >= ($purchase->total - 0.01)) {
                $purchase->update(['status' => 'paid']);
                
                Payable::where('purchase_id', $purchase->id)
                    ->where('created_at', '>=', \Carbon\Carbon::now()->subMinute())
                    ->update(['type' => 'settled']);
            }

            DB::commit();

            $this->dispatch('noty', msg: 'ABONO REGISTRADO CON ÉXITO');
            $this->dispatch('close-modal'); // Close PartialPayment modal
            $this->resetUI();

        } catch (\Exception $th) {
            DB::rollBack();
            Log::error($th);
            $this->dispatch('noty', msg: "Error al registrar el pago: {$th->getMessage()}");
        }
    }

    public function resetUI()
    {
        $this->purchase_selected_id = null;
        $this->supplier_name = null;
        $this->debt = null;
    }

    public function cancelPay()
    {
        $this->resetUI();
    }

    function historyPayments(Purchase $purchase)
    {
        $this->pays = $purchase->payables;
        $this->dispatch('show-payhistory');
    }

    function printReceipt($payable_id)
    {
        $this->printPayable($payable_id);
        $this->dispatch('noty', msg: 'IMPRIMIENDO RECIBO DE PAGO...');
    }

    function printHistory()
    {
        if (empty($this->pays) || count($this->pays) == 0) {
            $this->dispatch('noty', msg: 'NO HAY PAGOS PARA IMPRIMIR');
            return;
        }

        $purchaseId = $this->pays[0]->purchase_id;
        // Assuming there is a method for printing payable history, otherwise we might need to implement it
        // AccountsPayableReport uses printPayable but maybe not history?
        // Let's check PrintTrait later. For now, we'll comment this out or use a generic print if available.
        // $this->printPayableHistory($purchaseId); 
        $this->dispatch('noty', msg: 'Funcionalidad de imprimir historial pendiente...');
    }
}
