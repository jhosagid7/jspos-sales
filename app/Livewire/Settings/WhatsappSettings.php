<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\WhatsappTemplate;

class WhatsappSettings extends Component
{
    public $sale_active = true;
    public $sale_subject = 'Notificación de Venta';
    public $sale_body = 'Hola [CLIENTE], adjunto a este mensaje encontrarás el recibo de tu compra #[FACTURA] por un total de [TOTAL]. ¡Gracias por tu preferencia!';
    public $sale_dispatch_mode = 'auto';

    public $payment_active = true;
    public $payment_subject = 'Notificación de Abono';
    public $payment_body = 'Hola [CLIENTE], hemos recibido tu pago por [MONTO_PAGADO] a la factura #[FACTURA_PAGADA]. Tu saldo restante es de [SALDO_RESTANTE].';
    public $payment_dispatch_mode = 'auto';

    public $cargo_active = true;
    public $cargo_subject = 'Nuevo Cargo / Ajuste Creado';
    public $cargo_body = 'Hola, se ha registrado un nuevo Cargo #[CARGO_ID] por el motivo: [MOTIVO]. Responsable: [USUARIO]. Por favor revisa el panel para su aprobación.';
    public $cargo_dispatch_mode = 'auto';

    public $descargo_active = true;
    public $descargo_subject = 'Nuevo Descargo / Salida de Inventario';
    public $descargo_body = 'Hola, se ha registrado una nueva Salida #[DESCARGO_ID] por el motivo: [MOTIVO]. Responsable: [USUARIO]. Por favor revisa el panel para su aprobación.';
    public $descargo_dispatch_mode = 'auto';
    public $selectedRateGroups = [];
    public $selectedClosureGroups = [];
    public $selectedWeeklyReportGroups = [];
    public $selectedSopladosShiftGroups = [];
    public $selectedSopladosWeeklyGroups = [];

    public $emailRateRecipients = '';
    public $emailClosureRecipients = '';
    public $emailWeeklyReportRecipients = '';
    public $emailSopladosWeeklyRecipients = '';

    public $selectedRateUsers = [];
    public $selectedClosureUsers = [];
    public $selectedWeeklyReportUsers = [];
    public $selectedSopladosShiftUsers = [];
    public $selectedSopladosWeeklyUsers = [];

    public $searchRateQuery = '';
    public $searchClosureQuery = '';
    public $searchWeeklyReportQuery = '';
    public $searchSopladosShiftQuery = '';
    public $searchSopladosWeeklyQuery = '';

    public $rateUsersResults = [];
    public $closureUsersResults = [];
    public $weeklyReportUsersResults = [];
    public $sopladosShiftUsersResults = [];
    public $sopladosWeeklyUsersResults = [];

    // Weekly scheduling config
    public $weeklyReportSendDay = 6;
    public $weeklyReportSendHour = '10:00';

