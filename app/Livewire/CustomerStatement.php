<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\SaleReturn;
use App\Models\Configuration;
use Illuminate\Support\Facades\DB;
use Livewire\WithPagination;
use Barryvdh\DomPDF\Facade\Pdf;

class CustomerStatement extends Component
{
    use WithPagination;

    public $customerId;
    public $search;
    public $dateFrom;
    public $dateTo;
    public $referenceSearch = '';

    // Summary data
    public $totalSales = 0;
    public $totalPayments = 0;
    public $totalReturns = 0;
    public $currentBalance = 0;
    
    // PDF Preview
    public $showPdfModal = false;
    public $pdfUrl = '';

    protected $listeners = [
        'account_customer' => 'selectCustomerByEvent'
    ];

    public function mount()
    {
        $this->dateFrom = now()->subDays(30)->format('Y-m-d');
        $this->dateTo = now()->format('Y-m-d');
    }

    public function render()
    {
        return view('livewire.customer-statement', [
            'customers' => $this->getCustomers(),
            'ledger' => $this->getLedgerData()
        ])->layout('layouts.theme.app');
    }

    public function getCustomers()
    {
        if (empty($this->search) || $this->customerId) return [];

        $query = Customer::query();
        
        $searchTerm = '%' . $this->search . '%';
        $query->where(function($q) use ($searchTerm) {
            $q->where('name', 'like', $searchTerm)
              ->orWhere('id', 'like', $searchTerm)
              ->orWhere('taxpayer_id', 'like', $searchTerm);
        });

        // Privacy check (Elizabeth and Foreign Sellers)
        if (!auth()->user()->can('customers.index')) { // Simple check, adjust if needed
             // If they don't have general index, maybe they only see their own
        }
        
        // Applying the specific request: Foreign sellers (like Elizabeth) see their own
        if (!auth()->user()->hasRole(['Admin', 'Super Admin', 'Dueño'])) {
            $query->where('seller_id', auth()->id());
        }

        return $query->take(10)->get();
    }

    public function selectCustomerByEvent($data)
    {
        if (isset($data['customer']['id'])) {
            $this->selectCustomer($data['customer']['id']);
        }
    }

    public function selectCustomer($id)
    {
        $customer = Customer::find($id);
        if ($customer) {
            $this->customerId = $id;
            $this->search = $customer->name;
            $this->calculateTotals();
        }
    }

    public function clearCustomer()
    {
        $this->customerId = null;
        $this->search = '';
        $this->totalSales = 0;
        $this->totalPayments = 0;
        $this->totalReturns = 0;
        $this->currentBalance = 0;
        $this->dispatch('clear-customer-search');
    }

    public function calculateTotals()
    {
        if (!$this->customerId) return;

        $from = $this->dateFrom . ' 00:00:00';
        $to = $this->dateTo . ' 23:59:59';

        // Filtered sales
        $salesQuery = Sale::where('customer_id', $this->customerId)
            ->whereNotIn('status', ['voided', 'returned'])
            ->whereBetween('created_at', [$from, $to]);
        
        $sales = $salesQuery->get();
        
        $this->totalSales = $sales->sum(function($s) {
            return $s->total_usd > 0 ? $s->total_usd : ($s->primary_exchange_rate > 0 ? $s->total / $s->primary_exchange_rate : $s->total);
        });

        // Filtered payments
        $this->totalPayments = Payment::whereIn('sale_id', Sale::where('customer_id', $this->customerId)->pluck('id'))
            ->where('status', 'approved')
            ->whereBetween('payment_date', [$from, $to])
            ->get()
            ->sum(function($p) {
                $amountUSD = $p->exchange_rate > 0 ? $p->amount / $p->exchange_rate : $p->amount;
                $adjustmentUSD = $p->discount_applied ?? 0;
                
                if ($p->rule_type === 'overdue') {
                    return $amountUSD - $adjustmentUSD;
                } else {
                    return $amountUSD + $adjustmentUSD;
                }
            });

        // Filtered returns
        $this->totalReturns = SaleReturn::where('customer_id', $this->customerId)
            ->where('status', 'approved')
            ->whereBetween('created_at', [$from, $to])
            ->get()
            ->sum(function($r) {
                return $r->total_returned; 
            });

        $this->currentBalance = $this->totalSales - $this->totalPayments - $this->totalReturns;
    }

