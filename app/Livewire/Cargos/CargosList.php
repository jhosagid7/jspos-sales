<?php

namespace App\Livewire\Cargos;

use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Models\Cargo;

class CargosList extends Component
{
    use \Livewire\WithPagination;

    public $search = '';
    public $dateFrom;
    public $dateTo;
    public $warehouse_id;
    
    // Detail properties
    public $cargo_id;
    public $cargoObt;
    public $details = [];

    // Action properties
    public $action_type = ''; // 'reject' or 'delete'
    public $reason = '';
    
    protected $paginationTheme = 'bootstrap';

    public function mount()
    {
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->endOfMonth()->format('Y-m-d');
    }

    public function getCargoDetail($id)
    {
        $this->cargo_id = $id;
        $this->cargoObt = Cargo::find($id);
        $this->details = $this->cargoObt->details;
        $this->dispatch('show-detail');
    }

    public function approve($id)
    {
        if (!auth()->user()->can('adjustments.approve_cargo')) {
            $this->dispatch('noty', msg: 'No tienes permisos para aprobar cargos.', type: 'error');
            return;
        }

        $cargo = Cargo::find($id);
        if ($id && $cargo->status == 'pending') {
            try {
                \Illuminate\Support\Facades\DB::beginTransaction();

                foreach ($cargo->details as $item) {
                    $product = $item->product;
                    
                    // Create Product Items if variable
                    if ($item->items_json) {
                        $items = json_decode($item->items_json, true);
                        foreach ($items as $bobina) {
                            \App\Models\ProductItem::create([
                                'product_id' => $item->product_id,
                                'warehouse_id' => $cargo->warehouse_id,
                                'quantity' => $bobina['weight'],
                                'original_quantity' => $bobina['weight'],
                                'color' => $bobina['color'] ?? null,
                                'batch' => $bobina['batch'] ?? null,
                                'production_date' => $bobina['production_date'] ?? null,
                                'operator_name' => $bobina['operator_name'] ?? null,
                                'status' => 'available'
                            ]);
                        }
                    }

                    // Update Stock
                    // Check if pivot exists
                    $pivot = $product->warehouses()->where('warehouse_id', $cargo->warehouse_id)->first();
                    
                    if ($pivot) {
                        $newQty = $pivot->pivot->stock_qty + $item->quantity;
                        $product->warehouses()->updateExistingPivot($cargo->warehouse_id, ['stock_qty' => $newQty]);
                    } else {
                        $product->warehouses()->attach($cargo->warehouse_id, ['stock_qty' => $item->quantity]);
                    }
                    
                    // Update global stock
                    $product->stock_qty += $item->quantity;
                    $product->save();
                }

                $cargo->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approval_date' => now()
                ]);

                if ($cargo->production_id) {
                    \App\Models\Production::where('id', $cargo->production_id)->update(['status' => 'sent']);
                }

                \Illuminate\Support\Facades\DB::commit();
                $this->dispatch('noty', msg: 'Cargo aprobado y stock actualizado.');

                if ($cargo->production_id) {
                    $this->checkAndSendConsolidatedEmail($cargo);
                }
                
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                $this->dispatch('noty', msg: 'Error al aprobar: ' . $e->getMessage(), type: 'error');
            }
        }
    }

    public function openActionModal($id, $type)
    {
        $this->cargo_id = $id;
        $this->action_type = $type;
        $this->reason = '';
        $this->dispatch('show-action-modal');
    }

    public function processAction()
    {
        $this->validate([
            'reason' => 'required|min:5|max:255'
        ]);

        $cargo = Cargo::find($this->cargo_id);
        
        if ($this->action_type === 'reject') {
            if (!auth()->user()->can('adjustments.reject_cargo')) {
                $this->dispatch('noty', msg: 'No tienes permisos para rechazar.', type: 'error');
                return;
            }
            $cargo->update([
                'status' => 'rejected',
                'rejection_reason' => $this->reason,
                'rejected_by' => auth()->id(),
                'rejection_date' => now()
            ]);
            $this->dispatch('noty', msg: 'Cargo rechazado.');
        } else {
            if (!auth()->user()->can('adjustments.delete_cargo')) {
                $this->dispatch('noty', msg: 'No tienes permisos para eliminar.', type: 'error');
                return;
            }

            try {
                \Illuminate\Support\Facades\DB::beginTransaction();

                // If cargo was approved, we MUST reverse the stock impacts
                if ($cargo->status == 'approved') {
                    foreach ($cargo->details as $item) {
                        $product = $item->product;

                        // Reverse ProductItems created
                        if ($item->items_json) {
                            $items = json_decode($item->items_json, true);
                            foreach ($items as $bobina) {
                                // Find and delete matching items created by this cargo if still available
                                // Note: if they were sold, we don't have them but we still reduce global stock
                                $foundItem = \App\Models\ProductItem::where('product_id', $item->product_id)
                                    ->where('warehouse_id', $cargo->warehouse_id)
                                    ->where('quantity', $bobina['weight'])
                                    ->where('status', 'available')
                                    ->orderBy('created_at', 'desc') // take the most recent
                                    ->first();

                                if ($foundItem) {
                                    $foundItem->delete();
                                }
                            }
                        }

                        // Reverse Stock (DECREASE)
                        $pivot = $product->warehouses()->where('warehouse_id', $cargo->warehouse_id)->first();
                        
                        if ($pivot) {
                            $newQty = $pivot->pivot->stock_qty - $item->quantity;
                            $product->warehouses()->updateExistingPivot($cargo->warehouse_id, ['stock_qty' => $newQty]);
                        }
                        
                        // Update global stock
                        $product->stock_qty -= $item->quantity;
                        $product->save();
                    }
                }

                $cargo->update([
                    'status' => 'voided',
                    'deletion_reason' => $this->reason,
                    'deleted_by' => auth()->id(),
                    'deletion_date' => now()
                ]);

                \Illuminate\Support\Facades\DB::commit();
                $this->dispatch('noty', msg: 'Cargo eliminado y stock revertido.');

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\DB::rollBack();
                $this->dispatch('noty', msg: 'Error al eliminar: ' . $e->getMessage(), type: 'error');
            }
        }

        $this->dispatch('hide-action-modal');
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function checkAndSendConsolidatedEmail($cargo)
    {
        try {
            $productionId = $cargo->production_id;
            if (!$productionId) {
                return;
            }
            
            // Query all cargos belonging to this production
            $cargos = \App\Models\Cargo::where('production_id', $productionId)->get();
                
            if ($cargos->isEmpty()) {
                return;
            }
            
            // Check if there are any pending cargos from this group
            $pendingCount = $cargos->where('status', 'pending')->count();
            if ($pendingCount > 0) {
                // Not all cargos from this production are approved yet
                return;
            }
            
            // All cargos are approved (or rejected/voided, i.e., none are pending)
            // Let's filter to get only the approved ones for the PDF report
            $approvedCargos = $cargos->where('status', 'approved');
            if ($approvedCargos->isEmpty()) {
                return;
            }
            
            // Get unique production IDs from approved cargos
            $productionIds = $approvedCargos->pluck('production_id')->unique();
            $productions = \App\Models\Production::with(['details.product'])
                ->whereIn('id', $productionIds)
                ->get();
                
            if ($productions->isEmpty()) {
                return;
            }
            
            $config = \App\Models\Configuration::first();
            if (!$config || empty($config->production_email_recipients)) {
                return;
            }
            
            // Generate PDFs for each production day
            $pdfs = [];
            $totalWeight = 0;
            $totalQuantity = 0;
            $resumenDetalles = "";
            
            foreach ($productions as $prod) {
                $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.bags_production', ['production' => $prod]);
                $pdf->setPaper('letter', 'portrait');
                $pdfContent = $pdf->output();
                
                $prodDateStr = \Carbon\Carbon::parse($prod->production_date)->format('Y-m-d');
                $pdfs[] = [
                    'content' => $pdfContent,
                    'name'    => 'produccion_bolsas_' . $prodDateStr . '_lote_' . $prod->id . '.pdf',
                ];
                
                // Aggregate metrics for email body
                $prodWeight = $prod->details->sum('weight');
                $prodQty = $prod->details->sum('quantity');
                $totalWeight += $prodWeight;
                $totalQuantity += $prodQty;
                
                $resumenDetalles .= "📅 Día de Producción: " . \Carbon\Carbon::parse($prod->production_date)->format('d/m/Y') . " (Lote #{$prod->id})\n";
                $resumenDetalles .= "• Cantidad: " . number_format($prodQty, 2) . " unidades\n";
                $resumenDetalles .= "• Peso: " . number_format($prodWeight, 2) . " Kg\n";
                $resumenDetalles .= "--------------------------------------------------\n";
            }
            
            // Email metadata replacements
            $date = \Carbon\Carbon::now()->format('d/m/Y');
            $greeting = "Hola";
            $hour = \Carbon\Carbon::now()->hour;
            if ($hour < 12) {
                $greeting = "Buenos días";
            } elseif ($hour < 18) {
                $greeting = "Buenas tardes";
            } else {
                $greeting = "Buenas noches";
            }
            
            $user = auth()->user()->name;
            $businessName = $config->business_name ?? 'Fábrica de Bolsas';
            
            $prodIdsStr = $productionIds->implode(', ');
            
            $subject = (!empty($config->production_email_subject)) ? $config->production_email_subject : "Planilla de Levantamiento de la Fábrica de Bolsas - Lote #[PRODUCCION_ID] - [FECHA]";
            $subject = str_replace('[FECHA]', $date, $subject);
            $subject = str_replace('[PESO_TOTAL]', number_format($totalWeight, 2), $subject);
            $subject = str_replace('[RESUMEN_DETALLES]', $resumenDetalles, $subject);
            $subject = str_replace('[EMPRESA]', $businessName, $subject);
            $subject = str_replace('[PRODUCCION_ID]', $prodIdsStr, $subject);
            
            $body = (!empty($config->production_email_body)) ? $config->production_email_body : "[SALUDO],\n\nAdjunto a este correo electrónico se encuentra el reporte consolidado detallado correspondiente al levantamiento de la Fábrica de Bolsas del [FECHA].\n\nA continuación, se presenta un resumen de la planilla procesada y aprobada:\n\n==================================================\n📊 RESUMEN DE LEVANTAMIENTO\n==================================================\n• Lote(s) de Producción: #[PRODUCCION_ID]\n• Fecha de Registro: [FECHA]\n• Aprobado Por: [USUARIO]\n• Empresa / Planta: [EMPRESA]\n• Cantidad Total Levantada: [CANTIDAD_TOTAL] unidades\n• Peso Total Levantado: [PESO_TOTAL] Kg\n\n==================================================\n📝 DETALLE POR PLANILLA\n==================================================\n[RESUMEN_DETALLES]\n\n*(El desglose por producto, peso por rollo y operario fabricante se encuentra detallado en los archivos PDF adjuntos independientes para cada lote).* \n\n--------------------------------------------------\nEste es un reporte automático emitido por el Sistema de Control de Producción y Ventas de [EMPRESA].\n\nQuedamos atentos a cualquier consulta técnica o administrativa.\n\nAtentamente,\nDepartamento de Control de Calidad y Manufactura\n[EMPRESA]";
            
            $body = str_replace('[FECHA]', $date, $body);
            $body = str_replace('[SALUDO]', $greeting, $body);
            $body = str_replace('[USUARIO]', $user, $body);
            $body = str_replace('[CANTIDAD_TOTAL]', number_format($totalQuantity, 2), $body);
            $body = str_replace('[PESO_TOTAL]', number_format($totalWeight, 2), $body);
            $body = str_replace('[RESUMEN_DETALLES]', $resumenDetalles, $body);
            $body = str_replace('[EMPRESA]', $businessName, $body);
            $body = str_replace('[PRODUCCION_ID]', $prodIdsStr, $body);
            
            $body = nl2br($body);
            
            \Illuminate\Support\Facades\Mail::to($config->production_email_recipients)
                ->send(new \App\Mail\BagsProductionConsolidatedMail($subject, $body, $pdfs));
                
            $this->dispatch('noty', msg: 'Correo consolidado enviado correctamente.');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Failed to send bags consolidated report email: " . $e->getMessage());
            $this->dispatch('noty', msg: 'Error al enviar correo consolidado: ' . $e->getMessage(), type: 'error');
        }
    }

    #[Layout('layouts.theme.app')]
    public function render()
    {
        $cargos = \App\Models\Cargo::with(['warehouse', 'user'])
            ->when($this->search, function ($q) {
                $q->where('motive', 'like', '%' . $this->search . '%')
                  ->orWhere('authorized_by', 'like', '%' . $this->search . '%')
                  ->orWhere('id', 'like', '%' . $this->search . '%');
            })
            ->when($this->warehouse_id, function ($q) {
                $q->where('warehouse_id', $this->warehouse_id);
            })
            ->when($this->dateFrom && $this->dateTo, function ($q) {
                $q->whereBetween('date', [$this->dateFrom . ' 00:00:00', $this->dateTo . ' 23:59:59']);
            })
            ->orderBy('date', 'desc')
            ->paginate(10);

        return view('livewire.cargos.cargos-list', [
            'cargos' => $cargos,
            'warehouses' => \App\Models\Warehouse::where('is_active', 1)->get()
        ]);
    }
}
