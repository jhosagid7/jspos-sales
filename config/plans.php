<?php

/**
 * Configuración centralizada de Planes SaaS y Módulos.
 * Fuente única de verdad para CLI (GenerateLicense), UI (LicenseGenerator) y middleware.
 *
 * Cada plan define sus módulos por defecto, pero cualquier módulo puede
 * agregarse o quitarse como add-on individual a cualquier plan.
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Plan Tiers (Jerarquía de Planes)
    |--------------------------------------------------------------------------
    */
    'tiers' => [
        'basic' => [
            'label' => 'Básico',
            'level' => 1,
            'max_devices' => 1,
            'modules' => [
                // Núcleo POS: ventas contado, stock básico, clientes, caja, impresión
                // No incluye módulos adicionales por defecto
            ],
        ],
        'pro' => [
            'label' => 'Pro',
            'level' => 2,
            'max_devices' => 5,
            'modules' => [
                'module_credits',
                'module_purchases',
                'module_advanced_payments',
                'module_multi_warehouse',
                'module_advanced_products',
                'module_labels',
                'module_roles',
                'module_advanced_reports',
                'module_departments',
                'module_services',
                'module_pos_optimizations',
                'module_seller_grouped',
            ],
        ],
        'premium' => [
            'label' => 'Premium',
            'level' => 3,
            'max_devices' => 999,
            'modules' => 'all', // Todos los módulos disponibles
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Dependencias entre Módulos
    |--------------------------------------------------------------------------
    | Al agregar un módulo, sus dependencias se incluyen automáticamente.
    | Al quitar un módulo, se advierte si otro módulo depende de él.
    */
    'dependencies' => [
        'module_production'         => ['module_multi_warehouse'],
        'module_soplados'           => ['module_production', 'module_multi_warehouse'],
        'module_bolsas'             => ['module_production', 'module_multi_warehouse'],
        'module_seller_performance' => ['module_commissions'],
        'module_seller_grouped'     => ['module_commissions'],
        'module_collection_audit'   => ['module_credits'],
        'module_credit_auth_history' => ['module_credits'],
        'module_cash_flow'          => ['module_credits'],
        'module_differential_audit' => ['module_commissions'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Catálogo Completo de Módulos Disponibles
    |--------------------------------------------------------------------------
    | Cualquier módulo puede agregarse como add-on individual a cualquier plan.
    */
    'available_modules' => [
        // ── Módulos Core ──────────────────────────────────────────────
        'module_credits'            => 'Créditos y Cuentas por Cobrar',
        'module_purchases'          => 'Compras a Proveedores',
        'module_multi_warehouse'    => 'Múltiples Depósitos y Traspasos',
        'module_advanced_payments'  => 'Pagos en Divisas y Zelle',
        'module_advanced_products'  => 'Productos Variables y Tallas',
        'module_labels'             => 'Etiquetas de Código de Barras',
        'module_roles'              => 'Control Granular de Roles',
        'module_departments'        => 'Departamentos de Productos',
        'module_services'           => 'Servicios y Precios Variables',
        'module_pos_optimizations'  => 'Optimizaciones del POS (Caja, Cliente Default, Impresión en 2do Plano)',

        // ── Módulos Premium ──────────────────────────────────────────
        'module_commissions'        => 'Comisiones a Vendedores',
        'module_whatsapp'           => 'Integración WhatsApp API',
        'module_production'         => 'Manufactura y Producción',
        'module_soplados'           => 'Producción de Soplados',
        'module_bolsas'             => 'Fábrica de Bolsas',
        'module_delivery'           => 'Despacho y Mapa de Rutas',
        'module_updates'            => 'Actualizaciones del Sistema',
        'module_backups'            => 'Copias de Seguridad (Backups)',
        'module_treasury'           => 'Tesorería Bancaria',

        // ── Reportes y Analíticas (Add-ons individuales) ─────────────
        'module_advanced_reports'       => 'Reportes Avanzados (General)',
        'module_strategic_analysis'     => 'Análisis Estratégico',
        'module_weekly_income'          => 'Reporte Semanal de Ingresos',
        'module_monthly_income'         => 'Reporte Mensual de Ingresos',
        'module_customer_report'        => 'Reporte de Clientes',
        'module_customer_activity'      => 'Actividad de Clientes',
        'module_sales_analysis'         => 'Análisis de Ventas',
        'module_seller_performance'     => 'Desempeño de Vendedores',
        'module_seller_grouped'         => 'Reporte Agrupado por Vendedor',
        'module_operator_efficiency'    => 'Eficiencia de Operadores',
        'module_differential_audit'     => 'Auditoría de Diferencial',
        'module_cash_flow'              => 'Flujo y Cobranza',
        'module_collection_audit'       => 'Auditoría de Cobranza',
        'module_invoice_audit'          => 'Auditoría de Facturas',
        'module_credit_auth_history'    => 'Historial Auth Créditos',
    ],
];
