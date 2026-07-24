<?php

namespace App\Livewire\Settings;

use Livewire\Component;

class LicenseGenerator extends Component
{
    public $clientId;
    public $days = 30;
    public $maxDevices = 1;
    public $selectedModules = [];
    public $selectedPlan = 'BÁSICO';
    public $generatedKey = '';

    public $availableModules = [
        'module_credits' => 'Créditos y Cuentas por Cobrar',
        'module_purchases' => 'Compras a Proveedores',
        'module_multi_warehouse' => 'Múltiples Depósitos y Traspasos',
        'module_advanced_payments' => 'Pagos en Divisas y Zelle',
        'module_advanced_products' => 'Productos Variables y Tallas',
        'module_labels' => 'Etiquetas de Código de Barras',
        'module_roles' => 'Control Granular de Roles',
        'module_whatsapp' => 'Integración WhatsApp API',
        'module_commissions' => 'Comisiones a Vendedores',
        'module_production' => 'Manufactura y Producción',
        'module_soplados' => 'Producción de Soplados',
        'module_bolsas' => 'Fábrica de Bolsas',
        'module_delivery' => 'Despacho y Mapa de Rutas',
        'module_updates' => 'Actualizaciones del Sistema',
        'module_backups' => 'Copias de Seguridad (Backups)',

        // Reportes y Analíticas (Granulares)
        'module_strategic_analysis' => 'Análisis Estratégico',
        'module_weekly_income' => 'Reporte Semanal de Ingresos',
        'module_monthly_income' => 'Reporte Mensual de Ingresos',
        'module_customer_report' => 'Reporte de Clientes',
        'module_customer_activity' => 'Actividad de Clientes',
        'module_sales_analysis' => 'Análisis de Ventas',
        'module_seller_performance' => 'Desempeño de Vendedores',
        'module_seller_grouped' => 'Reporte Agrupado por Vendedor',
        'module_operator_efficiency' => 'Eficiencia de Operadores',
        'module_differential_audit' => 'Auditoría de Diferencial',
        'module_cash_flow' => 'Flujo y Cobranza',
        'module_collection_audit' => 'Auditoría de Cobranza',
        'module_invoice_audit' => 'Auditoría de Facturas',
        'module_credit_auth_history' => 'Historial Auth Créditos',
        
        // Módulos Especiales de Catálogo y Servicios
        'module_departments' => 'Departamentos de Productos',
        'module_services' => 'Servicios y Precios Variables',
        
        // Optimizaciones
        'module_pos_optimizations' => 'Optimizaciones del POS (Caja, Cliente Default, Impresión en 2do Plano)'
    ];

    public function setPreset($plan)
    {
        if ($plan === 'PRO') {
            $this->selectedModules = ['module_credits', 'module_purchases', 'module_advanced_payments', 'module_multi_warehouse', 'module_advanced_products', 'module_labels', 'module_roles', 'module_departments', 'module_seller_grouped', 'module_services', 'module_pos_optimizations'];
            $this->maxDevices = 5;
            $this->selectedPlan = 'PRO';
        } elseif ($plan === 'PREMIUM') {
            $this->selectedModules = array_keys($this->availableModules);
            $this->maxDevices = 999;
            $this->selectedPlan = 'PREMIUM';
        } else {
            $this->selectedModules = [];
            $this->maxDevices = 1;
            $this->selectedPlan = 'BÁSICO';
        }
    }

    public function generate()
    {
        $this->validate([
            'clientId' => 'required|string|min:5',
            'days' => 'required|numeric|min:1',
            'maxDevices' => 'required|numeric|min:1'
        ]);

        $add = implode(',', $this->selectedModules);

        // Run the artisan command silently and grab output
        $exitCode = \Illuminate\Support\Facades\Artisan::call('license:generate', [
            'client_id' => $this->clientId,
            'days' => $this->days,
            '--plan' => $this->selectedPlan, // We dynamically pass the current logical plan
            '--add' => $add,
            '--devices' => $this->maxDevices
        ]);

        $output = \Illuminate\Support\Facades\Artisan::output();
        
        if ($exitCode === 0) {
            $lines = explode("\n", trim($output));
            $this->generatedKey = end($lines);
            $this->dispatch('noty', msg: 'Licencia SaaS generada exitosamente');
        } else {
            $this->dispatch('msg-error', msg: 'Error de jerarquía. Revisa la consola o las dependencias.');
            $this->generatedKey = "ERROR: \n" . $output;
        }
    }

    public function render()
    {
        return view('livewire.settings.license-generator')
            ->extends('layouts.theme.app')
            ->section('content');
    }
}
