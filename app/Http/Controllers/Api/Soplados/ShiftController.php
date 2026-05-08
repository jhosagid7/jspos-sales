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

        return response()->json(['success' => true, 'shift' => $shift]);
    }

    public function open(Request $request)
    {
        $request->validate([
            'type' => 'required|in:day,night',
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id'
        ]);

        // Check if there is already an open shift
        $active = \App\Models\Shift::where('status', 'open')->first();
        if ($active) {
            return response()->json(['success' => false, 'message' => 'Ya existe un turno abierto.'], 400);
        }

        $shift = \App\Models\Shift::create([
            'type' => $request->type,
            'start_time' => now(),
            'status' => 'open',
            'user_id' => auth()->id() ?? $request->user_ids[0]
        ]);

        $shift->users()->attach($request->user_ids);

        return response()->json(['success' => true, 'message' => 'Turno abierto correctamente', 'shift' => $shift->load('users')]);
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
