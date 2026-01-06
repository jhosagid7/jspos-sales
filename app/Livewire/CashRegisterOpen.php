<?php

namespace App\Livewire;

use App\Models\Currency;
use App\Services\CashRegisterService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class CashRegisterOpen extends Component
{
    public $openingAmounts = [];
    public $notes;
    public $currencies;

    public function mount(CashRegisterService $service)
    {
        if ($service->hasOpenRegister(Auth::id())) {
            return redirect()->route('sales');
        }

        $this->currencies = Currency::all();
        foreach ($this->currencies as $currency) {
            $this->openingAmounts[$currency->code] = 0;
        }
    }

    public function openRegister(CashRegisterService $service)
    {
        $this->validate([
            'openingAmounts.*' => 'required|numeric|min:0',
        ]);

        // Verificar que al menos un monto sea mayor a 0
        $total = array_sum($this->openingAmounts);
        if ($total <= 0) {
            $this->addError('openingAmounts', 'Debe ingresar un monto inicial en al menos una moneda.');
            return;
        }

        try {
            $service->openRegister(Auth::id(), $this->openingAmounts, $this->notes);
            
            session()->flash('message', 'Caja abierta exitosamente.');
            return redirect()->route('sales');
        } catch (\Exception $e) {
            $this->addError('general', $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.cash-register-open')
            ->layout('layouts.theme.app');
    }
}
