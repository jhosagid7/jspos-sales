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
    public $customer; 
    public $drivers = [], $driver_id, $selectedSaleId, $filter_driver_id = 'all';
    public $saleHistory = []; 
    public $showPdfModal = false;
    public $pdfUrl = '';
    public $groupBy = 'none'; // New property for grouping
    public $availableGroups = [];
    public $selectedGroups = [];
    public $shouldResetGroups = false;
    
    public $columns = [
        'folio' => true,
        'cliente' => true,
        'operador' => false,
        'vendedor' => false,
        'base' => true,
        'porcentaje' => true,
        'comision' => true,
        'flete' => true,
        'recargo' => true,
        'diferencial' => true,
        'total' => true,
        'credito' => true,
        'acuerdo' => false,
        'articulos' => true,
        'estatus' => true,
        'tipo' => true,
        'fecha' => true,
    ];

    public function searchData()
    {
        $this->shouldResetGroups = true;
        $this->showReport = true;
        $this->dispatch('noty', msg: 'INFO ACTUALIZADA');
    }

    public function updatedGroupBy()
    {
        $this->shouldResetGroups = true;
    }

    function mount()
    {
        session()->forget('sale_customer');
        session(['map' => "TOTAL COSTO $0.00", 'child' => 'TOTAL VENTA $0.00', 'rest' => 'GANANCIA: $0.00 / MARGEN: 0.00%', 'pos' => 'Reporte de Ventas']);

        $this->users = User::orderBy('name')->get();
        $this->sellers = User::sellers()->orderBy('name')->get();
        $this->currencies = \App\Models\Currency::orderBy('id')->get();
        $this->drivers = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Driver', 'chofer', 'repartidor', 'Chofer']);
        })->orderBy('name')->get();
    }

    public function render()
    {
        $reportData = $this->getReport();
        
        $groupedSales = $reportData['groupedSales'] ?? null;
        if ($this->groupBy !== 'none' && $groupedSales) {
            $groupedSales = array_intersect_key($groupedSales, array_flip($this->selectedGroups));
        }

        return view('livewire.reports.salesr', [
            'sales' => $reportData['sales'] ?? [],
            'groupedSales' => $groupedSales ?? [],
            'isGrouped' => $this->groupBy !== 'none'
        ]);
    }

    #[On('sale_customer')]
    function setCustomer($customer = null)
    {
        session(['sale_customer' => $customer]);
        $this->customer = $customer;
    }

    function getReport()
    {
        if (!$this->showReport) return ['sales' => []];

        try {
            $dFrom = null;
            $dTo = null;
            
            if($this->dateFrom && $this->dateTo) {
                $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
                $dTo = Carbon::parse($this->dateTo)->endOfDay();
            }

            $query = Sale::with([
                'customer.seller', 
                'details.product', 
                'user', 
                'driver',
                'paymentDetails' => fn($q) => $q->whereBetween('created_at', [$dFrom, $dTo]),
                'changeDetails' => fn($q) => $q->whereBetween('created_at', [$dFrom, $dTo]),
                'returns' => fn($q) => $q->whereBetween('created_at', [$dFrom, $dTo])
            ])
            ->withCount('history')
            ->when($dFrom && $dTo, function($q) use ($dFrom, $dTo) {
                $q->whereBetween('created_at', [$dFrom, $dTo]);
            })
            ->when(!auth()->user()->can('sales.view_all') && auth()->user()->can('sales.view_own'), function($q) {
                $q->where('user_id', auth()->id());
            })
            ->when($this->user_id != null, function ($q) {
                $q->where('user_id', $this->user_id);
            })
            ->when($this->seller_id != null, function ($q) {
                $q->whereHas('customer', function($sub) {
                    $sub->where('seller_id', $this->seller_id);
                });
            })
            ->when($this->customer != null, function ($q) {
                 $q->where('customer_id', $this->customer['id']);
            })
            ->when(!empty(trim($this->searchFactura)), function ($q) {
                $searchValue = trim($this->searchFactura);
                $q->where(function($sub) use ($searchValue) {
                    $sub->where('id', 'like', "%{$searchValue}%")
                      ->orWhere('invoice_number', 'like', "%{$searchValue}%");
                });
            })
            ->when($this->type != 0, function ($q) {
                $q->where('type', $this->type);
            })
            ->when($this->filter_driver_id !== 'all', function ($q) {
                if ($this->filter_driver_id === 'with_route') {
                    $q->whereNotNull('driver_id');
                } elseif ($this->filter_driver_id === 'without_route') {
                    $q->whereNull('driver_id');
                } else {
                    $q->where('driver_id', $this->filter_driver_id);
                }
            })
            ->orderBy('id', 'desc');

            $sales = [];
            $groupedSales = null;

            if ($this->groupBy === 'none') {
                $sales = (clone $query)->paginate($this->pagination);
                $this->availableGroups = [];
                $this->selectedGroups = [];
            } else {
                // If grouping, get all results (or we could chunk them, but typically grouping implies seeing all for the period)
                $allSales = (clone $query)->get();
                $groupedData = [];

                foreach ($allSales as $sale) {
                    $key = ''; 
                    $name = '';
                    
                    if ($this->groupBy == 'customer_id') {
                        $key = $sale->customer_id ?? 'NA'; 
                        $name = $sale->customer?->name ?? 'SIN CLIENTE';
                    } elseif ($this->groupBy == 'user_id') {
                        $key = $sale->user_id ?? 'NA'; 
                        $name = $sale->user?->name ?? 'SIN OPERADOR';
                    } elseif ($this->groupBy == 'seller_id') {
                        $key = $sale->customer?->seller_id ?? 'NA';
                        $name = $sale->customer?->seller?->name ?? 'SIN VENDEDOR';
                    } elseif ($this->groupBy == 'driver_id') {
                        $key = $sale->driver_id ?? 'NA';
                        $name = $sale->driver?->name ?? 'SIN CHOFER';
                    } elseif ($this->groupBy == 'date') {
                        $key = $sale->created_at->format('Y-m-d'); 
                        $name = $sale->created_at->format('d/m/Y');
                    }

                    if (!isset($groupedData[$key])) { 
                        $groupedData[$key] = ['name' => $name, 'sales' => [], 'total_usd' => 0]; 
                    }
                    $groupedData[$key]['sales'][] = $sale;
                    $groupedData[$key]['total_usd'] += $sale->total_usd;
                }
                
                // Sort by name or date appropriately
                if ($this->groupBy == 'date') {
                    krsort($groupedData); // newest first
                } else {
                    uasort($groupedData, function($a, $b) {
                        return strcmp($a['name'], $b['name']);
                    });
                }
                
                $groupedSales = $groupedData;

                // Build available groups list
                $this->availableGroups = [];
                foreach ($groupedSales as $key => $data) {
                    $this->availableGroups[$key] = $data['name'];
                }

                // Initialize or reset selectedGroups if needed
                if ($this->shouldResetGroups || empty($this->selectedGroups)) {
                    $this->selectedGroups = array_keys($this->availableGroups);
                    $this->shouldResetGroups = false;
                }
            }

            if ($this->groupBy !== 'none') {
                $totalSale = 0.0;
                $totalCost = 0.0;
                foreach ($groupedSales as $groupKey => $groupData) {
                    if (in_array($groupKey, $this->selectedGroups)) {
                        $totalSale += collect($groupData['sales'])->sum('total');
                        foreach ($groupData['sales'] as $sale) {
                            foreach ($sale->details as $detail) {
                                $totalCost += $detail->quantity * ($detail->product->cost ?? 0);
                            }
                        }
                    }
                }
            } else {
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
                    
                $totalCost = $totalCostQuery->sum(DB::raw('sale_details.quantity * products.cost'));
            }

            $profit = $totalSale - $totalCost;
            $this->totales = $totalSale;

            // Actualizar header
            $map = "TOTAL COSTO $" . number_format($totalCost, 2);
            $child = "TOTAL VENTA $" . number_format($totalSale, 2);
            $margin = $totalSale > 0 ? ($profit / $totalSale) * 100 : 0;
            $rest = " GANANCIA: $" . number_format($profit, 2) . " / MARGEN: " . number_format($margin, 2) . "%";

            session(['map' => $map, 'child' => $child, 'rest' => $rest, 'pos' => 'Reporte de Ventas']);
            
            $this->dispatch('update-header', map: $map, child: $child, rest: $rest);
            return [
                'sales' => $sales,
                'groupedSales' => $groupedSales
            ];
            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar obtener el reporte de ventas \n {$th->getMessage()}");
            return [
                'sales' => [],
                'groupedSales' => null
            ];
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

    public function getSaleHistory(Sale $sale)
    {
        $this->sale_id = $sale->id;
        $logs = $sale->history()->with('user')->orderBy('created_at', 'desc')->get();
        
        $processedHistory = [];

        foreach ($logs as $log) {
            $oldData = $log->old_data ?? [];
            $newData = $log->new_data ?? [];

            // Format details and resolve product names dynamically
            $oldDetails = $this->formatHistoryDetails($oldData['details'] ?? []);
            $newDetails = $this->formatHistoryDetails($newData['details'] ?? []);

            // Compute Smart Diff (Added, Removed, Modified, Unchanged)
            $added = [];
            $removed = [];
            $modified = [];
            $unchanged = [];

            // Match new items against old items
            foreach ($newDetails as $newItem) {
                $pid = $newItem['product_id'];
                $oldItem = $pid ? $oldDetails->firstWhere('product_id', $pid) : null;

                if (!$oldItem) {
                    $oldItem = $oldDetails->firstWhere('product_name', $newItem['product_name']);
                }

                if ($oldItem) {
                    $oldQty = (float)$oldItem['quantity'];
                    $newQty = (float)$newItem['quantity'];
                    $oldPrice = (float)$oldItem['sale_price'];
                    $newPrice = (float)$newItem['sale_price'];

                    if (abs($oldQty - $newQty) > 0.0001 || abs($oldPrice - $newPrice) > 0.01) {
                        $modified[] = [
                            'name' => $newItem['product_name'],
                            'old_qty' => $oldQty,
                            'new_qty' => $newQty,
                            'old_price' => $oldPrice,
                            'new_price' => $newPrice,
                            'old_total' => $oldItem['total'],
                            'new_total' => $newItem['total'],
                        ];
                    } else {
                        $unchanged[] = $newItem;
                    }
                } else {
                    $added[] = $newItem;
                }
            }

            // Check for items removed
            foreach ($oldDetails as $oldItem) {
                $pid = $oldItem['product_id'];
                $newItem = $pid ? $newDetails->firstWhere('product_id', $pid) : null;

                if (!$newItem) {
                    $newItem = $newDetails->firstWhere('product_name', $oldItem['product_name']);
                }

                if (!$newItem) {
                    $removed[] = $oldItem;
                }
            }

            $oldTotal = (float)($oldData['total_usd'] ?? $oldData['total'] ?? 0);
            $newTotal = (float)($newData['total_usd'] ?? $newData['total'] ?? 0);

            // Handle Legacy Logs (created before v1.10.322 where newDetails was empty)
            $isLegacyLog = false;
            if ($newDetails->isEmpty() && $oldDetails->isNotEmpty()) {
                $isLegacyLog = true;
                // For legacy logs, newDetails was not saved in DB, so do not mark old items as 'removed'
                $removed = [];
                $added = [];
                $modified = [];

                if ($newTotal <= 0) {
                    $newTotal = (float)($sale->total_usd ?? $sale->total ?? 0);
                }
            }

            // Compare Invoice Configuration (Flete, Comisión, Recargo, Diferencial, Vendedor, Crédito)
            $configChanges = [];

            // 1. Flete %
            $oldFreight = isset($oldData['applied_freight_percent']) ? (float)$oldData['applied_freight_percent'] : null;
            $newFreight = isset($newData['applied_freight_percent']) ? (float)$newData['applied_freight_percent'] : null;
            if ($oldFreight !== null && $newFreight !== null && abs($oldFreight - $newFreight) > 0.01) {
                $configChanges[] = [
                    'label' => 'Flete %',
                    'old' => number_format($oldFreight, 2) . '%',
                    'new' => number_format($newFreight, 2) . '%',
                    'icon' => 'fas fa-truck'
                ];
            }

            // 2. Comisión %
            $oldComm = isset($oldData['applied_commission_percent']) ? (float)$oldData['applied_commission_percent'] : null;
            $newComm = isset($newData['applied_commission_percent']) ? (float)$newData['applied_commission_percent'] : null;
            if ($oldComm !== null && $newComm !== null && abs($oldComm - $newComm) > 0.01) {
                $configChanges[] = [
                    'label' => 'Comisión Vendedor %',
                    'old' => number_format($oldComm, 2) . '%',
                    'new' => number_format($newComm, 2) . '%',
                    'icon' => 'fas fa-percentage'
                ];
            }

            // 3. Recargo Base %
            $oldMarkup = isset($oldData['applied_base_markup_percent']) ? (float)$oldData['applied_base_markup_percent'] : null;
            $newMarkup = isset($newData['applied_base_markup_percent']) ? (float)$newData['applied_base_markup_percent'] : null;
            if ($oldMarkup !== null && $newMarkup !== null && abs($oldMarkup - $newMarkup) > 0.01) {
                $configChanges[] = [
                    'label' => 'Recargo Base %',
                    'old' => number_format($oldMarkup, 2) . '%',
                    'new' => number_format($newMarkup, 2) . '%',
                    'icon' => 'fas fa-chart-line'
                ];
            }

            // 4. Diferencial Cambiario %
            $oldDiff = isset($oldData['applied_exchange_diff_percent']) ? (float)$oldData['applied_exchange_diff_percent'] : null;
            $newDiff = isset($newData['applied_exchange_diff_percent']) ? (float)$newData['applied_exchange_diff_percent'] : null;
            if ($oldDiff !== null && $newDiff !== null && abs($oldDiff - $newDiff) > 0.01) {
                $configChanges[] = [
                    'label' => 'Diferencial Cambiario %',
                    'old' => number_format($oldDiff, 2) . '%',
                    'new' => number_format($newDiff, 2) . '%',
                    'icon' => 'fas fa-exchange-alt'
                ];
            }

            // 5. Días de Crédito
            $oldCreditDays = isset($oldData['credit_days']) ? (int)$oldData['credit_days'] : null;
            $newCreditDays = isset($newData['credit_days']) ? (int)$newData['credit_days'] : null;
            if ($oldCreditDays !== null && $newCreditDays !== null && $oldCreditDays !== $newCreditDays) {
                $configChanges[] = [
                    'label' => 'Días de Crédito',
                    'old' => "{$oldCreditDays} días",
                    'new' => "{$newCreditDays} días",
                    'icon' => 'far fa-calendar-alt'
                ];
            }

            // Resolve Operador que realizó la factura (Creador original)
            $creatorId = $oldData['user_id'] ?? $newData['user_id'] ?? $sale->user_id ?? null;
            $creator = $creatorId ? \App\Models\User::find($creatorId) : null;
            $creatorName = $creator ? $creator->name : 'No registrado';

            // Resolve Vendedores Asignados
            $oldSellerId = $oldData['seller_id'] ?? $oldData['customer']['seller_id'] ?? $sale->seller_id ?? null;
            $newSellerId = $newData['seller_id'] ?? $newData['customer']['seller_id'] ?? $sale->seller_id ?? null;
            $oldSeller = $oldSellerId ? \App\Models\User::find($oldSellerId) : null;
            $newSeller = $newSellerId ? \App\Models\User::find($newSellerId) : null;
            $oldSellerName = $oldSeller ? $oldSeller->name : 'Sin Vendedor';
            $newSellerName = $newSeller ? $newSeller->name : 'Sin Vendedor';

            if ($oldSellerId && $newSellerId && $oldSellerId != $newSellerId) {
                $configChanges[] = [
                    'label' => 'Vendedor Asignado',
                    'old' => $oldSellerName,
                    'new' => $newSellerName,
                    'icon' => 'far fa-user'
                ];
            }

            // Extract snapshots of configuration for side-by-side comparison
            $oldConfigSnapshot = [
                'facturado_por' => $creatorName,
                'vendedor' => $oldSellerName,
                'flete' => isset($oldData['applied_freight_percent']) ? number_format((float)$oldData['applied_freight_percent'], 2) . '%' : '0%',
                'comision' => isset($oldData['applied_commission_percent']) ? number_format((float)$oldData['applied_commission_percent'], 2) . '%' : '0%',
                'recargo' => isset($oldData['applied_base_markup_percent']) ? number_format((float)$oldData['applied_base_markup_percent'], 2) . '%' : '0%',
                'diferencial' => isset($oldData['applied_exchange_diff_percent']) ? number_format((float)$oldData['applied_exchange_diff_percent'], 2) . '%' : '0%',
                'credit_days' => isset($oldData['credit_days']) ? (int)$oldData['credit_days'] . ' días' : '0 días',
            ];

            $newConfigSnapshot = [
                'facturado_por' => $creatorName,
                'vendedor' => $newSellerName,
                'flete' => isset($newData['applied_freight_percent']) ? number_format((float)$newData['applied_freight_percent'], 2) . '%' : '0%',
                'comision' => isset($newData['applied_commission_percent']) ? number_format((float)$newData['applied_commission_percent'], 2) . '%' : '0%',
                'recargo' => isset($newData['applied_base_markup_percent']) ? number_format((float)$newData['applied_base_markup_percent'], 2) . '%' : '0%',
                'diferencial' => isset($newData['applied_exchange_diff_percent']) ? number_format((float)$newData['applied_exchange_diff_percent'], 2) . '%' : '0%',
                'credit_days' => isset($newData['credit_days']) ? (int)$newData['credit_days'] . ' días' : '0 días',
            ];

            $diffTotal = $newTotal - $oldTotal;

            $processedHistory[] = [
                'id' => $log->id,
                'created_at' => $log->created_at,
                'user_name' => $log->user->name ?? 'Usuario',
                'creator_name' => $creatorName,
                'old_seller_name' => $oldSellerName,
                'new_seller_name' => $newSellerName,
                'reason' => $log->reason ?? 'Edición de venta autorizada',
                'old_total' => $oldTotal,
                'new_total' => $newTotal,
                'diff_total' => $diffTotal,
                'added' => $added,
                'removed' => $removed,
                'modified' => $modified,
                'unchanged' => $unchanged,
                'config_changes' => $configChanges,
                'old_config' => $oldConfigSnapshot,
                'new_config' => $newConfigSnapshot,
                'old_details' => $oldDetails->toArray(),
                'new_details' => $newDetails->toArray(),
                'has_new_details' => $newDetails->isNotEmpty(),
                'is_legacy_log' => $isLegacyLog,
            ];
        }

        $this->saleHistory = $processedHistory;
        $this->dispatch('show-history');
    }

    private function formatHistoryDetails($detailsArray)
    {
        if (empty($detailsArray) || !is_array($detailsArray)) {
            return collect();
        }

        return collect($detailsArray)->map(function ($d) {
            $productId = $d['product_id'] ?? null;
            $name = $d['product']['name'] ?? $d['product_name'] ?? null;

            if (!$name || $name === 'Producto') {
                if ($productId) {
                    $product = \App\Models\Product::find($productId);
                    if ($product) {
                        $name = $product->name;
                    }
                }
            }

            if (!$name) {
                $name = 'Producto ' . ($productId ? "#{$productId}" : '');
            }

            $qty = (float)($d['quantity'] ?? 0);
            $price = (float)($d['sale_price'] ?? $d['price'] ?? 0);

            return [
                'product_id' => $productId,
                'product_name' => trim($name),
                'quantity' => $qty,
                'sale_price' => $price,
                'total' => $qty * $price,
            ];
        });
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

                    // RESTORE PRODUCT ITEM (BOBINAS/REELS)
                    $meta = json_decode($detail->metadata, true);
                    if ($meta && isset($meta['product_item_id'])) {
                        $pi = \App\Models\ProductItem::find($meta['product_item_id']);
                        if ($pi) {
                            $pi->status = 'available';
                            $pi->save();
                        }
                    }

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
                        \Illuminate\Support\Facades\Mail::to($supervisor->email)->queue(new \App\Mail\SaleDeletionRequested($sale, $user));
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

    public function editDriver($saleId)
    {
        $this->selectedSaleId = $saleId;
        $sale = Sale::findOrFail($saleId);
        $this->driver_id = $sale->driver_id;
        $this->dispatch('show-driver-modal');
    }

    public function updateDriver()
    {
        if (!$this->selectedSaleId) return;

        $sale = Sale::findOrFail($this->selectedSaleId);
        $sale->update(['driver_id' => $this->driver_id ?: null]);

        $this->dispatch('noty', msg: 'Chofer actualizado correctamente');
        $this->dispatch('hide-driver-modal');
    }

    public function editSale($saleId)
    {
        $sale = Sale::findOrFail($saleId);
        $user = auth()->user();

        $canEditAnytime = $user->can('sales.edit_anytime');
        $canEditTemp = $user->can('sales.edit_temporary') && $sale->is_within_edit_window;

        if (!$canEditAnytime && !$canEditTemp) {
            $this->dispatch('noty', msg: 'No tienes permiso para editar esta venta o el tiempo límite ha expirado.');
            return;
        }

        session(['editing_sale_id' => $saleId]);
        return redirect()->route('sales');
    }

    public function openPdfPreview()
    {
        $params = [
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'user_id' => $this->user_id,
            'seller_id' => $this->seller_id,
            'customer_id' => $this->customer ? $this->customer['id'] : null,
            'type' => $this->type,
            'searchFactura' => $this->searchFactura,
            'driver_id' => $this->filter_driver_id,
            'groupBy' => $this->groupBy,
            'columns' => json_encode($this->columns),
            'selectedGroups' => implode(',', $this->selectedGroups),
        ];

        $this->pdfUrl = route('reports.general.sales.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }
}
