<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Configuration extends Model
{
    use HasFactory;

    protected $table = 'configurations';

    protected $fillable = [
        'plan_type',
        'addon_modules',
        'business_name',
        'address',
        'city',
        'phone',
        'taxpayer_id',
        'vat',
        'decimals',
        'printer_name',
        'leyend',
        'global_commission_1_threshold',
        'global_commission_1_percentage',
        'global_commission_2_threshold',
        'global_commission_2_percentage',
        'website',
        'auto_select_default_customer',
        'credit_days',
        'credit_purchase_days',
        'confirmation_code',
        'invoice_sequence',
        'order_sequence',
        'global_commission_1_threshold',
        'global_commission_1_percentage',
        'global_commission_2_threshold',
        'global_commission_2_threshold',
        'global_commission_2_percentage',
        'logo',
        'default_warehouse_id',
        'check_stock_reservation',
        'backup_emails',
        'purchasing_calculation_mode',
        'purchasing_coverage_days',
        'production_email_recipients',
        'bags_admin_email_recipients',
        'production_email_subject',
        'production_email_body',
        'soplados_email_recipients',
        'soplados_email_subject',
        'soplados_email_body',
        'is_network',
        'printer_user',
        'printer_user',
        'printer_password',
        'global_allow_credit',
        'global_credit_days',
        'global_credit_limit',
        'global_usd_payment_discount',
        'global_usd_payment_discount_tag',
        'license_notification_email',
        'license_request_email',
        'license_request_phone',
        'bcv_rate',
        'binance_rate',
        'binance_markup_points',
        'sales_view_mode',
        'enable_shared_cash_register',
        'price_list_show_info_block',
        'catalogue_show_prices',
        'catalogue_show_base_prices',
        'sales_edit_timeout',
        'soplados_warehouse_id',
        'bolsas_warehouse_id',
        'production_materials_warehouse_id',
        'sequential_cut_off_date',
        'whatsapp_rate_groups',
        'whatsapp_closure_groups',
        'whatsapp_weekly_report_groups',
        'price_list_decimals',
        'email_rate_recipients',
        'email_closure_recipients',
        'email_weekly_report_recipients',
        'whatsapp_rate_users',
        'whatsapp_closure_users',
        'whatsapp_weekly_report_users',
        'whatsapp_soplados_shift_groups',
        'whatsapp_soplados_shift_users',
        'whatsapp_soplados_weekly_groups',
        'whatsapp_soplados_weekly_users',
        'whatsapp_bags_shift_groups',
        'whatsapp_bags_shift_users',
        'whatsapp_bags_admin_groups',
        'whatsapp_bags_admin_users',
        'email_soplados_weekly_recipients',
        'weekly_report_send_day',
        'weekly_report_send_hour',
        'email_credit_auth_recipients',
        'whatsapp_credit_auth_users',
        'treasury_cutoff_hour',
        'treasury_auto_close',
        'sales_show_rate_badge',
        'sales_show_commissions',
        'sales_show_freight',
        'sales_show_breakdown_freight',
        'sales_show_warehouse',
        'sales_show_driver',
    ];

    protected $casts = [
        'addon_modules' => 'array',
        'local_overrides' => 'array',
        'backup_emails' => 'array',
        'production_email_recipients' => 'array',
        'bags_admin_email_recipients' => 'array',
        'soplados_email_recipients' => 'array',
        'is_network' => 'boolean',
        'enable_shared_cash_register' => 'boolean',
        'price_list_show_info_block' => 'boolean',
        'catalogue_show_prices' => 'boolean',
        'catalogue_show_base_prices' => 'boolean',
        'whatsapp_rate_groups' => 'array',
        'whatsapp_closure_groups' => 'array',
        'whatsapp_weekly_report_groups' => 'array',
        'price_list_decimals' => 'integer',
        'email_rate_recipients' => 'array',
        'email_closure_recipients' => 'array',
        'email_weekly_report_recipients' => 'array',
        'whatsapp_rate_users' => 'array',
        'whatsapp_closure_users' => 'array',
        'whatsapp_weekly_report_users' => 'array',
        'whatsapp_soplados_shift_groups' => 'array',
        'whatsapp_soplados_shift_users' => 'array',
        'whatsapp_soplados_weekly_groups' => 'array',
        'whatsapp_soplados_weekly_users' => 'array',
        'whatsapp_bags_shift_groups' => 'array',
        'whatsapp_bags_shift_users' => 'array',
        'whatsapp_bags_admin_groups' => 'array',
        'whatsapp_bags_admin_users' => 'array',
        'email_soplados_weekly_recipients' => 'array',
        'email_credit_auth_recipients' => 'array',
        'whatsapp_credit_auth_users' => 'array',
        'weekly_report_send_day' => 'integer',
        'treasury_auto_close' => 'boolean',
        'sales_show_rate_badge' => 'boolean',
        'sales_show_commissions' => 'boolean',
        'sales_show_freight' => 'boolean',
        'sales_show_breakdown_freight' => 'boolean',
        'sales_show_warehouse' => 'boolean',
        'sales_show_driver' => 'boolean',
    ];

    public function defaultWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'default_warehouse_id');
    }

    /**
     * Comprueba si la suscripción actual cumple o supera el plan requerido.
     * Ejemplo: si el plan requerido es 'pro', y la empresa tiene 'premium', retorna true.
     */
    public function hasPlan($plan)
    {
        $hierarchy = ['basic' => 1, 'pro' => 2, 'premium' => 3];
        $currentPlanLevel = $hierarchy[$this->plan_type ?? 'premium'] ?? 3;
        $requiredPlanLevel = $hierarchy[$plan] ?? 1;
        
        return $currentPlanLevel >= $requiredPlanLevel;
    }

    /**
     * Comprueba si un módulo adicional a la carta está activo.
     * Evalúa primero local_overrides.
     */
    public function hasAddon($addon)
    {
        // 1. Verificar si hay un override local que force habilitar o deshabilitar
        if (!empty($this->local_overrides) && isset($this->local_overrides[$addon])) {
            return (bool) $this->local_overrides[$addon];
        }

        // 2. Si no hay override, validar lo que diga la licencia
        // Los modulos estan dentro de la data de la licencia, no en addon_modules de DB
        $licenseModules = config('tenant.modules');
        if ($licenseModules === null) {
            // Si por alguna razón no pasó por el middleware, lo leemos directo
            $status = app(\App\Services\LicenseService::class)->checkLicense();
            $licenseModules = $status['modules'] ?? [];
        }
        
        return in_array($addon, $licenseModules);
    }
}
