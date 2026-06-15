<?php

namespace App\Livewire\Audit;

use App\Models\Sale;
use App\Models\User;
use App\Models\Payment;
use App\Models\ExchangeRateHistory;
use App\Models\Configuration;
use Livewire\Component;
use Livewire\WithPagination;
use Carbon\Carbon;

class InvoicesAuditList extends Component
{
    use WithPagination;

    public $dateFrom;
    public $dateTo;
    public $auditStatus = 'all';
    public $sellerId = 'all';
    public $operatorId = 'all';
    public $paymentAgreement = 'all';
    public $paymentStatus = 'all';
    public $searchQuery = '';

    public $sortField = 'created_at';
    public $sortDirection = 'desc';

    public $selectedColumns = [
        'invoice_number',
        'created_at',
        'customer',
        'seller',
        'operator',
        'total_usd',
        'payment_agreement',
        'audit_status',
        'actions'
    ];

    public $selectedSale = null;
    public $selectedPaymentId = null;

    protected $paginationTheme = 'bootstrap';

    protected $queryString = [
        'dateFrom' => ['except' => ''],
        'dateTo' => ['except' => ''],
        'auditStatus' => ['except' => 'all'],
        'sellerId' => ['except' => 'all'],
        'operatorId' => ['except' => 'all'],
        'paymentAgreement' => ['except' => 'all'],
        'paymentStatus' => ['except' => 'all'],
        'searchQuery' => ['except' => ''],
    ];

    public function mount()
    {
        // Default to current month
        $this->dateFrom = Carbon::now()->startOfMonth()->format('Y-m-d');
        $this->dateTo = Carbon::now()->format('Y-m-d');

        session([
            'map' => 'AUDITORÍA DE FACTURAS',
            'child' => 'Dashboard Global',
            'rest' => '',
            'pos' => 'Auditoría'
        ]);
    }

    public function updating($name)
    {
        if (in_array($name, ['dateFrom', 'dateTo', 'auditStatus', 'sellerId', 'operatorId', 'paymentAgreement', 'paymentStatus', 'searchQuery'])) {
            $this->resetPage();
        }
    }

    public function getUnifiedPayments($sale)
    {
        if (!$sale) {
            return [];
        }

        $unified = [];

        // 1. Post-sale payments (abonos)
        foreach ($sale->payments as $p) {
            $unified[] = [
                'id' => 'payment-' . $p->id,
                'db_id' => $p->id,
                'type' => 'post-sale',
                'amount' => $p->amount,
                'currency' => $p->currency,
                'exchange_rate' => $p->exchange_rate,
                'pay_way' => $p->pay_way,
                'bank' => $p->bank,
                'deposit_number' => $p->deposit_number,
                'created_at' => $p->created_at,
                'payment_date' => $p->payment_date,
                'zelleRecord' => $p->zelleRecord,
                'user' => $p->user,
                'model' => $p,
            ];
        }

        // 2. Initial checkout payments (paymentDetails)
        foreach ($sale->paymentDetails as $pd) {
            // Map to a transient Payment model so validation works seamlessly
            $pModel = new Payment([
                'id' => $pd->id,
                'sale_id' => $pd->sale_id,
                'amount' => $pd->amount,
                'currency' => $pd->currency_code,
                'exchange_rate' => $pd->exchange_rate,
                'pay_way' => $pd->payment_method,
                'bank' => $pd->bank_name,
                'deposit_number' => $pd->reference_number,
                'status' => 'approved',
                'created_at' => $pd->created_at,
                'payment_date' => $pd->created_at,
            ]);
            $pModel->setRelation('sale', $sale);
            $pModel->setRelation('zelleRecord', $pd->zelleRecord);

            $unified[] = [
                'id' => 'detail-' . $pd->id,
                'db_id' => $pd->id,
                'type' => 'checkout',
                'amount' => $pd->amount,
                'currency' => $pd->currency_code,
                'exchange_rate' => $pd->exchange_rate,
                'pay_way' => $pd->payment_method,
                'bank' => $pd->bank_name,
                'deposit_number' => $pd->reference_number,
                'created_at' => $pd->created_at,
                'payment_date' => $pd->created_at,
                'zelleRecord' => $pd->zelleRecord,
                'user' => $sale->user,
                'model' => $pModel,
            ];
        }

        // Sort by created_at desc
        usort($unified, function($a, $b) {
            return $b['created_at']->timestamp <=> $a['created_at']->timestamp;
        });

        return $unified;
    }

