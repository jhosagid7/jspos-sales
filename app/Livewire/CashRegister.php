<?php

namespace App\Livewire;

use App\Models\Currency;
use App\Models\User;
use App\Services\CashRegisterService;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithPagination;
use App\Models\CashRegister as CashRegisterModel;

class CashRegister extends Component
{
    use WithPagination;

    // Propiedades para Cierre de Caja
    public $countedAmounts = [];
    public $expectedAmounts = [];
    public $differences = [];
    public $notes;
    public $currencies;
    public $registerId;
    public $totalExpected = 0;
    public $totalCounted = 0;
    public $totalDifference = 0;
    public $salesByCurrency = [];
    public $hasOpenRegister = false;

    // Propiedades para Historial
    public $tab = 1;
    public $search = '';
    public $dateFrom = '';
    public $dateTo = '';
    public $selectedUser = null;
    public $users = [];

    // Propiedades para Modal de Detalles
    public $selectedRegister = null;

    protected $paginationTheme = 'bootstrap';

    public function mount(CashRegisterService $service)
    {
        // Inicializar fechas para historial
        $this->dateFrom = now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
        
        // Cargar usuarios para el filtro (solo si tiene permiso de ver todo)
        if (Auth::user()->can('cash_register.view_all')) {
            $this->users = User::orderBy('name')->get();
        }

        // Verificar si hay caja abierta
        $register = $service->getActiveCashRegister(Auth::id());
        
        if ($register) {
            $this->hasOpenRegister = true;
            $this->registerId = $register->id;
            $this->initializeCloseRegister($service, $register);
            $this->tab = 1; // Default a cerrar caja si hay una abierta
        } else {
            $this->hasOpenRegister = false;
            $this->tab = 2; // Default a historial si no hay caja abierta
        }
    }

    public function initializeCloseRegister($service, $register)
    {
        $this->currencies = Currency::all();
        
        foreach ($this->currencies as $currency) {
            // Obtener saldo esperado del sistema
            $balance = $service->getBalance($this->registerId, $currency->code);
            $this->expectedAmounts[$currency->code] = $balance;
            $this->countedAmounts[$currency->code] = $balance; // Por defecto sugerir lo esperado
            $this->calculateDifference($currency->code);
        }
        
        $this->calculateTotals();
        $this->calculateNonCashTotals();
    }

    public function calculateNonCashTotals()
    {
        // Inicializar estructura
        $this->salesByCurrency = [
            'cash' => [],
            'nequi' => [],
            'deposit' => [],
            'credit' => 0
        ];

        // Obtener ventas asociadas a esta caja
        $register = CashRegisterModel::find($this->registerId);
        
        $sales = \App\Models\Sale::where('user_id', $register->user_id)
            ->where('created_at', '>=', $register->opening_date)
            ->where('status', '!=', 'canceled')
            ->get();

        foreach ($sales as $sale) {
            if ($sale->type == 'credit') {
                $this->salesByCurrency['credit'] += $sale->total;
                continue;
            }

            $paymentDetails = $sale->paymentDetails;
            
            if ($paymentDetails->count() > 0) {
                foreach ($paymentDetails as $detail) {
                    $currency = $detail->currency_code ?? 'COP';
                    $amount = $detail->amount;
                    $type = $sale->type; 
                    
                    $category = 'cash';
                    if (!empty($detail->bank_name)) {
                        $category = 'deposit';
                    } elseif ($type == 'nequi') {
                        $category = 'nequi';
                    } elseif ($type == 'cash/nequi') {
                        $category = 'cash'; 
                    }

                    if ($category == 'deposit') {
                        $bankName = $detail->bank_name ?? 'Banco General';
                        if (!isset($this->salesByCurrency['deposit'][$bankName])) {
                            $this->salesByCurrency['deposit'][$bankName] = [];
                        }
                        if (!isset($this->salesByCurrency['deposit'][$bankName][$currency])) {
                            $this->salesByCurrency['deposit'][$bankName][$currency] = 0;
                        }
                        $this->salesByCurrency['deposit'][$bankName][$currency] += $amount;
                    } else {
                        if (!isset($this->salesByCurrency[$category][$currency])) {
                            $this->salesByCurrency[$category][$currency] = 0;
                        }
                        $this->salesByCurrency[$category][$currency] += $amount;
                    }
                }
            } else {
                $currency = 'COP'; 
                $amount = $sale->total; 
                
                if ($sale->type == 'cash') {
                    $amount = $sale->cash - $sale->change;
                }
                
                $category = ($sale->type == 'deposit') ? 'deposit' : (($sale->type == 'nequi') ? 'nequi' : 'cash');
                
                if ($category == 'deposit') {
                    $bankName = 'Banco General';
                    if (!isset($this->salesByCurrency['deposit'][$bankName])) {
                        $this->salesByCurrency['deposit'][$bankName] = [];
                    }
                    if (!isset($this->salesByCurrency['deposit'][$bankName][$currency])) {
                        $this->salesByCurrency['deposit'][$bankName][$currency] = 0;
                    }
                    $this->salesByCurrency['deposit'][$bankName][$currency] += $amount;
                } else {
                    if (!isset($this->salesByCurrency[$category][$currency])) {
                        $this->salesByCurrency[$category][$currency] = 0;
                    }
                    $this->salesByCurrency[$category][$currency] += $amount;
                }
            }
        }
    }