    public function mount()
    {
        $config = \App\Models\Configuration::first();
        $this->selectedRateGroups = $config->whatsapp_rate_groups ?? [];
        $this->selectedClosureGroups = $config->whatsapp_closure_groups ?? [];
        $this->selectedWeeklyReportGroups = $config->whatsapp_weekly_report_groups ?? [];
        $this->selectedSopladosShiftGroups = $config->whatsapp_soplados_shift_groups ?? [];
        $this->selectedSopladosWeeklyGroups = $config->whatsapp_soplados_weekly_groups ?? [];

        $this->emailRateRecipients = implode(', ', $config->email_rate_recipients ?? []);
        $this->emailClosureRecipients = implode(', ', $config->email_closure_recipients ?? []);
        $this->emailWeeklyReportRecipients = implode(', ', $config->email_weekly_report_recipients ?? []);
        $this->emailSopladosWeeklyRecipients = implode(', ', $config->email_soplados_weekly_recipients ?? []);

        $this->selectedRateUsers = $config->whatsapp_rate_users ?? [];
        $this->selectedClosureUsers = $config->whatsapp_closure_users ?? [];
        $this->selectedWeeklyReportUsers = $config->whatsapp_weekly_report_users ?? [];
        $this->selectedSopladosShiftUsers = $config->whatsapp_soplados_shift_users ?? [];
        $this->selectedSopladosWeeklyUsers = $config->whatsapp_soplados_weekly_users ?? [];

        $this->weeklyReportSendDay = $config->weekly_report_send_day ?? 6;
        $this->weeklyReportSendHour = $config->weekly_report_send_hour ?? '10:00';
        $saleTemplate = WhatsappTemplate::firstOrCreate(
            ['event_type' => 'sale_created'],
            ['subject' => $this->sale_subject, 'body' => $this->sale_body, 'is_active' => true, 'dispatch_mode' => 'auto']
        );
        $this->sale_active = $saleTemplate->is_active;
        $this->sale_subject = $saleTemplate->subject;
        $this->sale_body = $saleTemplate->body;
        $this->sale_dispatch_mode = $saleTemplate->dispatch_mode ?? 'auto';

        $paymentTemplate = WhatsappTemplate::firstOrCreate(
            ['event_type' => 'payment_received'],
            ['subject' => $this->payment_subject, 'body' => $this->payment_body, 'is_active' => true, 'dispatch_mode' => 'auto']
        );
        $this->payment_active = $paymentTemplate->is_active;
        $this->payment_subject = $paymentTemplate->subject;
        $this->payment_body = $paymentTemplate->body;
        $this->payment_dispatch_mode = $paymentTemplate->dispatch_mode ?? 'auto';

        $cargoTemplate = WhatsappTemplate::firstOrCreate(
            ['event_type' => 'cargo_created'],
            [
                'subject' => $this->cargo_subject,
                'body' => $this->cargo_body,
                'is_active' => true,
                'dispatch_mode' => 'auto'
            ]
        );
        $this->cargo_active = $cargoTemplate->is_active;
        $this->cargo_subject = $cargoTemplate->subject;
        $this->cargo_body = $cargoTemplate->body;
        $this->cargo_dispatch_mode = $cargoTemplate->dispatch_mode ?? 'auto';

        $descargoTemplate = WhatsappTemplate::firstOrCreate(
            ['event_type' => 'descargo_created'],
            [
                'subject' => $this->descargo_subject,
                'body' => $this->descargo_body,
                'is_active' => true,
                'dispatch_mode' => 'auto'
            ]
        );
        $this->descargo_active = $descargoTemplate->is_active;
        $this->descargo_subject = $descargoTemplate->subject;
        $this->descargo_body = $descargoTemplate->body;
        $this->descargo_dispatch_mode = $descargoTemplate->dispatch_mode ?? 'auto';

        $this->loadGroups();

        session(['map' => 'Ajustes', 'child' => ' WhatsApp']);
    }

    public $groups = [];

    public function loadGroups()
    {
        try {
            $whatsappService = app(\App\Services\WhatsappService::class);
            if ($whatsappService->checkStatus()) {
                $this->groups = $whatsappService->getGroups() ?: [];
            } else {
                $this->groups = [];
            }
        } catch (\Exception $e) {
            $this->groups = [];
        }
    }

    public function toggleGroup($groupId, $actionType)
    {
        if ($actionType === 'rate') {
            if (in_array($groupId, $this->selectedRateGroups)) {
                $this->selectedRateGroups = array_values(array_diff($this->selectedRateGroups, [$groupId]));
            } else {
                $this->selectedRateGroups[] = $groupId;
            }
        } elseif ($actionType === 'closure') {
            if (in_array($groupId, $this->selectedClosureGroups)) {
                $this->selectedClosureGroups = array_values(array_diff($this->selectedClosureGroups, [$groupId]));
            } else {
                $this->selectedClosureGroups[] = $groupId;
            }
        } elseif ($actionType === 'weekly_report') {
            if (in_array($groupId, $this->selectedWeeklyReportGroups)) {
                $this->selectedWeeklyReportGroups = array_values(array_diff($this->selectedWeeklyReportGroups, [$groupId]));
            } else {
                $this->selectedWeeklyReportGroups[] = $groupId;
            }
        } elseif ($actionType === 'soplados_shift') {
            if (in_array($groupId, $this->selectedSopladosShiftGroups)) {
                $this->selectedSopladosShiftGroups = array_values(array_diff($this->selectedSopladosShiftGroups, [$groupId]));
            } else {
                $this->selectedSopladosShiftGroups[] = $groupId;
            }
        } elseif ($actionType === 'soplados_weekly') {
            if (in_array($groupId, $this->selectedSopladosWeeklyGroups)) {
                $this->selectedSopladosWeeklyGroups = array_values(array_diff($this->selectedSopladosWeeklyGroups, [$groupId]));
            } else {
                $this->selectedSopladosWeeklyGroups[] = $groupId;
            }
        }
    }

