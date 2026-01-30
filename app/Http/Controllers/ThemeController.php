<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ThemeController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'key' => 'required|string',
            'value' => 'required', 
        ]);

        $user = Auth::user();
        $theme = $user->theme;
        
        // Defensive check: ensure theme is array
        if (is_string($theme)) {
            $theme = json_decode($theme, true);
        }
        
        if (!is_array($theme)) {
            $theme = [];
        }

        \Log::info('Theme Update Request:', $request->all());
        
        $theme[$request->key] = $request->value;
        
        \Log::info('New Theme State:', $theme);
        
        $user->theme = $theme;
        $user->save();

        return response()->json(['success' => true]);
    }
}
