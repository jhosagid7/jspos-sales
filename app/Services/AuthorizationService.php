<?php

namespace App\Services;

use App\Models\CreditAuthorization;
use App\Models\Configuration;
use App\Models\User;
use App\Models\Sale;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class AuthorizationService
{
    public static function generateUniquePin(): string
    {
        do {
            $pin = sprintf('%06d', mt_rand(100000, 999999));
        } while (CreditAuthorization::where('pin_code', $pin)->where('status', 'pending')->where('expires_at', '>', now())->exists());

        return $pin;
    }

    public static function requestAuthorization(string $actionType, Sale $sale, string $reason, ?User $requestedBy = null): int
    {
        $config = Configuration::first();
        $requestedBy = $requestedBy ?? auth()->user();

        $actionTitle = $actionType === 'sale_edit' ? 'EDICIÓN DE FACTURA' : ($actionType === 'sale_delete' ? 'ELIMINACIÓN / ANULACIÓN DE FACTURA' : 'AUTORIZACIÓN DE CRÉDITO');

        $invoiceNum = $sale->invoice_number ?: $sale->id;
        $customerName = $sale->customer ? $sale->customer->name : 'Cliente General';
        $totalAmount = number_format($sale->total_usd > 0 ? $sale->total_usd : $sale->total, 2);

        $baseMessage = "*SOLICITUD DE {$actionTitle}*\n\n" .
                       "*Operador:* " . $requestedBy->name . "\n" .
                       "*Factura:* #" . $invoiceNum . "\n" .
                       "*Cliente:* " . $customerName . "\n" .
                       "*Monto Total:* $" . $totalAmount . "\n\n" .
                       "*Motivo Explicativo:* " . $reason;

        $emailRecipients = [];
        $whatsappUserIds = [];

        if ($actionType === 'sale_edit') {
            $emailRecipients = !empty($config->email_edit_auth_recipients) ? $config->email_edit_auth_recipients : ($config->email_credit_auth_recipients ?? []);
            $whatsappUserIds = !empty($config->whatsapp_edit_auth_users) ? $config->whatsapp_edit_auth_users : ($config->whatsapp_credit_auth_users ?? []);
        } elseif ($actionType === 'sale_delete') {
            $emailRecipients = !empty($config->email_delete_auth_recipients) ? $config->email_delete_auth_recipients : ($config->email_credit_auth_recipients ?? []);
            $whatsappUserIds = !empty($config->whatsapp_delete_auth_users) ? $config->whatsapp_delete_auth_users : ($config->whatsapp_credit_auth_users ?? []);
        } else {
            $emailRecipients = $config->email_credit_auth_recipients ?? [];
            $whatsappUserIds = $config->whatsapp_credit_auth_users ?? [];
        }

        $sentCount = 0;

        // Enviar por correo a cada destinatario con su PIN Único Individual
        if (!empty($emailRecipients)) {
            foreach ($emailRecipients as $email) {
                $cleanEmail = trim($email);
                if (empty($cleanEmail)) continue;

                $user = User::whereRaw('LOWER(email) = ?', [strtolower($cleanEmail)])->first();
                $pin = self::generateUniquePin();

                CreditAuthorization::create([
                    'customer_id' => $sale->customer_id,
                    'requested_by_id' => $requestedBy->id,
                    'approved_by_id' => $user ? $user->id : null,
                    'pin_code' => $pin,
                    'status' => 'pending',
                    'action_type' => $actionType,
                    'recipient_email' => $cleanEmail,
                    'amount_requested' => $sale->total,
                    'sale_id' => $sale->id,
                    'expires_at' => now()->addMinutes(15),
                ]);

                $personalMessage = $baseMessage . "\n\n*TU PIN DE AUTORIZACIÓN ÚNICO:* *" . $pin . "*\n(Válido por 15 minutos)";

                try {
                    Mail::raw($personalMessage, function($msg) use ($cleanEmail, $pin, $actionTitle, $invoiceNum) {
                        $msg->to($cleanEmail)->subject("{$actionTitle} Factura #{$invoiceNum} - PIN: {$pin}");
                    });
                    $sentCount++;
                } catch (\Exception $e) {
                    Log::error("Error enviando email auth: " . $e->getMessage());
                }
            }
        }

        // Enviar por WhatsApp a cada usuario con su PIN Único Individual
        if (!empty($whatsappUserIds)) {
            $whatsappService = app(WhatsappService::class);
            if ($whatsappService->checkStatus()) {
                $users = User::whereIn('id', $whatsappUserIds)->get();
                foreach ($users as $u) {
                    if ($u->phone) {
                        $phone = preg_replace('/[^0-9]/', '', $u->phone);
                        if (strlen($phone) >= 10) {
                            $pin = self::generateUniquePin();

                            CreditAuthorization::create([
                                'customer_id' => $sale->customer_id,
                                'requested_by_id' => $requestedBy->id,
                                'approved_by_id' => $u->id,
                                'pin_code' => $pin,
                                'status' => 'pending',
                                'action_type' => $actionType,
                                'recipient_phone' => $phone,
                                'amount_requested' => $sale->total,
                                'sale_id' => $sale->id,
                                'expires_at' => now()->addMinutes(15),
                            ]);

                            $personalMessage = $baseMessage . "\n\n*TU PIN DE AUTORIZACIÓN ÚNICO:* *" . $pin . "*\n(Válido por 15 minutos)";
                            $whatsappService->sendMessage($phone, $personalMessage);
                            $sentCount++;
                        }
                    }
                }
            }
        }

        return $sentCount;
    }

    public static function validatePin(string $pinCode, string $actionType, ?int $saleId = null): array
    {
        $pinCode = strtoupper(trim($pinCode));
        if (empty($pinCode)) {
            return ['success' => false, 'message' => 'Por favor ingrese el PIN de autorización.'];
        }

        $query = CreditAuthorization::where('pin_code', $pinCode)
            ->where('status', 'pending')
            ->where('expires_at', '>', now());

        if ($actionType) {
            $query->where('action_type', $actionType);
        }

        if ($saleId) {
            $query->where('sale_id', $saleId);
        }

        $auth = $query->first();

        if (!$auth) {
            return ['success' => false, 'message' => 'El PIN ingresado es incorrecto o ha expirado.'];
        }

        $supervisor = $auth->approvedBy ?: ($auth->recipient_email ? User::whereRaw('LOWER(email) = ?', [strtolower($auth->recipient_email)])->first() : null);
        $supervisorId = $supervisor ? $supervisor->id : auth()->id();

        $auth->update([
            'status' => 'approved',
            'approved_by_id' => $supervisorId
        ]);

        // Expirar PINs concurrentes de la misma solicitud
        CreditAuthorization::where('action_type', $auth->action_type)
            ->where('status', 'pending')
            ->where('requested_by_id', $auth->requested_by_id)
            ->where('id', '!=', $auth->id)
            ->update(['status' => 'expired']);

        return [
            'success' => true,
            'authorization' => $auth,
            'supervisor' => $supervisor,
            'supervisor_name' => $supervisor ? $supervisor->name : 'Supervisor Autorizado'
        ];
    }
}