    public function disconnectWhatsapp()
    {
        try {
            $response = \Illuminate\Support\Facades\Http::post('http://localhost:3000/logout');
            
            if ($response->successful()) {
                $this->dispatch('noty', msg: 'SESIÓN DE WHATSAPP DESCONECTADA');
            } else {
                $this->dispatch('msg-error', msg: 'Hubo un problema al intentar desconectar.');
            }
        } catch (\Exception $e) {
            $this->dispatch('msg-error', msg: 'No se pudo conectar con el servicio de WhatsApp.');
        }
    }

    public function save()
    {
        WhatsappTemplate::updateOrCreate(
            ['event_type' => 'sale_created'],
            [
                'subject' => $this->sale_subject,
                'body' => $this->sale_body,
                'is_active' => $this->sale_active,
                'dispatch_mode' => $this->sale_dispatch_mode
            ]
        );

        WhatsappTemplate::updateOrCreate(
            ['event_type' => 'payment_received'],
            [
                'subject' => $this->payment_subject,
                'body' => $this->payment_body,
                'is_active' => $this->payment_active,
                'dispatch_mode' => $this->payment_dispatch_mode
            ]
        );

        WhatsappTemplate::updateOrCreate(
            ['event_type' => 'cargo_created'],
            [
                'subject' => $this->cargo_subject,
                'body' => $this->cargo_body,
                'is_active' => $this->cargo_active,
                'dispatch_mode' => $this->cargo_dispatch_mode
            ]
        );

        WhatsappTemplate::updateOrCreate(
            ['event_type' => 'descargo_created'],
            [
                'subject' => $this->descargo_subject,
                'body' => $this->descargo_body,
                'is_active' => $this->descargo_active,
                'dispatch_mode' => $this->descargo_dispatch_mode
            ]
        );

        $config = \App\Models\Configuration::first();
        if ($config) {
            $rateEmails = array_values(array_filter(array_map('trim', explode(',', $this->emailRateRecipients))));
            $closureEmails = array_values(array_filter(array_map('trim', explode(',', $this->emailClosureRecipients))));
            $weeklyEmails = array_values(array_filter(array_map('trim', explode(',', $this->emailWeeklyReportRecipients))));
            $sopladosWeeklyEmails = array_values(array_filter(array_map('trim', explode(',', $this->emailSopladosWeeklyRecipients))));

            $config->update([
                'whatsapp_rate_groups' => $this->selectedRateGroups,
                'whatsapp_closure_groups' => $this->selectedClosureGroups,
                'whatsapp_weekly_report_groups' => $this->selectedWeeklyReportGroups,
                'email_rate_recipients' => $rateEmails,
                'email_closure_recipients' => $closureEmails,
                'email_weekly_report_recipients' => $weeklyEmails,
                'whatsapp_rate_users' => $this->selectedRateUsers,
                'whatsapp_closure_users' => $this->selectedClosureUsers,
                'whatsapp_weekly_report_users' => $this->selectedWeeklyReportUsers,
                'whatsapp_soplados_shift_groups' => $this->selectedSopladosShiftGroups,
                'whatsapp_soplados_shift_users' => $this->selectedSopladosShiftUsers,
                'whatsapp_soplados_weekly_groups' => $this->selectedSopladosWeeklyGroups,
                'whatsapp_soplados_weekly_users' => $this->selectedSopladosWeeklyUsers,
                'email_soplados_weekly_recipients' => $sopladosWeeklyEmails,
                'weekly_report_send_day' => (int) $this->weeklyReportSendDay,
                'weekly_report_send_hour' => trim($this->weeklyReportSendHour),
            ]);
        }

        $this->dispatch('noty', msg: 'CONFIGURACIÓN DE WHATSAPP GUARDADA');
    }

    public function updatedSearchRateQuery()
    {
        if (strlen($this->searchRateQuery) < 2) {
            $this->rateUsersResults = [];
            return;
        }
        $this->rateUsersResults = \App\Models\User::where('name', 'like', '%' . $this->searchRateQuery . '%')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->limit(5)
            ->get(['id', 'name', 'phone'])
            ->toArray();
    }

    public function updatedSearchClosureQuery()
    {
        if (strlen($this->searchClosureQuery) < 2) {
            $this->closureUsersResults = [];
            return;
        }
        $this->closureUsersResults = \App\Models\User::where('name', 'like', '%' . $this->searchClosureQuery . '%')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->limit(5)
            ->get(['id', 'name', 'phone'])
            ->toArray();
    }

    public function updatedSearchWeeklyReportQuery()
    {
        if (strlen($this->searchWeeklyReportQuery) < 2) {
            $this->weeklyReportUsersResults = [];
            return;
        }
        $this->weeklyReportUsersResults = \App\Models\User::where('name', 'like', '%' . $this->searchWeeklyReportQuery . '%')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->limit(5)
            ->get(['id', 'name', 'phone'])
            ->toArray();
    }

