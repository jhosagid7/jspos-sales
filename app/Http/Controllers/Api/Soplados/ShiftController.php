<?php

namespace App\Http\Controllers\Api\Soplados;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ShiftController extends Controller
{
    public function current()
    {
        $shift = \App\Models\Shift::with('users')->where('status', 'open')->latest()->first();

        if (!$shift) {
            return response()->json(['success' => true, 'shift' => null]);
        }

        // Add opened_at alias for the Flutter app (DB column is start_time)
        $data = $shift->toArray();
        $data['opened_at'] = $shift->start_time;

        // Calculate Shift Stats (Good vs Damaged)
        $stats = \App\Models\ProductionLog::where('shift_id', $shift->id)
            ->with(['outputs' => function($q) {
                $q->select('production_log_id', 'quality', 'quantity');
            }])
            ->get();

        $good = 0;
        $damaged = 0;
        
        foreach ($stats as $log) {
            foreach ($log->outputs as $out) {
                if ($out->quality == '1st' || $out->quality == '2nd') {
                    $good += $out->quantity;
                } else if ($out->quality == 'damaged') {
                    $damaged += $out->quantity;
                }
            }
        }

        $data['stats'] = [
            'good_production' => $good,
            'damaged_production' => $damaged,
            'total' => $good + $damaged,
            'yield' => ($good + $damaged) > 0 ? round(($good / ($good + $damaged)) * 100, 2) : 100
        ];

        return response()->json(['success' => true, 'shift' => $data]);
    }

    public function open(Request $request)
    {
        // Accept both Spanish (diurno/nocturno) and English (day/night) type values
        $typeMap = ['diurno' => 'day', 'nocturno' => 'night', 'day' => 'day', 'night' => 'night'];

        $request->validate([
            'type' => ['required', \Illuminate\Validation\Rule::in(array_keys($typeMap))],
        ]);

        // Check if there is already an open shift
        $active = \App\Models\Shift::where('status', 'open')->first();
        if ($active) {
            return response()->json(['success' => false, 'message' => 'Ya existe un turno abierto.'], 400);
        }

        $authUserId = auth()->id();
        $resolvedType = $typeMap[$request->type] ?? $request->type;

        $config = \App\Models\Configuration::first();
        $soplados_id = $config->soplados_warehouse_id ?? $config->default_warehouse_id ?? 1;

        $shift = \App\Models\Shift::create([
            'type'         => $resolvedType,
            'start_time'   => now(),
            'status'       => 'open',
            'user_id'      => $authUserId,
            'warehouse_id' => auth()->user()->warehouse_id ?? $soplados_id,
        ]);

        // Attach authenticated user (and any extra user_ids if provided)
        $userIds = collect($request->input('user_ids', []))->push($authUserId)->unique()->filter()->values()->all();
        $shift->users()->attach($userIds);

        // Add opened_at alias for the Flutter app
        $shiftData = $shift->load('users')->toArray();
        $shiftData['opened_at'] = $shift->start_time;

        return response()->json(['success' => true, 'message' => 'Turno abierto correctamente', 'shift' => $shiftData]);
    }

    public function close(Request $request)
    {
        $shift = \App\Models\Shift::where('status', 'open')->latest()->first();

        if (!$shift) {
            return response()->json(['success' => false, 'message' => 'No hay turno abierto para cerrar.'], 400);
        }

        $shift->update([
            'end_time' => now(),
            'status' => 'closed',
            'notes' => $request->notes
        ]);

        return response()->json(['success' => true, 'message' => 'Turno cerrado correctamente']);
    }
}
