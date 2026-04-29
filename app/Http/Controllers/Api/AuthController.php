<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email','password' => 'required','device_name' => 'required']);

        $email = trim(strtolower($request->email));
        $ip = $request->ip();
        
        Log::info("Intento de login App desde IP: [" . $ip . "] Buscando usuario: " . $email);

        $credentials = [
            'email' => $email,
            'password' => $request->password,
        ];

        if (! Auth::attempt($credentials)) {
            $userExists = User::where('email', $email)->exists();
            Log::warning("Fallo de login App (IP: " . $ip . "): El usuario " . ($userExists ? 'SÍ existe en DB' : 'NO existe en DB') . " pero la clave fue rechazada.");
            
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user = Auth::user();
        Log::info("Login App EXITOSO (IP: " . $ip . ") para: " . $user->email);

        // Generate the Sanctum token
        $token = $user->createToken($request->device_name)->plainTextToken;

        // Identify device for the response
        $device = \App\Models\DeviceAuthorization::where('ip_address', $ip)
            ->where('user_agent', $request->userAgent() ?? 'Unknown')
            ->where('status', 'approved')
            ->orderBy('last_accessed_at', 'desc')
            ->first();

        return response()->json([
            'token' => $token,
            'device_uuid' => $device ? $device->uuid : null,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'profile' => $user->profile,
                'order_deadline_at' => $user->order_deadline_at,
                'is_deadline_active' => $user->is_deadline_active,
            ],
        ]);
    }

    /**
     * Log the user out of the application.
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Sesión cerrada correctamente.',
        ]);
    }
}
