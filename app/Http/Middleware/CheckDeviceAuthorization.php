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
        // Skip if not installed
        if (config('app.installed') !== true) {
            return $next($request);
        }

        // Exclude public routes and login/logout
        if ($request->is('login', 'logout', 'register', 'password/*', 'access-denied')) {
            return $next($request);
        }

        $cookieName = 'device_token';
        $token = $request->cookie($cookieName) ?? session($cookieName);
        $device = null;

        if ($token) {
            $device = \App\Models\DeviceAuthorization::where('uuid', $token)->first();
            
            // If found in session but cookie is missing, re-sync cookie
            if ($device && !$request->cookie($cookieName)) {
                $this->queueDeviceCookie($token, $request->isSecure());
            }
        }

        if (!$device) {
            // New Device
            $token = (string) \Illuminate\Support\Str::uuid();
            $config = \App\Models\Configuration::first();
            $status = ($config && $config->device_access_mode === 'restricted') ? 'pending' : 'approved';

            try {
                $userAgent = $request->userAgent();
                $userAgent = iconv('UTF-8', 'UTF-8//IGNORE', $userAgent);
                if (!mb_check_encoding($userAgent, 'UTF-8')) {
                    $userAgent = 'Unknown User Agent';
                }

                $device = \App\Models\DeviceAuthorization::create([
                    'uuid' => $token,
                    'name' => 'Dispositivo ' . \Illuminate\Support\Str::random(4),
                    'ip_address' => $request->ip(),
                    'user_agent' => $userAgent,
                    'status' => $status,
                    'last_accessed_at' => now(),
                ]);

                // Save to session and queue cookie
                session([$cookieName => $token]);
                $this->queueDeviceCookie($token, $request->isSecure());

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Device Auth Creation Failed: ' . $e->getMessage());
                $device = new \App\Models\DeviceAuthorization();
                $device->status = 'approved';
                $device->uuid = $token;
            }
        } else {
            // Existing Device - Update info only if it's been more than 30 mins
            try {
                if (!$device->last_accessed_at || $device->last_accessed_at->diffInMinutes(now()) >= 30) {
                    $userAgent = $request->userAgent();
                    $userAgent = iconv('UTF-8', 'UTF-8//IGNORE', $userAgent);
                    if (!mb_check_encoding($userAgent, 'UTF-8')) {
                        $userAgent = 'Unknown User Agent';
                    }

                    $device->update([
                        'ip_address' => $request->ip(),
                        'user_agent' => $userAgent,
                        'last_accessed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                // If update fails, just log it and continue. Do not block the request.
                \Illuminate\Support\Facades\Log::error('Device Auth Update Failed: ' . $e->getMessage());
            }
        }

        if ($device->status !== 'approved') {
            return redirect()->route('access.denied', ['device_uuid' => $device->uuid]);
        }

        return $next($request);
    }

    /**
     * Helper to queue device cookie with consistent parameters
     */
    private function queueDeviceCookie($token, $isSecure)
    {
        \Illuminate\Support\Facades\Cookie::queue(
            'device_token', 
            $token, 
            5256000, 
            '/', 
            null, 
            $isSecure, 
            false, // httpOnly = false so JS can potentially see it if needed for debugging
            false, 
            'Lax'
        );
    }
}
