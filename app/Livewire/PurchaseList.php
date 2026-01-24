<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Purchase;
use App\Models\Product;
use Livewire\WithPagination;
use Livewire\Attributes\Layout;

class PurchaseList extends Component
{
    use WithPagination;

    public $search, $selected_id, $pageTitle, $componentName;
    public $details = [], $viewDetailsModal = false;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Compras';
    }

    #[Layout('layouts.theme.app')]
    public function render()
    {
        $purchases = Purchase::join('users', 'users.id', '=', 'purchases.user_id')
            ->join('suppliers', 'suppliers.id', '=', 'purchases.supplier_id')
            ->select('purchases.*', 'users.name as user_name', 'suppliers.name as supplier_name')
            ->where(function($query) {
                $query->where('suppliers.name', 'like', '%' . $this->search . '%')
                      ->orWhere('purchases.id', 'like', '%' . $this->search . '%');
            })
            ->orderBy('purchases.id', 'desc')
            ->paginate(10);

        return view('livewire.purchases.purchase-list', [
            'purchases' => $purchases
        ]);
    }

    public function viewDetails($id)
    {
        $purchase = Purchase::with('details.product')->find($id);
        if ($purchase) {
            $this->details = $purchase->details;
            $this->dispatch('show-modal', 'detailsModal');
        }
    }

    public function closeDetails()
    {
        $this->viewDetailsModal = false;
        $this->dispatch('hide-modal', 'detailsModal');
    }

    /*
    public function destroy($id)
    {
        // Implement cancellation logic if needed (revert stock, etc.)
        // For now, just delete if status is pending? 
        // Or "Anular" which keeps record but reverts stock.
    }
    */
}
