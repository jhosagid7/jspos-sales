<?php

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email','password' => 'required','device_name' => 'required']);

        $credentials = [
            'email' => trim(strtolower($request->email)),
            'password' => $request->password,
        ];

        // Use Auth::attempt to ensure consistency with the web login
        if (! Auth::attempt($credentials)) {
            return response()->json([
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        $user = Auth::user();

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
