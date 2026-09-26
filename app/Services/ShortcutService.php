<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

class ShortcutService
{
    /**
     * Catálogo completo de accesos directos disponibles en el sistema correspondientes
     * a todos los módulos y submenús del menú lateral (sidebar).
     */
    public static function getCatalog(): array
    {
        return [
            // ==========================================
            // 1. GESTIÓN COMERCIAL
            // ==========================================
            'welcome' => [
                'key' => 'welcome',
                'label' => 'Dashboard',
                'short_label' => 'Dashboard',
                'icon' => 'fas fa-tachometer-alt',
                'route' => 'welcome',
                'permission' => null,
                'category' => 'Gestión Comercial',
                'color' => '#007bff',
            ],
            'sales' => [
                'key' => 'sales',
                'label' => 'Ventas (POS)',
                'short_label' => 'Ventas POS',
                'icon' => 'fas fa-cash-register',
                'route' => 'sales',
                'permission' => 'sales.index',
                'category' => 'Gestión Comercial',
                'color' => '#28a745',
            ],
            'credit.authorizations' => [
                'key' => 'credit.authorizations',
                'label' => 'Historial Auth. Crédito',
                'short_label' => 'Auth. Crédito',
                'icon' => 'fas fa-user-check',
                'route' => 'credit.authorizations',
                'permission' => 'sales.index',
                'module' => 'module_credits',
                'category' => 'Gestión Comercial',
                'color' => '#ffc107',
            ],
            'purchases' => [
                'key' => 'purchases',
                'label' => 'Nueva Compra',
                'short_label' => 'Nueva Compra',
                'icon' => 'fas fa-cart-plus',
                'route' => 'purchases',
                'permission' => 'purchases.create',
                'module' => 'module_purchases',
                'category' => 'Gestión Comercial',
                'color' => '#17a2b8',
            ],
            'purchase.list' => [
                'key' => 'purchase.list',
                'label' => 'Historial de Compras',
                'short_label' => 'Hist. Compras',
                'icon' => 'fas fa-receipt',
                'route' => 'purchase.list',
                'permission' => 'purchases.index',
                'module' => 'module_purchases',
                'category' => 'Gestión Comercial',
                'color' => '#6c757d',
            ],
            'price-list.index' => [
                'key' => 'price-list.index',
                'label' => 'Lista de Precios',
                'short_label' => 'Lista Precios',
                'icon' => 'fas fa-list-alt',
                'route' => 'price-list.index',
                'permission' => 'sales.generate_price_list',
                'category' => 'Gestión Comercial',
                'color' => '#fd7e14',
            ],
            'commissions' => [
                'key' => 'commissions',
                'label' => 'Comisiones',
                'short_label' => 'Comisiones',
                'icon' => 'fas fa-percentage',
                'route' => 'commissions',
                'permission' => 'reports.commissions',
                'module' => 'module_commissions',
                'category' => 'Gestión Comercial',
                'color' => '#20c997',
            ],

            // ==========================================
            // 2. LOGÍSTICA Y DESPACHO
            // ==========================================
            'driver.dashboard' => [
                'key' => 'driver.dashboard',
                'label' => 'Logística / Rutas',
                'short_label' => 'Rutas Despacho',
                'icon' => 'fas fa-route',
                'route' => 'driver.dashboard',
                'permission' => 'sales.index',
                'module' => 'module_delivery',
                'category' => 'Logística y Despacho',
                'color' => '#17a2b8',
            ],
            'delivery.map' => [
                'key' => 'delivery.map',
                'label' => 'Mapa de Choferes',
                'short_label' => 'Mapa Choferes',
                'icon' => 'fas fa-map-marked-alt',
                'route' => 'delivery.map',
                'permission' => 'distribution.map',
                'module' => 'module_delivery',
                'category' => 'Logística y Despacho',
                'color' => '#28a745',
            ],
            'reports.dispatch' => [
                'key' => 'reports.dispatch',
                'label' => 'Relación de Despacho',
                'short_label' => 'Rel. Despacho',
                'icon' => 'fas fa-truck',
                'route' => 'reports.dispatch',
                'permission' => 'reports.sales',
                'module' => 'module_delivery',
                'category' => 'Logística y Despacho',
                'color' => '#ffc107',
            ],

            // ==========================================
            // 3. INVENTARIO & STOCK
            // ==========================================
            'products' => [
                'key' => 'products',
                'label' => 'Listado Maestro de Productos',
                'short_label' => 'Productos',
                'icon' => 'fas fa-barcode',
                'route' => 'products',
                'permission' => 'products.index',
                'category' => 'Inventario & Stock',
                'color' => '#0d6efd',
            ],
            'price-groups' => [
                'key' => 'price-groups',
                'label' => 'Grupos de Precio',
                'short_label' => 'Grupos Precio',
                'icon' => 'fas fa-tags',
                'route' => 'price-groups',
                'permission' => 'products.index',
                'category' => 'Inventario & Stock',
                'color' => '#6610f2',
            ],
            'inventories' => [
                'key' => 'inventories',
                'label' => 'Stock General',
                'short_label' => 'Inventario',
                'icon' => 'fas fa-boxes',
                'route' => 'inventories',
                'permission' => 'inventory.index',
                'category' => 'Inventario & Stock',
                'color' => '#ffc107',
            ],
            'cargos' => [
                'key' => 'cargos',
                'label' => 'Entradas (Ajuste)',
                'short_label' => 'Cargos Stock',
                'icon' => 'fas fa-plus-circle',
                'route' => 'cargos',
                'permission' => 'adjustments.create',
                'category' => 'Inventario & Stock',
                'color' => '#28a745',
            ],
            'descargos' => [
                'key' => 'descargos',
                'label' => 'Salidas (Ajuste)',
                'short_label' => 'Descargos',
                'icon' => 'fas fa-minus-circle',
                'route' => 'descargos',
                'permission' => 'adjustments.create',
                'category' => 'Inventario & Stock',
                'color' => '#dc3545',
            ],
            'transfers' => [
                'key' => 'transfers',
                'label' => 'Traspasos entre Almacenes',
                'short_label' => 'Traspasos',
                'icon' => 'fas fa-exchange-alt',
                'route' => 'transfers',
                'permission' => 'transfers.create',
                'module' => 'module_multi_warehouse',
                'category' => 'Inventario & Stock',
                'color' => '#fd7e14',
            ],
            'requisition' => [
                'key' => 'requisition',
                'label' => 'Requisiciones',
                'short_label' => 'Requisiciones',
                'icon' => 'fas fa-clipboard-list',
                'route' => 'requisition',
                'permission' => 'transfers.create',
                'module' => 'module_multi_warehouse',
                'category' => 'Inventario & Stock',
                'color' => '#6f42c1',
            ],
            'labels.index' => [
                'key' => 'labels.index',
                'label' => 'Generador de Etiquetas',
                'short_label' => 'Etiquetas',
                'icon' => 'fas fa-qrcode',
                'route' => 'labels.index',
                'permission' => 'products.labels',
                'module' => 'module_labels',
                'category' => 'Inventario & Stock',
                'color' => '#20c997',
            ],

            // ==========================================
            // 4. FINANZAS, CAJA & COBRANZA
            // ==========================================
            'cash-register.close' => [
                'key' => 'cash-register.close',
                'label' => 'Cerrar Caja',
                'short_label' => 'Cerrar Caja',
                'icon' => 'fas fa-cash-register',
                'route' => 'cash-register.close',
                'permission' => 'cash_register.close',
                'category' => 'Finanzas & Caja',
                'color' => '#28a745',
            ],
            'credit.auth.history' => [
                'key' => 'credit.auth.history',
                'label' => 'Historial Auth. Crédito (Finanzas)',
                'short_label' => 'Hist. Crédito',
                'icon' => 'fas fa-history',
                'route' => 'credit.auth.history',
                'permission' => 'cash_register.close',
                'module' => 'module_credit_auth_history',
                'category' => 'Finanzas & Caja',
                'color' => '#ffc107',
            ],
            'cash.count' => [
                'key' => 'cash.count',
                'label' => 'Historial de Arqueos',
                'short_label' => 'Arqueos',
                'icon' => 'fas fa-coins',
                'route' => 'cash.count',
                'permission' => 'cash_register.close',
                'category' => 'Finanzas & Caja',
                'color' => '#fd7e14',
            ],
            'reports.accounts.receivable' => [
                'key' => 'reports.accounts.receivable',
                'label' => 'Cuentas por Cobrar',
                'short_label' => 'Por Cobrar',
                'icon' => 'fas fa-hand-holding-usd',
                'route' => 'reports.accounts.receivable',
                'permission' => 'reports.financial',
                'module' => 'module_credits',
                'category' => 'Finanzas & Caja',
                'color' => '#e83e8c',
            ],
            'customer-statement' => [
                'key' => 'customer-statement',
                'label' => 'Estado de Cuenta Clientes',
                'short_label' => 'Edo. Cuenta',
                'icon' => 'fas fa-file-invoice',
                'route' => 'customer-statement',
                'permission' => 'customer_statement.index',
                'module' => 'module_credits',
                'category' => 'Finanzas & Caja',
                'color' => '#17a2b8',
            ],
            'reports.returns' => [
                'key' => 'reports.returns',
                'label' => 'Notas de Crédito / Devoluciones',
                'short_label' => 'Notas Crédito',
                'icon' => 'fas fa-undo-alt',
                'route' => 'reports.returns',
                'permission' => 'reports.sales',
                'category' => 'Finanzas & Caja',
                'color' => '#dc3545',
            ],
            'pos.debit-notes' => [
                'key' => 'pos.debit-notes',
                'label' => 'Notas de Débito',
                'short_label' => 'Notas Débito',
                'icon' => 'fas fa-file-signature',
                'route' => 'pos.debit-notes',
                'permission' => 'manage_debit_notes',
                'module' => 'module_credits',
                'category' => 'Finanzas & Caja',
                'color' => '#6f42c1',
            ],
            'reports.accounts.payables' => [
                'key' => 'reports.accounts.payables',
                'label' => 'Cuentas por Pagar',
                'short_label' => 'Por Pagar',
                'icon' => 'fas fa-money-bill-wave',
                'route' => 'reports.accounts.payables',
                'permission' => 'reports.financial',
                'module' => 'module_purchases',
                'category' => 'Finanzas & Caja',
                'color' => '#e83e8c',
            ],
            'consultation.zelle' => [
                'key' => 'consultation.zelle',
                'label' => 'Auditoría Pagos Zelle',
                'short_label' => 'Zelle',
                'icon' => 'fas fa-dollar-sign',
                'route' => 'consultation.zelle',
                'permission' => 'zelle_index',
                'module' => 'module_advanced_payments',
                'category' => 'Finanzas & Caja',
                'color' => '#6f42c1',
            ],
            'consultation.usdt' => [
                'key' => 'consultation.usdt',
                'label' => 'Auditoría Pagos USDT ($)',
                'short_label' => 'USDT',
                'icon' => 'fas fa-coins',
                'route' => 'consultation.usdt',
                'permission' => 'zelle_index',
                'module' => 'module_usdt',
                'category' => 'Finanzas & Caja',
                'color' => '#28a745',
            ],
            'consultation.bank' => [
                'key' => 'consultation.bank',
                'label' => 'Auditoría Pagos Bancarios',
                'short_label' => 'Pagos Banco',
                'icon' => 'fas fa-university',
                'route' => 'consultation.bank',
                'permission' => 'bank_index',
                'module' => 'module_advanced_payments',
                'category' => 'Finanzas & Caja',
                'color' => '#007bff',
            ],
            'consultation.approvals' => [
                'key' => 'consultation.approvals',
                'label' => 'Aprobación de Tasas',
                'short_label' => 'Aprob. Tasas',
                'icon' => 'fas fa-check-double',
                'route' => 'consultation.approvals',
                'permission' => 'payments.approve_custom_rate',
                'module' => 'module_advanced_payments',
                'category' => 'Finanzas & Caja',
                'color' => '#20c997',
            ],
            'treasury.dashboard' => [
                'key' => 'treasury.dashboard',
                'label' => 'Tesorería y Bancos',
                'short_label' => 'Tesorería',
                'icon' => 'fas fa-vault',
                'route' => 'treasury.dashboard',
                'permission' => 'treasury.index',
                'module' => 'module_treasury',
                'category' => 'Finanzas & Caja',
                'color' => '#ffc107',
            ],

            // ==========================================
            // 5. REGISTROS MAESTROS
            // ==========================================
            'customers' => [
                'key' => 'customers',
                'label' => 'Clientes',
                'short_label' => 'Clientes',
                'icon' => 'fas fa-users',
                'route' => 'customers',
                'permission' => 'customers.index',
                'category' => 'Registros Maestros',
                'color' => '#fd7e14',
            ],
            'suppliers' => [
                'key' => 'suppliers',
                'label' => 'Proveedores',
                'short_label' => 'Proveedores',
                'icon' => 'fas fa-handshake',
                'route' => 'suppliers',
                'permission' => 'suppliers.index',
                'category' => 'Registros Maestros',
                'color' => '#17a2b8',
            ],
            'categories' => [
                'key' => 'categories',
                'label' => 'Categorías',
                'short_label' => 'Categorías',
                'icon' => 'fas fa-tags',
                'route' => 'categories',
                'permission' => 'categories.index',
                'category' => 'Registros Maestros',
                'color' => '#6610f2',
            ],
            'warehouses' => [
                'key' => 'warehouses',
                'label' => 'Almacenes / Depósitos',
                'short_label' => 'Almacenes',
                'icon' => 'fas fa-warehouse',
                'route' => 'warehouses',
                'permission' => 'inventory.index',
                'module' => 'module_multi_warehouse',
                'category' => 'Registros Maestros',
                'color' => '#28a745',
            ],

            // ==========================================
            // 6. REPORTES - VENTAS Y COBROS
            // ==========================================
            'reports.sales' => [
                'key' => 'reports.sales',
                'label' => 'Reporte General de Ventas',
                'short_label' => 'Rep. Ventas',
                'icon' => 'fas fa-file-invoice-dollar',
                'route' => 'reports.sales',
                'permission' => 'reports.sales',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#007bff',
            ],
            'reports.daily.sales' => [
                'key' => 'reports.daily.sales',
                'label' => 'Ventas Diarias',
                'short_label' => 'Ventas Diarias',
                'icon' => 'fas fa-calendar-day',
                'route' => 'reports.daily.sales',
                'permission' => 'reports.sales',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#17a2b8',
            ],
            'reports.strategic' => [
                'key' => 'reports.strategic',
                'label' => 'Análisis Estratégico',
                'short_label' => 'Análisis Estrat.',
                'icon' => 'fas fa-chart-pie',
                'route' => 'reports.strategic',
                'permission' => 'reports.sales',
                'module' => 'module_strategic_analysis',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#6f42c1',
            ],
            'reports.partner.sales' => [
                'key' => 'reports.partner.sales',
                'label' => 'Ventas por Socio (FIFO)',
                'short_label' => 'Ventas Socios',
                'icon' => 'fas fa-handshake',
                'route' => 'reports.partner.sales',
                'permission' => 'reports.sales',
                'module' => 'module_partner_sales',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#fd7e14',
            ],
            'reports.payment.relationship' => [
                'key' => 'reports.payment.relationship',
                'label' => 'Relación de Cobros',
                'short_label' => 'Cobros',
                'icon' => 'fas fa-money-check-alt',
                'route' => 'reports.payment.relationship',
                'permission' => 'reports.sales',
                'module' => 'module_credits',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#20c997',
            ],
            'reports.weekly.income' => [
                'key' => 'reports.weekly.income',
                'label' => 'Reporte Semanal de Ingresos',
                'short_label' => 'Ingresos Sem.',
                'icon' => 'fas fa-calendar-week',
                'route' => 'reports.weekly.income',
                'permission' => 'reports.sales',
                'module' => 'module_weekly_income',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#28a745',
            ],
            'reports.monthly.income' => [
                'key' => 'reports.monthly.income',
                'label' => 'Reporte Mensual de Ingresos',
                'short_label' => 'Ingresos Mens.',
                'icon' => 'fas fa-calendar-alt',
                'route' => 'reports.monthly.income',
                'permission' => 'reports.sales',
                'module' => 'module_monthly_income',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#17a2b8',
            ],
            'reports.customers' => [
                'key' => 'reports.customers',
                'label' => 'Reporte de Clientes',
                'short_label' => 'Rep. Clientes',
                'icon' => 'fas fa-user-friends',
                'route' => 'reports.customers',
                'permission' => 'reports.sales',
                'module' => 'module_customer_report',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#0d6efd',
            ],
            'reports.customer.activity' => [
                'key' => 'reports.customer.activity',
                'label' => 'Actividad de Clientes',
                'short_label' => 'Actividad Clientes',
                'icon' => 'fas fa-user-clock',
                'route' => 'reports.customer.activity',
                'permission' => 'reports.sales',
                'module' => 'module_customer_activity',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#ffc107',
            ],
            'reports.sales.analysis' => [
                'key' => 'reports.sales.analysis',
                'label' => 'Análisis de Ventas',
                'short_label' => 'Análisis Ventas',
                'icon' => 'fas fa-chart-line',
                'route' => 'reports.sales.analysis',
                'permission' => 'reports.sales',
                'module' => 'module_sales_analysis',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#6f42c1',
            ],
            'reports.sellers.performance' => [
                'key' => 'reports.sellers.performance',
                'label' => 'Desempeño de Vendedores',
                'short_label' => 'Vendedores',
                'icon' => 'fas fa-user-tie',
                'route' => 'reports.sellers.performance',
                'permission' => 'reports.sales',
                'module' => 'module_seller_performance',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#fd7e14',
            ],
            'reports.goal.commissions' => [
                'key' => 'reports.goal.commissions',
                'label' => 'Comisiones por Metas',
                'short_label' => 'Comis. Metas',
                'icon' => 'fas fa-bullseye',
                'route' => 'reports.goal.commissions',
                'permission' => 'reports.sales',
                'module' => 'module_commissions',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#e83e8c',
            ],
            'reports.seller_grouped' => [
                'key' => 'reports.seller_grouped',
                'label' => 'Cobranza por Operador',
                'short_label' => 'Cobranza Operador',
                'icon' => 'fas fa-users-cog',
                'route' => 'reports.seller_grouped',
                'permission' => 'reports.sales',
                'module' => 'module_seller_grouped',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#20c997',
            ],
            'reports.operators.precision' => [
                'key' => 'reports.operators.precision',
                'label' => 'Eficiencia de Operadores',
                'short_label' => 'Eficiencia Oper.',
                'icon' => 'fas fa-tachometer-alt',
                'route' => 'reports.operators.precision',
                'permission' => 'reports.sales',
                'module' => 'module_operator_efficiency',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#17a2b8',
            ],
            'reports.exchange.diff' => [
                'key' => 'reports.exchange.diff',
                'label' => 'Auditoría de Diferencial',
                'short_label' => 'Dif. Cambiario',
                'icon' => 'fas fa-balance-scale',
                'route' => 'reports.exchange.diff',
                'permission' => 'reports.sales',
                'module' => 'module_differential_audit',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#fd7e14',
            ],
            'reports.cash.flow.forecast' => [
                'key' => 'reports.cash.flow.forecast',
                'label' => 'Flujo y Cobranza',
                'short_label' => 'Flujo Caja',
                'icon' => 'fas fa-chart-area',
                'route' => 'reports.cash.flow.forecast',
                'permission' => 'reports.sales',
                'module' => 'module_cash_flow',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#28a745',
            ],
            'reports.customer.payment.relationship' => [
                'key' => 'reports.customer.payment.relationship',
                'label' => 'Cobros por Cliente',
                'short_label' => 'Cobros Cliente',
                'icon' => 'fas fa-hand-holding-usd',
                'route' => 'reports.customer.payment.relationship',
                'permission' => 'reports.customer_payment_relationship',
                'module' => 'module_collection_audit',
                'category' => 'Reportes - Ventas y Cobros',
                'color' => '#007bff',
            ],

            // ==========================================
            // 7. REPORTES - STOCK Y MÉTRICAS
            // ==========================================
            'reports.inventory' => [
                'key' => 'reports.inventory',
                'label' => 'Inventario Actual',
                'short_label' => 'Inv. Actual',
                'icon' => 'fas fa-dolly-flatbed',
                'route' => 'reports.inventory',
                'permission' => 'reports.stock',
                'category' => 'Reportes - Stock y Métricas',
                'color' => '#17a2b8',
            ],
            'reports.movements' => [
                'key' => 'reports.movements',
                'label' => 'Kardex (Movimientos)',
                'short_label' => 'Kardex',
                'icon' => 'fas fa-truck-moving',
                'route' => 'reports.movements',
                'permission' => 'reports.stock',
                'category' => 'Reportes - Stock y Métricas',
                'color' => '#fd7e14',
            ],
            'reports.audit' => [
                'key' => 'reports.audit',
                'label' => 'Auditoría de Stock / Actividad',
                'short_label' => 'Auditoría Stock',
                'icon' => 'fas fa-clipboard-check',
                'route' => 'reports.audit',
                'permission' => 'reports.audit',
                'category' => 'Reportes - Stock y Métricas',
                'color' => '#6c757d',
            ],
            'reports.best.sellers' => [
                'key' => 'reports.best.sellers',
                'label' => 'Productos Más Vendidos',
                'short_label' => 'Más Vendidos',
                'icon' => 'fas fa-award',
                'route' => 'reports.best.sellers',
                'permission' => 'reports.sales',
                'category' => 'Reportes - Stock y Métricas',
                'color' => '#ffc107',
            ],
            'reports.rotation' => [
                'key' => 'reports.rotation',
                'label' => 'Rotación de Stock',
                'short_label' => 'Rotación Stock',
                'icon' => 'fas fa-sync-alt',
                'route' => 'reports.rotation',
                'permission' => 'reports.stock',
                'module' => 'module_advanced_reports',
                'category' => 'Reportes - Stock y Métricas',
                'color' => '#20c997',
            ],

            // ==========================================
            // 8. FÁBRICA & PRODUCCIÓN
            // ==========================================
            'production.report' => [
                'key' => 'production.report',
                'label' => 'Reporte Producción Soplados',
                'short_label' => 'Rep. Soplados',
                'icon' => 'fas fa-industry',
                'route' => 'production.report',
                'permission' => 'production.index',
                'module' => 'module_soplados',
                'category' => 'Fábrica & Producción',
                'color' => '#dc3545',
            ],
            'soplados.formulas' => [
                'key' => 'soplados.formulas',
                'label' => 'Configuración de Recetas (Soplados)',
                'short_label' => 'Recetas Soplado',
                'icon' => 'fas fa-flask',
                'route' => 'soplados.formulas',
                'permission' => 'production.index',
                'module' => 'module_soplados',
                'category' => 'Fábrica & Producción',
                'color' => '#6f42c1',
            ],
            'soplados.shifts' => [
                'key' => 'soplados.shifts',
                'label' => 'Historial de Turnos (Soplados)',
                'short_label' => 'Turnos Soplado',
                'icon' => 'fas fa-user-clock',
                'route' => 'soplados.shifts',
                'permission' => 'production.index',
                'module' => 'module_soplados',
                'category' => 'Fábrica & Producción',
                'color' => '#0d6efd',
            ],
            'soplados.inventories' => [
                'key' => 'soplados.inventories',
                'label' => 'Historial Inventarios Soplados',
                'short_label' => 'Inv. Soplados',
                'icon' => 'fas fa-boxes',
                'route' => 'soplados.inventories',
                'permission' => 'production.index',
                'module' => 'module_soplados',
                'category' => 'Fábrica & Producción',
                'color' => '#ffc107',
            ],
            'soplados.expected-production' => [
                'key' => 'soplados.expected-production',
                'label' => 'Metas de Producción Soplados',
                'short_label' => 'Metas Soplado',
                'icon' => 'fas fa-bullseye',
                'route' => 'soplados.expected-production',
                'permission' => 'production.index',
                'module' => 'module_soplados',
                'category' => 'Fábrica & Producción',
                'color' => '#28a745',
            ],
            'production.index' => [
                'key' => 'production.index',
                'label' => 'Historial Levantamiento Bolsas',
                'short_label' => 'Prod. Bolsas',
                'icon' => 'fas fa-layer-group',
                'route' => 'production.index',
                'permission' => 'production.index',
                'module' => 'module_bolsas',
                'category' => 'Fábrica & Producción',
                'color' => '#6c757d',
            ],

            // ==========================================
            // 9. ADMINISTRACIÓN & SISTEMA
            // ==========================================
            'users' => [
                'key' => 'users',
                'label' => 'Gestión de Usuarios',
                'short_label' => 'Usuarios',
                'icon' => 'fas fa-users-cog',
                'route' => 'users',
                'permission' => 'users.index',
                'category' => 'Administración & Sistema',
                'color' => '#007bff',
            ],
            'roles' => [
                'key' => 'roles',
                'label' => 'Roles y Permisos',
                'short_label' => 'Roles',
                'icon' => 'fas fa-user-shield',
                'route' => 'roles',
                'role' => 'Super Admin',
                'module' => 'module_roles',
                'category' => 'Administración & Sistema',
                'color' => '#6f42c1',
            ],
            'asignar' => [
                'key' => 'asignar',
                'label' => 'Asignación de Permisos',
                'short_label' => 'Asignar Perm.',
                'icon' => 'fas fa-key',
                'route' => 'asignar',
                'role' => 'Super Admin',
                'module' => 'module_roles',
                'category' => 'Administración & Sistema',
                'color' => '#fd7e14',
            ],
            'audit.sheet' => [
                'key' => 'audit.sheet',
                'label' => 'Auditoría de Cobranza',
                'short_label' => 'Audit. Cobranza',
                'icon' => 'fas fa-clipboard-check',
                'route' => 'audit.sheet',
                'permission' => 'collections.audit',
                'module' => 'module_collection_audit',
                'category' => 'Administración & Sistema',
                'color' => '#17a2b8',
            ],
            'audit.invoices' => [
                'key' => 'audit.invoices',
                'label' => 'Auditoría de Facturas',
                'short_label' => 'Audit. Facturas',
                'icon' => 'fas fa-search-dollar',
                'route' => 'audit.invoices',
                'permission' => 'collections.audit',
                'module' => 'module_invoice_audit',
                'category' => 'Administración & Sistema',
                'color' => '#6c757d',
            ],
            'devices' => [
                'key' => 'devices',
                'label' => 'Dispositivos Autorizados',
                'short_label' => 'Dispositivos',
                'icon' => 'fas fa-mobile-alt',
                'route' => 'devices',
                'role' => 'Super Admin',
                'category' => 'Administración & Sistema',
                'color' => '#20c997',
            ],
            'settings.whatsapp' => [
                'key' => 'settings.whatsapp',
                'label' => 'WhatsApp Configuración',
                'short_label' => 'WhatsApp Config',
                'icon' => 'fab fa-whatsapp',
                'route' => 'settings.whatsapp',
                'role' => 'Super Admin',
                'category' => 'Administración & Sistema',
                'color' => '#25d366',
            ],
            'settings.whatsapp_outbox' => [
                'key' => 'settings.whatsapp_outbox',
                'label' => 'Bandeja de Salida WhatsApp',
                'short_label' => 'WhatsApp Salida',
                'icon' => 'fas fa-paper-plane',
                'route' => 'settings.whatsapp_outbox',
                'role' => 'Super Admin',
                'category' => 'Administración & Sistema',
                'color' => '#128c7e',
            ],
            'settings.email' => [
                'key' => 'settings.email',
                'label' => 'Configuración de Email',
                'short_label' => 'Email Config',
                'icon' => 'fas fa-envelope',
                'route' => 'settings.email',
                'role' => 'Super Admin',
                'category' => 'Administración & Sistema',
                'color' => '#ea4335',
            ],
            'settings.email_outbox' => [
                'key' => 'settings.email_outbox',
                'label' => 'Bandeja de Salida Email',
                'short_label' => 'Email Salida',
                'icon' => 'fas fa-mail-bulk',
                'route' => 'settings.email_outbox',
                'role' => 'Super Admin',
                'category' => 'Administración & Sistema',
                'color' => '#fbbc05',
            ],
            'settings' => [
                'key' => 'settings',
                'label' => 'Configuración General',
                'short_label' => 'Ajustes',
                'icon' => 'fas fa-cogs',
                'route' => 'settings',
                'permission' => 'settings.index',
                'category' => 'Administración & Sistema',
                'color' => '#6c757d',
            ],
            'updates' => [
                'key' => 'updates',
                'label' => 'Sistema de Actualizaciones',
                'short_label' => 'Actualizaciones',
                'icon' => 'fas fa-cloud-download-alt',
                'route' => 'updates',
                'role' => 'Super Admin',
                'permission' => 'settings.update',
                'module' => 'module_updates',
                'category' => 'Administración & Sistema',
                'color' => '#007bff',
            ],
            'backups' => [
                'key' => 'backups',
                'label' => 'Respaldos de Base de Datos',
                'short_label' => 'Respaldos',
                'icon' => 'fas fa-database',
                'route' => 'backups',
                'role' => 'Super Admin',
                'permission' => 'settings.backups',
                'module' => 'module_backups',
                'category' => 'Administración & Sistema',
                'color' => '#28a745',
            ],
            'settings.license_generator' => [
                'key' => 'settings.license_generator',
                'label' => 'Generador SaaS de Licencias',
                'short_label' => 'Licencias SaaS',
                'icon' => 'fas fa-id-badge',
                'route' => 'settings.license_generator',
                'role' => 'Super Admin',
                'category' => 'Administración & Sistema',
                'color' => '#fd7e14',
            ],
            'settings.user_menus' => [
                'key' => 'settings.user_menus',
                'label' => 'Permisos de Menús por Usuario',
                'short_label' => 'Permisos Menús',
                'icon' => 'fas fa-user-lock',
                'route' => 'settings.user_menus',
                'role' => 'Super Admin',
                'category' => 'Administración & Sistema',
                'color' => '#17a2b8',
            ],
        ];
    }

