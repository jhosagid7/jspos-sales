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
        
        Log::info("Intento de login App: Buscando usuario con email: " . $email);

        $credentials = [
            'email' => $email,
            'password' => $request->password,
        ];

        if (! Auth::attempt($credentials)) {
            $userExists = User::where('email', $email)->exists();
            Log::warning("Fallo de login App: El usuario " . ($userExists ? 'SÍ existe en DB' : 'NO existe en DB') . " pero la clave fue rechazada.");
            
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user = Auth::user();
        Log::info("Login App EXITOSO para: " . $user->email);

        // Generate the Sanctum token
        $token = $user->createToken($request->device_name)->plainTextToken;

        return response()->json([
            'token' => $token,
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
