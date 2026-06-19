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
    if(!empty($theme['sidebar_nav_compact']) && filter_var($theme['sidebar_nav_compact'], FILTER_VALIDATE_BOOLEAN)) $navClasses[] = 'nav-compact';
    if(!empty($theme['sidebar_nav_child_indent']) && filter_var($theme['sidebar_nav_child_indent'], FILTER_VALIDATE_BOOLEAN)) $navClasses[] = 'nav-child-indent';
    if(!empty($theme['sidebar_nav_child_hide']) && filter_var($theme['sidebar_nav_child_hide'], FILTER_VALIDATE_BOOLEAN)) $navClasses[] = 'nav-collapse-hide-child';
    if(!empty($theme['sidebar_nav_text_sm']) && filter_var($theme['sidebar_nav_text_sm'], FILTER_VALIDATE_BOOLEAN)) $navClasses[] = 'text-sm';
    $navClassString = implode(' ', $navClasses);
@endphp
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
                <li class="nav-item">
                    <a href="{{ route('welcome') }}" class="nav-link {{ Request::is('welcome') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>DASHBOARD</p>
                    </a>
                </li>
                @endunlessrole

                @php
                    $isDriver = auth()->user()->hasRole('Driver');
                    $canSeeLogistics = auth()->user()->hasRole(['Admin', 'Supervisor', 'Super Admin']) || auth()->user()->can('sales.index');
                @endphp

                {{-- MÓDULO 1: GESTIÓN COMERCIAL --}}
                @unlessrole('Driver')
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-shopping-cart"></i>
                        <p>
                            GESTIÓN COMERCIAL
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('sales.index')
                        <li class="nav-item">
                            <a href="{{ route('sales') }}" class="nav-link {{ Request::is('sales*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Ventas (POS)</p>
                            </a>
                        </li>
                        @endcan

                        @module('module_purchases')
                        @can('purchases.index')
                        <li class="nav-item {{ Request::is('purchases*') || Request::is('purchase-list*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('purchases*') || Request::is('purchase-list*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Compras
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('purchases') }}" class="nav-link {{ Request::is('purchases') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Nueva Compra</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('purchase.list') }}" class="nav-link {{ Request::is('purchase-list') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Historial</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endcan
                        @endmodule

                        @can('sales.generate_price_list')
                        <li class="nav-item">
                            <a href="{{ route('price-list.index') }}" class="nav-link {{ Request::is('price-list') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Lista de Precios</p>
                            </a>
                        </li>
                        @endcan

                        @module('module_commissions')
                        @can('reports.commissions')
                        <li class="nav-item">
                            <a href="{{ route('commissions') }}" class="nav-link {{ Request::is('commissions') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Comisiones</p>
                            </a>
                        </li>
                        @endcan
                        @endmodule
                    </ul>
                </li>
                @endunlessrole

                {{-- MÓDULO 2: LOGÍSTICA Y DESPACHO --}}
                @if($isDriver || $canSeeLogistics)
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-truck"></i>
                        <p>
                            LOGÍSTICA Y DESPACHO
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('driver.dashboard') }}" class="nav-link {{ Request::is('driver/dashboard') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>{{ $isDriver ? 'MI RUTA' : 'Logística / Rutas' }}</p>
                            </a>
                        </li>
                        @module('module_delivery')
                        @can('distribution.map')
                        <li class="nav-item">
                            <a href="{{ route('delivery.map') }}" class="nav-link {{ Request::is('delivery/map') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Mapa Choferes</p>
                            </a>
                        </li>
                        @endcan
                        @can('reports.sales')
                        <li class="nav-item">
                            <a href="{{ route('reports.dispatch') }}" class="nav-link {{ Request::is('reports/dispatch*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Relación de Despacho</p>
                            </a>
                        </li>
                        @endcan
                        @endmodule
                    </ul>
                </li>
                @endif

                {{-- MÓDULO 3: INVENTARIO Y PRODUCCIÓN --}}
                @unlessrole('Driver')
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-boxes"></i>
                        <p>
                            INVENTARIO Y PRODUCCIÓN
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('products.index')
                        <li class="nav-item {{ Request::is('products*') || Request::is('catalogue*') || Request::is('price-groups*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('products*') || Request::is('catalogue*') || Request::is('price-groups*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Productos
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('products') }}" class="nav-link {{ Request::is('products') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Listado Maestro</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('catalogue.pdf') }}" target="_blank" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Catálogo PDF</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('price-groups') }}" class="nav-link {{ Request::is('price-groups') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Grupos de Precio</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endcan

                        @can('inventory.index')
                        <li class="nav-item {{ Request::is('inventories*') || Request::is('cargos*') || Request::is('descargos*') || Request::is('transfers*') || Request::is('requisition*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('inventories*') || Request::is('cargos*') || Request::is('descargos*') || Request::is('transfers*') || Request::is('requisition*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Gestión de Stock
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('inventories') }}" class="nav-link {{ Request::is('inventories') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Stock General</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('cargos') }}" class="nav-link {{ Request::is('cargos*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Entradas (Ajuste)</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('descargos') }}" class="nav-link {{ Request::is('descargos*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Salidas (Ajuste)</p>
                                    </a>
                                </li>
                                @module('module_multi_warehouse')
                                <li class="nav-item">
                                    <a href="{{ route('transfers') }}" class="nav-link {{ Request::is('transfers') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Traspasos</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('requisition') }}" class="nav-link {{ Request::is('requisition') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Requisiciones</p>
                                    </a>
                                </li>
                                @endmodule
                            </ul>
                        </li>
                        @endcan



                        @module('module_labels')
                        @can('products.labels')
                        <li class="nav-item">
                            <a href="{{ route('labels.index') }}" class="nav-link {{ Request::is('labels') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Etiquetas</p>
                            </a>
                        </li>
                        @endcan
                        @endmodule
                    </ul>
                </li>
                @endunlessrole

                {{-- MÓDULO: FÁBRICA SOPLADOS (BOTELLONES) --}}
                @unlessrole('Driver')
                <li class="nav-item {{ Request::is('production-report*') || Request::is('soplados/*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Request::is('production-report*') || Request::is('soplados/*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-industry"></i>
                        <p>
                            FÁBRICA SOPLADOS
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('production.report') }}" class="nav-link {{ Request::is('production-report') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Reporte Producción</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('soplados.formulas') }}" class="nav-link {{ Request::is('soplados/formulas') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Configuración de Recetas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('soplados.shifts') }}" class="nav-link {{ Request::is('soplados/shifts') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Historial de Turnos</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endunlessrole

                {{-- MÓDULO: FÁBRICA BOLSAS --}}
                @unlessrole('Driver')
                @module('module_production')
                @can('production.index')
                <li class="nav-item {{ Request::is('production*') ? 'menu-open' : '' }}">
                    <a href="#" class="nav-link {{ Request::is('production*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-shopping-bag"></i>
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
                @endcan
                @endmodule
                @endunlessrole

                {{-- MÓDULO 4: FINANZAS Y AUDITORÍA --}}
                @unlessrole('Driver')
                @canany(['cash_register.close', 'customer_statement.index', 'reports.financial', 'zelle_index', 'bank_index', 'payments.approve_custom_rate'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-file-invoice-dollar"></i>
                        <p>
                            FINANZAS Y AUDITORÍA
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('cash_register.close')
                        <li class="nav-item {{ Request::is('cash-register*') || Request::is('cash-count*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('cash-register*') || Request::is('cash-count*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Control de Caja
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('cash-register.close') }}" class="nav-link {{ Request::is('cash-register/close') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Cerrar Caja</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('cash.count') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Historial Arqueos</p>
                                    </a>
                                </li>
                            </ul>
                        </li>
                        @endcan

                        <li class="nav-item {{ Request::is('reports/accounts-*') || Request::is('customer-statement*') || Request::is('reports/returns*') || Request::is('pos/debit-notes*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('reports/accounts-*') || Request::is('customer-statement*') || Request::is('reports/returns*') || Request::is('pos/debit-notes*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Cartera y Crédito
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('reports.financial')
                                <li class="nav-item">
                                    <a href="{{ route('reports.accounts.receivable') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Cuentas por Cobrar</p>
                                    </a>
                                </li>
                                @endcan
                                @can('customer_statement.index')
                                <li class="nav-item">
                                    <a href="{{ route('customer-statement') }}" class="nav-link {{ Request::is('customer-statement') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Estado de Cuenta</p>
                                    </a>
                                </li>
                                @endcan
                                @can('reports.sales')
                                <li class="nav-item">
                                    <a href="{{ route('reports.returns') }}" class="nav-link {{ Request::is('reports/returns*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Notas de Crédito</p>
                                    </a>
                                </li>
                                @endcan
                                @can('manage_debit_notes')
                                <li class="nav-item">
                                    <a href="{{ route('pos.debit-notes') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Notas de Débito</p>
                                    </a>
                                </li>
                                @endcan
                                @module('module_purchases')
                                @can('reports.financial')
                                <li class="nav-item">
                                    <a href="{{ route('reports.accounts.payables') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Cuentas por Pagar</p>
                                    </a>
                                </li>
                                @endcan
                                @endmodule
                            </ul>
                        </li>

                        @module('module_advanced_payments')
                        @canany(['zelle_index', 'bank_index', 'payments.approve_custom_rate'])
                        <li class="nav-item {{ Request::is('consultation*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('consultation*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Auditoría Pagos
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('zelle_index')
                                <li class="nav-item">
                                    <a href="{{ route('consultation.zelle') }}" class="nav-link {{ Request::is('consultation/zelle*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Pagos Zelle</p>
                                    </a>
                                </li>
                                @endcan
                                @can('bank_index')
                                <li class="nav-item">
                                    <a href="{{ route('consultation.bank') }}" class="nav-link {{ Request::is('consultation/bank*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Pagos Bancarios</p>
                                    </a>
                                </li>
                                @endcan
                                @can('payments.approve_custom_rate')
                                <li class="nav-item">
                                    <a href="{{ route('consultation.approvals') }}" class="nav-link {{ Request::is('consultation/approvals*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Aprobación de Tasas</p>
                                    </a>
                                </li>
                                @endcan
                            </ul>
                        </li>
                        @endcanany
                        @endmodule
                    </ul>
                </li>
                @endcanany
                @endunlessrole

                {{-- MÓDULO 5: ENTIDADES Y MAESTROS --}}
                @unlessrole('Driver')
                @canany(['customers.index', 'suppliers.index', 'categories.index', 'inventory.index'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-folder"></i>
                        <p>
                            REGISTROS MAESTROS
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('customers.index')
                        <li class="nav-item">
                            <a href="{{ route('customers') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Clientes</p>
                            </a>
                        </li>
                        @endcan
                        @can('suppliers.index')
                        <li class="nav-item">
                            <a href="{{ route('suppliers') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Proveedores</p>
                            </a>
                        </li>
                        @endcan
                        @can('categories.index')
                        <li class="nav-item">
                            <a href="{{ route('categories') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Categorías</p>
                            </a>
                        </li>
                        @endcan
                        @module('module_multi_warehouse')
                        @can('inventory.index')
                        <li class="nav-item">
                            <a href="{{ route('warehouses') }}" class="nav-link {{ Request::is('warehouses') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Depósitos / Almacenes</p>
                            </a>
                        </li>
                        @endcan
                        @endmodule
                    </ul>
                </li>
                @endcanany
                @endunlessrole

                {{-- MÓDULO 6: CENTRO DE REPORTES --}}
                @unlessrole('Driver')
                @canany(['reports.sales', 'reports.purchases', 'reports.financial', 'reports.stock'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-chart-line"></i>
                        <p>
                            CENTRO DE REPORTES
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item {{ Request::is('reports/sales*') || Request::is('reports/daily-sales*') || Request::is('reports/payment-relationship*') || Request::is('reports/customer-payment*') || Request::is('reports/weekly-income*') || Request::is('reports/monthly-income*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('reports/sales*') || Request::is('reports/daily-sales*') || Request::is('reports/payment-relationship*') || Request::is('reports/customer-payment*') || Request::is('reports/weekly-income*') || Request::is('reports/monthly-income*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Ventas y Cobros
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('reports.sales')
                                  <li class="nav-item">
                                      <a href="{{ route('reports.sales') }}" class="nav-link">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Reporte de Ventas</p>
                                      </a>
                                  </li>
                                  <li class="nav-item">
                                      <a href="{{ route('reports.daily.sales') }}" class="nav-link">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Ventas Diarias</p>
                                      </a>
                                  </li>
                                  <li class="nav-item">
                                      <a href="{{ route('reports.payment.relationship') }}" class="nav-link">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Relación de Cobros</p>
                                      </a>
                                  </li>
                                  <li class="nav-item">
                                      <a href="{{ route('reports.weekly.income') }}" class="nav-link {{ Request::is('reports/weekly-income*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Reporte Semanal de Ingresos</p>
                                      </a>
                                  </li>
                                  <li class="nav-item">
                                      <a href="{{ route('reports.monthly.income') }}" class="nav-link {{ Request::is('reports/monthly-income*') ? 'active' : '' }}">
                                          <i class="far fa-dot-circle nav-icon"></i>
                                          <p>Reporte Mensual de Ingresos</p>
                                      </a>
                                  </li>
                                @endcan
                                @can('reports.customer_payment_relationship')
                                <li class="nav-item">
                                    <a href="{{ route('reports.customer.payment.relationship') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Cobros por Cliente</p>
                                    </a>
                                </li>
                                @endcan
                            </ul>
                        </li>

                        <li class="nav-item {{ Request::is('reports/inventory*') || Request::is('reports/movements*') || Request::is('reports/best-sellers*') || Request::is('reports/rotation*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('reports/inventory*') || Request::is('reports/movements*') || Request::is('reports/best-sellers*') || Request::is('reports/rotation*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Stock y Desempeño
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('reports.inventory') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Inventario Actual</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('reports.movements') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Kardex (Movimientos)</p>
                                    </a>
                                </li>
                                @can('reports.audit')
                                <li class="nav-item">
                                    <a href="{{ route('reports.audit') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Auditoría de Stock</p>
                                    </a>
                                </li>
                                @endcan
                                <li class="nav-item">
                                    <a href="{{ route('reports.best.sellers') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Más Vendidos</p>
                                    </a>
                                </li>
                                @module('module_advanced_reports')
                                <li class="nav-item">
                                    <a href="{{ route('reports.rotation') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Rotación de Stock</p>
                                    </a>
                                </li>
                                @endmodule
                            </ul>
                        </li>
                    </ul>
                </li>
                @endcanany
                @endunlessrole

                {{-- MÓDULO 7: ADMINISTRACIÓN Y CONFIGURACIÓN --}}
                @unlessrole('Driver')
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>
                            ADMINISTRACIÓN
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @canany(['users.index', 'roles.index', 'permissions.assign'])
                        <li class="nav-item {{ Request::is('users*') || Request::is('roles*') || Request::is('asignar*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('users*') || Request::is('roles*') || Request::is('asignar*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Equipo de Trabajo
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @can('users.index')
                                <li class="nav-item">
                                    <a href="{{ route('users') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Usuarios</p>
                                    </a>
                                </li>
                                @endcan
                                @module('module_roles')
                                @can('roles.index')
                                <li class="nav-item">
                                    <a href="{{ route('roles') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Roles y Permisos</p>
                                    </a>
                                </li>
                                @endcan
                                @can('permissions.assign')
                                <li class="nav-item">
                                    <a href="{{ route('asignar') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Asignación</p>
                                    </a>
                                </li>
                                @endcan
                                @endmodule
                            </ul>
                        </li>
                        @endcanany

                        @can('collections.audit')
                        <li class="nav-item">
                            <a href="{{ route('audit.sheet') }}" class="nav-link {{ Request::is('audit/sheet*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Auditoría de Cobranza</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('audit.invoices') }}" class="nav-link {{ Request::is('audit/invoices*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Auditoría de Facturas</p>
                            </a>
                        </li>
                        @endcan

                        @can('settings.index')
                        <li class="nav-item">
                            <a href="{{ route('devices') }}" class="nav-link {{ Request::is('devices') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Dispositivos</p>
                            </a>
                        </li>

                        <li class="nav-item {{ Request::is('settings/whatsapp*') || Request::is('settings/email*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('settings/whatsapp*') || Request::is('settings/email*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Mensajería
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('settings.whatsapp') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>WhatsApp Config</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('settings.whatsapp_outbox') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>WA Bandeja</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('settings.email') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Email Config</p>
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a href="{{ route('settings.email_outbox') }}" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Email Bandeja</p>
                                    </a>
                                </li>
                            </ul>
                        </li>

                        <li class="nav-item {{ Request::is('settings') || Request::is('updates*') || Request::is('backups*') || Request::is('settings/license-generator*') ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ Request::is('settings') || Request::is('updates*') || Request::is('backups*') || Request::is('settings/license-generator*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>
                                    Ajustes Globales
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                <li class="nav-item">
                                    <a href="{{ route('settings') }}" class="nav-link {{ Request::is('settings') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Configuración</p>
                                    </a>
                                </li>
                                @module('module_updates')
                                @can('settings.update')
                                <li class="nav-item">
                                    <a href="{{ route('updates') }}" class="nav-link {{ Request::is('updates') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Actualizaciones</p>
                                    </a>
                                </li>
                                @endcan
                                @endmodule
                                @module('module_backups')
                                @can('settings.backups')
                                <li class="nav-item">
                                    <a href="{{ route('backups') }}" class="nav-link {{ Request::is('backups') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Respaldos</p>
                                    </a>
                                </li>
                                @endcan
                                @endmodule
                                <li class="nav-item">
                                    <a href="javascript:void(0)" onclick="Livewire.dispatch('trigger-license-modal')" class="nav-link">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Licencia</p>
                                    </a>
                                </li>
                                @role('Super Admin')
                                <li class="nav-item">
                                    <a href="{{ route('settings.license_generator') }}" class="nav-link {{ Request::is('settings/license-generator*') ? 'active' : '' }}">
                                        <i class="far fa-dot-circle nav-icon"></i>
                                        <p>Generador SaaS</p>
                                    </a>
                                </li>
                                @endrole
                            </ul>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endunlessrole

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
