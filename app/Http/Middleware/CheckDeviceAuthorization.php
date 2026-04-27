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

        // Exclude public routes and login/logout (Both Web and API)
        if ($request->is('login', 'logout', 'register', 'password/*', 'access-denied', 'api/login', 'api/vip/login')) {
            return $next($request);
        }

        $cookieName = 'device_token';
        $headerName = 'X-Device-Token';

        // Check for token in Cookie, Session, or Custom Header (Apps)
        $token = $request->cookie($cookieName) ?? session($cookieName) ?? $request->header($headerName);
        $device = null;

        if ($token) {
            $device = \App\Models\DeviceAuthorization::where('uuid', $token)->first();
            
            // If found but cookie is missing (and it's a web request), re-sync cookie
            if ($device && !$request->expectsJson() && !$request->cookie($cookieName)) {
                $this->queueDeviceCookie($token);
            }
        }

        if (!$device) {
            // New Device - Use provided token or generate UUID
            $token = $token ?: (string) \Illuminate\Support\Str::uuid();
            $config = \App\Models\Configuration::first();
            $status = ($config && $config->device_access_mode === 'restricted') ? 'pending' : 'approved';
            
            // Bypass for Admins (if already logged in)
            if (auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Super Admin'])) {
                $status = 'approved';
            }

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

                // Save to session and queue cookie if it's a web request
                if (!$request->expectsJson()) {
                    session([$cookieName => $token]);
                    $this->queueDeviceCookie($token);
                }

            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Device Auth Creation Failed: ' . $e->getMessage());
                $device = new \App\Models\DeviceAuthorization();
                $device->status = 'approved';
                $device->uuid = $token;
            }
        } else {
            // Existing Device - Update info
            try {
                if (!$device->last_accessed_at || $device->last_accessed_at->diffInMinutes(now()) >= 60) {
                    $device->update([
                        'ip_address' => $request->ip(),
                        'last_accessed_at' => now(),
                    ]);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Device Auth Update Failed: ' . $e->getMessage());
            }
        }

        if ($device->status !== 'approved') {
            // Check if we can auto-approve this device because the user is an Admin
            if (auth()->check() && auth()->user()->hasAnyRole(['Admin', 'Super Admin'])) {
                $device->update(['status' => 'approved']);
            } else {
                // If it's an API request, return JSON instead of Redirect
                if ($request->expectsJson() || $request->is('api/*')) {
                    return response()->json([
                        'message' => 'Dispositivo no autorizado.',
                        'device_uuid' => $device->uuid,
                        'status' => $device->status,
                        'ip' => $request->ip()
                    ], 403);
                }

                return redirect()->route('access.denied', ['device_uuid' => $device->uuid]);
            }
        }

        return $next($request);
    }

    /**
     * Helper to queue device cookie with consistent parameters
     */
    private function queueDeviceCookie($token)
    {
        // Format: Name, Value, Minutes
        // 5256000 minutes = 10 years
        \Illuminate\Support\Facades\Cookie::queue('device_token', $token, 5256000);
    }
}
