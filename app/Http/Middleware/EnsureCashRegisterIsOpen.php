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
        if (Auth::check() && !$this->cashRegisterService->hasOpenRegister(Auth::id())) {
            return redirect()->route('cash-register.open');
        }

        return $next($request);
    }
}
