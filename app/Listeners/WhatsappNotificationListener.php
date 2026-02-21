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

class WhatsappNotificationListener
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
        if ($event instanceof SaleCreated) {
            $this->handleSaleCreated($event->sale);
        } elseif ($event instanceof PaymentReceived) {
            $this->handlePaymentReceived($event->paymentModel, $event->amountPaid, $event->sale);
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

    protected function compileTemplate($body, $sale, $customer, $amountPaid = 0, $debt = 0, $paymentCurrencyCode = null)
    {
        $primaryCurrency = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
        $primarySymbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
        
        $paymentCurrencySymbol = $primarySymbol;
        if ($paymentCurrencyCode) {
            $paymentCurrencySymbol = \App\Helpers\CurrencyHelper::getSymbol($paymentCurrencyCode);
        }

        $vars = [
            '{CLIENTE}' => $customer->name,
            '[CLIENTE]' => $customer->name,
            '{FACTURA}' => $sale->id,
            '[FACTURA]' => $sale->id,
            '[FACTURA_PAGADA]' => $sale->id,
            '{TOTAL}' => $primarySymbol . ' ' . number_format($sale->total, 2),
            '[TOTAL]' => $primarySymbol . ' ' . number_format($sale->total, 2),
            '{ABONO}' => $paymentCurrencySymbol . ' ' . number_format($amountPaid, 2),
            '[MONTO_PAGADO]' => $paymentCurrencySymbol . ' ' . number_format($amountPaid, 2),
            '{SALDO}' => $primarySymbol . ' ' . number_format($debt, 2),
            '[SALDO_RESTANTE]' => $primarySymbol . ' ' . number_format($debt, 2),
        ];

        return str_replace(array_keys($vars), array_values($vars), $body);
    }
}
