<?php

namespace App\Livewire\Production;

use Livewire\Component;

class ProductionList extends Component
{
    use \Livewire\WithPagination;

    public $search;
    public $selected_id;
    public $pageTitle, $componentName;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Producción';
    }

    #[Layout('layouts.theme.app')]
    public function render()
    {
        $productions = \App\Models\Production::join('users', 'users.id', '=', 'productions.user_id')
            ->select('productions.*', 'users.name as user_name')
            ->orderBy('productions.id', 'desc')
            ->paginate(10);

        return view('livewire.production.production-list', [
            'productions' => $productions
        ]);
    }

    public function delete($id)
    {
        $production = \App\Models\Production::find($id);
        if ($production) {
            if ($production->status == 'sent') {
                $this->dispatch('noty', msg: 'No se puede eliminar una producción ya enviada a inventario', type: 'error');
                return;
            }
            $production->delete();
            $this->dispatch('noty', msg: 'Producción eliminada correctamente');
        }
    }

    public function sendToCargo($id)
    {
        $production = \App\Models\Production::with('details')->find($id);
        if (!$production) return;
        
        if ($production->status == 'sent') {
            $this->dispatch('noty', msg: 'Esta producción ya fue enviada', type: 'warning');
            return;
        }

        // Group details by warehouse
        $groupedDetails = $production->details->groupBy('warehouse_id');

        try {
            \Illuminate\Support\Facades\DB::beginTransaction();

            foreach ($groupedDetails as $warehouseId => $details) {
                // If warehouse_id is null, fallback to default (should not happen if enforced, but safety check)
                if (!$warehouseId) {
                    $warehouse = \App\Models\Warehouse::where('is_active', 1)->first();
                    $warehouseId = $warehouse->id ?? null;
                    if (!$warehouseId) continue; // Skip if no warehouse
                }

                // Create Cargo for this warehouse
                $cargo = \App\Models\Cargo::create([
                    'warehouse_id' => $warehouseId,
                    'user_id' => auth()->id(),
                    'authorized_by' => auth()->user()->name,
                    'motive' => 'Producción del ' . $production->production_date->format('d-m-Y'),
                    'date' => now(),
                    'comments' => 'Generado desde Módulo de Producción #' . $production->id,
                    'status' => 'pending'
                ]);

                foreach ($details as $detail) {
                    // Create Cargo Detail
                    \App\Models\CargoDetail::create([
                        'cargo_id' => $cargo->id,
                        'product_id' => $detail->product_id,
                        'quantity' => $detail->quantity,
                        'cost' => $detail->product->cost ?? 0
                    ]);

                    // Update Stock
                    $product = \App\Models\Product::find($detail->product_id);

                    // Create Product Items (Bobinas) from Metadata
                    if ($product->is_variable_quantity && !empty($detail->metadata)) {
                        foreach ($detail->metadata as $bobina) {
                            \App\Models\ProductItem::create([
                                'product_id' => $detail->product_id,
                                'warehouse_id' => $warehouseId,
                                'quantity' => $bobina['weight'],
                                'original_quantity' => $bobina['weight'],
                                'color' => $bobina['color'] ?? null,
                                'batch' => $bobina['batch'] ?? null,
                                'status' => 'available'
                            ]);
                        }
                    }
                    
                    // Check if pivot exists
                    $pivot = $product->warehouses()->where('warehouse_id', $warehouseId)->first();
                    
                    if ($pivot) {
                        $newQty = $pivot->pivot->stock_qty + $detail->quantity;
                        $product->warehouses()->updateExistingPivot($warehouseId, ['stock_qty' => $newQty]);
                    } else {
                        $product->warehouses()->attach($warehouseId, ['stock_qty' => $detail->quantity]);
                    }
                    
                    // Update global stock
                    $product->stock_qty += $detail->quantity;
                    $product->save();
                }
            }

            // Update Production Status
            $production->status = 'sent';
            $production->save();

            \Illuminate\Support\Facades\DB::commit();
            $this->dispatch('noty', msg: 'Enviado a Cargo(s) e Inventario actualizado correctamente');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->dispatch('noty', msg: 'Error al enviar: ' . $e->getMessage(), type: 'error');
        }
    }

    public $details = [];
    public $viewDetailsModal = false;

    public function viewDetails($id)
    {
        $production = \App\Models\Production::with('details.product')->find($id);
        if ($production) {
            $this->details = $production->details;
            $this->dispatch('show-modal', 'detailsModal');
        }
    }

    public function closeDetails()
    {
        $this->viewDetailsModal = false;
        $this->dispatch('hide-modal', 'detailsModal');
    }

    public function sendEmail($id)
    {
        $production = \App\Models\Production::with(['details.product', 'user'])->find($id);
        if (!$production) return;

        $config = \App\Models\Configuration::first();
        if (!$config || empty($config->production_email_recipients)) {
            $this->dispatch('noty', msg: 'No hay destinatarios configurados en Ajustes', type: 'warning');
            return;
        }

        try {
            // 1. Prepare Variables
            $date = \Carbon\Carbon::parse($production->production_date)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY');
            $user = auth()->user()->name;
            
            $hour = now()->hour;
            $greeting = 'Buenas noches';
            if ($hour >= 5 && $hour < 12) $greeting = 'Buenos días';
            elseif ($hour >= 12 && $hour < 19) $greeting = 'Buenas tardes';

            // 2. Replace Variables in Subject and Body
            $subject = $config->production_email_subject ?? 'Reporte de Producción';
            $subject = str_replace('[FECHA]', $date, $subject);
            $subject = str_replace('[SALUDO]', $greeting, $subject);
            $subject = str_replace('[USUARIO]', $user, $subject);

            $body = $config->production_email_body ?? 'Adjunto reporte de producción.';
            $body = str_replace('[FECHA]', $date, $body);
            $body = str_replace('[SALUDO]', $greeting, $body);
            $body = str_replace('[USUARIO]', $user, $body);
            // Convert newlines to BR for HTML email
            $body = nl2br($body);

            // 3. Generate PDF
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.production', compact('production'));
            $pdf->setPaper('letter', 'portrait');
            $pdfContent = $pdf->output();
            $fileName = 'produccion_' . $production->id . '.pdf';

            // 4. Send Email
            \Illuminate\Support\Facades\Mail::to($config->production_email_recipients)
                ->send(new \App\Mail\ProductionReportMail($subject, $body, $pdfContent, $fileName));

            $this->dispatch('noty', msg: 'Correo enviado correctamente');

        } catch (\Exception $e) {
            $this->dispatch('noty', msg: 'Error al enviar correo: ' . $e->getMessage(), type: 'error');
        }
    }
}
