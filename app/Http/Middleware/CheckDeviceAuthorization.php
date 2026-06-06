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

        // FINGERPRINT FALLBACK: If no device found by token, try to find an existing APPROVED device by IP + UA
        // This prevents duplication from mobile apps that haven't saved their token yet.
        if (!$device) {
            $device = \App\Models\DeviceAuthorization::where('ip_address', $request->ip())
                ->where('user_agent', $request->userAgent() ?? 'Unknown')
                ->where('status', 'approved')
                ->orderBy('last_accessed_at', 'desc')
                ->first();
            
            if ($device) {
                $token = $device->uuid;
                // If it's a web request, sync the cookie so we don't have to fallback again
                if (!$request->expectsJson() && !$request->cookie($cookieName)) {
                    $this->queueDeviceCookie($token);
                }
            }
        }

        if (!$device) {
            $config = \App\Models\Configuration::first();
            $isRestricted = ($config && $config->device_access_mode === 'restricted');
            
            // OPTIMIZATION: If it's an API request, we don't have a token, and we are NOT in restricted mode,
            // do NOT create a new device record. This prevents DB clutter from anonymous app requests.
            // We only create it if it's a Web request (to set cookie) or if it's Restricted (to show 'pending' to admin).
            if ($request->expectsJson() && !$isRestricted) {
                // Return a dummy approved device object to allow access without DB record
                $device = new \App\Models\DeviceAuthorization();
                $device->status = 'approved';
                $device->uuid = $token ?: 'anonymous';
                
                return $next($request);
            }

            // New Device - Use provided token or generate UUID
            $token = $token ?: (string) \Illuminate\Support\Str::uuid();
            $status = $isRestricted ? 'pending' : 'approved';
            
            // Bypass for Super Admin (if already logged in)
            if (auth()->check() && auth()->user()->hasRole('Super Admin')) {
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
                // Update every 2 minutes instead of 60 to allow real-time online status (5m window)
                if (!$device->last_accessed_at || $device->last_accessed_at->diffInMinutes(now()) >= 2) {
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
            // Only Super Admin can bypass / auto-approve their own devices
            if (auth()->check() && auth()->user()->hasRole('Super Admin')) {
                if ($device->status === 'pending') {
                    $device->update(['status' => 'approved']);
                }
                return $next($request);
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

        $response = $next($request);

        // Add the device token to the header for API responses so the app can learn/save it
        if ($device && $device->uuid && $request->expectsJson() && $response instanceof \Illuminate\Http\Response) {
            $response->header('X-Device-Token', $device->uuid);
        }

        return $response;
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
