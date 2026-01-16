<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SanitizeUserData
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if ($user) {
            $attributes = $user->getAttributes();
            foreach ($attributes as $key => $value) {
                if (is_string($value)) {
                    $user->$key = $this->cleanString($value);
                }
            }
        }

        return $next($request);
    }

    private function cleanString($string)
    {
        if (is_null($string)) return '';

        // Force UTF-8 first
        $string = iconv('UTF-8', 'UTF-8//IGNORE', $string);
        
        // Remove control characters
        $cleaned = preg_replace('/[\x00-\x1F\x7F]/u', '', $string);
        $string = $cleaned ?? $string;

        // JSON Failsafe
        if (json_encode($string) === false) {
            return "INVALID_ENCODING";
        }
        
        return $string;
    }
}