    /**
     * Determina si el usuario actual tiene acceso al shortcut dado según ruta, permisos y módulos.
     */
    public static function canAccessShortcut(array $item, ?User $user = null): bool
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            return false;
        }

        // 1. Validar que la ruta existe en Laravel
        if (!Route::has($item['route'])) {
            return false;
        }

        // 2. Si tiene restricción exclusiva para Driver
        if (method_exists($user, 'hasRole') && $user->hasRole('Driver') && !in_array($item['key'], ['welcome', 'sales', 'driver.dashboard'])) {
            return false;
        }

        // 3. Si requiere un rol específico (ej: Super Admin)
        if (!empty($item['role'])) {
            if (!method_exists($user, 'hasRole') || !$user->hasRole($item['role'])) {
                return false;
            }
        }

        // 4. Validar módulos tenant si está definido
        if (!empty($item['module'])) {
            $tenantModules = config('tenant.modules');
            if (is_array($tenantModules) && !empty($tenantModules)) {
                if (!in_array($item['module'], $tenantModules)) {
                    return false;
                }
            }
        }

        // 5. Validar permisos Spatie
        if (!empty($item['permission'])) {
            if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Admin', 'Super Admin', 'Administrador'])) {
                return true;
            }

            if (method_exists($user, 'can') && !$user->can($item['permission'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Retorna los accesos disponibles agrupados por categoría que el usuario PUEDE ver según sus permisos.
     */
    public static function getAvailableForUser(?User $user = null): array
    {
        return self::getAvailableGroupedForUser($user);
    }

    /**
     * Retorna los accesos predeterminados según el rol del usuario.
     */
    public static function getDefaultKeysForUser(?User $user = null): array
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            return ['sales', 'reports.daily.sales', 'reports.payment.relationship', 'reports.sales.analysis'];
        }

        if (method_exists($user, 'hasRole') && $user->hasRole('Driver')) {
            return ['driver.dashboard', 'sales'];
        }

        if (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['Admin', 'Super Admin', 'Supervisor'])) {
            return [
                'sales',
                'reports.daily.sales',
                'reports.payment.relationship',
                'reports.sales.analysis',
                'reports.accounts.receivable',
                'inventories',
            ];
        }

        // Default para cajeros y vendedores
        return [
            'sales',
            'reports.daily.sales',
            'reports.payment.relationship',
            'credit.authorizations',
        ];
    }

    /**
     * Verifica si un menú específico está permitido para el usuario considerando:
     * 1. Si es Super Admin -> siempre permitido.
     * 2. Si el Super Admin configuró 'allowed_menus' para este usuario -> solo se permite si está en dicha lista y cumple módulos tenant.
     * 3. Si no tiene 'allowed_menus' configurado -> se evalúan los permisos de rol normales (canAccessShortcut).
     */
    public static function isMenuAllowedForUser(string $menuKey, ?User $user = null): bool
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            return false;
        }

        // El acceso al panel de configuración de menús siempre está garantizado para Super Admin
        if ($menuKey === 'settings.user_menus' && method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return true;
        }

        $catalog = self::getCatalog();
        $item = $catalog[$menuKey] ?? null;
        if (!$item) {
            return false;
        }

        $theme = $user->theme;
        if (is_string($theme)) {
            $theme = json_decode($theme, true);
        }
        $theme = is_array($theme) ? $theme : [];

        // Si tiene override personalizado configurado (array)
        if (isset($theme['allowed_menus']) && is_array($theme['allowed_menus'])) {
            if (!in_array($menuKey, $theme['allowed_menus'])) {
                return false;
            }

            // Validar existencia de la ruta y módulos de licencia tenant
            if (!Route::has($item['route'])) {
                return false;
            }
            if (!empty($item['module'])) {
                $tenantModules = config('tenant.modules');
                if (is_array($tenantModules) && !empty($tenantModules) && !in_array($item['module'], $tenantModules)) {
                    return false;
                }
            }

            return true;
        }

        // Si no tiene override personalizado:
        // Super Admin sin restricciones tiene acceso completo por defecto
        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }
        if (method_exists($user, 'hasRole') && $user->hasRole('Super Admin')) {
            return true;
        }

        // Si no tiene override personalizado, evalúa por rol y permisos estándar
        return self::canAccessShortcut($item, $user);
    }

    /**
     * Verifica si al menos uno de los menús indicados está permitido para el usuario.
     */
    public static function isAnyMenuAllowedForUser(array $keys, ?User $user = null): bool
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            return false;
        }

        foreach ($keys as $key) {
            if (self::isMenuAllowedForUser($key, $user)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retorna todos los ítems del catálogo que el usuario tiene permitido ver (plano: key => item).
     */
    public static function getAvailableMenusForUser(?User $user = null): array
    {
        $user = $user ?: Auth::user();
        $catalog = self::getCatalog();
        $allowed = [];

        foreach ($catalog as $key => $item) {
            if (self::isMenuAllowedForUser($key, $user)) {
                $allowed[$key] = $item;
            }
        }

        return $allowed;
    }

    /**
     * Retorna los menús permitidos agrupados por categoría para la interfaz del modal de atajos.
     */
    public static function getAvailableGroupedForUser(?User $user = null): array
    {
        $user = $user ?: Auth::user();
        $available = self::getAvailableMenusForUser($user);
        $grouped = [];

        foreach ($available as $key => $item) {
            $category = $item['category'] ?? 'General';
            $grouped[$category][] = $item;
        }

        return $grouped;
    }

    /**
     * Establece o limpia los menús permitidos para un usuario específico.
     */
    public static function setAllowedMenusForUser(User $user, ?array $allowedKeys): bool
    {
        $theme = $user->theme;
        if (is_string($theme)) {
            $theme = json_decode($theme, true);
        }
        $theme = is_array($theme) ? $theme : [];

        if ($allowedKeys === null) {
            unset($theme['allowed_menus']);
        } else {
            $theme['allowed_menus'] = array_values(array_unique($allowedKeys));
        }

        $user->theme = $theme;
        return $user->save();
    }

    /**
     * Determina si el usuario tiene una lista de menús personalizada configurada por el Super Admin.
     */
    public static function hasCustomOverrides(?User $user = null): bool
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            return false;
        }

        $theme = $user->theme;
        if (is_string($theme)) {
            $theme = json_decode($theme, true);
        }

        return is_array($theme) && isset($theme['allowed_menus']) && is_array($theme['allowed_menus']);
    }

    /**
     * Retorna las claves de menú que el rol del usuario permite por defecto.
     */
    public static function getRoleDefaultMenuKeys(User $user): array
    {
        $catalog = self::getCatalog();
        $keys = [];

        foreach ($catalog as $key => $item) {
            if (self::canAccessShortcut($item, $user)) {
                $keys[] = $key;
            }
        }

        return $keys;
    }

    /**
     * Obtiene la lista final de accesos directos activos para renderizar en la cinta.
     */
    public static function getActiveShortcutsForUser(?User $user = null): array
    {
        $user = $user ?: Auth::user();
        if (!$user) {
            return [];
        }

        $theme = $user->theme;
        if (is_string($theme)) {
            $theme = json_decode($theme, true);
        }
        $theme = is_array($theme) ? $theme : [];

        $configuredKeys = $theme['shortcuts'] ?? null;

        // Si no tiene configurado, usar defaults
        if (!is_array($configuredKeys) || empty($configuredKeys)) {
            $configuredKeys = self::getDefaultKeysForUser($user);
        }

        // Limitar a máximo 6 para mantener la interfaz limpia
        $configuredKeys = array_slice($configuredKeys, 0, 6);

        $catalog = self::getCatalog();
        $activeShortcuts = [];

        foreach ($configuredKeys as $key) {
            if (isset($catalog[$key])) {
                $item = $catalog[$key];
                if (self::isMenuAllowedForUser($key, $user)) {
                    $item['url'] = route($item['route']);
                    $item['is_active'] = request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*');
                    $activeShortcuts[] = $item;
                }
            }
        }

        return $activeShortcuts;
    }
}