    public function updatedCountedAmounts($value, $key)
    {
        $this->calculateDifference($key);
        $this->calculateTotals();
    }

    public function calculateDifference($currencyCode)
    {
        $expected = $this->expectedAmounts[$currencyCode] ?? 0;
        $counted = $this->countedAmounts[$currencyCode] ?? 0;
        
        if (!is_numeric($counted)) $counted = 0;
        
        $this->differences[$currencyCode] = $counted - $expected;
    }

    public function calculateTotals()
    {
        $this->totalExpected = 0;
        $this->totalCounted = 0;
        $this->totalDifference = 0;
        
        $primaryCurrency = Currency::where('is_primary', true)->first();

        foreach ($this->currencies as $currency) {
            $expected = $this->expectedAmounts[$currency->code] ?? 0;
            $counted = $this->countedAmounts[$currency->code] ?? 0;
            
            if (!is_numeric($counted)) $counted = 0;

            $rate = $currency->exchange_rate;
            
            if ($currency->is_primary) {
                $this->totalExpected += $expected;
                $this->totalCounted += $counted;
            } else {
                $this->totalExpected += ($expected / $rate * $primaryCurrency->exchange_rate);
                $this->totalCounted += ($counted / $rate * $primaryCurrency->exchange_rate);
            }
        }
        
        $this->totalDifference = $this->totalCounted - $this->totalExpected;
    }

    public function closeRegister(CashRegisterService $service)
    {
        if (!Auth::user()->can('cash_register.close')) {
            $this->addError('general', 'No tienes permiso para cerrar cajas.');
            return;
        }

        $this->validate([
            'countedAmounts.*' => 'required|numeric|min:0',
        ]);

        try {
            $service->closeRegister($this->registerId, $this->countedAmounts, $this->notes);
            
            session()->flash('message', 'Caja cerrada exitosamente.');
            
            // Recargar componente para actualizar estado
            return redirect()->route('cash-register.close'); 
        } catch (\Exception $e) {
            $this->addError('general', $e->getMessage());
        }
    }

    public function viewDetails($registerId)
    {
        $this->selectedRegister = CashRegisterModel::with(['user', 'details'])->find($registerId);
        $this->dispatch('show-modal', 'cashRegisterDetailsModal');
    }

    public function render()
    {
        $history = [];
        
        // Solo cargar historial si estamos en la pestaña 2
        if ($this->tab == 2) {
            $query = CashRegisterModel::with('user')
                ->where('status', 'closed')
                ->whereBetween('closing_date', [$this->dateFrom . ' 00:00:00', $this->dateTo . ' 23:59:59']);
            
            // Permisos de visualización
            if (!Auth::user()->can('cash_register.view_all')) {
                // Si no puede ver todo, solo ve lo suyo
                $query->where('user_id', Auth::id());
            } elseif ($this->selectedUser) {
                // Si es admin y seleccionó usuario
                $query->where('user_id', $this->selectedUser);
            }
            
            // Búsqueda
            if ($this->search) {
                $query->where(function($q) {
                    $q->where('id', 'like', "%{$this->search}%")
                      ->orWhere('closing_notes', 'like', "%{$this->search}%");
                });
            }
            
            $history = $query->orderBy('closing_date', 'desc')->paginate(10);
        }

        return view('livewire.cash-register', [
            'history' => $history
        ])->layout('layouts.theme.app');
    }
}
