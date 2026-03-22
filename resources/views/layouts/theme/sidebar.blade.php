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

                @if($isDriver || $canSeeLogistics)
                <li class="nav-item">
                    <a href="{{ route('driver.dashboard') }}" class="nav-link {{ Request::is('driver/dashboard') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-truck-loading"></i>
                        <p>{{ $isDriver ? 'MI RUTA' : 'LOGÍSTICA / RUTAS' }}</p>
                    </a>
                </li>
                @endif
                
                @unlessrole('Driver')
                
                @can('sales.index')
                <li class="nav-item">
                    <a href="{{ route('sales') }}" class="nav-link {{ Request::is('sales*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-shopping-cart"></i>
                        <p>VENTAS</p>
                    </a>
                </li>
                @endcan
                @can('sales.generate_price_list')
                <li class="nav-item">
                    <a href="{{ route('price-list.index') }}" class="nav-link {{ Request::is('price-list') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-file-invoice-dollar"></i>
                        <p>LISTA DE PRECIOS</p>
                    </a>
                </li>
                @endcan
                @module('module_delivery')
                @can('distribution.map')
                <li class="nav-item">
                    <a href="{{ route('delivery.map') }}" class="nav-link {{ Request::is('delivery/map') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-map-marked-alt"></i>
                        <p>MAPA CHOFERES</p>
                    </a>
                </li>
                @endcan
                @endmodule
                @can('cash_register.close')
                <li class="nav-item">
                    <a href="{{ route('cash-register.close') }}" class="nav-link {{ Request::is('cash-register/close') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cash-register"></i>
                        <p>CERRAR CAJA</p>
                    </a>
                </li>
                @endcan

                @module('module_purchases')
                @can('purchases.index')
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-truck"></i>
                        <p>
                            COMPRAS
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('purchases') }}" class="nav-link {{ Request::is('purchases') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Nueva Compra</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('purchase.list') }}" class="nav-link {{ Request::is('purchase-list') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Listado</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endcan
                @endmodule

                @canany(['users.index', 'roles.index', 'permissions.assign'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        <p>
                            PERSONAL
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('users.index')
                        <li class="nav-item">
                            <a href="{{ route('users') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Usuarios</p>
                            </a>
                        </li>
                        @endcan
                        @module('module_roles')
                        @can('roles.index')
                        <li class="nav-item">
                            <a href="{{ route('roles') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Roles y Permisos</p>
                            </a>
                        </li>
                        @endcan
                        @can('permissions.assign')
                        <li class="nav-item">
                            <a href="{{ route('asignar') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Asignación</p>
                            </a>
                        </li>
                        @endcan
                        @endmodule
                    </ul>
                </li>
                @endcan

                @canany(['customers.index', 'categories.index', 'suppliers.index', 'products.index'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-folder"></i>
                        <p>
                            CATALOGOS
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
                        @can('categories.index')
                        <li class="nav-item">
                            <a href="{{ route('categories') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Categorías</p>
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
                        @can('products.index')
                        <li class="nav-item">
                            <a href="{{ route('products') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Productos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('price-groups') }}" class="nav-link {{ Request::is('price-groups') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Grupos de Precio</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcan
                
                @module('module_advanced_payments')
                @canany(['zelle_index', 'bank_index'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-search-dollar"></i>
                        <p>
                            CONSULTAS
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('zelle_index')
                        <li class="nav-item">
                            <a href="{{ route('consultation.zelle') }}" class="nav-link {{ Request::is('consultation/zelle*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Pagos Zelle</p>
                            </a>
                        </li>
                        @endcan
                        @can('bank_index')
                        <li class="nav-item">
                            <a href="{{ route('consultation.bank') }}" class="nav-link {{ Request::is('consultation/bank*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Pagos Bancarios</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcan
                @endmodule

                @canany(['reports.sales', 'reports.purchases', 'reports.financial', 'reports.stock'])
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>
                            REPORTES
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('reports.sales')
                        <li class="nav-item">
                            <a href="{{ route('reports.sales') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Ventas</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('reports.daily.sales') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Ventas Diarias</p>
                            </a>
                        </li>
                        @module('module_delivery')
                        <li class="nav-item">
                            <a href="{{ route('reports.dispatch') }}" class="nav-link {{ Request::is('reports/dispatch*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Relación de Despacho</p>
                            </a>
                        </li>
                        @endmodule
                        @endcan
                        @module('module_purchases')
                        @can('reports.purchases')
                        <li class="nav-item">
                            <a href="{{ route('reports.purchases') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Compras</p>
                            </a>
                        </li>
                        @endcan
                        @endmodule
                        @module('module_credits')
                        @can('reports.financial')
                        <li class="nav-item">
                            <a href="{{ route('reports.accounts.receivable') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Cuentas por Cobrar</p>
                            </a>
                        </li>
                        @endcan
                        @endmodule
                        @module('module_purchases')
                        @can('reports.financial')
                        <li class="nav-item">
                            <a href="{{ route('reports.accounts.payables') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Cuentas por Pagar</p>
                            </a>
                        </li>
                        @endcan
                        @endmodule
                        @module('module_credits')
                        @can('reports.sales')
                        <li class="nav-item">
                            <a href="{{ route('reports.payment.relationship') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Relación de Pagos</p>
                            </a>
                        </li>
                        @endcan
                        @endmodule
                        @can('cash_register.close')
                        <li class="nav-item">
                            <a href="{{ route('cash.count') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Corte de Caja</p>
                            </a>
                        </li>
                        @endcan
                        <li class="nav-item">
                            <a href="{{ route('reports.best.sellers') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Más Vendidos</p>
                            </a>
                        </li>
                        @module('module_advanced_reports')
                        <li class="nav-item">
                            <a href="{{ route('reports.rotation') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Rotación</p>
                            </a>
                        </li>
                        @endmodule
                    </ul>
                </li>
                @endcan

                @module('module_commissions')
                @can('reports.commissions')
                <li class="nav-item">
                    <a href="{{ route('commissions') }}" class="nav-link {{ Request::is('commissions') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-hand-holding-usd"></i>
                        <p>COMISIONES</p>
                    </a>
                </li>
                @endcan
                @endmodule

                @module('module_labels')
                @can('products.labels')
                <li class="nav-item">
                    <a href="{{ route('labels.index') }}" class="nav-link {{ Request::is('labels') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tags"></i>
                        <p>ETIQUETAS</p>
                    </a>
                </li>
                @endcan
                @endmodule

                @module('module_production')
                @can('production.index')
                <li class="nav-item">
                    <a href="{{ route('production.index') }}" class="nav-link {{ Request::is('production*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-industry"></i>
                        <p>PRODUCCIÓN</p>
                    </a>
                </li>
                @endcan
                @endmodule

                @can('inventory.index')
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-boxes"></i>
                        <p>
                            INVENTARIOS
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('inventories') }}" class="nav-link {{ Request::is('inventories') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Inventario General</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('cargos') }}" class="nav-link {{ Request::is('cargos*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Cargos / Ajustes</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('descargos') }}" class="nav-link {{ Request::is('descargos*') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Descargos / Salidas</p>
                            </a>
                        </li>
                        @module('module_multi_warehouse')
                        <li class="nav-item">
                            <a href="{{ route('warehouses') }}" class="nav-link {{ Request::is('warehouses') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Depósitos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('transfers') }}" class="nav-link {{ Request::is('transfers') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Traspasos</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('requisition') }}" class="nav-link {{ Request::is('requisition') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Requisición</p>
                            </a>
                        </li>
                        @endmodule
                    </ul>
                </li>
                @endcan

                @can('settings.index')
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>
                            SISTEMA
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="{{ route('settings') }}" class="nav-link {{ Request::is('settings') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Configuración</p>
                            </a>
                        </li>
                        @module('module_updates')
                        @can('settings.update')
                        <li class="nav-item">
                            <a href="{{ route('updates') }}" class="nav-link {{ Request::is('updates') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Actualizaciones</p>
                            </a>
                        </li>
                        @endcan
                        @endmodule
                        @module('module_backups')
                        @can('settings.backups')
                        <li class="nav-item">
                            <a href="{{ route('backups') }}" class="nav-link {{ Request::is('backups') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Copias de Seguridad</p>
                            </a>
                        </li>
                        @endcan
                        @endmodule
                        <li class="nav-item">
                            <a href="{{ route('devices') }}" class="nav-link {{ Request::is('devices') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Dispositivos</p>
                            </a>
                        </li>
                        @module('module_whatsapp')
                        <li class="nav-item">
                            <a href="{{ route('settings.whatsapp') }}" class="nav-link {{ Request::is('settings/whatsapp*') && !Request::is('settings/whatsapp-outbox*') ? 'active' : '' }}">
                                <i class="fab fa-whatsapp nav-icon"></i>
                                <p>WhatsApp Config</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('settings.whatsapp_outbox') }}" class="nav-link {{ Request::is('settings/whatsapp-outbox*') ? 'active' : '' }}">
                                <i class="fas fa-inbox nav-icon"></i>
                                <p>WhatsApp Bandeja</p>
                            </a>
                        </li>
                        @endmodule
                        <li class="nav-item">
                            <a href="javascript:void(0)" onclick="Livewire.dispatch('trigger-license-modal')" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Licencia</p>
                            </a>
                        </li>
                        @role('Super Admin')
                        <li class="nav-item">
                            <a href="{{ route('settings.license_generator') }}" class="nav-link {{ Request::is('settings/license-generator*') ? 'active' : '' }}">
                                <i class="fas fa-key nav-icon"></i>
                                <p>SaaS Generador</p>
                            </a>
                        </li>
                        @endrole
                    </ul>
                </li>
                @endcan

                @endunlessrole
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
