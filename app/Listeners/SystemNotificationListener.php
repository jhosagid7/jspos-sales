<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\WhatsappTemplate;
use App\Models\WhatsappMessage;
use App\Models\EmailTemplate;
use App\Models\EmailMessage;
use App\Models\Sale;
use App\Models\Customer;
use App\Models\User;
use App\Events\SaleCreated;
use App\Events\PaymentReceived;
use App\Events\CargoCreated;
use App\Events\DescargoCreated;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class SystemNotificationListener implements ShouldQueue
{
    use \App\Traits\PdfInvoiceTrait;

    public function handle(object $event): void
    {
        if ($event instanceof SaleCreated) {
            $this->handleSaleCreated($event->sale);
        } elseif ($event instanceof PaymentReceived) {
            $this->handlePaymentReceived($event->paymentModel, $event->amountPaid, $event->sale);
        } elseif ($event instanceof CargoCreated) {
            $this->handleCargoCreated($event->cargo);
        } elseif ($event instanceof DescargoCreated) {
            $this->handleDescargoCreated($event->descargo);
        }
    }

    // --- SALES ---
    protected function handleSaleCreated(Sale $sale)
    {
        try {
            // Reload customer to ensure we have fresh data for notification flags
            $customer = Customer::find($sale->customer_id);
            if (!$customer) return;

            // 1. WHATSAPP
            $this->processWhatsappSale($sale, $customer);

            // 2. EMAIL
            $this->processEmailSale($sale, $customer);

        } catch (\Exception $e) {
            Log::error("Error en SystemNotificationListener (SaleCreated): " . $e->getMessage());
        }
    }

    protected function processWhatsappSale(Sale $sale, Customer $customer)
    {
        $template = WhatsappTemplate::where('event_type', 'sale_created')->first();
        if (!$template) return;

        $phone = $this->resolvePhoneNumber($customer);
        if (!$phone) return;

        $messageText = $this->compileTemplate($template->body, $sale, $customer);
        $pdfPath = $this->generateSalePdf($sale);

        if (!$pdfPath) return;

        $msg = WhatsappMessage::create([
            'related_model_id' => $sale->id,
            'related_model_type' => Sale::class,
            'customer_id' => $customer->id,
            'phone_number' => $phone,
            'message_body' => $messageText,
            'attachment_path' => $pdfPath,
            'status' => 'pending'
        ]);

        // Dispatch Mode Logic: Priority to Global setting if Customer explicitly allows or hasn't disabled
        $globalMode = $template->dispatch_mode ?? 'auto';
        $customerMode = $customer->wa_dispatch_mode ?? 'auto';
        
        $shouldAutoSend = $template->is_active && ($customer->whatsapp_notify_sales !== false);

        if ($shouldAutoSend && $globalMode === 'auto' && $customerMode === 'auto') {
            \App\Jobs\SendWhatsappMessage::dispatch($msg->id);
        }
    }

    protected function processEmailSale(Sale $sale, Customer $customer)
    {
        $template = EmailTemplate::where('event_type', 'sale_created')->first();
        if (!$template) return;

        $email = $this->resolveEmail($customer);
        if (!$email) return;

        $subject = $this->compileTemplate($template->subject, $sale, $customer);
        $body = $this->compileTemplate($template->body, $sale, $customer);
        $pdfPath = $this->generateSalePdf($sale);

        if (!$pdfPath) return;

        $msg = EmailMessage::create([
            'related_model_id' => $sale->id,
            'related_model_type' => Sale::class,
            'customer_id' => $customer->id,
            'email_address' => $email,
            'subject' => $subject,
            'message_body' => $body,
            'attachment_path' => $pdfPath,
            'status' => 'pending'
        ]);

        $globalMode = $template->dispatch_mode ?? 'auto';
        $customerMode = $customer->email_dispatch_mode ?? 'auto';
        $shouldAutoSend = $template->is_active && ($customer->email_notify_sales !== false);

        if ($shouldAutoSend && $globalMode === 'auto' && $customerMode === 'auto') {
            \App\Jobs\SendEmailNotification::dispatch($msg->id);
        }
    }

    // --- PAYMENTS ---
    protected function handlePaymentReceived($payment, $amountPaid, Sale $sale)
    {
        try {
            // Reload customer to ensure we have fresh data for notification flags
            $customer = Customer::find($sale->customer_id);
            if (!$customer) return;

            // 1. WHATSAPP
            $this->processWhatsappPayment($payment, $amountPaid, $sale, $customer);

            // 2. EMAIL
            $this->processEmailPayment($payment, $amountPaid, $sale, $customer);

        } catch (\Exception $e) {
            Log::error("Error en SystemNotificationListener (PaymentReceived): " . $e->getMessage());
        }
    }

    protected function processWhatsappPayment($payment, $amountPaid, Sale $sale, Customer $customer)
    {
        $template = WhatsappTemplate::where('event_type', 'payment_received')->first();
        if (!$template) return;

        $phone = $this->resolvePhoneNumber($customer);
        if (!$phone) return;

        $debt = round($sale->total - $sale->payments()->where('status', 'approved')->sum('amount'), 2);
        $currencyCode = $payment->currency ?? null;
        $messageText = $this->compileTemplate($template->body, $sale, $customer, $amountPaid, max(0, $debt), $currencyCode);
        
        $pdfPath = $this->generatePaymentPdf($sale); 
        if (!$pdfPath) return;

        $msg = WhatsappMessage::create([
            'related_model_id' => $payment->id,
            'related_model_type' => \App\Models\Payment::class,
            'customer_id' => $customer->id,
            'phone_number' => $phone,
            'message_body' => $messageText,
            'attachment_path' => $pdfPath,
            'status' => 'pending'
        ]);

        $globalMode = $template->dispatch_mode ?? 'auto';
        $customerMode = $customer->wa_dispatch_mode ?? 'auto';

        $shouldAutoSend = $template->is_active && ($customer->whatsapp_notify_payments != false);

        if ($shouldAutoSend && $globalMode === 'auto' && $customerMode === 'auto') {
            \App\Jobs\SendWhatsappMessage::dispatch($msg->id);
        }
    }

    protected function processEmailPayment($payment, $amountPaid, Sale $sale, Customer $customer)
    {
        $template = EmailTemplate::where('event_type', 'payment_received')->first();
        if (!$template) return;

        $email = $this->resolveEmail($customer);
        if (!$email) return;

        $debt = round($sale->total - $sale->payments()->where('status', 'approved')->sum('amount'), 2);
        $currencyCode = $payment->currency ?? null;
        
        $subject = $this->compileTemplate($template->subject, $sale, $customer, $amountPaid, max(0, $debt), $currencyCode);
        $body = $this->compileTemplate($template->body, $sale, $customer, $amountPaid, max(0, $debt), $currencyCode);
        
        $pdfPath = $this->generatePaymentPdf($sale);
        if (!$pdfPath) return;

        $msg = EmailMessage::create([
            'related_model_id' => $payment->id,
            'related_model_type' => \App\Models\Payment::class,
            'customer_id' => $customer->id,
            'email_address' => $email,
            'subject' => $subject,
            'message_body' => $body,
            'attachment_path' => $pdfPath,
            'status' => 'pending'
        ]);

        $globalMode = $template->dispatch_mode ?? 'auto';
        $customerMode = $customer->email_dispatch_mode ?? 'auto';
        // Treat NULL or empty as TRUE (default behavior for existing customers)
        $shouldAutoSend = $template->is_active && ($customer->email_notify_payments !== false);

        if ($shouldAutoSend && $globalMode === 'auto' && $customerMode === 'auto') {
            \App\Jobs\SendEmailNotification::dispatch($msg->id);
        }
    }

    // --- CARGOS & DESCARGOS ---
    protected function handleCargoCreated(\App\Models\Cargo $cargo)
    {
        try {
            $waTemplate = WhatsappTemplate::where('event_type', 'cargo_created')->first();
            $emailTemplate = EmailTemplate::where('event_type', 'cargo_created')->first();
            
            if ($waTemplate || $emailTemplate) {
                $approvers = User::permission('adjustments.approve_cargo')->get();
                $pdfPath = $this->generateCargoPdf($cargo);
                
                foreach ($approvers as $user) {
                    // WhatsApp
                    if ($waTemplate) {
                        $phone = $user->phone ? preg_replace('/[^0-9]/', '', $user->phone) : null;
                        if ($phone) {
                            $msg = WhatsappMessage::create([
                                'related_model_id' => $cargo->id, 'related_model_type' => \App\Models\Cargo::class,
                                'phone_number' => $phone, 'status' => 'pending',
                                'message_body' => $this->compileTemplate($waTemplate->body, null, null, 0, 0, null, $cargo),
                                'attachment_path' => $pdfPath,
                            ]);
                            if ($waTemplate->is_active && ($waTemplate->dispatch_mode ?? 'auto') === 'auto') {
                                \App\Jobs\SendWhatsappMessage::dispatch($msg->id);
                            }
                        }
                    }
                    // Email
                    if ($emailTemplate && $user->email) {
                        $msg = EmailMessage::create([
                            'related_model_id' => $cargo->id, 'related_model_type' => \App\Models\Cargo::class,
                            'email_address' => $user->email, 'status' => 'pending',
                            'subject' => $this->compileTemplate($emailTemplate->subject, null, null, 0, 0, null, $cargo),
                            'message_body' => $this->compileTemplate($emailTemplate->body, null, null, 0, 0, null, $cargo),
                            'attachment_path' => $pdfPath,
                        ]);
                        if ($emailTemplate->is_active && ($emailTemplate->dispatch_mode ?? 'auto') === 'auto') {
                            \App\Jobs\SendEmailNotification::dispatch($msg->id);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error en SystemNotificationListener (CargoCreated): " . $e->getMessage());
        }
    }

    protected function handleDescargoCreated(\App\Models\Descargo $descargo)
    {
        try {
            $waTemplate = WhatsappTemplate::where('event_type', 'descargo_created')->first();
            $emailTemplate = EmailTemplate::where('event_type', 'descargo_created')->first();
            
            if ($waTemplate || $emailTemplate) {
                $approvers = User::permission('adjustments.approve_descargo')->get();
                $pdfPath = $this->generateDescargoPdf($descargo);
                
                foreach ($approvers as $user) {
                    if ($waTemplate) {
                        $phone = $user->phone ? preg_replace('/[^0-9]/', '', $user->phone) : null;
                        if ($phone) {
                            $msg = WhatsappMessage::create([
                                'related_model_id' => $descargo->id, 'related_model_type' => \App\Models\Descargo::class,
                                'phone_number' => $phone, 'status' => 'pending',
                                'message_body' => $this->compileTemplate($waTemplate->body, null, null, 0, 0, null, null, $descargo),
                                'attachment_path' => $pdfPath,
                            ]);
                            if ($waTemplate->is_active && ($waTemplate->dispatch_mode ?? 'auto') === 'auto') {
                                \App\Jobs\SendWhatsappMessage::dispatch($msg->id);
                            }
                        }
                    }
                    if ($emailTemplate && $user->email) {
                        $msg = EmailMessage::create([
                            'related_model_id' => $descargo->id, 'related_model_type' => \App\Models\Descargo::class,
                            'email_address' => $user->email, 'status' => 'pending',
                            'subject' => $this->compileTemplate($emailTemplate->subject, null, null, 0, 0, null, null, $descargo),
                            'message_body' => $this->compileTemplate($emailTemplate->body, null, null, 0, 0, null, null, $descargo),
                            'attachment_path' => $pdfPath,
                        ]);
                        if ($emailTemplate->is_active && ($emailTemplate->dispatch_mode ?? 'auto') === 'auto') {
                            \App\Jobs\SendEmailNotification::dispatch($msg->id);
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error("Error en SystemNotificationListener (DescargoCreated): " . $e->getMessage());
        }
    }

    // --- HELPERS ---
    protected function generatePaymentPdf($sale)
    {
        $config = \App\Models\Configuration::first();
        $fullSale = Sale::with(['customer', 'user', 'payments', 'returns'])->find($sale->id);
        
        $pdf = Pdf::loadView('reports.payment-history-pdf', [
            'sale' => $fullSale,
            'config' => $config
        ]);
        
        $dir = storage_path('app/public/notification_pdfs');
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        
        $path = $dir . '/recibo_pago_sale_' . $sale->id . '.pdf';
        $pdf->save($path);
        
        return (file_exists($path)) ? $path : null;
    }

    protected function generateSalePdf($sale)
    {
        $customFilename = 'factura_notif_' . $sale->id;
        $fullSale = Sale::with(['customer', 'user', 'details', 'details.product'])->find($sale->id);
        
        if ($fullSale->status == 'paid') {
            $path = $this->getSavedPdfInvoicePathPaid($fullSale, $customFilename);
        } else {
            $path = $this->getSavedPdfInvoicePathPending($fullSale, $customFilename);
        }

        return (file_exists($path)) ? $path : null;
    }

    protected function generateCargoPdf($cargo)
    {
        $config = \App\Models\Configuration::first();
        $fullCargo = \App\Models\Cargo::with(['warehouse', 'user', 'details.product'])->find($cargo->id);
        $pdf = Pdf::loadView('reports.cargo-detail-pdf', ['cargo' => $fullCargo, 'config' => $config]);
        $dir = storage_path('app/public/notification_pdfs');
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        $path = $dir . '/cargo_' . $cargo->id . '.pdf';
        $pdf->save($path);
        return $path;
    }

    protected function generateDescargoPdf($descargo)
    {
        $config = \App\Models\Configuration::first();
        $fullDescargo = \App\Models\Descargo::with(['warehouse', 'user', 'details.product'])->find($descargo->id);
        $pdf = Pdf::loadView('reports.descargo-detail-pdf', ['adjustment' => $fullDescargo, 'config' => $config]);
        $dir = storage_path('app/public/notification_pdfs');
        if (!file_exists($dir)) mkdir($dir, 0777, true);
        $path = $dir . '/descargo_' . $descargo->id . '.pdf';
        $pdf->save($path);
        return $path;
    }

    protected function resolvePhoneNumber($customer)
    {
        $phone = $customer->phone;
        if (empty($phone) && $customer->seller) $phone = $customer->seller->phone;
        return (empty($phone)) ? null : preg_replace('/[^0-9]/', '', $phone);
    }

    protected function resolveEmail($customer)
    {
        $email = $customer->email;
        if (empty($email) && $customer->seller) $email = $customer->seller->email;
        return (empty($email)) ? null : $email;
    }

    protected function compileTemplate($body, $sale = null, $customer = null, $amountPaid = 0, $debt = 0, $paymentCurrencyCode = null, $cargo = null, $descargo = null)
    {
        if (!$body) return '';
        $primaryCurrency = \App\Helpers\CurrencyHelper::getPrimaryCurrency();
        $primarySymbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
        $paymentCurrencySymbol = $paymentCurrencyCode ? \App\Helpers\CurrencyHelper::getSymbol($paymentCurrencyCode) : $primarySymbol;
        $conf = \App\Models\Configuration::first();
        $adj = $cargo ?? $descargo;

        $vars = [
            '{CLIENTE}' => $customer ? $customer->name : '', '[CLIENTE]' => $customer ? $customer->name : '',
            '{FACTURA}' => $sale ? $sale->id : '', '[FACTURA]' => $sale ? $sale->id : '',
            '[FACTURA_PAGADA]' => $sale ? $sale->id : '',
            '{TOTAL}' => $sale ? ($primarySymbol . ' ' . number_format($sale->total, 2)) : '', '[TOTAL]' => $sale ? ($primarySymbol . ' ' . number_format($sale->total, 2)) : '',
            '{ABONO}' => $paymentCurrencySymbol . ' ' . number_format($amountPaid, 2), '[MONTO_PAGADO]' => $paymentCurrencySymbol . ' ' . number_format($amountPaid, 2),
            '{SALDO}' => $primarySymbol . ' ' . number_format($debt, 2), '[SALDO_RESTANTE]' => $primarySymbol . ' ' . number_format($debt, 2),
            '[EMPRESA]' => $conf->business_name ?? 'Sistema POS',
            '[CARGO_ID]' => $cargo ? $cargo->id : '', '[DESCARGO_ID]' => $descargo ? $descargo->id : '',
            '[MOTIVO]' => $adj ? $adj->motive : '', '[USUARIO]' => $adj ? $adj->user->name : '',
            '[FECHA]' => $sale ? $sale->created_at->format('d/m/Y H:i') : now()->format('d/m/Y H:i')
        ];
        return str_replace(array_keys($vars), array_values($vars), $body);
    }
}
