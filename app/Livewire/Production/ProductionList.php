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
        $productions = \App\Models\Production::with('cargos')
            ->join('users', 'users.id', '=', 'productions.user_id')
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
            if ($production->status != 'pending') {
                $this->dispatch('noty', msg: 'Solo se puede eliminar una producción en estado pendiente', type: 'error');
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
        
        if ($production->status != 'pending') {
            $this->dispatch('noty', msg: 'Esta producción ya no está en estado pendiente', type: 'warning');
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

                // Create Cargo for this warehouse (in pending status, without updating stock yet)
                $cargo = \App\Models\Cargo::create([
                    'warehouse_id' => $warehouseId,
                    'user_id' => auth()->id(),
                    'authorized_by' => auth()->user()->name,
                    'motive' => 'Producción del ' . $production->production_date->format('d-m-Y'),
                    'date' => now(),
                    'comments' => 'Generado desde Módulo de Producción #' . $production->id,
                    'status' => 'pending',
                    'production_id' => $production->id
                ]);

                foreach ($details as $detail) {
                    $product = \App\Models\Product::find($detail->product_id);
                    $qty = ($product && $product->is_variable_quantity) ? $detail->weight : $detail->quantity;

                    $itemsJson = null;
                    if ($product && $product->is_variable_quantity && !empty($detail->metadata)) {
                        $metadata = $detail->metadata;
                        $enrichedMetadata = array_map(function($bobina) use ($detail) {
                            $bobina['production_date'] = $bobina['production_date'] ?? ($detail->production_date ? $detail->production_date->format('Y-m-d') : null);
                            $bobina['operator_name'] = $bobina['operator_name'] ?? $detail->operator_name;
                            return $bobina;
                        }, $metadata);
                        $itemsJson = json_encode($enrichedMetadata);
                    }

                    // Create Cargo Detail
                    \App\Models\CargoDetail::create([
                        'cargo_id' => $cargo->id,
                        'product_id' => $detail->product_id,
                        'quantity' => $qty,
                        'cost' => $product->cost ?? 0,
                        'items_json' => $itemsJson,
                    ]);
                }
            }

            // Update Production Status to approved (awaits warehouse approval of the generated Cargo)
            $production->status = 'approved';
            $production->save();

            \Illuminate\Support\Facades\DB::commit();
            $this->dispatch('noty', msg: 'Planilla aprobada y Cargo(s) de Inventario pendiente(s) generado(s) correctamente');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\DB::rollBack();
            $this->dispatch('noty', msg: 'Error al aprobar planilla: ' . $e->getMessage(), type: 'error');
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

            $totalQuantity = 0;
            $totalWeight = 0;
            $resumenRows = [];
            foreach ($production->details as $d) {
                $pName = $d->product->name ?? 'Producto';
                $qty = number_format($d->quantity, 0);
                $w = number_format($d->weight, 2);
                $matType = $d->material_type ? " (" . ($d->material_type === 'OB' ? 'Original' : ($d->material_type === 'RB' ? 'Recuperado' : $d->material_type)) . ")" : '';
                $resumenRows[] = "• {$pName}{$matType}: {$qty} unidades / {$w} Kg";
                $totalQuantity += $d->quantity;
                $totalWeight += $d->weight;
            }
            $resumenDetalles = implode("\n", $resumenRows);
            $businessName = $config->business_name ?? 'Fábrica';

            // 2. Replace Variables in Subject and Body
            $subject = (!empty($config->production_email_subject)) ? $config->production_email_subject : '[SALUDO], Reporte Diario de Producción - [FECHA] (Lote #[PRODUCCION_ID]) - [EMPRESA]';
            $subject = str_replace('[FECHA]', $date, $subject);
            $subject = str_replace('[SALUDO]', $greeting, $subject);
            $subject = str_replace('[USUARIO]', $user, $subject);
            $subject = str_replace('[PRODUCCION_ID]', $production->id, $subject);
            $subject = str_replace('[NOTA]', $production->note ?? 'Sin novedades', $subject);
            $subject = str_replace('[CANTIDAD_TOTAL]', number_format($totalQuantity, 0), $subject);
            $subject = str_replace('[PESO_TOTAL]', number_format($totalWeight, 2), $subject);
            $subject = str_replace('[RESUMEN_DETALLES]', $resumenDetalles, $subject);
            $subject = str_replace('[EMPRESA]', $businessName, $subject);

            $body = (!empty($config->production_email_body)) ? $config->production_email_body : "[SALUDO],\n\nAdjunto a este correo electrónico se encuentra el reporte oficial detallado correspondiente a la jornada de producción del [FECHA].\n\nA continuación, se presenta un resumen de los lotes procesados y consolidados durante este turno:\n\n==================================================\n📝 DATOS GENERALES DE LA ORDEN DE TRABAJO\n==================================================\n• Lote de Producción: #[PRODUCCION_ID]\n• Fecha de Cierre: [FECHA]\n• Operador a Cargo del Reporte: [USUARIO]\n• Empresa / Planta: [EMPRESA]\n\n==================================================\n📊 TOTALES DE PLANTA\n==================================================\n• Cantidad Total Producida: [CANTIDAD_TOTAL] unidades\n• Peso Total de Material Procesado: [PESO_TOTAL] Kg\n\n==================================================\n📦 DESGLOSE POR PRODUCTO Y TIPO DE MATERIAL\n==================================================\n[RESUMEN_DETALLES]\n\n*(El detalle técnico por bobina individual, tipo de resina (Original/Recuperado), y mermas de extrusión y soplado se encuentra desglosado en el PDF adjunto).*\n\n==================================================\n🔍 OBSERVACIONES Y EVENTUALIDADES DE JORNADA\n==================================================\n[NOTA]\n\n--------------------------------------------------\nEste es un reporte automático emitido por el Sistema de Control de Producción y Ventas de [EMPRESA].\n\nQuedamos atentos a cualquier consulta técnica o administrativa.\n\nAtentamente,\nDepartamento de Control de Calidad y Manufactura\n[EMPRESA]";
            $body = str_replace('[FECHA]', $date, $body);
            $body = str_replace('[SALUDO]', $greeting, $body);
            $body = str_replace('[USUARIO]', $user, $body);
            $body = str_replace('[PRODUCCION_ID]', $production->id, $body);
            $body = str_replace('[NOTA]', $production->note ?? 'Sin novedades', $body);
            $body = str_replace('[CANTIDAD_TOTAL]', number_format($totalQuantity, 0), $body);
            $body = str_replace('[PESO_TOTAL]', number_format($totalWeight, 2), $body);
            $body = str_replace('[RESUMEN_DETALLES]', $resumenDetalles, $body);
            $body = str_replace('[EMPRESA]', $businessName, $body);
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