    public function toggleInvoiceAudit($saleId)
    {
        $sale = Sale::with(['payments', 'paymentDetails'])->findOrFail($saleId);

        if (in_array($sale->status, ['voided', 'cancelled', 'anulated']) || $sale->deletion_approved_at !== null) {
            session()->flash('error', 'No se pueden auditar facturas eliminadas/anuladas.');
            return;
        }

        if (!$sale->is_audited) {
            // Check if any payment of this sale was billed in USD but paid at BCV rate
            $unified = $this->getUnifiedPayments($sale);
            foreach ($unified as $pItem) {
                $validation = $this->getPaymentValidation($pItem['model']);
                if ($validation['color'] === 'red' && $validation['message'] === 'Tasa BCV en acuerdo USD.') {
                    session()->flash('error', 'No se puede auditar esta factura: fue facturada a acuerdo USD pero pagada a tasa BCV.');
                    return;
                }
            }
        }

        $sale->is_audited = !$sale->is_audited;
        $sale->audited_at = $sale->is_audited ? now() : null;
        $sale->save();

        session()->flash('success', 'Estado de auditoría actualizado para la factura ' . ($sale->invoice_number ?: ('#' . $sale->id)));
    }

    public function showSaleDetails($saleId)
    {
        $this->selectedSale = Sale::with([
            'customer',
            'payments.zelleRecord',
            'payments.user',
            'paymentDetails.zelleRecord',
            'user'
        ])->findOrFail($saleId);

        $unified = $this->getUnifiedPayments($this->selectedSale);
        if (!empty($unified)) {
            $this->selectedPaymentId = $unified[0]['id'];
        } else {
            $this->selectedPaymentId = null;
        }
        $this->dispatch('show-sale-details-modal');
    }

    public function closeSaleDetails()
    {
        $this->selectedSale = null;
        $this->selectedPaymentId = null;
        $this->dispatch('close-sale-details-modal');
    }