    public function updatedSearchSopladosShiftQuery()
    {
        if (strlen($this->searchSopladosShiftQuery) < 2) {
            $this->sopladosShiftUsersResults = [];
            return;
        }
        $this->sopladosShiftUsersResults = \App\Models\User::where('name', 'like', '%' . $this->searchSopladosShiftQuery . '%')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->limit(5)
            ->get(['id', 'name', 'phone'])
            ->toArray();
    }

    public function updatedSearchSopladosWeeklyQuery()
    {
        if (strlen($this->searchSopladosWeeklyQuery) < 2) {
            $this->sopladosWeeklyUsersResults = [];
            return;
        }
        $this->sopladosWeeklyUsersResults = \App\Models\User::where('name', 'like', '%' . $this->searchSopladosWeeklyQuery . '%')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->limit(5)
            ->get(['id', 'name', 'phone'])
            ->toArray();
    }

    public function selectUser($userId, $type)
    {
        if ($type === 'rate') {
            if (!in_array($userId, $this->selectedRateUsers)) {
                $this->selectedRateUsers[] = $userId;
            }
            $this->searchRateQuery = '';
            $this->rateUsersResults = [];
        } elseif ($type === 'closure') {
            if (!in_array($userId, $this->selectedClosureUsers)) {
                $this->selectedClosureUsers[] = $userId;
            }
            $this->searchClosureQuery = '';
            $this->closureUsersResults = [];
        } elseif ($type === 'weekly_report') {
            if (!in_array($userId, $this->selectedWeeklyReportUsers)) {
                $this->selectedWeeklyReportUsers[] = $userId;
            }
            $this->searchWeeklyReportQuery = '';
            $this->weeklyReportUsersResults = [];
        } elseif ($type === 'soplados_shift') {
            if (!in_array($userId, $this->selectedSopladosShiftUsers)) {
                $this->selectedSopladosShiftUsers[] = $userId;
            }
            $this->searchSopladosShiftQuery = '';
            $this->sopladosShiftUsersResults = [];
        } elseif ($type === 'soplados_weekly') {
            if (!in_array($userId, $this->selectedSopladosWeeklyUsers)) {
                $this->selectedSopladosWeeklyUsers[] = $userId;
            }
            $this->searchSopladosWeeklyQuery = '';
            $this->sopladosWeeklyUsersResults = [];
        }
    }

    public function removeUser($userId, $type)
    {
        if ($type === 'rate') {
            $this->selectedRateUsers = array_values(array_diff($this->selectedRateUsers, [$userId]));
        } elseif ($type === 'closure') {
            $this->selectedClosureUsers = array_values(array_diff($this->selectedClosureUsers, [$userId]));
        } elseif ($type === 'weekly_report') {
            $this->selectedWeeklyReportUsers = array_values(array_diff($this->selectedWeeklyReportUsers, [$userId]));
        } elseif ($type === 'soplados_shift') {
            $this->selectedSopladosShiftUsers = array_values(array_diff($this->selectedSopladosShiftUsers, [$userId]));
        } elseif ($type === 'soplados_weekly') {
            $this->selectedSopladosWeeklyUsers = array_values(array_diff($this->selectedSopladosWeeklyUsers, [$userId]));
        }
    }

    public function render()
    {
        $rateUsers = \App\Models\User::whereIn('id', $this->selectedRateUsers)->get(['id', 'name', 'phone']);
        $closureUsers = \App\Models\User::whereIn('id', $this->selectedClosureUsers)->get(['id', 'name', 'phone']);
        $weeklyUsers = \App\Models\User::whereIn('id', $this->selectedWeeklyReportUsers)->get(['id', 'name', 'phone']);
        $sopladosShiftUsers = \App\Models\User::whereIn('id', $this->selectedSopladosShiftUsers)->get(['id', 'name', 'phone']);
        $sopladosWeeklyUsers = \App\Models\User::whereIn('id', $this->selectedSopladosWeeklyUsers)->get(['id', 'name', 'phone']);

        return view('livewire.settings.whatsapp-settings', [
            'selectedRateUsersList' => $rateUsers,
            'selectedClosureUsersList' => $closureUsers,
            'selectedWeeklyUsersList' => $weeklyUsers,
            'selectedSopladosShiftUsersList' => $sopladosShiftUsers,
            'selectedSopladosWeeklyUsersList' => $sopladosWeeklyUsers,
        ]);
    }
}
