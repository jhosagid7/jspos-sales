<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sale;
use App\Models\Payment;
use App\Models\SalePaymentDetail;
use App\Models\CollectionSheet;
use App\Models\Configuration;
use App\Services\WhatsappService;
use App\Services\IncomeReportService;
use Carbon\Carbon;

class SendDailyClosureNotification extends Command
{
    protected $signature = 'app:send-daily-closure {date?}';
    protected $description = 'Send daily sales closure report via WhatsApp';

    public function handle()
    {
        $date = $this->argument('date') ?: Carbon::today()->toDateString();
        $dateParsed = Carbon::parse($date);
        
        $config = Configuration::first();
        if (!$config) {
            $this->error('No configuration found.');
            return 1;
        }

        // Fetch sales for the day
        $sales = Sale::whereBetween('created_at', [
                $dateParsed->copy()->startOfDay(), 
                $dateParsed->copy()->endOfDay()
            ])
            ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
            ->with(['paymentDetails', 'changeDetails'])
            ->get();

        // Fetch sheet & payments for the day
        $sheet = CollectionSheet::whereDate('opened_at', $date)->first();
        $payments = collect();
        if ($sheet) {
            $payments = Payment::where('collection_sheet_id', $sheet->id)
                ->where('status', 'approved')
                ->get();
        }

        // Collect extra bank names from transactions
        $extraBankNames = [];
        foreach ($sales as $sale) {
            foreach ($sale->paymentDetails as $d) {
                if ($d->bank_name) {
                    $extraBankNames[] = $d->bank_name;
                }
            }
        }
        foreach ($payments as $p) {
            if ($p->bank) {
                $extraBankNames[] = $p->bank;
            }
        }

        $categories = IncomeReportService::getCategories($extraBankNames);

        // Initialize day data
        $dayData = [];
        foreach ($categories as $cat) {
            $dayData[$cat] = [
                'contado' => 0.0,
                'cobranza' => 0.0
            ];
        }

        // 1. CONTADO
        foreach ($sales as $sale) {
            $details = $sale->paymentDetails;
            if ($details->count() > 0) {
                foreach ($details as $d) {
                    $rate = $d->exchange_rate > 0 ? $d->exchange_rate : 1;
                    $amtUSD = $d->amount / $rate;
                    $cat = IncomeReportService::resolveCategory($d->payment_method, $d->currency_code, $d->bank_name, $categories);
                    if (!isset($dayData[$cat])) {
                        $dayData[$cat] = ['contado' => 0.0, 'cobranza' => 0.0];
                    }
                    $dayData[$cat]['contado'] += $amtUSD;
                }

                // Subtract change
                $changes = $sale->changeDetails;
                foreach ($changes as $c) {
                    $rate = $c->exchange_rate > 0 ? $c->exchange_rate : 1;
                    $amtUSD = $c->amount / $rate;
                    $cat = IncomeReportService::resolveCategory('cash', $c->currency_code, null, $categories);
                    if (!isset($dayData[$cat])) {
                        $dayData[$cat] = ['contado' => 0.0, 'cobranza' => 0.0];
                    }
                    $dayData[$cat]['contado'] -= $amtUSD;
                }
            } else {
                if ($sale->type !== 'credit') {
                    $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                    $netAmt = ($sale->cash - $sale->change) / $rate;
                    $cat = IncomeReportService::resolveCategory($sale->type, $sale->primary_currency_code, $sale->bank_name, $categories);
                    if (!isset($dayData[$cat])) {
                        $dayData[$cat] = ['contado' => 0.0, 'cobranza' => 0.0];
                    }
                    $dayData[$cat]['contado'] += $netAmt;
                }
            }
        }

        // 2. COBRANZA
        foreach ($payments as $p) {
            $rate = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
            $amtUSD = $p->amount / $rate;
            $cat = IncomeReportService::resolveCategory($p->pay_way, $p->currency, $p->bank, $categories);
            if (!isset($dayData[$cat])) {
                $dayData[$cat] = ['contado' => 0.0, 'cobranza' => 0.0];
            }
            $dayData[$cat]['cobranza'] += $amtUSD;
        }

        // 3. VENTAS A CREDITO
        $creditSales = Sale::whereBetween('created_at', [
                $dateParsed->copy()->startOfDay(), 
                $dateParsed->copy()->endOfDay()
            ])
            ->where('type', 'credit')
            ->whereNotIn('status', ['voided', 'cancelled', 'anulated', 'returned'])
            ->get();

        $dayCreditTotal = 0.0;
        foreach ($creditSales as $sale) {
            $returnsForSale = \App\Models\SaleReturn::where('sale_id', $sale->id)
                ->where('status', 'approved')
                ->get();
            $retAmtUSD = 0.0;
            foreach ($returnsForSale as $ret) {
                $rt_rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                $retAmtUSD += ($ret->total_returned / $rt_rate);
            }
            $netSaleUSD = $sale->total_usd - $retAmtUSD;

            $pdSum = $sale->paymentDetails->sum(function($d) {
                $rate = $d->exchange_rate > 0 ? $d->exchange_rate : 1;
                return $d->amount / $rate;
            });
            $dayCreditTotal += max(0.0, $netSaleUSD - $pdSum);
        }

        // Calculate Subtotals
        $subtotalContado = 0.0;
        $subtotalCobranza = 0.0;
        foreach ($categories as $cat) {
            $subtotalContado += $dayData[$cat]['contado'];
            $subtotalCobranza += $dayData[$cat]['cobranza'];
        }

        $ventasMasCreditoContado = $subtotalContado + $dayCreditTotal;
        $totalGeneral = $ventasMasCreditoContado + $subtotalCobranza;
        $totalRecibido = $subtotalContado + $subtotalCobranza;

        // Build the closure text message
        $companyName = strtoupper($config->business_name ?: 'SISTEMA');
        $dateFormatted = $dateParsed->format('d/m/Y');
        $dayName = strtoupper($dateParsed->locale('es')->dayName);

        $waMessage = "*CIERRE DE VENTAS DIARIAS*\n\n" .
                     "*Empresa:* {$companyName}\n" .
                     "*Fecha:* {$dayName} ({$dateFormatted})\n" .
                     "-----------------------------------\n";

        foreach ($categories as $cat) {
            $contado = $dayData[$cat]['contado'];
            $cobranza = $dayData[$cat]['cobranza'];
            if ($contado > 0 || $cobranza > 0) {
                $waMessage .= "*{$cat}*:\n";
                if ($contado > 0) $waMessage .= "   • Contado: $" . number_format($contado, 2) . " USD\n";
                if ($cobranza > 0) $waMessage .= "   • Cobranza: $" . number_format($cobranza, 2) . " USD\n";
            }
        }

        $waMessage .= "-----------------------------------\n" .
                      "*Subtotal Contado:* $" . number_format($subtotalContado, 2) . " USD\n" .
                      "*Subtotal Cobranza:* $" . number_format($subtotalCobranza, 2) . " USD\n" .
                      "*Ventas a Crédito:* $" . number_format($dayCreditTotal, 2) . " USD\n" .
                      "-----------------------------------\n" .
                      "*Total General:* $" . number_format($totalGeneral, 2) . " USD\n" .
                      "*Total Recibido (Caja):* $" . number_format($totalRecibido, 2) . " USD";

        // Dispatch via Email
        try {
            $emailRecipients = $config->email_closure_recipients ?: [];
            if (!empty($emailRecipients)) {
                $companyName = strtoupper($config->business_name ?: 'SISTEMA');
                $subject = "Cierre Diario de Ventas - {$companyName}";
                
                // Formatear texto para Markdown de email
                $emailBody = str_replace("\n", "\n\n", $waMessage);
                
                \Illuminate\Support\Facades\Mail::to($emailRecipients)->queue(new \App\Mail\GenericNotificationMail(
                    $subject,
                    $emailBody
                ));
                $this->info("Daily closure report sent successfully via email.");
            }
        } catch (\Exception $e) {
            $this->error("Error sending daily closure email notification: " . $e->getMessage());
        }

        // Dispatch via WhatsApp
        try {
            $whatsappService = app(WhatsappService::class);
            if ($whatsappService->checkStatus()) {
                // Send to Groups
                $selectedGroups = $config->whatsapp_closure_groups ?: [];
                if (empty($selectedGroups)) {
                    $whatsappService->sendToGroupByName('Diferencial', $waMessage);
                } else {
                    foreach ($selectedGroups as $groupId) {
                        $whatsappService->sendMessage($groupId, $waMessage);
                    }
                }

                // Send to specific Users
                $selectedUsers = $config->whatsapp_closure_users ?: [];
                if (!empty($selectedUsers)) {
                    $users = \App\Models\User::whereIn('id', $selectedUsers)->whereNotNull('phone')->get();
                    foreach ($users as $user) {
                        $whatsappService->sendMessage($user->phone, $waMessage);
                    }
                }
                $this->info("Daily closure report sent successfully via WhatsApp.");
            } else {
                $this->warn("WhatsApp client is offline. Skipping send.");
            }
        } catch (\Exception $e) {
            $this->error("Error sending WhatsApp notification: " . $e->getMessage());
        }

        return 0;
    }
}
