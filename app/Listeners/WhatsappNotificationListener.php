<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\WhatsappTemplate;
use App\Models\WhatsappMessage;
use App\Models\Sale;
use App\Events\SaleCreated;
use App\Events\PaymentReceived;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class WhatsappNotificationListener implements ShouldQueue
{
    use \App\Traits\PdfInvoiceTrait;

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
    public function handle(object $event): void
    {
        if ($event instanceof \App\Events\SaleCreated) {
            $this->handleSaleCreated($event->sale);
        } elseif ($event instanceof \App\Events\PaymentReceived) {
            $this->handlePaymentReceived($event->paymentModel, $event->amountPaid, $event->sale);
        } elseif ($event instanceof \App\Events\CargoCreated) {
            $this->handleCargoCreated($event->cargo);
        } elseif ($event instanceof \App\Events\DescargoCreated) {
            $this->handleDescargoCreated($event->descargo);
        }
    }

    protected function handleCargoCreated(\App\Models\Cargo $cargo)
    {
        try {
            $template = WhatsappTemplate::where('event_type', 'cargo_created')->where('is_active', true)->first();
            if (!$template) return;

            // Generate Cargo Detail PDF
            $config = \App\Models\Configuration::first();
            $fullCargo = \App\Models\Cargo::with(['warehouse', 'user', 'details.product'])->find($cargo->id);
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.cargo-detail-pdf', ['cargo' => $fullCargo, 'config' => $config]);
            
            $pdfDir = storage_path('app/public/whatsapp_pdfs');
            if (!file_exists($pdfDir)) {
                mkdir($pdfDir, 0777, true);
            }
            $pdfPath = $pdfDir . '/cargo_detalle_' . $cargo->id . '.pdf';
            $pdf->save($pdfPath);

            // Find users with 'adjustments.approve_cargo' permission
            $approvers = \App\Models\User::permission('adjustments.approve_cargo')->get();

            foreach ($approvers as $user) {
                if (!$user->phone) continue;
                $phone = preg_replace('/[^0-9]/', '', $user->phone);
                
                $messageText = $this->compileTemplate($template->body, null, null, 0, 0, null, $cargo);

                WhatsappMessage::create([
                    'related_model_id' => $cargo->id,
                    'related_model_type' => \App\Models\Cargo::class,
                    'customer_id' => null, // Internal notify
                    'phone_number' => $phone,
                    'message_body' => $messageText,
                    'attachment_path' => $pdfPath,
                    'status' => 'pending'
                ]);
            }

            // Finally: Dispatch current batch of messages
            $pending = WhatsappMessage::where('status', 'pending')
                ->where('related_model_id', $cargo->id)
                ->where('related_model_type', \App\Models\Cargo::class)
                ->get();

            foreach ($pending as $msg) {
                \App\Jobs\SendWhatsappMessage::dispatch($msg->id);
            }

        } catch (\Exception $e) {
            Log::error("Error en WhatsappNotificationListener (CargoCreated): " . $e->getMessage());
        }
    }

    protected function handleDescargoCreated(\App\Models\Descargo $descargo)
    {
        try {
            $template = WhatsappTemplate::where('event_type', 'descargo_created')->where('is_active', true)->first();
            if (!$template) return;

            // Generate Descargo Detail PDF
            $config = \App\Models\Configuration::first();
            $fullDescargo = \App\Models\Descargo::with(['warehouse', 'user', 'details.product'])->find($descargo->id);
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('reports.descargo-detail-pdf', ['adjustment' => $fullDescargo, 'config' => $config]);
            
            $pdfDir = storage_path('app/public/whatsapp_pdfs');
            if (!file_exists($pdfDir)) {
                mkdir($pdfDir, 0777, true);
            }
            $pdfPath = $pdfDir . '/descargo_detalle_' . $descargo->id . '.pdf';
            $pdf->save($pdfPath);

            // Find users with 'adjustments.approve_descargo' permission
            $approvers = \App\Models\User::permission('adjustments.approve_descargo')->get();

            foreach ($approvers as $user) {
                if (!$user->phone) continue;
                $phone = preg_replace('/[^0-9]/', '', $user->phone);
                
                $messageText = $this->compileTemplate($template->body, null, null, 0, 0, null, null, $descargo);

                WhatsappMessage::create([
                    'related_model_id' => $descargo->id,
                    'related_model_type' => \App\Models\Descargo::class,
                    'customer_id' => null, // Internal notify
                    'phone_number' => $phone,
                    'message_body' => $messageText,
                    'attachment_path' => $pdfPath,
                    'status' => 'pending'
                ]);
            }

            // Finally: Dispatch current batch of messages
            $pending = WhatsappMessage::where('status', 'pending')
                 ->where('related_model_id', $descargo->id)
                 ->where('related_model_type', \App\Models\Descargo::class)
                 ->get();

            foreach ($pending as $msg) {
                \App\Jobs\SendWhatsappMessage::dispatch($msg->id);
            }

        } catch (\Exception $e) {
            Log::error("Error en WhatsappNotificationListener (DescargoCreated): " . $e->getMessage());
        }
    }

    protected function handleSaleCreated(Sale $sale)
    {
        try {
            $customer = $sale->customer;
            if (!$customer || !$customer->whatsapp_notify_sales) return;

            $phone = $this->resolvePhoneNumber($customer);
            if (!$phone) return;

            $template = WhatsappTemplate::where('event_type', 'sale_created')->where('is_active', true)->first();
            if (!$template) return;

            $messageText = $this->compileTemplate($template->body, $sale, $customer);
            
            // Generar PDF usando el formato profesional (Jhosagid Invoices)
            $customFilename = 'factura_wa_' . $sale->id;
            
            // Reload sale to ensure relations are present for the invoice trait
            $fullSale = Sale::with(['customer', 'user', 'details', 'details.product'])->find($sale->id);
            
            if ($fullSale->status == 'paid') {
                $pdfPath = $this->getSavedPdfInvoicePathPaid($fullSale, $customFilename);
            } else {
                $pdfPath = $this->getSavedPdfInvoicePathPending($fullSale, $customFilename);
            }
            
            if (!$pdfPath || !file_exists($pdfPath)) {
                Log::error("WhatsappNotificationListener: no se pudo generar el PDF profesional para la venta {$sale->id}");
                return;
            }

            $msg = WhatsappMessage::create([
                'related_model_id' => $sale->id,
                'related_model_type' => \App\Models\Sale::class,
                'customer_id' => $customer->id,
                'phone_number' => $phone,
                'message_body' => $messageText,
                'attachment_path' => $pdfPath,
                'status' => 'pending'
            ]);

            \App\Jobs\SendWhatsappMessage::dispatch($msg->id);

        } catch (\Exception $e) {
            Log::error("Error en WhatsappNotificationListener (SaleCreated): " . $e->getMessage());
        }
    }

    protected function handlePaymentReceived($payment, $amountPaid, Sale $sale)
    {
        Log::info("WhatsappNotificationListener: handlePaymentReceived triggered for sale: {$sale->id}");
        try {
            $customer = $sale->customer;
            if (!$customer) {
                Log::info("WhatsappNotificationListener: No customer found.");
                return;
            }
            Log::info("WhatsappNotificationListener: Customer {$customer->id} found. whatsapp_notify_payments: " . ($customer->whatsapp_notify_payments ?? 'null'));

            if (empty($customer->whatsapp_notify_payments)) {
                Log::info("WhatsappNotificationListener: Customer doesn't have whatsapp_notify_payments enabled.");
                return;
            }

            $phone = $this->resolvePhoneNumber($customer);
            if (!$phone) {
                Log::info("WhatsappNotificationListener: No phone number resolved.");
                return;
            }

            $template = WhatsappTemplate::where('event_type', 'payment_received')->where('is_active', true)->first();
            if (!$template) {
                Log::info("WhatsappNotificationListener: No active template found for payment_received.");
                return;
            }

            Log::info("WhatsappNotificationListener: Building message. Amount: $amountPaid");

            // Optional: calculate account balance
            $debt = round($sale->total - $sale->payments()->where('status', 'approved')->sum('amount'), 2);
            $paymentCurrencyCode = $payment->currency ?? null;
            $messageText = $this->compileTemplate($template->body, $sale, $customer, $amountPaid, max(0, $debt), $paymentCurrencyCode);

            // Generate Payment Receipt PDF
            $config = \App\Models\Configuration::first();
            $fullSale = Sale::with(['customer', 'payments.zelleRecord', 'payments.bankRecord.bank', 'user'])->find($sale->id);
            $pdf = Pdf::loadView('reports.payment-history-pdf', ['sale' => $fullSale, 'config' => $config]);
            $pdfPath = storage_path('app/public/whatsapp_pdfs/recibo_pago_' . $payment->id . '.pdf');
            
            if (!file_exists(storage_path('app/public/whatsapp_pdfs'))) {
                mkdir(storage_path('app/public/whatsapp_pdfs'), 0777, true);
            }
            $pdf->save($pdfPath);

            $msg = WhatsappMessage::create([
                'related_model_id' => $payment->id,
                'related_model_type' => \App\Models\Payment::class,
                'customer_id' => $customer->id,
                'phone_number' => $phone,
                'message_body' => $messageText,
                'attachment_path' => $pdfPath,
                'status' => 'pending'
            ]);

            Log::info("WhatsappNotificationListener: Message created with ID {$msg->id}. Dispatching job.");
            \App\Jobs\SendWhatsappMessage::dispatch($msg->id);

        } catch (\Exception $e) {
            Log::error("Error en WhatsappNotificationListener (PaymentReceived): " . $e->getMessage());
        }
    }

    protected function resolvePhoneNumber($customer)
    {
        $phone = $customer->phone;
        // Fallback to seller if no customer phone
        if (empty($phone) && $customer->seller) {
            $phone = $customer->seller->phone;
        }

        if (empty($phone)) return null;

        // Limpiar
        return preg_replace('/[^0-9]/', '', $phone);
    }

    protected function compileTemplate($body, $sale = null, $customer = null, $amountPaid = 0, $debt = 0, $paymentCurrencyCode = null, $cargo = null, $descargo = null)
    {
        $primaryCurrency = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
        $primarySymbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
        
        $paymentCurrencySymbol = $primarySymbol;
        if ($paymentCurrencyCode) {
            $paymentCurrencySymbol = \App\Helpers\CurrencyHelper::getSymbol($paymentCurrencyCode);
        }

        $conf = \App\Models\Configuration::first();

        // Support for both Cargo and Descargo in the same variables or separate if user prefers
        $adj = $cargo ?? $descargo;

        $vars = [
            '{CLIENTE}' => $customer ? $customer->name : '',
            '[CLIENTE]' => $customer ? $customer->name : '',
            '{FACTURA}' => $sale ? $sale->id : '',
            '[FACTURA]' => $sale ? $sale->id : '',
            '[FACTURA_PAGADA]' => $sale ? $sale->id : '',
            '{TOTAL}' => $sale ? ($primarySymbol . ' ' . number_format($sale->total, 2)) : '',
            '[TOTAL]' => $sale ? ($primarySymbol . ' ' . number_format($sale->total, 2)) : '',
            '{ABONO}' => $paymentCurrencySymbol . ' ' . number_format($amountPaid, 2),
            '[MONTO_PAGADO]' => $paymentCurrencySymbol . ' ' . number_format($amountPaid, 2),
            '{SALDO}' => $primarySymbol . ' ' . number_format($debt, 2),
            '[SALDO_RESTANTE]' => $primarySymbol . ' ' . number_format($debt, 2),
            '[EMPRESA]' => $conf->business_name ?? 'Sistema POS',
            '[CARGO_ID]' => $cargo ? $cargo->id : '',
            '[DESCARGO_ID]' => $descargo ? $descargo->id : '',
            '[MOTIVO]' => $adj ? $adj->motive : '',
            '[USUARIO]' => $adj ? $adj->user->name : '',
            '[AUTORIZADO]' => $adj ? $adj->authorized_by : '',
            '[FECHA]' => $adj ? $adj->date->format('d/m/Y H:i') : ($sale ? $sale->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i'))
        ];

        return str_replace(array_keys($vars), array_values($vars), $body);
    }
}
