<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use App\Models\Customer;
use App\Models\DebitNote;
use Livewire\Component;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class DebitNotes extends Component
{
    use WithPagination;

    public $pagination = 10;
    public $dateFrom, $dateTo;
    public $search = '';
    
    // Form fields
    public $customer_id, $sale_id, $amount, $concept, $currency = 'USD', $exchange_rate = 1;
    public $selected_customer_name = '';

    public function mount()
    {
        $this->dateFrom = Carbon::now()->subDays(30)->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');
        $this->exchange_rate = \App\Models\Configuration::first()->exchange_rate ?? 1;
    }

    public function render()
    {
        return view('livewire.debit-notes', [
            'notes' => $this->getNotes(),
            'customers' => Customer::orderBy('name')->get(['id', 'name']),
            'sales' => $this->customer_id ? Sale::where('customer_id', $this->customer_id)->orderBy('id', 'desc')->limit(20)->get() : []
        ]);
    }

    public function getNotes()
    {
        $query = DebitNote::with(['customer', 'sale', 'user'])
            ->when(!empty(trim($this->search)), function($q) {
                $q->where(function($sub) {
                    $sub->where('debit_number', 'like', '%' . trim($this->search) . '%')
                      ->orWhere('concept', 'like', '%' . trim($this->search) . '%')
                      ->orWhereHas('customer', function($c) {
                          $c->where('name', 'like', '%' . trim($this->search) . '%');
                      });
                });
            });

        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay()
            ]);
        }

        return $query->orderBy('id', 'desc')->paginate($this->pagination);
    }

    public function store()
    {
        if (!auth()->user()->can('manage_debit_notes') && !auth()->user()->hasRole('Admin')) {
            $this->dispatch('noty', msg: 'No tienes permiso para crear notas de débito', type: 'error');
            return;
        }

        $this->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'concept' => 'required|string|min:3',
            'currency' => 'required|string',
            'exchange_rate' => 'required|numeric|min:0'
        ]);

        DB::beginTransaction();
        try {
            $lastNote = DebitNote::latest('id')->first();
            $nextNumber = $lastNote ? $lastNote->id + 1 : 1;
            $debitNumber = 'ND-' . str_pad($nextNumber, 6, '0', STR_PAD_LEFT);

            DebitNote::create([
                'debit_number' => $debitNumber,
                'customer_id' => $this->customer_id,
                'user_id' => auth()->id(),
                'sale_id' => $this->sale_id ?: null,
                'amount' => $this->amount,
                'concept' => $this->concept,
                'currency' => $this->currency,
                'exchange_rate' => $this->exchange_rate,
                'status' => 'pending'
            ]);

            DB::commit();
            $this->reset(['customer_id', 'sale_id', 'amount', 'concept', 'selected_customer_name']);
            $this->dispatch('noty', msg: 'Nota de Débito creada con éxito', type: 'success');
            $this->dispatch('close-modal');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('noty', msg: 'Error: ' . $e->getMessage(), type: 'error');
        }
    }

    public function voidNote($id)
    {
        if (!auth()->user()->can('manage_debit_notes') && !auth()->user()->hasRole('Admin')) {
            $this->dispatch('noty', msg: 'No tienes permiso', type: 'error');
            return;
        }

        $note = DebitNote::findOrFail($id);
        if ($note->status === 'paid') {
            $this->dispatch('noty', msg: 'No se puede anular una nota pagada', type: 'warning');
            return;
        }

        $note->update(['status' => 'voided']);
        $this->dispatch('noty', msg: 'Nota de Débito anulada', type: 'info');
    }

    #[On('set-customer')]
    public function setCustomer($id = null)
    {
        $this->customer_id = $id;
        if($id) {
            $customer = Customer::find($id);
            $this->selected_customer_name = $customer ? $customer->name : '';
            
            // Get sales and dispatch them for TomSelect
            $sales = Sale::where('customer_id', $id)->orderBy('id', 'desc')->limit(20)->get();
            $this->dispatch('update-sales', sales: $sales);
        } else {
            $this->selected_customer_name = '';
            $this->dispatch('update-sales', sales: []);
        }
    }
}
