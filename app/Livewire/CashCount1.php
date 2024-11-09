<?php

namespace App\Livewire;

use App\Models\Payment;
use Carbon\Carbon;
use App\Models\Sale;
use App\Models\User;
use App\Traits\PrintTrait;
use Livewire\Component;
use Livewire\Attributes\On;

class CashCount extends Component
{
    use PrintTrait;

    public $users = [], $user, $user_id = 0, $totales = 0, $dateFrom, $dateTo;
    public $totalDeposit = 0, $totalNequi = 0, $totalCash = 0, $totalSales = 0, $totalCreditSales = 0, $totalPayments = 0, $totalPaymentsDeposit = 0, $totalPaymentsCash = 0, $totalPaymentsNequi = 0;


    function mount()
    {
        session(['map' => "", 'child' => '', 'pos' => 'Arqueo de Caja']);

        $this->users = User::orderBy('name')->get();
    }


    public function render()
    {
        $this->user = session('cashcount_user', 0);

        return view('livewire.cash-count');
    }


    function updatedUserId()
    {
        session(['cashcount_user' => User::find($this->user_id)]);
        $this->user = session('cashcount_user');
    }

    function getSalesBetweenDates()
    {
        if ($this->user_id == null && $this->dateFrom == null && $this->dateTo == null) {
            $this->dispatch('noty', msg: 'SELECCIONA EL USUARIO Y/O LAS FECHAS DE CONSULTA');
            return;
        }

        if (($this->dateFrom != null && $this->dateTo == null) || ($this->dateFrom == null && $this->dateTo != null)) {
            $this->dispatch('noty', msg: 'SELECCIONA LA FECHA DESDE Y HASTA');
            return;
        }


        sleep(1);

        $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
        $dTo = Carbon::parse($this->dateTo)->endOfDay();

        try {
            $sales = Sale::whereBetween('created_at', [$dFrom, $dTo])
                ->when($this->user_id != 0, function ($qry) {
                    $qry->where('user_id', $this->user_id);
                })
                ->select('total', 'cash', 'change', 'type')
                ->get();


            $this->totalSales = $sales->sum('total');
            $this->totalCash = $sales->sum('cash') - $sales->sum('change');
            $this->totalNequi = ($sales->where('type', 'cash/nequi')->sum('total') + $sales->where('type', 'nequi')->sum('total')) - ($sales->where('type', 'cash/nequi')->sum('cash') + $sales->where('type', 'cash/nequi')->sum('change'));
            $this->totalCreditSales = $sales->where('type', 'credit')->sum('total');
            $this->totalDeposit = $sales->where('type', 'deposit')->sum('total');

            $payments = Payment::whereBetween('created_at', [$dFrom, $dTo])
                ->when($this->user_id != 0, function ($qry) {
                    $qry->where('user_id', $this->user_id);
                })
                ->select('pay_way', 'cash', 'change', 'type')
                ->get();

            $this->totalPaymentsCash = $payments->where('pay_way', 'cash')->sum('amount');
            $this->totalPaymentsDeposit = $payments->where('pay_way', 'deposit')->sum('amount');
            $this->totalPaymentsNequi = $payments->where('pay_way', 'nequi')->sum('amount');
            $this->totalPayments = $payments->sum('amount');



            $this->dispatch('noty', msg: 'Info actualizada');
            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al obtener la información de las ventas por fecha: {$th->getMessage()} ");
        }
    }

    function getDailySales()
    {
        sleep(1);

        $dFrom = Carbon::today()->startOfDay();
        $dTo = Carbon::today()->endOfDay();
        $this->dateFrom = $dFrom;
        $this->dateTo = $dTo;

        try {
            $sales = Sale::whereBetween('created_at', [$dFrom, $dTo])
                ->when($this->user_id != 0, function ($qry) {
                    $qry->where('user_id', $this->user_id);
                })
                ->select('total', 'cash', 'change', 'type')
                ->get();

            $this->totalSales = $sales->sum('total');
            $this->totalCash = $sales->sum('cash') - $sales->sum('change');
            $this->totalNequi = ($sales->where('type', 'cash/nequi')->sum('total') + $sales->where('type', 'nequi')->sum('total')) - ($sales->where('type', 'cash/nequi')->sum('cash') + $sales->where('type', 'cash/nequi')->sum('change'));
            $this->totalCreditSales = $sales->where('type', 'credit')->sum('total');
            $this->totalDeposit = $sales->where('type', 'deposit')->sum('total');

            $payments = Payment::whereBetween('created_at', [$dFrom, $dTo])
                ->when($this->user_id != 0, function ($qry) {
                    $qry->where('user_id', $this->user_id);
                })
                ->select('pay_way', 'amount')
                ->get();

            $this->totalPaymentsCash = $payments->where('pay_way', 'cash')->sum('amount');
            $this->totalPaymentsDeposit = $payments->where('pay_way', 'deposit')->sum('amount');
            $this->totalPaymentsNequi = $payments->where('pay_way', 'nequi')->sum('amount');
            $this->totalPayments = $payments->sum('amount');

            $this->dispatch('noty', msg: 'Info actualizada');
            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al obtener la información de las ventas del día:  {$th->getMessage()} ");
        }
    }

    function printCC()
    {
        $username = $this->user_id == 0 ? 'Todos los usuarios' : User::find($this->user_id)->name;
        $this->printCashCount($username, $this->dateFrom, $this->dateTo, $this->totalSales, $this->totalCash, $this->totalNequi, $this->totalDeposit, $this->totalPayments, $this->totalCreditSales, $this->totalPaymentsCash, $this->totalPaymentsDeposit, $this->totalPaymentsNequi);

        $this->dispatch('noty', msg: 'Impresión de corte enviada');
    }
}
