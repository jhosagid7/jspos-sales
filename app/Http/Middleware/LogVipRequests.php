<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class LogVipRequests
{
    public function handle(Request $request, Closure $next)
    {
        if (str_contains($request->path(), 'vip/')) {
            $user = $request->user();
            $logHtml = date('Y-m-d H:i:s') . " | [" . $request->method() . "] PATH: " . $request->path() . " | IP: " . $request->ip() . " | USER: " . ($user ? $user->id . ' - ' . $user->name : 'NULL') . "\n";
            file_put_contents(public_path('vip_debug.log'), $logHtml, FILE_APPEND);
        }

        $response = $next($request);

        if (str_contains($request->path(), 'vip/')) {
            $logHtml = date('Y-m-d H:i:s') . " | RESPONSE (" . $response->getStatusCode() . "): " . substr($response->getContent(), 0, 100) . "\n";
            file_put_contents(public_path('vip_debug.log'), $logHtml, FILE_APPEND);
        }

        return $response;
    }
}
