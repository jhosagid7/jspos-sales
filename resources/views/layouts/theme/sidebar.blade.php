@php
    $theme = auth()->user()->theme ?? [];
    
    // Aside Classes
    $asideClasses = ['main-sidebar', 'elevation-4'];
    if(!empty($theme['sidebar_variant'])) {
        $asideClasses[] = $theme['sidebar_variant'];
    } else {
        $asideClasses[] = 'sidebar-dark-primary';
    }
    if(!empty($theme['sidebar_no_expand']) && filter_var($theme['sidebar_no_expand'], FILTER_VALIDATE_BOOLEAN)) $asideClasses[] = 'sidebar-no-expand';
    $asideClassString = implode(' ', $asideClasses);

    // Brand Link Classes
    $brandClasses = ['brand-link'];
    if(!empty($theme['brand_text_sm']) && filter_var($theme['brand_text_sm'], FILTER_VALIDATE_BOOLEAN)) $brandClasses[] = 'text-sm';
    $brandClassString = implode(' ', $brandClasses);

    // Nav Sidebar Classes
    $navClasses = ['nav', 'nav-pills', 'nav-sidebar', 'flex-column'];
    if(!empty($theme['sidebar_nav_flat']) && filter_var($theme['sidebar_nav_flat'], FILTER_VALIDATE_BOOLEAN)) $navClasses[] = 'nav-flat';
    if(!empty($theme['sidebar_nav_legacy']) && filter_var($theme['sidebar_nav_legacy'], FILTER_VALIDATE_BOOLEAN)) $navClasses[] = 'nav-legacy';
    if(!isset($theme['sidebar_nav_compact']) || filter_var($theme['sidebar_nav_compact'], FILTER_VALIDATE_BOOLEAN)) $navClasses[] = 'nav-compact';
    if(!empty($theme['sidebar_nav_child_indent']) && filter_var($theme['sidebar_nav_child_indent'], FILTER_VALIDATE_BOOLEAN)) $navClasses[] = 'nav-child-indent';
    if(!empty($theme['sidebar_nav_child_hide']) && filter_var($theme['sidebar_nav_child_hide'], FILTER_VALIDATE_BOOLEAN)) $navClasses[] = 'nav-collapse-hide-child';
    if(!empty($theme['sidebar_nav_text_sm']) && filter_var($theme['sidebar_nav_text_sm'], FILTER_VALIDATE_BOOLEAN)) $navClasses[] = 'text-sm';
    $navClassString = implode(' ', $navClasses);
@endphp

<style>
    /* Estilo para los sub-menús activos */
    .nav-sidebar .nav-treeview > .nav-item > .nav-link.active {
        background-color: rgba(255,255,255,0.09) !important;
        color: #ffffff !important;
        border-left: 3px solid #007bff;
        border-radius: 0 4px 4px 0;
        font-weight: 500;
    }
    
    /* El ícono circular toma color azul cuando está activo */
    .nav-sidebar .nav-treeview > .nav-item > .nav-link.active .nav-icon {
        color: #007bff !important;
    }
    
    /* Efecto Hover suave */
    .nav-sidebar .nav-treeview > .nav-item > .nav-link:hover {
        background-color: rgba(255,255,255,0.05);
        color: #ffffff;
    }
