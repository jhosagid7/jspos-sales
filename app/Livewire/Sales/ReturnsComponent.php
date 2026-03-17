<?php

namespace App\Livewire\Sales;

use Livewire\Component;
use App\Models\Sale;
use App\Models\SaleDetail;
use App\Models\SaleReturn;
use App\Models\SaleReturnDetail;
use App\Models\ProductWarehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ReturnsComponent extends Component
{
    public $saleId;
    public $sale;
    public $returnItems = []; // Array of items with 'qty_to_return'
    public $refundMethod = 'debt_reduction'; // default
    public $cashRegisterId;
    public $reason = '';
    // Calculated values
    public $totalReturnAmount = 0;
    
    // Modals config
    public $registers = [];
    public $warehouses = []; // For selecting destination on "Mal Estado" returns
    public $currencySymbol = '$';

    protected $listeners = [
        'openReturnModal' => 'loadSale'
    ];

    public function mount()
    {
        // Load active cash registers in case refund is cash
        $this->registers = \App\Models\CashRegister::where('status', 'open')->get();
        // Load active warehouses for "Mal Estado" destination selection
        $this->warehouses = \App\Models\Warehouse::where('is_active', true)->get();
        
        $primaryCurrency = \App\Models\Currency::where('is_primary', true)->first();
        if ($primaryCurrency) {
            $this->currencySymbol = $primaryCurrency->symbol ?? $primaryCurrency->code;
        }

    }

    public function loadSale($id)
    {
        $this->reset(['returnItems', 'totalReturnAmount', 'reason']);
        
        $this->saleId = $id;
        $this->sale = Sale::with(['details.product', 'customer'])->findOrFail($id);
        
        // Let's decide default refund method
        // If no debt, assume cash/wallet. If debt, assume debt_reduction.
        if ($this->sale->debt > 0) {
            $this->refundMethod = 'debt_reduction';
        } else {
            $this->refundMethod = 'cash';
        }

        // Prepare returnable items
        foreach ($this->sale->details as $detail) {
            // Find if this detail has already been partially returned
            $alreadyReturned = SaleReturnDetail::where('sale_detail_id', $detail->id)->sum('quantity_returned');
            $qtyAvailable = $detail->quantity - $alreadyReturned;
            
            if ($qtyAvailable > 0) {
                // Determine base unit price for refunds
                // We use regular_price if available, otherwise sale_price
                $baseUnitPrice = $detail->regular_price ?? $detail->sale_price;
                
                // Calculate effective unit price including applied percentages
                $commPct = $this->sale->applied_commission_percent ?? 0;
                $diffPct = $this->sale->applied_exchange_diff_percent ?? 0;
                $combinedPct = ($commPct + $diffPct) / 100;
                
                // Freight is calculated per item if applicable
                // Only if the sale is foreign do the comm/diff apply
                $additionalCharges = 0;
                if ($this->sale->is_foreign_sale) {
                    $additionalCharges = $baseUnitPrice * $combinedPct;
                }
                
                $freightPerItem = 0;
                if ($detail->quantity > 0) {
                     $freightPerItem = $detail->freight_amount / $detail->quantity;
                }
                
                $effectiveUnitPrice = $baseUnitPrice + $additionalCharges + $freightPerItem;
                
                $this->returnItems[$detail->id] = [
                    'detail_id' => $detail->id,
                    'product_id' => $detail->product_id,
                    'product_name' => $detail->product->name ?? 'Producto',
                    'qty_available' => $qtyAvailable,
                    'qty_to_return' => 0, // Default 0
                    'unit_price' => $effectiveUnitPrice,
                    'base_unit_price' => $baseUnitPrice, // Keep it for reference
                    'freight_amount' => $detail->freight_amount, 
                    'condition' => 'good', // default condition 'good' or 'bad'
                    'destination_warehouse_id' => '' // to be chosen if condition is 'bad'
                ];
            }
        }
        
        if (empty($this->returnItems)) {
            $this->dispatch('noty', msg: 'No hay productos disponibles para devolución en esta venta.', type: 'error');
            return;
        }
        
        $this->dispatch('show-return-modal');
    }

    public function calculateTotal()
    {
        $total = 0;
        foreach ($this->returnItems as $index => $item) {
            if (!is_array($item)) continue;

            $qty = (float)($item['qty_to_return'] ?? 0);
            if ($qty > 0) {
                $available = (float)($item['qty_available'] ?? 0);
                $price = (float)($item['unit_price'] ?? 0);

                // Ensure they don't return more than available
                if ($qty > $available) {
                    $qty = $available;
                    $this->returnItems[$index]['qty_to_return'] = $qty;
                }
                $total += ($qty * $price);
            }
        }
        $this->totalReturnAmount = $total;
    }

    // Called whenever input changes
    public function updatedReturnItems()
    {
        $this->calculateTotal();
    }

    public function processReturn()
    {
        $this->calculateTotal();

        if ($this->totalReturnAmount <= 0) {
            $this->dispatch('noty', msg: 'Debe seleccionar al menos 1 producto para devolver.', type: 'warning');
            return;
        }

        if ($this->refundMethod === 'cash' && empty($this->cashRegisterId)) {
            $this->dispatch('noty', msg: 'Seleccione una caja de la cual saldrá el efectivo.', type: 'error');
            return;
        }

        if (empty(trim($this->reason))) {
            $this->dispatch('noty', msg: 'Por favor, indique el motivo de la devolución.', type: 'error');
            return;
        }

        // Validate Bad Condition Warehouses
        foreach ($this->returnItems as $item) {
            if (!is_array($item)) continue;
            $qty = (float)($item['qty_to_return'] ?? 0);
            if ($qty > 0) {
                if (($item['condition'] ?? 'good') === 'bad' && empty($item['destination_warehouse_id'])) {
                    $this->dispatch('noty', msg: 'Para la mercancía en mal estado de "' . ($item['product_name'] ?? 'Producto') . '", debe seleccionar un almacén de destino.', type: 'error');
                    return;
                }
            }
        }

        DB::beginTransaction();
        try {
            $user = auth()->user();
            $canApprove = $user->can('sales.approve_return') || $user->hasRole('Admin');

            // 1. Create Sale Return Header
            $isFullReturn = true;
            foreach ($this->returnItems as $item) {
                if (!is_array($item)) continue;
                $qty = (float)($item['qty_to_return'] ?? 0);
                $avail = (float)($item['qty_available'] ?? 0);
                if ($qty < $avail) {
                    $isFullReturn = false; 
                    break;
                }
            }
            
            $saleReturn = SaleReturn::create([
                'sale_id' => $this->sale->id,
                'customer_id' => $this->sale->customer_id,
                'user_id' => auth()->id(),
                'requested_by' => auth()->id(),
                'requested_at' => Carbon::now(),
                'approved_by' => $canApprove ? auth()->id() : null,
                'approved_at' => $canApprove ? Carbon::now() : null,
                'status' => $canApprove ? 'approved' : 'pending',
                'return_number' => 'DEV-' . strtoupper(Str::random(6)),
                'total_returned' => $this->totalReturnAmount,
                'reason' => $this->reason,
                'return_type' => $isFullReturn ? 'full' : 'partial',
                'refund_method' => $this->refundMethod,
                'cash_register_id' => $this->refundMethod === 'cash' ? $this->cashRegisterId : null,
            ]);

            // Create Details
            foreach ($this->returnItems as $item) {
                if (!is_array($item)) continue;
                $qty = (float)($item['qty_to_return'] ?? 0);
                
                if ($qty > 0) {
                    $detailId = $item['detail_id'] ?? null;
                    $productId = $item['product_id'] ?? null;
                    $unitPrice = (float)($item['unit_price'] ?? 0);
                    $subtotal = $qty * $unitPrice;
                    $condition = $item['condition'] ?? 'good';
                    
                    if ($detailId) {
                        SaleReturnDetail::create([
                            'sale_return_id' => $saleReturn->id,
                            'sale_detail_id' => $detailId,
                            'product_id' => $productId,
                            'quantity_returned' => $qty,
                            'unit_price' => $unitPrice,
                            'subtotal' => $subtotal,
                            'stock_action' => $condition === 'good' ? 'returned_to_stock' : 'damaged'
                        ]);
                    }
                }
            }

            // 2. Adjust Stock & Money ONLY if approved
            if ($canApprove) {
                $this->executeReturnEffects($saleReturn);
            } else {
                // Notify Supervisors
                try {
                    $supervisors = \App\Models\User::permission('sales.approve_return')->get();
                    if ($supervisors->isEmpty()) {
                        $supervisors = \App\Models\User::role('Admin')->get();
                    }
                    foreach ($supervisors as $supervisor) {
                        \Illuminate\Support\Facades\Mail::to($supervisor->email)->send(new \App\Mail\SaleReturnRequested($saleReturn, $user));
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error enviando correo de solicitud de devolución: ' . $e->getMessage());
                }
            }

            DB::commit();

            $this->dispatch('hide-return-modal');
            
            if ($canApprove) {
                $this->dispatch('noty', msg: 'Devolución procesada y aprobada correctamente.', type: 'success');
            } else {
                $this->dispatch('noty', msg: 'Solicitud de devolución enviada a supervisión.', type: 'info');
            }
            
            $this->dispatch('refreshSales'); 

        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('noty', msg: 'Error al procesar: ' . $e->getMessage(), type: 'error');
        }
    }

    /**
     * Executes the actual stock and financial effects of a return
     */
    protected function executeReturnEffects(SaleReturn $saleReturn)
    {
        // Adjust Stock
        foreach ($saleReturn->details as $item) {
            if ($item->product_id) {
                $targetWarehouseId = null;
                $saleDetail = SaleDetail::find($item->sale_detail_id);
                
                if ($item->stock_action === 'returned_to_stock') {
                    if ($saleDetail && $saleDetail->warehouse_id) {
                        $targetWarehouseId = $saleDetail->warehouse_id;
                    }
                } else {
                    // Merma/Damaged - Finding the designated warehouse logic should be here or from the original request
                    // For now, we take it from the original item if we have it, or fallback
                    // (In a real scenario, we might need to store the target warehouse in the detail)
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
    }

    public function ApproveReturn($returnId)
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
            $this->dispatch('refreshSales');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->dispatch('noty', msg: 'Error al aprobar: ' . $e->getMessage(), type: 'error');
        }
    }

    public function RejectReturn($returnId)
    {
        if (!auth()->user()->can('sales.approve_return') && !auth()->user()->hasRole('Admin')) {
            $this->dispatch('noty', msg: 'No tienes permiso para rechazar devoluciones', type: 'error');
            return;
        }

        $saleReturn = SaleReturn::findOrFail($returnId);
        $saleReturn->update([
            'status' => 'rejected',
            'approved_by' => auth()->id(),
            'approved_at' => Carbon::now()
        ]);

        $this->dispatch('noty', msg: 'Devolución rechazada', type: 'info');
        $this->dispatch('refreshSales');
    }

    public function render()
    {
        return view('livewire.sales.returns-component');
    }
}
