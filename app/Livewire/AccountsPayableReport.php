<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\Bank;
use App\Models\Payable;
use App\Models\Purchase;
use Livewire\Component;
use App\Traits\PrintTrait;
use Livewire\Attributes\On;
use Livewire\WithPagination;

class AccountsPayableReport extends Component
{
    use PrintTrait;
    use WithPagination;


    public $pagination = 10, $banks = [], $supplier, $supplier_name, $debt, $dateFrom, $dateTo, $showReport = false, $status = 0;
    public $totales = 0, $purchase_id, $details = [], $pays = [];
    public $amount, $acountNumber, $depositNumber, $bank, $phoneNumber;
    public $payWith = 'ABONO CON EFECTIVO';

    function updatedBank()
    {
        if ($this->bank != 0) {
            $this->reset(['amount', 'phoneNumber']);
            $this->payWith = 'MONTO CONSIGNADO CON BANCO';
        } else {
            $this->reset(['amount', 'phoneNumber', 'acountNumber', 'depositNumber', 'bank']);
            $this->payWith = 'ABONO CON EFECTIVO';
        }
    }
    function updatedPhoneNumber()
    {

        if (empty($this->phoneNumber) || strlen($this->phoneNumber) < 1) {
            $this->reset(['amount', 'phoneNumber', 'acountNumber', 'depositNumber', 'bank']);
            $this->payWith = 'ABONO CON EFECTIVO';
        } else {
            $this->reset(['amount', 'acountNumber', 'depositNumber', 'bank']);
            $this->payWith = 'MONTO CONSIGNADO CON NEQUI';
        }
    }

    function mount()
    {
        session()->forget('account_supplier');
        $this->banks = Bank::orderBy('sort')->get();
        session(['map' => "", 'child' => '', 'pos' => 'Reporte de Cuentas por Pagar']);
    }

    public function render()
    {
        $this->supplier = session('account_supplier', null);

        return view('livewire.reports.accounts-payable-report', [
            'purchases' => $this->getReport()
        ]);
    }


    #[On('account_supplier')]
    function setSupplier($supplier)
    {
        session(['account_supplier' => $supplier]);
        $this->supplier = $supplier;
    }



    function getReport()
    {
        if (!$this->showReport) return [];

        if ($this->supplier == null && $this->dateFrom == null && $this->dateTo == null) {
            $this->dispatch('noty', msg: 'SELECCIONA EL CLIENTE Y/O LAS FECHAS PARA CONSULTAR LAS COMPRAS');
            return;
        }
        if ($this->dateFrom != null && $this->dateTo == null) {
            $this->dispatch('noty', msg: 'SELECCIONA LA FECHA DESDE Y HASTA');
            return;
        }
        if ($this->dateFrom == null && $this->dateTo != null) {
            $this->dispatch('noty', msg: 'SELECCIONA LA FECHA DESDE Y HASTA');
            return;
        }

        try {


            $dFrom = Carbon::parse($this->dateFrom)->startOfDay();
            $dTo = Carbon::parse($this->dateTo)->endOfDay();

            $purchases = Purchase::with(['supplier', 'payables'])->whereBetween('created_at', [$dFrom, $dTo])
                ->where('type', 'credit')
                ->when($this->supplier != null, function ($query) {
                    $query->where('supplier_id', $this->supplier['id']);
                })
                ->when($this->status != 0, function ($query) {
                    $query->where('status', $this->status);
                })
                ->orderBy('id', 'desc')
                ->paginate($this->pagination);


            $this->totales = $purchases->sum(function ($purchase) {
                return $purchase->total;
            });

            return $purchases;

            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar obtener el reporte \n {$th->getMessage()}");
            return [];
        }
    }

    function initPayable(Purchase $purchase, $supplier_name)
    {
        $debt = round($purchase->total - $purchase->payables->sum('amount'), 2);
        $this->debt = $debt;
        $this->supplier_name = $supplier_name;
        $this->purchase_id = $purchase->id;
        $this->dispatch('show-modal-payable');
    }

    function doPayable()
    {
        $this->resetValidation();

        if ($this->bank != 0) {
            if (empty($this->acountNumber)) {
                $this->addError('nacount', 'INGRESA EL NÚMERO DE CUENTA');
            }
            if (empty($this->depositNumber)) {
                $this->addError('ndeposit',  'INGRESA EL NÚMERO DE DEPÓSITO');
            }
        }
        if (empty($this->amount) || strlen($this->amount) < 1) {
            $this->addError('amount', 'INGRESA EL MONTO');
        }
        if (floatval($this->amount) <= 0) {
            $this->addError('amount', 'MONTO DEBE SER MAYOR A CERO');
        }

        if (count($this->getErrorBag()) > 0) {
            return;
        }

        try {

            $type = null;
            $amount = floatval($this->amount);
            if (floatval($this->amount) >= floatval($this->debt)) {
                $type = 'settled'; //liquida crédito
            } else {
                $type = 'pay'; // abono
            }

            if (floatval($this->amount) > floatval($this->debt)) {
                $amount = $this->debt;
            }

            //crear pago
            $pay =  Payable::create(
                [
                    'user_id' => Auth()->user()->id,
                    'purchase_id' => $this->purchase_id,
                    'amount' => floatval($amount),
                    'pay_way' => match (true) {
                        ($this->bank ?? 0) === 0 && ($this->phoneNumber ?? 0) === 0 => 'cash',
                        ($this->bank ?? 0) !== 0 => 'deposit',
                        default => 'nequi',
                    },
                    'type' => $type,
                    'bank' => ($this->bank == 0 ? '' : Bank::where('id', $this->bank)->first()->name),
                    'account_number' => $this->acountNumber,
                    'deposit_number' => $this->depositNumber,
                    'phone_number' => $this->phoneNumber
                ]
            );

            // actualizar status venta
            if ($type == 'settled') {
                Purchase::where('id', $this->purchase_id)->update([
                    'status' => 'paid'
                ]);
            }

            $this->printPayable($pay->id);
            $this->dispatch('noty', msg: 'PAGO REGISTRADO CON ÉXITO');
            $this->reset('amount', 'acountNumber', 'depositNumber', 'debt', 'bank', 'phoneNumber');
            $this->dispatch('close-modal');
            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar registrar el pago parcial: {$th->getMessage()} ");
        }
    }

    function historyPayables(Purchase $purchase)
    {
        $this->pays = $purchase->payables;
        $this->dispatch('show-payablehistory');
    }
}
