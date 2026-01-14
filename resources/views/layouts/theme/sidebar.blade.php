<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    @php
        $config = \App\Models\Configuration::first();
        $logo = $config && $config->logo ? asset('storage/' . $config->logo) : asset('assets/images/logo/logo-icon.png');
        $appName = $config && $config->business_name ? $config->business_name : 'JSPOS v1.7';
    @endphp
    <a href="{{ route('sales') }}" class="brand-link">
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
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <li class="nav-item">
                    <a href="{{ route('welcome') }}" class="nav-link {{ Request::is('welcome') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tachometer-alt"></i>
                        <p>DASHBOARD</p>
                    </a>
                </li>
                
                @can('ventas')
                <li class="nav-item">
                    <a href="{{ route('sales') }}" class="nav-link {{ Request::is('sales') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-shopping-cart"></i>
                        <p>VENTAS</p>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="{{ route('cash-register.close') }}" class="nav-link {{ Request::is('cash-register/close') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-cash-register"></i>
                        <p>CERRAR CAJA</p>
                    </a>
                </li>
                @endcan

                @can('compras')
                <li class="nav-item">
                    <a href="{{ route('purchases') }}" class="nav-link {{ Request::is('purchases') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-truck"></i>
                        <p>COMPRAS</p>
                    </a>
                </li>
                @endcan

                @can('personal')
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-users"></i>
                        <p>
                            PERSONAL
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('usuarios')
                        <li class="nav-item">
                            <a href="{{ route('users') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Usuarios</p>
                            </a>
                        </li>
                        @endcan
                        @can('roles')
                        <li class="nav-item">
                            <a href="{{ route('roles') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Roles y Permisos</p>
                            </a>
                        </li>
                        @endcan
                        @can('asignacion')
                        <li class="nav-item">
                            <a href="{{ route('asignar') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Asignación</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcan

                @can('catalogos')
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-folder"></i>
                        <p>
                            CATALOGOS
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('clientes')
                        <li class="nav-item">
                            <a href="{{ route('customers') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Clientes</p>
                            </a>
                        </li>
                        @endcan
                        @can('categorias')
                        <li class="nav-item">
                            <a href="{{ route('categories') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Categorías</p>
                            </a>
                        </li>
                        @endcan
                        @can('proveedores')
                        <li class="nav-item">
                            <a href="{{ route('suppliers') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Proveedores</p>
                            </a>
                        </li>
                        @endcan
                        @can('productos')
                        <li class="nav-item">
                            <a href="{{ route('products') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Productos</p>
                            </a>
                        </li>
                        @endcan
                    </ul>
                </li>
                @endcan

                @can('reportes')
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-chart-bar"></i>
                        <p>
                            REPORTES
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        @can('reporte-ventas')
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
                        @endcan
                        @can('reporte-compras')
                        <li class="nav-item">
                            <a href="{{ route('reports.purchases') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Compras</p>
                            </a>
                        </li>
                        @endcan
                        @can('reporte-cuentas-cobrar')
                        <li class="nav-item">
                            <a href="{{ route('reports.accounts.receivable') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Cuentas por Cobrar</p>
                            </a>
                        </li>
                        @endcan
                        @can('reporte-cuentas-pagar')
                        <li class="nav-item">
                            <a href="{{ route('reports.accounts.payables') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Cuentas por Pagar</p>
                            </a>
                        </li>
                        @endcan
                        @can('reporte-cuentas-cobrar')
                        <li class="nav-item">
                            <a href="{{ route('reports.payment.relationship') }}" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Relación de Pagos</p>
                            </a>
                        </li>
                        @endcan
                        @can('corte-de-caja')
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
                    </ul>
                </li>
                @endcan

                @can('gestionar_comisiones')
                <li class="nav-item">
                    <a href="{{ route('commissions') }}" class="nav-link {{ Request::is('commissions') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-hand-holding-usd"></i>
                        <p>COMISIONES</p>
                    </a>
                </li>
                @endcan

                @can('inventarios')
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
                    </ul>
                </li>
                @endcan

                @can('settings')
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
                        <li class="nav-item">
                            <a href="{{ route('updates') }}" class="nav-link {{ Request::is('updates') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Actualizaciones</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="{{ route('backups') }}" class="nav-link {{ Request::is('backups') ? 'active' : '' }}">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Copias de Seguridad</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endcan

            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
