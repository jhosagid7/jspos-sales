<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Services\CashRegisterService;
use Illuminate\Support\Facades\Auth;

class EnsureCashRegisterIsOpen
{
    protected $cashRegisterService;

    public function __construct(CashRegisterService $cashRegisterService)
    {
        $this->cashRegisterService = $cashRegisterService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        // 1. Bypass Permission (Foreign Sellers)
        if ($user->can('cash_register.bypass')) {
            return $next($request);
        }

        // 2. Check if user has THEIR OWN open register
        if ($this->cashRegisterService->hasOpenRegister($user->id)) {
            return $next($request);
        }

        // 3. Shared Cash Register Mode
        $config = \App\Models\Configuration::first();
        if ($config && $config->enable_shared_cash_register) {
            // Check if there is ANY open register in the system
            $anyOpenRegister = \App\Models\CashRegister::where('status', 'open')->exists();
            if ($anyOpenRegister) {
                 return $next($request);
            }
        }

        // Default: Redirect to open register
        return redirect()->route('cash-register.open');
    }
}
