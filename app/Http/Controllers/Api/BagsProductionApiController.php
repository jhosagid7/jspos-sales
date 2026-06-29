<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Production;
use App\Models\ProductionDetail;
use App\Models\Cargo;
use App\Models\CargoDetail;
use App\Models\Configuration;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class BagsProductionApiController extends Controller
{
    /**
     * Get products filtered by Tag "M&F" or Supplier "M&F Steel".
     */
    public function products(Request $request)
    {
        $query = Product::query()
            ->where(function ($q) {
                $q->whereHas('tags', function ($sub) {
                    $sub->where('name', 'M&F');
                })
                ->orWhereHas('supplier', function ($sub) {
                    $sub->where('name', 'like', '%M&F Steel%');
                });
            });

        if ($request->has('search') && !empty($request->search)) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', '=', $search);
            });
        }

        $products = $query->orderBy('name')
            ->get(['id', 'name', 'sku', 'cost', 'is_variable_quantity']);

        return response()->json($products);
    }

    /**
     * Store bags production and generate pending cargo.
     */
    public function store(Request $request)
    {
        $request->validate([
            'production_date'        => 'required|date_format:Y-m-d',
            'notes'                  => 'nullable|string',
            'details'                => 'required|array|min:1',
            'details.*.product_id'   => 'required|exists:products,id',
            'details.*.quantity'     => 'required|numeric|min:0.0001',
            'details.*.weight'       => 'required|numeric|min:0.0001',
            'details.*.operator_name'=> 'required|string|max:255',
            'details.*.production_date' => 'required|date_format:Y-m-d',
            'details.*.metadata'     => 'nullable|array',
        ]);

        try {
            DB::beginTransaction();

            $config = Configuration::first();
            
            // Resolve warehouse: use configured bags warehouse, fallback to default or first active warehouse
            $warehouseId = $config->bolsas_warehouse_id 
                ?? $config->default_warehouse_id 
                ?? Warehouse::where('is_active', 1)->first()?->id 
                ?? 1;

            // 1. Create Production Header in state 'pending'
            $production = Production::create([
                'user_id'         => auth()->id(),
                'production_date' => $request->production_date,
                'status'          => 'pending',
                'note'            => $request->notes,
            ]);

            // 2. Process Details
            foreach ($request->details as $item) {
                $product = Product::find($item['product_id']);

                // Create ProductionDetail
                ProductionDetail::create([
                    'production_id'   => $production->id,
                    'product_id'      => $item['product_id'],
                    'production_date' => $item['production_date'],
                    'warehouse_id'    => $warehouseId,
                    'material_type'   => 'Original', // Default tag required by DB
                    'quantity'        => $item['quantity'],
                    'weight'          => $item['weight'],
                    'operator_name'   => $item['operator_name'],
                    'metadata'        => $item['metadata'] ?? null,
                    'cost'            => $product->cost ?? 0,
                ]);
            }

            DB::commit();

            // Send immediate receipt email with original PDF to production_email_recipients
            try {
                $config = Configuration::first();
                if ($config && !empty($config->production_email_recipients)) {
                    $production->load(['details.product', 'user']);

                    $date = Carbon::parse($production->production_date)->format('d/m/Y');
                    $userName = auth()->user()->name ?? 'Operador';
                    $businessName = $config->business_name ?? 'Fábrica de Bolsas';

                    $subject = "Copia de Levantamiento Original - Lote #{$production->id} - {$date}";

                    // Build summary
                    $resumenRows = [];
                    $totalQty = 0;
                    $totalWeight = 0;
                    foreach ($production->details as $d) {
                        $pName = $d->product->name ?? 'Producto';
                        $resumenRows[] = "• {$pName}: " . number_format($d->quantity, 2) . " unidades / " . number_format($d->weight, 2) . " Kg (Operario: {$d->operator_name})";
                        $totalQty += $d->quantity;
                        $totalWeight += $d->weight;
                    }
                    $resumen = implode("\n", $resumenRows);

                    $body = "Hola,\n\nEste correo es una copia automática del levantamiento de producción registrado desde la aplicación móvil.\n\n";
                    $body .= "==================================================\n";
                    $body .= "📋 DATOS DEL LEVANTAMIENTO ORIGINAL\n";
                    $body .= "==================================================\n";
                    $body .= "• Lote de Producción: #{$production->id}\n";
                    $body .= "• Fecha de Producción: {$date}\n";
                    $body .= "• Registrado por: {$userName}\n";
                    $body .= "• Empresa: {$businessName}\n";
                    $body .= "• Cantidad Total: " . number_format($totalQty, 2) . " unidades\n";
                    $body .= "• Peso Total: " . number_format($totalWeight, 2) . " Kg\n\n";
                    $body .= "==================================================\n";
                    $body .= "📦 DETALLE DE PRODUCTOS\n";
                    $body .= "==================================================\n";
                    $body .= "{$resumen}\n\n";
                    $body .= "⚠️ Este correo es un comprobante del levantamiento original tal como fue registrado por el operador. Cualquier edición posterior en el sistema no afecta esta copia.\n\n";
                    $body .= "--------------------------------------------------\n";
                    $body .= "Reporte automático emitido por el Sistema de Control de Producción y Ventas de {$businessName}.\n";
                    $body = nl2br($body);

                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.bags_production', compact('production'));
                    $pdf->setPaper('letter', 'portrait');
                    $pdfContent = $pdf->output();
                    $fileName = 'levantamiento_original_lote_' . $production->id . '.pdf';

                    \Illuminate\Support\Facades\Mail::to($config->production_email_recipients)
                        ->queue(new \App\Mail\ProductionReportMail($subject, $body, $pdfContent, $fileName));
                }
            } catch (\Exception $mailEx) {
                \Illuminate\Support\Facades\Log::warning("Receipt email failed for production #{$production->id}: " . $mailEx->getMessage());
            }

            return response()->json([
                'success'       => true,
                'message'       => 'Levantamiento de producción registrado correctamente',
                'production_id' => $production->id,
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al registrar la producción: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Fetch production history.
     */
    public function history(Request $request)
    {
        $query = Production::with(['details.product', 'user'])->orderBy('id', 'desc');

        if ($request->filled('production_date')) {
            $query->whereDate('production_date', $request->production_date);
        }

        if ($request->filled('lifting_date')) {
            $query->whereDate('created_at', $request->lifting_date);
        }

        if ($request->filled('operator_name')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('operator_name', 'like', '%' . $request->operator_name . '%');
            });
        }

        if ($request->filled('product_id')) {
            $query->whereHas('details', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('note', 'like', "%{$search}%")
                  ->orWhereHas('details', function ($sub) use ($search) {
                      $sub->where('operator_name', 'like', "%{$search}%")
                          ->orWhereHas('product', function ($p) use ($search) {
                              $p->where('name', 'like', "%{$search}%")
                                ->orWhere('sku', 'like', "%{$search}%");
                          });
                  });
            });
        }

        $history = $query->paginate(20);

        return response()->json([
            'success' => true,
            'data'    => $history,
        ]);
    }
}
