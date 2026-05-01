<?php

namespace App\Livewire\Reports;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetail;
use App\Models\SaleDetail;
use App\Models\ProductWarehouse;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use App\Traits\CollectionSheetTrait;

class ReturnsReport extends Component
{
    use WithPagination, CollectionSheetTrait;

    public $pagination = 10;
    public $dateFrom, $dateTo;
    public $status = 'all'; // all, pending, approved, rejected
    public $customer_id = 0;
    public $customers = [];
    public $totales = 0;
    public $searchFolio;

    public function mount()
    {
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->endOfMonth()->format('Y-m-d');
        $this->customers = \App\Models\Customer::orderBy('name')->get();
    }

    public function searchData()
    {
        $this->resetPage();
    }

    public function render()
    {
        $returns = $this->getReturns();

        return view('livewire.reports.returns-report', [
            'returns' => $returns
        ]);
    }

    protected function getReturns()
    {
        $query = SaleReturn::with(['customer', 'sale', 'user', 'approver'])
            ->when($this->status != 'all', function ($q) {
                $q->where('status', $this->status);
            })
            ->when($this->customer_id != 0, function ($q) {
                $q->where('customer_id', $this->customer_id);
            })
            ->when(!empty(trim($this->searchFolio)), function ($q) {
                $q->where('return_number', 'like', '%' . trim($this->searchFolio) . '%');
            });

        if ($this->dateFrom && $this->dateTo) {
            $query->whereBetween('created_at', [
                Carbon::parse($this->dateFrom)->startOfDay(),
                Carbon::parse($this->dateTo)->endOfDay()
            ]);
        }

        // Calculate totals for the current filter
        $this->totales = (clone $query)->sum('total_returned');

        return $query->orderBy('id', 'desc')->paginate($this->pagination);
    }

    public function approveReturn($returnId)
    {
        if (!auth()->user()->can('sales.approve_return') && !auth()->user()->hasRole('Admin')) {
            $this->dispatch('noty', msg: 'No tienes permiso para aprobar devoluciones', type: 'error');
            return;
        }

        DB::beginTransaction();
        try {
            $saleReturn = SaleReturn::with('details', 'sale')->findOrFail($returnId);
            
            if ($saleReturn->status !== 'pending') {
                $this->dispatch('noty', msg: 'Esta devolución ya fue procesada', type: 'warning');
                return;
            }

            $saleReturn->update([
                'status' => 'approved',
                'approved_by' => auth()->id(),
                'approved_at' => Carbon::now()
            ]);

            $this->executeReturnEffects($saleReturn);

            DB::commit();
            $this->dispatch('noty', msg: 'Devolución aprobada con éxito', type: 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('noty', msg: 'Error al aprobar: ' . $e->getMessage(), type: 'error');
        }
    }

    public function rejectReturn($returnId)
    {
        if (!auth()->user()->can('sales.approve_return') && !auth()->user()->hasRole('Admin')) {
            $this->dispatch('noty', msg: 'No tienes permiso para rechazar devoluciones', type: 'error');
            return;
        }

        try {
            $saleReturn = SaleReturn::findOrFail($returnId);
            $saleReturn->update([
                'status' => 'rejected',
                'approved_by' => auth()->id(),
                'approved_at' => Carbon::now()
            ]);

            $this->dispatch('noty', msg: 'Devolución rechazada', type: 'info');
        } catch (\Exception $e) {
            $this->dispatch('noty', msg: 'Error al rechazar: ' . $e->getMessage(), type: 'error');
        }
    }

    protected function executeReturnEffects(SaleReturn $saleReturn)
    {
        // Adjust Stock
        foreach ($saleReturn->details as $item) {
            if ($item->product_id) {
                $targetWarehouseId = null;
                $saleDetail = SaleDetail::find($item->sale_detail_id);
                
                if ($item->stock_action === 'returned_to_stock') {
                    if ($saleDetail) {
                         // RESTORE PRODUCT ITEM (BOBINAS/REELS)
                        $meta = json_decode($saleDetail->metadata, true);
                        if ($meta && isset($meta['product_item_id'])) {
                            $pi = \App\Models\ProductItem::find($meta['product_item_id']);
                            if ($pi) {
                                $pi->status = 'available';
                                $pi->save();
                            }
                        }

                        if ($saleDetail->warehouse_id) {
                            $targetWarehouseId = $saleDetail->warehouse_id;
                        }
                    }
                }
                
                if ($targetWarehouseId) {
                    $productWarehouse = ProductWarehouse::firstOrCreate(
                        ['product_id' => $item->product_id, 'warehouse_id' => $targetWarehouseId],
                        ['stock_qty' => 0]
                    );
                    $productWarehouse->stock_qty += $item->quantity_returned;
                    $productWarehouse->save();
                }
            }
        }

        // Handle Money Logic
        if ($saleReturn->refund_method === 'wallet' && $saleReturn->customer_id) {
            $customer = \App\Models\Customer::find($saleReturn->customer_id);
            $customer->wallet_balance += $saleReturn->total_returned;
            $customer->save();
        } 
        elseif ($saleReturn->refund_method === 'cash' && $saleReturn->cash_register_id) {
            \App\Models\CashMovement::create([
                'cash_register_id' => $saleReturn->cash_register_id,
                'user_id' => auth()->id(),
                'type' => 'expense',
                'amount' => $saleReturn->total_returned,
                'concept' => 'Devolución Factura #' . ($saleReturn->sale->invoice_number ?? $saleReturn->sale_id),
            ]);
        }
        
        if ($saleReturn->refund_method === 'debt_reduction') {
            $saleReturn->sale->checkSettlement();
        }
    }
}