    public function getLedgerData()
    {
        if (!$this->customerId) return [];

        $cid = $this->customerId;
        $from = $this->dateFrom . ' 00:00:00';
        $to = $this->dateTo . ' 23:59:59';
        $search = $this->referenceSearch;

        // Unified query using UNION
        $transactions = DB::table(DB::raw("(
            SELECT 
                created_at as t_date, 
                CAST(COALESCE(invoice_number, id) AS CHAR) as reference, 
                'VENTA' as concept, 
                total as debit_native,
                primary_exchange_rate as rate,
                (CASE WHEN total_usd > 0 THEN total_usd ELSE total / (CASE WHEN primary_exchange_rate > 0 THEN primary_exchange_rate ELSE 1 END) END) as debit_usd,
                0 as credit_usd,
                status
            FROM sales 
            WHERE customer_id = $cid AND status NOT IN ('voided')
            AND created_at BETWEEN '$from' AND '$to'
            " . ($search ? "AND (id LIKE '%$search%' OR invoice_number LIKE '%$search%')" : "") . "

            UNION ALL

            SELECT 
                payment_date as t_date, 
                CAST(p.id AS CHAR) as reference, 
                CONCAT(
                    'PAGO ', UPPER(p.pay_way), 
                    COALESCE(CONCAT(' ', UPPER(p.bank)), ''),
                    COALESCE(CONCAT(' #', p.deposit_number), ''),
                    ' (', p.currency, ' ', FORMAT(p.amount, 2), ' @ ', FORMAT(p.exchange_rate, 2), ')',
                    ' ($', FORMAT(p.amount / (CASE WHEN p.exchange_rate > 0 THEN p.exchange_rate ELSE 1 END), 2), ')',
                    CASE WHEN p.discount_applied > 0 THEN 
                        CONCAT(' + DESC. ', 
                            CASE 
                                WHEN p.rule_type = 'early_payment' OR p.discount_tag LIKE '%Pronto%' THEN 'PP'
                                WHEN p.rule_type = 'usd_payment' OR p.discount_tag LIKE '%Divisa%' OR p.discount_tag LIKE '%USD%' THEN 'PD'
                                ELSE UPPER(COALESCE(p.discount_tag, 'DESC'))
                            END,
                            '($', FORMAT(p.discount_applied, 2), ')'
                        ) 
                    ELSE '' END,
                    ' [FACT. #', COALESCE(s.invoice_number, CAST(s.id AS CHAR)), ']'
                ) as concept, 
                p.amount as debit_native,
                p.exchange_rate as rate,
                0 as debit_usd,
                (
                    (p.amount / (CASE WHEN p.exchange_rate > 0 THEN p.exchange_rate ELSE 1 END)) 
                    + (CASE WHEN p.rule_type = 'overdue' THEN -1 ELSE 1 END * COALESCE(p.discount_applied, 0))
                ) as credit_usd,
                p.status
            FROM payments p
            JOIN sales s ON p.sale_id = s.id
            WHERE s.customer_id = $cid AND p.status = 'approved'
            AND payment_date BETWEEN '$from' AND '$to'
            " . ($search ? "AND (p.id LIKE '%$search%' OR s.invoice_number LIKE '%$search%' OR s.id LIKE '%$search%')" : "") . "

            UNION ALL

            SELECT 
                r.created_at as t_date, 
                CAST(r.id AS CHAR) as reference, 
                CONCAT('DEVOLUCIÓN [FACT. #', COALESCE(s.invoice_number, CAST(s.id AS CHAR)), '] ', COALESCE(r.reason, '')) as concept, 
                r.total_returned as debit_native,
                1 as rate,
                0 as debit_usd,
                r.total_returned as credit_usd,
                r.status
            FROM sale_returns r
            JOIN sales s ON r.sale_id = s.id
            WHERE r.customer_id = $cid AND r.status = 'approved'
            AND r.created_at BETWEEN '$from' AND '$to'
            " . ($search ? "AND (r.id LIKE '%$search%' OR s.invoice_number LIKE '%$search%' OR s.id LIKE '%$search%')" : "") . "
        ) as combined"))
        ->orderBy('t_date', 'asc')
        ->get();

        // Calculate running balance in PHP for simplicity in this step
        $runningBalance = 0;
        foreach ($transactions as $tx) {
            $runningBalance += ($tx->debit_usd - $tx->credit_usd);
            $tx->running_balance = $runningBalance;
        }

        return $transactions;
    }

    public function exportPdf()
    {
        if (!$this->customerId) return;

        $customer = Customer::with('seller')->find($this->customerId);
        $config = Configuration::first();
        $ledger = $this->getLedgerData();

        $pdf = Pdf::loadView('pdf.customer-statement', [
            'customer' => $customer,
            'config' => $config,
            'ledger' => $ledger,
            'totalSales' => $this->totalSales,
            'totalPayments' => $this->totalPayments,
            'totalReturns' => $this->totalReturns,
            'currentBalance' => $this->currentBalance,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
        ]);

        return response()->streamDownload(function () use ($pdf) {
            echo $pdf->output();
        }, 'estado-cuenta-' . str_replace(' ', '-', strtolower($customer->name)) . '.pdf');
    }

    public function openPdfPreview()
    {
        if (!$this->customerId) return;
        
        $params = [
            'customer_id' => $this->customerId,
            'dateFrom' => $this->dateFrom,
            'dateTo' => $this->dateTo,
            'referenceSearch' => $this->referenceSearch,
        ];

        $this->pdfUrl = route('reports.customer.statement.pdf', $params);
        $this->showPdfModal = true;
    }

    public function closePdfPreview()
    {
        $this->showPdfModal = false;
        $this->pdfUrl = '';
    }
}