</style>
<aside class="{{ $asideClassString }}">
    <!-- Brand Logo -->
    @php
        $config = \App\Models\Configuration::first();
        $logo = $config && $config->logo ? asset('storage/' . $config->logo) : asset('assets/images/logo/logo-icon.png');
        $appName = $config && $config->business_name ? iconv('UTF-8', 'UTF-8//IGNORE', $config->business_name) : 'JSPOS v1.7';
    @endphp
    <a href="{{ route('sales') }}" class="{{ $brandClassString }}">
        <img src="{{ $logo }}" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">{{ $appName }}</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <a href="{{ route('profile.edit') }}">
                    @if(Auth::user()->profile_photo_path)
                        <img src="{{ asset('storage/' . Auth::user()->profile_photo_path) }}" class="img-circle elevation-2" alt="User Image" style="width: 33px; height: 33px; object-fit: cover;">
                    @else
                        <img src="https://ui-avatars.com/api/?name={{ urlencode(Auth::user()->name) }}&color=7F9CF5&background=EBF4FF" class="img-circle elevation-2" alt="User Image" style="width: 33px; height: 33px; object-fit: cover;">
                    @endif
                </a>
            </div>
            <div class="info">
                <a href="{{ route('profile.edit') }}" class="d-block">{{ Auth()->user()->name ?? 'Guest' }}</a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="{{ $navClassString }}" data-widget="treeview" role="menu" data-accordion="false">
                
                @unlessrole('Driver')
                @menuAllowed('welcome')
                <li class="nav-item">
                    <a href="{{ route('welcome') }}" class="nav-link {{ Request::is('welcome') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt text-primary"></i>
                        <p>DASHBOARD</p>
                    </a>
                </li>
                @endmenuAllowed
                @endunlessrole

                @php
                    $isDriver = auth()->user()->hasRole('Driver');
                    $canSeeLogistics = auth()->user()->hasRole(['Admin', 'Supervisor', 'Super Admin']) || auth()->user()->can('sales.index');
                @endphp

                {{-- MÓDULO 1: GESTIÓN COMERCIAL --}}
                @unlessrole('Driver')
                @anyMenuAllowed(['sales', 'credit.authorizations', 'purchases', 'purchase.list', 'price-list.index', 'commissions'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-shopping-cart text-success"></i>
                        <p>
                            GESTIÓN COMERCIAL
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @menuAllowed('sales')
                        <li class="nav-item">
                            <a href="{{ route('sales') }}" class="nav-link {{ Request::is('sales') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Ventas (POS)</p>
                            </a>
                        </li>
                        @endmenuAllowed

                        @menuAllowed('credit.authorizations')
                        @module('module_credits')
                        <li class="nav-item">
                            <a href="{{ route('credit.authorizations') }}" class="nav-link {{ Request::is('credit-authorizations') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Historial Auth. Crédito</p>
                            </a>
                        </li>
                        @endmodule
                        @endmenuAllowed

                        @anyMenuAllowed(['purchases', 'purchase.list'])
                        @module('module_purchases')
                        <li class="nav-item {{ Request::is('purchases*') || Request::is('purchase-list*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('purchases*') || Request::is('purchase-list*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Compras
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @menuAllowed('purchases')
                                <li class="nav-item">
                                    <a href="{{ route('purchases') }}" class="nav-link {{ Request::is('purchases') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Nueva Compra</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('purchase.list')
                                <li class="nav-item">
                                    <a href="{{ route('purchase.list') }}" class="nav-link {{ Request::is('purchase-list') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Historial</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                            </ul>
                        </li>
                        @endmodule
                        @endanyMenuAllowed

                        @menuAllowed('price-list.index')
                        <li class="nav-item">
                            <a href="{{ route('price-list.index') }}" class="nav-link {{ Request::is('price-list') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Lista de Precios</p>
                            </a>
                        </li>
                        @endmenuAllowed

                        @menuAllowed('commissions')
                        @module('module_commissions')
                        <li class="nav-item">
                            <a href="{{ route('commissions') }}" class="nav-link {{ Request::is('commissions') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Comisiones</p>
                            </a>
                        </li>
                        @endmodule
                        @endmenuAllowed
                    </ul>
                </li>
                @endanyMenuAllowed
                @endunlessrole

                {{-- MÓDULO 2: LOGÍSTICA Y DESPACHO --}}
                @module('module_delivery')
                @if($isDriver || $canSeeLogistics)
                @anyMenuAllowed(['driver.dashboard', 'delivery.map', 'reports.dispatch'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-truck text-info"></i>
                        <p>
                            LOGÍSTICA Y DESPACHO
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @menuAllowed('driver.dashboard')
                        <li class="nav-item">
                            <a href="{{ route('driver.dashboard') }}" class="nav-link {{ Request::is('driver/dashboard') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>{{ $isDriver ? 'MI RUTA' : 'Logística / Rutas' }}</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @menuAllowed('delivery.map')
                        <li class="nav-item">
                            <a href="{{ route('delivery.map') }}" class="nav-link {{ Request::is('delivery/map') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Mapa Choferes</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @menuAllowed('reports.dispatch')
                        <li class="nav-item">
                            <a href="{{ route('reports.dispatch') }}" class="nav-link {{ Request::is('reports/dispatch*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Relación de Despacho</p>
                            </a>
                        </li>
                        @endmenuAllowed
                    </ul>
                </li>
                @endanyMenuAllowed
                @endif
                @endmodule

                {{-- MÓDULO 3: INVENTARIO Y PRODUCCIÓN --}}
                @unlessrole('Driver')
                @anyMenuAllowed(['products', 'catalogue.pdf', 'price-groups', 'inventories', 'cargos', 'descargos', 'transfers', 'requisition', 'labels.index'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-boxes text-warning"></i>
                        <p>
                            INVENTARIO Y PRODUCCIÓN
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @anyMenuAllowed(['products', 'catalogue.pdf', 'price-groups'])
                        <li class="nav-item {{ Request::is('products*') || Request::is('catalogue*') || Request::is('price-groups*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('products*') || Request::is('catalogue*') || Request::is('price-groups*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Productos
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @menuAllowed('products')
                                <li class="nav-item">
                                    <a href="{{ route('products') }}" class="nav-link {{ Request::is('products') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Listado Maestro</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('catalogue.pdf')
                                <li class="nav-item">
                                    <a href="{{ route('catalogue.pdf') }}" target="_blank" class="nav-link {{ Route::is('catalogue.pdf*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Catálogo PDF</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('price-groups')
                                <li class="nav-item">
                                    <a href="{{ route('price-groups') }}" class="nav-link {{ Request::is('price-groups') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Grupos de Precio</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                            </ul>
                        </li>
                        @endanyMenuAllowed

                        @anyMenuAllowed(['inventories', 'cargos', 'descargos', 'transfers', 'requisition'])
                        <li class="nav-item {{ Request::is('inventories*') || Request::is('cargos*') || Request::is('descargos*') || Request::is('transfers*') || Request::is('requisition*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('inventories*') || Request::is('cargos*') || Request::is('descargos*') || Request::is('transfers*') || Request::is('requisition*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Gestión de Stock
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @menuAllowed('inventories')
                                <li class="nav-item">
                                    <a href="{{ route('inventories') }}" class="nav-link {{ Request::is('inventories') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Stock General</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('cargos')
                                <li class="nav-item">
                                    <a href="{{ route('cargos') }}" class="nav-link {{ Request::is('cargos*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Entradas (Ajuste)</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('descargos')
                                <li class="nav-item">
                                    <a href="{{ route('descargos') }}" class="nav-link {{ Request::is('descargos*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Salidas (Ajuste)</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @module('module_multi_warehouse')
                                @menuAllowed('transfers')
                                <li class="nav-item">
                                    <a href="{{ route('transfers') }}" class="nav-link {{ Request::is('transfers') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Traspasos</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('requisition')
                                <li class="nav-item">
                                    <a href="{{ route('requisition') }}" class="nav-link {{ Request::is('requisition') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Requisiciones</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @endmodule
                            </ul>
                        </li>
                        @endanyMenuAllowed

                        @module('module_labels')
                        @menuAllowed('labels.index')
                        <li class="nav-item">
                            <a href="{{ route('labels.index') }}" class="nav-link {{ Request::is('labels') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Etiquetas</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @endmodule
                    </ul>
                </li>
                @endanyMenuAllowed
                @endunlessrole

                {{-- MÓDULO: FÁBRICA SOPLADOS (BOTELLONES) --}}
                @module('module_soplados')
                @unlessrole('Driver')
                @anyMenuAllowed(['production.report', 'soplados.formulas', 'soplados.shifts', 'soplados.inventories', 'soplados.expected-production'])
                <li class="nav-item {{ Request::is('production-report*') || Request::is('soplados/*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Request::is('production-report*') || Request::is('soplados/*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-industry text-danger"></i>
                        <p>
                            FÁBRICA SOPLADOS
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @menuAllowed('production.report')
                        <li class="nav-item">
                            <a href="{{ route('production.report') }}" class="nav-link {{ Request::is('production-report') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Reporte Producción</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @menuAllowed('soplados.formulas')
                        <li class="nav-item">
                            <a href="{{ route('soplados.formulas') }}" class="nav-link {{ Request::is('soplados/formulas') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Configuración de Recetas</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @menuAllowed('soplados.shifts')
                        <li class="nav-item">
                            <a href="{{ route('soplados.shifts') }}" class="nav-link {{ Request::is('soplados/shifts') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Historial de Turnos</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @menuAllowed('soplados.inventories')
                        <li class="nav-item">
                            <a href="{{ route('soplados.inventories') }}" class="nav-link {{ Request::is('soplados/inventories*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Historial Inventarios</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @menuAllowed('soplados.expected-production')
                        <li class="nav-item">
                            <a href="{{ route('soplados.expected-production') }}" class="nav-link {{ Request::is('soplados/expected-production') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Metas de Producción</p>
                            </a>
                        </li>
                        @endmenuAllowed
                    </ul>
                </li>
                @endanyMenuAllowed
                @endunlessrole
                @endmodule

                {{-- MÓDULO: FÁBRICA BOLSAS --}}
                @module('module_bolsas')
                @unlessrole('Driver')
                @menuAllowed('production.index')
                <li class="nav-item {{ Request::is('production*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Request::is('production*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-shopping-bag text-secondary"></i>
                        <p>
                            FÁBRICA BOLSAS
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('production.index') }}" class="nav-link {{ Request::is('production') || Request::is('production/create*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Historial Levantamiento</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endmenuAllowed
                @endunlessrole
                @endmodule

                {{-- MÓDULO 4: FINANZAS Y AUDITORÍA --}}
                @unlessrole('Driver')
                @anyMenuAllowed(['cash-register.close', 'credit.auth.history', 'cash.count', 'reports.accounts.receivable', 'customer-statement', 'reports.returns', 'pos.debit-notes', 'reports.accounts.payables', 'consultation.zelle', 'consultation.usdt', 'consultation.bank', 'consultation.approvals', 'treasury.dashboard'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-file-invoice-dollar text-success"></i>
                        <p>
                            FINANZAS Y AUDITORÍA
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @anyMenuAllowed(['cash-register.close', 'credit.auth.history', 'cash.count'])
                        <li class="nav-item {{ Request::is('cash-register*') || Request::is('cash-count*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('cash-register*') || Request::is('cash-count*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Control de Caja
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @menuAllowed('cash-register.close')
                                <li class="nav-item">
                                    <a href="{{ route('cash-register.close') }}" class="nav-link {{ Request::is('cash-register/close') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Cerrar Caja</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @module('module_credit_auth_history')
                                @menuAllowed('credit.auth.history')
                                <li class="nav-item">
                                    <a href="{{ route('credit.auth.history') }}" class="nav-link {{ Route::is('credit.auth.history*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Historial Auth. Crédito</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @endmodule
                                @menuAllowed('cash.count')
                                <li class="nav-item">
                                    <a href="{{ route('cash.count') }}" class="nav-link {{ Route::is('cash.count*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Historial Arqueos</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                            </ul>
                        </li>
                        @endanyMenuAllowed

                        @anyMenuAllowed(['reports.accounts.receivable', 'customer-statement', 'reports.returns', 'pos.debit-notes', 'reports.accounts.payables'])
                        <li class="nav-item {{ Request::is('reports/accounts-*') || Request::is('customer-statement*') || Request::is('reports/returns*') || Request::is('pos/debit-notes*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('reports/accounts-*') || Request::is('customer-statement*') || Request::is('reports/returns*') || Request::is('pos/debit-notes*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Cartera y Crédito
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @module('module_credits')
                                @menuAllowed('reports.accounts.receivable')
                                <li class="nav-item">
                                    <a href="{{ route('reports.accounts.receivable') }}" class="nav-link {{ Route::is('reports.accounts.receivable*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Cuentas por Cobrar</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('customer-statement')
                                <li class="nav-item">
                                    <a href="{{ route('customer-statement') }}" class="nav-link {{ Request::is('customer-statement') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Estado de Cuenta</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @endmodule
                                @menuAllowed('reports.returns')
                                <li class="nav-item">
                                    <a href="{{ route('reports.returns') }}" class="nav-link {{ Request::is('reports/returns*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Notas de Crédito</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('pos.debit-notes')
                                <li class="nav-item">
                                    <a href="{{ route('pos.debit-notes') }}" class="nav-link {{ Route::is('pos.debit-notes*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Notas de Débito</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @module('module_purchases')
                                @menuAllowed('reports.accounts.payables')
                                <li class="nav-item">
                                    <a href="{{ route('reports.accounts.payables') }}" class="nav-link {{ Route::is('reports.accounts.payables*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Cuentas por Pagar</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @endmodule
                            </ul>
                        </li>
                        @endanyMenuAllowed

                        @anyMenuAllowed(['consultation.zelle', 'consultation.usdt', 'consultation.bank', 'consultation.approvals'])
                        @module('module_advanced_payments')
                        <li class="nav-item {{ Request::is('consultation*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('consultation*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Auditoría Pagos
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @menuAllowed('consultation.zelle')
                                <li class="nav-item">
                                    <a href="{{ route('consultation.zelle') }}" class="nav-link {{ Request::is('consultation/zelle*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Pagos Zelle</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @module('module_usdt')
                                @menuAllowed('consultation.usdt')
                                <li class="nav-item">
                                    <a href="{{ route('consultation.usdt') }}" class="nav-link {{ Request::is('consultation/usdt*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon text-success"></i>
                                        <p>Pagos USDT ($)</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @endmodule
                                @menuAllowed('consultation.bank')
                                <li class="nav-item">
                                    <a href="{{ route('consultation.bank') }}" class="nav-link {{ Request::is('consultation/bank*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Pagos Bancarios</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('consultation.approvals')
                                <li class="nav-item">
                                    <a href="{{ route('consultation.approvals') }}" class="nav-link {{ Request::is('consultation/approvals*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Aprobación de Tasas</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                            </ul>
                        </li>
                        @endmodule
                        @endanyMenuAllowed

                        @module('module_treasury')
                        @menuAllowed('treasury.dashboard')
                        <li class="nav-item">
                            <a href="{{ route('treasury.dashboard') }}" class="nav-link {{ Request::is('treasury*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Tesorería / Bancos</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @endmodule
                    </ul>
                </li>
                @endanyMenuAllowed
                @endunlessrole

                {{-- MÓDULO 5: ENTIDADES Y MAESTROS --}}
                @unlessrole('Driver')
                @anyMenuAllowed(['customers', 'suppliers', 'categories', 'warehouses'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-folder text-primary"></i>
                        <p>
                            REGISTROS MAESTROS
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @menuAllowed('customers')
                        <li class="nav-item">
                            <a href="{{ route('customers') }}" class="nav-link {{ Route::is('customers*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Clientes</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @menuAllowed('suppliers')
                        <li class="nav-item">
                            <a href="{{ route('suppliers') }}" class="nav-link {{ Route::is('suppliers*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Proveedores</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @menuAllowed('categories')
                        <li class="nav-item">
                            <a href="{{ route('categories') }}" class="nav-link {{ Route::is('categories*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Categorías</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @module('module_multi_warehouse')
                        @menuAllowed('warehouses')
                        <li class="nav-item">
                            <a href="{{ route('warehouses') }}" class="nav-link {{ Request::is('warehouses') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>{{ term('warehouse') == 'Bodega' ? 'Bodegas' : (term('warehouse') == 'Almacén' ? 'Almacenes' : 'Depósitos / Almacenes') }}</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @endmodule
                    </ul>
                </li>
                @endanyMenuAllowed
                @endunlessrole

                {{-- MÓDULO 6: CENTRO DE REPORTES --}}
                @unlessrole('Driver')
                @anyMenuAllowed(['reports.strategic', 'reports.sales', 'reports.daily.sales', 'reports.partner.sales', 'reports.payment.relationship', 'reports.weekly.income', 'reports.monthly.income', 'reports.customers', 'reports.customer.activity', 'reports.sales.analysis', 'reports.sellers.performance', 'reports.goal.commissions', 'reports.seller_grouped', 'reports.operators.precision', 'reports.exchange.diff', 'reports.audit', 'consultation.approvals', 'reports.cash.flow.forecast', 'reports.customer.payment.relationship', 'reports.inventory', 'reports.movements', 'reports.best.sellers', 'reports.rotation'])
                <li class="nav-item {{ Request::is('reports*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Request::is('reports*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-chart-line text-info"></i>
                        <p>
                            CENTRO DE REPORTES
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @anyMenuAllowed(['reports.strategic', 'reports.sales', 'reports.daily.sales', 'reports.partner.sales', 'reports.payment.relationship', 'reports.weekly.income', 'reports.monthly.income', 'reports.customers', 'reports.customer.activity', 'reports.sales.analysis', 'reports.sellers.performance', 'reports.goal.commissions', 'reports.seller_grouped', 'reports.operators.precision', 'reports.exchange.diff', 'reports.audit', 'consultation.approvals', 'reports.cash.flow.forecast', 'reports.customer.payment.relationship'])
                        <li class="nav-item {{ Request::is('reports/sales*') || Request::is('reports/daily-sales*') || Request::is('reports/partner-sales*') || Request::is('reports/payment-relationship*') || Request::is('reports/customer-payment*') || Request::is('reports/weekly-income*') || Request::is('reports/monthly-income*') || Request::is('reports/customers*') || Request::is('reports/customer-activity*') || Request::is('reports/sales-analysis*') || Request::is('reports/sellers-performance*') || Request::is('reports/operators-precision*') || Request::is('reports/exchange-diff*') || Request::is('reports/cash-flow-forecast*') || Request::is('reports/strategic*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('reports/sales*') || Request::is('reports/daily-sales*') || Request::is('reports/partner-sales*') || Request::is('reports/payment-relationship*') || Request::is('reports/customer-payment*') || Request::is('reports/weekly-income*') || Request::is('reports/monthly-income*') || Request::is('reports/customers*') || Request::is('reports/customer-activity*') || Request::is('reports/sales-analysis*') || Request::is('reports/sellers-performance*') || Request::is('reports/operators-precision*') || Request::is('reports/exchange-diff*') || Request::is('reports/cash-flow-forecast*') || Request::is('reports/strategic*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Ventas y Cobros
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @module('module_strategic_analysis')
                                @menuAllowed('reports.strategic')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.strategic') }}" class="nav-link {{ Request::is('reports/strategic*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Análisis Estratégico</p>
                                      </a>
                                  </li>
                                @endmenuAllowed
                                @endmodule
                                @menuAllowed('reports.sales')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.sales') }}" class="nav-link {{ Route::is('reports.sales*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Reporte de Ventas</p>
                                      </a>
                                  </li>
                                @endmenuAllowed
                                @menuAllowed('reports.daily.sales')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.daily.sales') }}" class="nav-link {{ Route::is('reports.daily.sales*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Ventas Diarias</p>
                                      </a>
                                  </li>
                                @endmenuAllowed
                                  @module('module_partner_sales')
                                  @menuAllowed('reports.partner.sales')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.partner.sales') }}" class="nav-link {{ Route::is('reports.partner.sales*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Ventas por {{ term('partner') }} (FIFO)</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                  @module('module_credits')
                                  @menuAllowed('reports.payment.relationship')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.payment.relationship') }}" class="nav-link {{ Route::is('reports.payment.relationship*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Relación de Cobros</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                  @module('module_weekly_income')
                                  @menuAllowed('reports.weekly.income')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.weekly.income') }}" class="nav-link {{ Request::is('reports/weekly-income*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Reporte Semanal de Ingresos</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                  @module('module_monthly_income')
                                  @menuAllowed('reports.monthly.income')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.monthly.income') }}" class="nav-link {{ Request::is('reports/monthly-income*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Reporte Mensual de Ingresos</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                  @module('module_customer_report')
                                  @menuAllowed('reports.customers')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.customers') }}" class="nav-link {{ Request::is('reports/customers*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Reporte de Clientes</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                  @module('module_customer_activity')
                                  @menuAllowed('reports.customer.activity')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.customer.activity') }}" class="nav-link {{ Request::is('reports/customer-activity*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Actividad de Clientes</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                  @module('module_sales_analysis')
                                  @menuAllowed('reports.sales.analysis')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.sales.analysis') }}" class="nav-link {{ Request::is('reports/sales-analysis*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Análisis de Ventas</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                  @module('module_seller_performance')
                                  @menuAllowed('reports.sellers.performance')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.sellers.performance') }}" class="nav-link {{ Request::is('reports/sellers-performance*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Desempeño de Vendedores</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                  @module('module_commissions')
                                  @menuAllowed('reports.goal.commissions')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.goal.commissions') }}" class="nav-link {{ Request::is('reports/goal-commissions*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Comisiones por Metas</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                  @module('module_seller_grouped')
                                  @menuAllowed('reports.seller_grouped')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.seller_grouped') }}" class="nav-link {{ Request::is('reports/seller-grouped*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Cobranza por Operador</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                  @module('module_operator_efficiency')
                                  @menuAllowed('reports.operators.precision')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.operators.precision') }}" class="nav-link {{ Request::is('reports/operators-precision*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Eficiencia de Operadores</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                  @module('module_differential_audit')
                                  @menuAllowed('reports.exchange.diff')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.exchange.diff') }}" class="nav-link {{ Request::is('reports/exchange-diff*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Auditoría de Diferencial</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                  @menuAllowed('reports.audit')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.audit') }}" class="nav-link {{ Request::is('reports/audit*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Auditoría de Actividad</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @menuAllowed('consultation.approvals')
                                  <li class="nav-item">
                                      <a href="{{ route('consultation.approvals') }}" class="nav-link {{ Request::is('consultation/approvals*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Aprobaciones de Supervisión</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @module('module_cash_flow')
                                  @menuAllowed('reports.cash.flow.forecast')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.cash.flow.forecast') }}" class="nav-link {{ Request::is('reports/cash-flow-forecast*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Flujo y Cobranza</p>
                                      </a>
                                  </li>
                                  @endmenuAllowed
                                  @endmodule
                                @module('module_collection_audit')
                                @menuAllowed('reports.customer.payment.relationship')
                                <li class="nav-item">
                                    <a href="{{ route('reports.customer.payment.relationship') }}" class="nav-link {{ Route::is('reports.customer.payment.relationship*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Cobros por Cliente</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @endmodule
                            </ul>
                        </li>
                        @endanyMenuAllowed

                        @anyMenuAllowed(['reports.inventory', 'reports.movements', 'reports.audit', 'reports.best.sellers', 'reports.rotation'])
                        <li class="nav-item {{ Route::is('reports.inventory*') || Route::is('reports.movements*') || Route::is('reports.audit*') || Route::is('reports.best.sellers*') || Route::is('reports.rotation*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Route::is('reports.inventory*') || Route::is('reports.movements*') || Route::is('reports.audit*') || Route::is('reports.best.sellers*') || Route::is('reports.rotation*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Stock y Desempeño
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @menuAllowed('reports.inventory')
                                <li class="nav-item">
                                    <a href="{{ route('reports.inventory') }}" class="nav-link {{ Route::is('reports.inventory*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Inventario Actual</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('reports.movements')
                                <li class="nav-item">
                                    <a href="{{ route('reports.movements') }}" class="nav-link {{ Route::is('reports.movements*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Kardex (Movimientos)</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('reports.audit')
                                <li class="nav-item">
                                    <a href="{{ route('reports.audit') }}" class="nav-link {{ Route::is('reports.audit*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Auditoría de Stock</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('reports.best.sellers')
                                <li class="nav-item">
                                    <a href="{{ route('reports.best.sellers') }}" class="nav-link {{ Route::is('reports.best.sellers*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Más Vendidos</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @module('module_advanced_reports')
                                @menuAllowed('reports.rotation')
                                <li class="nav-item">
                                    <a href="{{ route('reports.rotation') }}" class="nav-link {{ Route::is('reports.rotation*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Rotación de Stock</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @endmodule
                            </ul>
                        </li>
                        @endanyMenuAllowed
                    </ul>
                </li>
                @endanyMenuAllowed
                @endunlessrole

                {{-- MÓDULO 7: ADMINISTRACIÓN Y CONFIGURACIÓN --}}
                @unlessrole('Driver')
                @anyMenuAllowed(['users', 'roles', 'asignar', 'audit.sheet', 'audit.invoices', 'devices', 'settings.whatsapp', 'settings.whatsapp_outbox', 'settings.email', 'settings.email_outbox', 'settings', 'updates', 'backups', 'settings.license_generator', 'settings.user_menus'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-cogs text-warning"></i>
                        <p>
                            ADMINISTRACIÓN
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @anyMenuAllowed(['users', 'roles', 'asignar'])
                        <li class="nav-item {{ Request::is('users*') || Request::is('roles*') || Request::is('asignar*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('users*') || Request::is('roles*') || Request::is('asignar*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Equipo de Trabajo
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @menuAllowed('users')
                                <li class="nav-item">
                                    <a href="{{ route('users') }}" class="nav-link {{ Route::is('users*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Usuarios</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @module('module_roles')
                                @role('Super Admin')
                                @menuAllowed('roles')
                                <li class="nav-item">
                                    <a href="{{ route('roles') }}" class="nav-link {{ Route::is('roles*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Roles y Permisos</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('asignar')
                                <li class="nav-item">
                                    <a href="{{ route('asignar') }}" class="nav-link {{ Route::is('asignar*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Asignación</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @endrole
                                @endmodule
                            </ul>
                        </li>
                        @endanyMenuAllowed

                        @module('module_collection_audit')
                        @menuAllowed('audit.sheet')
                        <li class="nav-item">
                            <a href="{{ route('audit.sheet') }}" class="nav-link {{ Request::is('audit/sheet*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Auditoría de Cobranza</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @endmodule
                        @module('module_invoice_audit')
                        @menuAllowed('audit.invoices')
                        <li class="nav-item">
                            <a href="{{ route('audit.invoices') }}" class="nav-link {{ Request::is('audit/invoices*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Auditoría de Facturas</p>
                            </a>
                        </li>
                        @endmenuAllowed
                        @endmodule

                        @role('Super Admin')
                        @menuAllowed('devices')
                        <li class="nav-item">
                            <a href="{{ route('devices') }}" class="nav-link {{ Request::is('devices') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Dispositivos</p>
                            </a>
                        </li>
                        @endmenuAllowed

                        @anyMenuAllowed(['settings.whatsapp', 'settings.whatsapp_outbox', 'settings.email', 'settings.email_outbox'])
                        <li class="nav-item {{ Request::is('settings/whatsapp*') || Request::is('settings/email*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('settings/whatsapp*') || Request::is('settings/email*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Mensajería
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @menuAllowed('settings.whatsapp')
                                <li class="nav-item">
                                    <a href="{{ route('settings.whatsapp') }}" class="nav-link {{ Route::is('settings.whatsapp*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>WhatsApp Config</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('settings.whatsapp_outbox')
                                <li class="nav-item">
                                    <a href="{{ route('settings.whatsapp_outbox') }}" class="nav-link {{ Route::is('settings.whatsapp_outbox*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>WA Bandeja</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('settings.email')
                                <li class="nav-item">
                                    <a href="{{ route('settings.email') }}" class="nav-link {{ Route::is('settings.email*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Email Config</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('settings.email_outbox')
                                <li class="nav-item">
                                    <a href="{{ route('settings.email_outbox') }}" class="nav-link {{ Route::is('settings.email_outbox*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Email Bandeja</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                            </ul>
                        </li>
                        @endanyMenuAllowed
                        @endrole

                        @anyMenuAllowed(['settings', 'updates', 'backups', 'settings.license_generator', 'settings.user_menus'])
                        <li class="nav-item {{ Request::is('settings') || Request::is('updates*') || Request::is('backups*') || Request::is('settings/license-generator*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('settings') || Request::is('updates*') || Request::is('backups*') || Request::is('settings/license-generator*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Ajustes Globales
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @menuAllowed('settings')
                                <li class="nav-item">
                                    <a href="{{ route('settings') }}" class="nav-link {{ Request::is('settings') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Configuración</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @role('Super Admin')
                                @module('module_updates')
                                @menuAllowed('updates')
                                <li class="nav-item">
                                    <a href="{{ route('updates') }}" class="nav-link {{ Request::is('updates') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Actualizaciones</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @endmodule
                                @module('module_backups')
                                @menuAllowed('backups')
                                <li class="nav-item">
                                    <a href="{{ route('backups') }}" class="nav-link {{ Request::is('backups') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Respaldos</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @endmodule
                                <li class="nav-item">
                                    <a href="javascript:void(0)" onclick="Livewire.dispatch('trigger-license-modal')" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Licencia</p>
                                    </a>
                                </li>
                                @menuAllowed('settings.license_generator')
                                <li class="nav-item">
                                    <a href="{{ route('settings.license_generator') }}" class="nav-link {{ Request::is('settings/license-generator*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Generador SaaS</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @menuAllowed('settings.user_menus')
                                <li class="nav-item">
                                    <a href="{{ route('settings.user_menus') }}" class="nav-link {{ Request::is('settings/user-menus*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon text-info"></i>
                                        <p>Permisos de Menús</p>
                                    </a>
                                </li>
                                @endmenuAllowed
                                @endrole
                            </ul>
                        </li>
                        @endanyMenuAllowed
                    </ul>
                </li>
                @endanyMenuAllowed
                @endunlessrole

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
