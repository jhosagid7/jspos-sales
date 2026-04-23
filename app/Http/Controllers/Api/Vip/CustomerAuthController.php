<?php

namespace App\Http\Controllers\Api\Vip;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class CustomerAuthController extends Controller
{
    /**
     * Login for VIP Customers.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $customer = Customer::where('email', strtolower(trim($request->email)))->first();

        // Security Check: Customer must exist, password must match, and they must HAVE a password set (VIP Auth check)
        if (! $customer || ! $customer->password || ! Hash::check($request->password, $customer->password)) {
            Log::warning("Intento de login fallido VIP para el correo: {$request->email}");
            return response()->json([
                'message' => 'Credenciales inválidas o acceso no autorizado para esta cuenta.'
            ], 401);
        }

        // Optional: Check if customer is active, allowed credit, etc if needed.
        // For now, having a password implies they are authorized.

        $token = $customer->createToken('vip-mobile-app')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'taxpayer_id' => $customer->taxpayer_id,
            ]
        ]);
    }

    /**
     * Logout for VIP Customers.
     */
    public function logout(Request $request)
    {
        // Revoke the token that was used to authenticate the current request
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente'
        ]);
    }

    /**
     * Get current authenticated VIP customer details
     */
    public function me(Request $request)
    {
        return response()->json([
            'status' => 'success',
            'customer' => $request->user()
        ]);
    }
}
