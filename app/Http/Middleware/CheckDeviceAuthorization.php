<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckDeviceAuthorization
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Exclude public routes and login/logout
        if ($request->is('login', 'logout', 'register', 'password/*', 'access-denied')) {
            return $next($request);
        }

        $cookieName = 'device_token';
        $token = $request->cookie($cookieName);
        $device = null;

        if ($token) {
            $device = \App\Models\DeviceAuthorization::where('uuid', $token)->first();
        }

        if (!$device) {
            // New Device
            $token = (string) \Illuminate\Support\Str::uuid();
            $config = \App\Models\Configuration::first();
            $status = ($config && $config->device_access_mode === 'restricted') ? 'pending' : 'approved';

            $device = \App\Models\DeviceAuthorization::create([
                'uuid' => $token,
                'name' => 'Dispositivo ' . \Illuminate\Support\Str::random(4),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'status' => $status,
                'last_accessed_at' => now(),
            ]);

            // Queue cookie for 10 years
            \Illuminate\Support\Facades\Cookie::queue($cookieName, $token, 5256000);
        } else {
            // Existing Device - Update info
            $device->update([
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'last_accessed_at' => now(),
            ]);
        }

        if ($device->status !== 'approved') {
            return redirect()->route('access.denied', ['device_uuid' => $device->uuid]);
        }

        return $next($request);
    }
}
