<?php

namespace App\Listeners;

use Carbon\Carbon;
use App\Models\Sale;
use App\Models\Configuration;
use App\Models\Purchase;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginSuccess
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(Login $event)
    {
        // Verificar si el usuario está suspendido
        if ($event->user->status != 'Active') {

            Auth::logout();

            throw ValidationException::withMessages([
                'error_message' => 'Tu cuenta está suspendida. Por favor contacta al administrador',
            ]);

            return redirect('/login');
        }

        // si no existe, creamos config defecto
        if (Configuration::count() == 0) {
            Configuration::create([
                'business_name' => 'IT COMPANY',
                'address' => 'VENEZUELA',
                'phone' => '5555555',
                'taxpayer_id' => 'RUT123456',
                'vat' => 0,
                'printer_name' => '80mm',
                'leyend' => 'Gracias por su compra!',
                'website' => 'jhonnypirela.dev',
                'credit_days' => 15,
                'credit_purchase_days' => 0,
                'confirmation_code' => 7,
            ]);
        }

        session(['settings' => Configuration::first()]);

        //todo: convertir a un metodo para usarlo en diferentes lugares.

        // buscar las ventas pendientes de credito por mas de 30 dias
        $this->checkCreditSales();
        $this->checkCreditPurchases();
        // $sales = Sale::where('type', 'credit')->where('status', 'pending')->orderBy('id', 'asc')
        //     ->where('created_at', '<', Carbon::now()->subDays(session('settings')->credit_days))
        //     ->with('customer')
        //     ->get();

        // if ($sales != null && $sales->count() > 0) {
        //     session(['noty_sales' => $sales]);
        // }
    }

    public function checkCreditSales()
    {
        $sales = Sale::where('type', 'credit')->where('status', 'pending')->orderBy('id', 'asc')
            ->where('created_at', '<', Carbon::now()->subDays(session('settings')->credit_days))
            ->with('customer')
            ->get();

        if ($sales != null && $sales->count() > 0) {
            session(['noty_sales' => $sales]);
        }
    }
    public function checkCreditPurchases()
    {
        $purchases = Purchase::where('type', 'credit')->where('status', 'pending')->orderBy('id', 'asc')
            ->where('created_at', '<', Carbon::now()->subDays(session('settings')->credit_purchase_days))
            ->with('supplier')
            ->get();

        if ($purchases != null && $purchases->count() > 0) {
            session(['noty_purchases' => $purchases]);
        }
    }
}