    public function selectPayment($paymentId)
    {
        $this->selectedPaymentId = $paymentId;
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'desc';
        }
    }

    public function getPaymentValidation($payment)
    {
        $sale = $payment->sale;
        if (!$sale) {
            return [
                'color' => 'green',
                'message' => 'Sin factura asociada',
                'net_usd' => 0,
                'base_amount' => 0,
                'agreement' => 'N/A'
            ];
        }

        $paymentDate = $payment->payment_date ? Carbon::parse($payment->payment_date) : $payment->created_at;

        // Retrieve historical rates
        $historyBCV = ExchangeRateHistory::where('rate_type', 'BCV')
            ->where('created_at', '<=', $paymentDate->copy()->endOfDay())
            ->orderBy('created_at', 'desc')
            ->first();
        $bcvRate = $historyBCV ? floatval($historyBCV->rate) : null;
        if (!$bcvRate) {
            $config = Configuration::first();
            $bcvRate = $config ? floatval($config->bcv_rate) : 0;
        }

        $recordsBinance = ExchangeRateHistory::whereIn('rate_type', ['BinanceReal', 'Binance'])
            ->whereBetween('created_at', [$paymentDate->copy()->startOfDay(), $paymentDate->copy()->endOfDay()])
            ->orderBy('created_at', 'asc')
            ->get();
        
        if ($recordsBinance->isEmpty()) {
            $latestReal = ExchangeRateHistory::where('rate_type', 'BinanceReal')
                ->where('created_at', '<=', $paymentDate->copy()->endOfDay())
                ->orderBy('created_at', 'desc')
                ->first();
            $latestInflated = ExchangeRateHistory::where('rate_type', 'Binance')
                ->where('created_at', '<=', $paymentDate->copy()->endOfDay())
                ->orderBy('created_at', 'desc')
                ->first();
            $binanceRates = [];
            if ($latestReal) $binanceRates[] = floatval($latestReal->rate);
            if ($latestInflated) $binanceRates[] = floatval($latestInflated->rate);
        } else {
            $binanceRates = $recordsBinance->pluck('rate')->map(fn($r) => floatval($r))->toArray();
        }

        if (empty($binanceRates)) {
            $config = Configuration::first();
            $binanceReal = $config ? floatval($config->binance_rate) : 0;
            $binanceInflated = $binanceReal + ($config ? floatval($config->binance_markup_points) : 0);
            $binanceRates = [$binanceReal, $binanceInflated];
        }

        $binanceRate = !empty($binanceRates) ? $binanceRates[0] : 1;

        $currency = strtoupper($payment->currency);
        $payRate = floatval($payment->exchange_rate ?: 1);

        $paymentUsd = $payment->amount / ($payRate ?: 1);
        $ratio = $sale->total > 0 ? ($paymentUsd / $sale->total) : 0;

        $paymentBase = floatval($sale->base_amount) * $ratio;
        $paymentFreight = floatval($sale->freight_amount) * $ratio;
        $paymentCommission = floatval($sale->commission_amount) * $ratio;
        $paymentMarkup = floatval($sale->base_markup_amount) * $ratio;

        $agreement = $sale->payment_agreement ?: 'USD';

        $isWarning = false;
        $isLoss = false;
        $message = '';
        $computedNetUSD = 0;

        if ($currency === 'USD') {
            $computedNetUSD = $paymentUsd - $paymentFreight - $paymentCommission - $paymentMarkup;
            if ($computedNetUSD < $paymentBase - 0.0099) {
                $isLoss = true;
                $message = 'Monto neto menor al costo base.';
            }
        } elseif ($currency === 'VES' || $currency === 'VED') {
            if ($agreement === 'USD') {
                $isBcvRateUsed = false;
                if ($bcvRate > 0 && abs($payRate - $bcvRate) < 0.01) {
                    $isBcvRateUsed = true;
                } elseif ($binanceRate > 0 && $payRate <= $bcvRate + 0.05) {
                    $isBcvRateUsed = true;
                }

                if ($isBcvRateUsed) {
                    $isLoss = true;
                    $message = 'Tasa BCV en acuerdo USD.';
                } else {
                    $matchesBinance = false;
                    $matchedBinanceRate = null;
                    foreach ($binanceRates as $br) {
                        if (abs($payRate - $br) < 0.01) {
                            $matchesBinance = true;
                            $matchedBinanceRate = $br;
                            break;
                        }
                    }
                    if (!$matchesBinance) {
                        $isWarning = true;
                        $message = 'Tasa de pago no coincide con Binance oficial.';
                    } else {
                        $binanceRate = $matchedBinanceRate;
                    }
                }

                $realUsdValue = $payment->amount / ($binanceRate ?: 1);
                $computedNetUSD = $realUsdValue - $paymentFreight - $paymentCommission - $paymentMarkup;

                if ($computedNetUSD < $paymentBase - 0.0099) {
                    $isLoss = true;
                    if ($message === '') {
                        $message = 'Contravalor neto Binance menor al costo base.';
                    }
                }
            } else {
                if ($bcvRate > 0 && abs($payRate - $bcvRate) > 0.01) {
                    $isWarning = true;
                    $message = 'Tasa de pago no coincide con BCV oficial.';
                }

                $invoiceUsdCovered = $payment->amount / ($payRate ?: 1);
                $realBinanceUsd = ($invoiceUsdCovered * ($bcvRate ?: 1)) / ($binanceRate ?: 1);

                $computedNetUSD = $realBinanceUsd - $paymentFreight - $paymentCommission - $paymentMarkup;

                if ($computedNetUSD < $paymentBase - 0.0099) {
                    $isLoss = true;
                    $message = 'Contravalor real Binance menor al costo base.';
                }
            }
        } else {
            $computedNetUSD = $paymentUsd - $paymentFreight - $paymentCommission - $paymentMarkup;
            if ($computedNetUSD < $paymentBase - 0.0099) {
                $isLoss = true;
                $message = 'Monto neto menor al costo base.';
            }
        }

        $color = 'green';
        if ($isLoss) {
            $color = 'red';
        } elseif ($isWarning) {
            $color = 'orange';
        }

        return [
            'color' => $color,
            'message' => $message ?: 'Cumple rentabilidad',
            'net_usd' => $computedNetUSD,
            'base_amount' => $paymentBase,
            'agreement' => $agreement,
            'bcv_rate' => $bcvRate,
            'binance_rate' => $binanceRate,
            'binance_rates' => $binanceRates,
        ];
    }

    public function render()
    {
        $sellers = User::sellers()->orderBy('name')->get();
        $operators = User::orderBy('name')->get();

        $query = Sale::with(['customer.seller', 'user']);

        // Date range
        if ($this->dateFrom) {
            $query->whereDate('sales.created_at', '>=', $this->dateFrom);
        }
        if ($this->dateTo) {
            $query->whereDate('sales.created_at', '<=', $this->dateTo);
        }

        // Audit status
        if ($this->auditStatus !== 'all') {
            if ($this->auditStatus === 'audited') {
                $query->where('sales.is_audited', true)
                    ->whereNotIn('sales.status', ['voided', 'cancelled', 'anulated'])
                    ->whereNull('sales.deletion_approved_at');
            } elseif ($this->auditStatus === 'not_audited') {
                $query->where('sales.is_audited', false)
                    ->whereNotIn('sales.status', ['voided', 'cancelled', 'anulated'])
                    ->whereNull('sales.deletion_approved_at');
            } elseif ($this->auditStatus === 'deleted') {
                $query->where(function ($q) {
                    $q->whereIn('sales.status', ['voided', 'cancelled', 'anulated'])
                      ->orWhereNotNull('sales.deletion_approved_at');
                });
            }
        }

        // Payment Status
        if ($this->paymentStatus !== 'all') {
            if ($this->paymentStatus === 'paid') {
                $query->where('sales.status', 'paid');
            } elseif ($this->paymentStatus === 'pending') {
                $query->where('sales.status', 'pending');
            }
        }

        // Seller
        if ($this->sellerId !== 'all') {
            $query->whereHas('customer', function ($q) {
                $q->where('customers.seller_id', $this->sellerId);
            });
        }

        // Operator
        if ($this->operatorId !== 'all') {
            $query->where('sales.user_id', $this->operatorId);
        }

        // Payment Agreement
        if ($this->paymentAgreement !== 'all') {
            $query->where('sales.payment_agreement', $this->paymentAgreement);
        }

        // Search Query
        if ($this->searchQuery !== '') {
            $q = trim($this->searchQuery);
            $query->where(function ($subQuery) use ($q) {
                $subQuery->where('sales.invoice_number', 'like', "%{$q}%")
                    ->orWhere('sales.id', 'like', "%{$q}%")
                    ->orWhereHas('customer', function ($c) use ($q) {
                        $c->where('customers.name', 'like', "%{$q}%");
                    });
            });
        }

        // Sorting
        if ($this->sortField === 'customer') {
            $query->join('customers', 'sales.customer_id', '=', 'customers.id')
                ->orderBy('customers.name', $this->sortDirection)
                ->select('sales.*');
        } elseif ($this->sortField === 'seller') {
            $query->join('customers', 'sales.customer_id', '=', 'customers.id')
                ->join('users', 'customers.seller_id', '=', 'users.id')
                ->orderBy('users.name', $this->sortDirection)
                ->select('sales.*');
        } elseif ($this->sortField === 'operator') {
            $query->join('users', 'sales.user_id', '=', 'users.id')
                ->orderBy('users.name', $this->sortDirection)
                ->select('sales.*');
        } else {
            $query->orderBy($this->sortField, $this->sortDirection);
        }

        $sales = $query->paginate(15);

        return view('livewire.audit.invoices-audit-list', [
            'sales' => $sales,
            'sellers' => $sellers,
            'operators' => $operators,
            'config' => Configuration::first()
        ])->layout('layouts.theme.app');
    }
}
