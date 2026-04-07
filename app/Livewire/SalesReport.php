<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use App\Models\Product;
use Livewire\Component;
use App\Models\SaleDetail;
use Livewire\Attributes\On;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class SalesReport extends Component
{
    use  WithPagination;
    use \App\Traits\PrintTrait;

    public $pagination = 10, $users = [], $user_id, $dateFrom, $dateTo, $showReport = false, $type = 0;
    public $searchFactura;
    public $totales = 0, $sale_id, $sale_status, $details = [];
    public $salesObt;
    public $sale_note;
    public $currencies = [];
    public $sellers = [], $seller_id;
    public $customer; // New property for customer filter

    public function searchData()
    {
        $this->showReport = true;
    }

    function mount()
    {
        session()->forget('sale_customer'); // Clear session
        session(['map' => "TOTAL COSTO $0.00", 'child' => 'TOTAL VENTA $0.00', 'rest' => 'GANANCIA: $0.00 / MARGEN: 0.00%', 'pos' => 'Reporte de Ventas']);

        $this->users = User::orderBy('name')->get();
        $this->sellers = User::role(['Vendedor', 'Vendedor foraneo'])->orderBy('name')->get();
        $this->currencies = \App\Models\Currency::orderBy('id')->get();
    }

    public function render()
    {
        $this->customer = session('sale_customer', null); // Get customer from session

        return view('livewire.reports.salesr', [
            'sales' => $this->getReport()
        ]);
    }

    #[On('sale_customer')] // Listener for customer selection
    function setCustomer($customer)
    {
        session(['sale_customer' => $customer]);
        $this->customer = $customer;
    }

    function getReport()
    {
        if (!$this->showReport) return [];

        // Allow empty filters (return all)
        // if ($this->user_id == null && $this->dateFrom == null && $this->dateTo == null && $this->seller_id == null && $this->customer == null) {
        //     $this->dispatch('noty', msg: 'SELECCIONA LOS FILTROS PARA CONSULTAR LAS VENTAS');
        //     return;
        // }
        
        // ... (date validation logic remains similar, maybe adjust if needed)

        try {
            $dFrom = null;
            $dTo = null;
            
            if($this->dateFrom && $this->dateTo) {
                $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
                $dTo = Carbon::parse($this->dateTo)->endOfDay();
            }

            $sales = Sale::with([
                'customer', 
                'details', 
                'user', 
                'paymentDetails' => fn($q) => $q->whereBetween('created_at', [$dFrom, $dTo]),
                'changeDetails' => fn($q) => $q->whereBetween('created_at', [$dFrom, $dTo]),
                'returns' => fn($q) => $q->whereBetween('created_at', [$dFrom, $dTo])
            ])
                ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                    $q->whereBetween('created_at', [$dFrom, $dTo]);
                })
                ->when(!auth()->user()->can('sales.view_all') && auth()->user()->can('sales.view_own'), function($q) {
                    $q->where('user_id', auth()->id());
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('user_id', $this->user_id);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->whereHas('customer', function($q) {
                        $q->where('seller_id', $this->seller_id);
                    });
                })
                ->when($this->customer != null, function ($query) {
                     $query->where('customer_id', $this->customer['id']);
                })
                ->when(!empty(trim($this->searchFactura)), function ($query) {
                    $searchValue = trim($this->searchFactura);
                    $query->where(function($q) use ($searchValue) {
                        $q->where('id', 'like', "%{$searchValue}%")
                          ->orWhere('invoice_number', 'like', "%{$searchValue}%");
                    });
                })
                ->when($this->type != 0, function ($qry) {
                    $qry->where('type', $this->type);
                })
                ->orderBy('id', 'desc')
                ->paginate($this->pagination);

            // Calcular totales globales (sin paginación)
            $salesQuery = Sale::when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                    $q->whereBetween('created_at', [$dFrom, $dTo]);
                })
                ->when(!auth()->user()->can('sales.view_all') && auth()->user()->can('sales.view_own'), function($q) {
                    $q->where('user_id', auth()->id());
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('user_id', $this->user_id);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->whereHas('customer', function($q) {
                        $q->where('seller_id', $this->seller_id);
                    });
                })
                ->when($this->customer != null, function ($query) {
                     $query->where('customer_id', $this->customer['id']);
                })
                ->when(!empty(trim($this->searchFactura)), function ($query) {
                    $searchValue = trim($this->searchFactura);
                    $query->where(function($q) use ($searchValue) {
                        $q->where('id', 'like', "%{$searchValue}%")
                          ->orWhere('invoice_number', 'like', "%{$searchValue}%");
                    });
                })
                ->when($this->type != 0, function ($qry) {
                    $qry->where('type', $this->type);
                });
                // ->where('status', '<>', 'returned');

            $totalSale = $salesQuery->sum('total');

            // Calcular costo total
            $totalCostQuery = DB::table('sale_details')
                ->join('sales', 'sale_details.sale_id', '=', 'sales.id')
                ->join('products', 'sale_details.product_id', '=', 'products.id')
                ->join('customers', 'sales.customer_id', '=', 'customers.id') 
                ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                    $q->whereBetween('sales.created_at', [$dFrom, $dTo]);
                })
                ->when(!auth()->user()->can('sales.view_all') && auth()->user()->can('sales.view_own'), function($q) {
                    $q->where('sales.user_id', auth()->id());
                })
                ->when($this->user_id != null, function ($query) {
                    $query->where('sales.user_id', $this->user_id);
                })
                ->when($this->seller_id != null, function ($query) {
                    $query->where('customers.seller_id', $this->seller_id);
                })
                ->when($this->customer != null, function ($query) {
                     $query->where('sales.customer_id', $this->customer['id']);
                })
                ->when(!empty(trim($this->searchFactura)), function ($query) {
                    $searchValue = trim($this->searchFactura);
                    $saleId = 0;
                    if (is_numeric($searchValue)) {
                        $saleId = (int)$searchValue;
                    } elseif (preg_match('/^[Ff]0*([1-9][0-9]*)$/', $searchValue, $matches)) {
                        $saleId = (int)$matches[1];
                    }
                    if ($saleId > 0) {
                        $query->where('sales.id', $saleId);
                    }
                })
                ->when($this->type != 0, function ($qry) {
                    $qry->where('sales.type', $this->type);
                });
                // ->where('sales.status', '<>', 'returned');
                
            $totalCost = $totalCostQuery->sum(DB::raw('sale_details.quantity * products.cost'));

            $profit = $totalSale - $totalCost;
            $this->totales = $totalSale;

            // Actualizar header
            $map = "TOTAL COSTO $" . number_format($totalCost, 2);
            $child = "TOTAL VENTA $" . number_format($totalSale, 2);
            $margin = $totalSale > 0 ? ($profit / $totalSale) * 100 : 0;
            $rest = " GANANCIA: $" . number_format($profit, 2) . " / MARGEN: " . number_format($margin, 2) . "%";

            session(['map' => $map, 'child' => $child, 'rest' => $rest, 'pos' => 'Reporte de Ventas']);
            
            $this->dispatch('update-header', map: $map, child: $child, rest: $rest);
            $this->dispatch('noty', msg: 'INFO ACTUALIZADA');
            return $sales;
            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar obtener el reporte de ventas \n {$th->getMessage()}");
            return [];
        }
    }

    function getSaleDetail(Sale $sale)
    {
        // dd($sale->status);
        $this->salesObt = $sale->load(['deliveryCollections.payments.currency']);
        $this->sale_id = $sale->id;
        $this->sale_status = $sale->status;
        $this->details = $sale->details;
        $this->dispatch('show-detail');
    }

    #[On('refreshSales')]
    public function refreshDetails()
    {
        if ($this->sale_id) {
            $sale = Sale::find($this->sale_id);
            if ($sale) {
                $this->salesObt = $sale->load(['deliveryCollections.payments.currency', 'returns.details']);
                $this->sale_status = $sale->status;
                $this->details = $sale->details;
            }
        }
    }

    function getSaleDetailNote(Sale $sale)
    {
        $this->salesObt = $sale;
        $this->sale_id = $sale->id;
        $this->details = $sale->details;
        $this->sale_note = $sale->notes; // Populate the sale_note property
        $this->dispatch('show-detail-note');
    }


    public function saveSaleNote()
    {
        $this->validate([
            'sale_note' => 'nullable|string',
        ]);

        $this->salesObt->update([
            'notes' => $this->sale_note,
        ]);

        $this->dispatch('noty', msg: 'Nota de venta actualizada correctamente');
        $this->dispatch('close-detail-note'); // Close the modal
        return;
    }

    #[On('DestroySale')]
    public function DestroySale($saleId, $reason = null)
    {
        try {
            $user = auth()->user();
            $sale = Sale::findOrFail($saleId);

            // Check if user has permission to approve/force delete
            if ($user->can('sales.approve_deletion')) {
                // Check if reason is provided OR already exists in a request
                if (empty($reason) && empty($sale->deletion_reason)) {
                    $this->dispatch('noty', msg: 'Debes ingresar un motivo para la eliminación');
                    return;
                }

                // APPROVE / DELETE FLOW
                DB::beginTransaction();

                // Log approval if it was a request, or just self-deletion
                $sale->update([
                    'status' => 'returned',
                    'deleted_at' => Carbon::now(),
                    'deletion_approved_by' => $user->id,
                    'deletion_approved_at' => Carbon::now(),
                    'deletion_reason' => $reason ?: $sale->deletion_reason, // Use provided reason or keep existing request reason
                    'deletion_requested_at' => null, // CLEAR REQUEST STATE
                    'deletion_requested_by' => null,
                ]);

                foreach ($sale->details as $detail) {
                    $product = $detail->product;
                    if (!$product) continue;

                    // Calculate quantity to restore based on conversion factor if it was stored
                    // For now, use the detail quantity directly as it matches the deduction logic in Sales::storeOrder
                    $qtyToRestore = $detail->quantity;
                    $warehouseId = $detail->warehouse_id;

                    // Determine Composite Mode (matching Sales.php logic)
                    $isComposite = $product->components->count() > 0;
                    $isPreAssembled = $product->is_pre_assembled;
                    $isDynamic = $isComposite && !$isPreAssembled;

                    if ($isDynamic) {
                        // Dynamic Mode: Restore Components ONLY
                        foreach ($product->components as $component) {
                            $componentQtyToRestore = $qtyToRestore * $component->pivot->quantity;
                            $component->increment('stock_qty', $componentQtyToRestore);

                            if ($warehouseId) {
                                $compWarehouse = \App\Models\ProductWarehouse::where('product_id', $component->id)
                                    ->where('warehouse_id', $warehouseId)
                                    ->first();
                                if ($compWarehouse) {
                                    $compWarehouse->increment('stock_qty', $componentQtyToRestore);
                                } else {
                                    \App\Models\ProductWarehouse::create([
                                        'product_id' => $component->id,
                                        'warehouse_id' => $warehouseId,
                                        'stock_qty' => $componentQtyToRestore
                                    ]);
                                }
                            }
                        }
                    } else {
                        // Normal Product OR Pre-assembled Kit: Restore Product Stock
                        $product->increment('stock_qty', $qtyToRestore);

                        if ($warehouseId) {
                            $productWarehouse = \App\Models\ProductWarehouse::where('product_id', $product->id)
                                ->where('warehouse_id', $warehouseId)
                                ->first();

                            if ($productWarehouse) {
                                $productWarehouse->increment('stock_qty', $qtyToRestore);
                            } else {
                                \App\Models\ProductWarehouse::create([
                                    'product_id' => $product->id,
                                    'warehouse_id' => $warehouseId,
                                    'stock_qty' => $qtyToRestore
                                ]);
                            }
                        }
                    }
                }

                // Restore Balances and Delete Payments
                foreach ($sale->payments as $payment) {
                    // Restore Zelle Balance
                    if ($payment->zelle_record_id) {
                        $zelle = \App\Models\ZelleRecord::find($payment->zelle_record_id);
                        if ($zelle) {
                            $zelle->remaining_balance += $payment->amount;
                            if (abs($zelle->amount - $zelle->remaining_balance) < 0.01) {
                                $zelle->remaining_balance = $zelle->amount;
                                $zelle->status = 'unused';
                            } else {
                                $zelle->status = 'partial';
                            }
                            $zelle->save();
                        }
                    }

                    // Restore Bank Balance
                    if ($payment->bank_record_id) {
                        $bankRec = \App\Models\BankRecord::find($payment->bank_record_id);
                        if ($bankRec) {
                            $bankRec->remaining_balance += $payment->amount;
                            if (abs($bankRec->amount - $bankRec->remaining_balance) < 0.01) {
                                $bankRec->remaining_balance = $bankRec->amount;
                                $bankRec->status = 'unused';
                            } else {
                                $bankRec->status = 'partial';
                            }
                            $bankRec->save();
                        }
                    }

                    $payment->delete();
                }

                $sale->paymentDetails()->delete();
                $sale->changeDetails()->delete();

                DB::commit();

                $this->dispatch('noty', msg: 'Venta eliminada correctamente');

            } else {
                // REQUEST FLOW
                if (empty($reason)) {
                    $this->dispatch('noty', msg: 'Debes ingresar un motivo para solicitar la eliminación');
                    return;
                }

                $sale->update([
                    'deletion_requested_at' => Carbon::now(),
                    'deletion_reason' => $reason,
                    'deletion_requested_by' => $user->id
                ]);

                // Notify Supervisors
                try {
                    $supervisors = User::permission('sales.approve_deletion')->get();
                    foreach ($supervisors as $supervisor) {
                        \Illuminate\Support\Facades\Mail::to($supervisor->email)->send(new \App\Mail\SaleDeletionRequested($sale, $user));
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Error enviando correo de solicitud de eliminación: ' . $e->getMessage());
                }

                $this->dispatch('noty', msg: 'Solicitud enviada al supervisor');
            }

            return;

        } catch (\Exception $th) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al intentar procesar la solicitud \n {$th->getMessage()}");
            return;
        }
    }

    public function RejectDeletion($saleId)
    {
        if (!auth()->user()->can('sales.approve_deletion')) {
            $this->dispatch('noty', msg: 'No tienes permiso para realizar esta acción');
            return;
        }

        $sale = Sale::findOrFail($saleId);
        $sale->update([
            'deletion_requested_at' => null,
            'deletion_reason' => null,
            'deletion_requested_by' => null
        ]);

        $this->dispatch('noty', msg: 'Solicitud de eliminación rechazada');
    }
}
