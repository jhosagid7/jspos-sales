<?php

namespace App\Livewire;

use App\Models\Sale;
use App\Models\DeliveryLocation;
use Livewire\Component;
use Illuminate\Support\Facades\Auth;

class DriverDashboard extends Component
{
    public $sales;
    public $historySales;
    public $tab = 'pending'; // pending, history
    
    // Collection
    public $selectedSaleId = null;
    public $selectedSaleTotal = 0;
    public $collectionNote = '';

    public $collectionPayments = []; // [{currency_id, amount, exchange_rate}]
    public $existingCollections = [];


    public function mount()
    {
        $this->loadSales();
    }

    public function loadSales()
    {
        // Active Sales (Pending or In Transit)
        $this->sales = Sale::where('driver_id', Auth::id())
            ->whereIn('delivery_status', ['pending', 'in_transit'])
            ->with('customer')
            ->orderBy('created_at', 'asc') // Oldest first for delivery priority
            ->get();

        // History Sales (Delivered or Cancelled) - Last 50
        $this->historySales = Sale::where('driver_id', Auth::id())
            ->whereIn('delivery_status', ['delivered', 'cancelled'])
            ->with('customer')
            ->orderBy('delivered_at', 'desc') // Newest delivered first
            ->take(50)
            ->get();
    }

    public function setTab($tab)
    {
        $this->tab = $tab;
    }

    public function updateStatus($saleId, $status, $lat, $lng)
    {
        $sale = Sale::where('id', $saleId)->where('driver_id', Auth::id())->first();

        if ($sale) {
            $sale->delivery_status = $status;
            if ($status == 'delivered') {
                $sale->delivered_at = now();
            }
            $sale->save();

            DeliveryLocation::create([
                'sale_id' => $sale->id,
                'latitude' => $lat,
                'longitude' => $lng,
                'status_at_capture' => $status
            ]);

            $this->loadSales(); // Reload to move to history if delivered
            $this->dispatch('msg-ok', 'Estado actualizado correctamente');
        } else {
            $this->dispatch('msg-error', 'Pedido no encontrado o no asignado');
        }
    }

    public function selectSale($saleId)
    {
        $this->selectedSaleId = $saleId;
        
        // Find sale total
        $sale = $this->sales->firstWhere('id', $saleId);
        $this->selectedSaleTotal = $sale ? $sale->total : 0;

        // Fetch existing collections
        $this->existingCollections = \App\Models\DeliveryCollection::where('sale_id', $saleId)
            ->with(['payments.currency'])
            ->orderBy('created_at', 'desc')
            ->get();

        $this->collectionNote = '';
        // Initialize payments with one empty row or default currency
        $this->collectionPayments = [];
        $this->addPaymentRow();
        
        $this->dispatch('show-collection-modal');
    }

    public function addPaymentRow()
    {
        $defaultCurrency = \App\Models\Currency::first();
        $this->collectionPayments[] = [
            'currency_id' => $defaultCurrency ? $defaultCurrency->id : null,
            'amount' => '',
            'exchange_rate' => $defaultCurrency ? $defaultCurrency->exchange_rate : 1
        ];
    }

    public function removePaymentRow($index)
    {
        unset($this->collectionPayments[$index]);
        $this->collectionPayments = array_values($this->collectionPayments);
    }

    public function saveCollection()
    {
        // Filter out empty payment rows (where amount is empty or 0)
        $this->collectionPayments = array_filter($this->collectionPayments, function($payment) {
            return !empty($payment['amount']) && $payment['amount'] > 0;
        });

        // If no payments and no note, show error
        if (empty($this->collectionPayments) && empty(trim($this->collectionNote))) {
            $this->addError('collectionNote', 'Debe ingresar al menos una nota o un pago.');
            return;
        }

        $this->validate([
            'collectionNote' => 'nullable|string|max:255',
            'collectionPayments.*.amount' => 'required|numeric|min:0.01',
            'collectionPayments.*.currency_id' => 'required|exists:currencies,id'
        ]);

        $collection = \App\Models\DeliveryCollection::create([
            'sale_id' => $this->selectedSaleId,
            'driver_id' => Auth::id(),
            'amount' => 0, 
            'note' => $this->collectionNote
        ]);

        foreach ($this->collectionPayments as $payment) {
            $currency = \App\Models\Currency::find($payment['currency_id']);
            \App\Models\DeliveryCollectionPayment::create([
                'delivery_collection_id' => $collection->id,
                'currency_id' => $payment['currency_id'],
                'amount' => $payment['amount'],
                'exchange_rate' => $currency ? $currency->exchange_rate : 1
            ]);
        }

        $this->dispatch('hide-collection-modal');
        $this->dispatch('msg-ok', 'Novedad registrada correctamente');
        $this->dispatch('hide-collection-modal');
        $this->dispatch('msg-ok', 'Novedad registrada correctamente');
        $this->reset(['collectionPayments', 'collectionNote', 'selectedSaleId', 'selectedSaleTotal', 'existingCollections']);
    }

    public function updateDriverLocation($lat, $lng)
    {
        \App\Models\DriverLocation::create([
            'driver_id' => Auth::id(),
            'latitude' => $lat,
            'longitude' => $lng
        ]);
    }

    public function render()
    {
        return view('livewire.driver-dashboard', [
            'currencies' => \App\Models\Currency::all()
        ])->layout('layouts.theme.app');
    }
}
