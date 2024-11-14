<div class="sidebar-wrapper" sidebar-layout="stroke-svg">
    <div>
        <div class="logo-wrapper"><a href="index.html"><img class="img-fluid for-light" src="../assets/images/logo/logo.png"
                    alt=""><img class="img-fluid for-dark" src="../assets/images/logo/logo_dark.png"
                    alt=""></a>
            <div class="back-btn"><i class="fa fa-angle-left"></i></div>
            <div class="toggle-sidebar"><i class="status_toggle middle sidebar-toggle" data-feather="grid">
                </i></div>
        </div>
        <div class="logo-icon-wrapper"><a href="index.html"><img class="img-fluid"
                    src="../assets/images/logo/logo-icon.png" alt=""></a>
        </div>
        <nav class="sidebar-main">
            <div class="left-arrow" id="left-arrow"><i data-feather="arrow-left"></i></div>
            <div id="sidebar-menu">
                <ul class="sidebar-links" id="simple-bar">
                    <li class="back-btn">
                        <div class="mobile-back text-end"><span>Back</span><i class="fa fa-angle-right ps-2"
                                aria-hidden="true"></i></div>
                    </li>
                    <li class="pin-title sidebar-main-title">
                        <div>
                            <h6>Pinned</h6>
                        </div>
                    </li>

                    @can('ventas')
                        <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a
                                class="sidebar-link sidebar-title link-nav" href="{{ route('sales') }}">
                                <i class="icon-shopping-cart-full" style="font-size: 24px"></i>
                                <svg class="fill-icon">
                                    <use href="../assets/svg/icon-sprite.svg#fill-contact"> </use>
                                </svg><span>VENTAS</span></a>
                        </li>
                    @endcan

                    @can('compras')
                        <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a
                                class="sidebar-link sidebar-title link-nav" href="{{ route('purchases') }}">
                                <i class="icon-truck" style="font-size: 24px"></i>
                                <svg class="fill-icon">
                                    <use href="../assets/svg/icon-sprite.svg#fill-contact"> </use>
                                </svg><span>COMPRAS</span></a>
                        </li>
                    @endcan

                    @can('personal')
                        <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a class="sidebar-link sidebar-title"
                                href="#">
                                <i class="icon-user" style="font-size: 24px"></i>
                                <svg class="fill-icon">
                                    <use href="../assets/svg/icon-sprite.svg#fill-starter-kit"></use>
                                </svg><span>PERSONAL</span></a>
                            <ul class="sidebar-submenu">

                                @can('usuarios')
                                    <li><a href="{{ route('users') }}"><span>Usuarios</span></a></li>
                                @endcan
                                @can('roles')
                                    <li><a href="{{ route('roles') }}"><span>Roles y Permisos</span></a></li>
                                @endcan
                                @can('asignacion')
                                    <li><a href="{{ route('asignar') }}"><span>Asignación</span></a></li>
                                @endcan

                            </ul>
                        </li>
                    @endcan


                    @can('catalogos')
                        <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a class="sidebar-link sidebar-title"
                                href="#">
                                <i class="icon-folder" style="font-size: 24px"></i>
                                <svg class="fill-icon">
                                    <use href="../assets/svg/icon-sprite.svg#fill-starter-kit"></use>
                                </svg><span>CATALOGOS</span></a>
                            <ul class="sidebar-submenu">

                                @can('clientes')
                                    <li><a href="{{ route('customers') }}"><span>Clientes </span></a></li>
                                @endcan


                                @can('categorias')
                                    <li><a href="{{ route('categories') }}"><span>Categorías </span></a></li>
                                @endcan

                                @can('proveedores')
                                    <li><a href="{{ route('suppliers') }}"><span>Proveedores </span></a></li>
                                @endcan

                                @can('productos')
                                    <li><a href="{{ route('products') }}"><span>Productos </span></a></li>
                                @endcan

                            </ul>
                        </li>
                    @endcan


                    @can('reportes')
                        <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a class="sidebar-link sidebar-title"
                                href="#">
                                <i class="icon-clipboard" style="font-size:24px"></i>
                                <svg class="fill-icon">
                                    <use href="../assets/svg/icon-sprite.svg#fill-starter-kit"></use>

                                </svg><span>REPORTES</span></a>
                            <ul class="sidebar-submenu">
                                @can('reporte-ventas')
                                    <li><a href="{{ route('reports.sales') }}"><span>Ventas</span></a></li>
                                @endcan
                                @can('reporte-compras')
                                    <li><a href="{{ route('reports.purchases') }}"><span>Compras</span></a></li>
                                @endcan
                                @can('reporte-cuentas-cobrar')
                                    <li><a href="{{ route('reports.accounts.receivable') }}"><span>Cuentas por
                                                Cobrar</span></a></li>
                                @endcan
                                @can('reporte-cuentas-pagar')
                                    <li><a href="{{ route('reports.accounts.payables') }}"><span>Cuentas por
                                                Pagar</span></a></li>
                                @endcan
                                @can('corte-de-caja')
                                    <li><a href="{{ route('cash.count') }}"><span>Corte de Caja</span></a></li>
                                @endcan

                            </ul>
                        </li>
                    @endcan

                    @can('inventarios')
                        <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a class="sidebar-link sidebar-title"
                                href="{{ route('inventories') }}">
                                <i class="icon-package " style="font-size:24px"></i>
                                <svg class="fill-icon">
                                    <use href="../assets/svg/icon-sprite.svg#fill-starter-kit"></use>
                                </svg><span>INVENTARIOS</span></a>
                        </li>
                    @endcan

                    @can('settings')
                        <li class="sidebar-list"><i class="fa fa-thumb-tack"></i><a class="sidebar-link sidebar-title"
                                href="#">
                                <i class="icon-desktop" style="font-size:24px"></i>
                                <span>SISTEMA</span></a>
                            <ul class="sidebar-submenu">
                                <li><a href="{{ route('settings') }}"><span>Settings <i
                                                class="icon-receipt"></i></span></a>
                                </li>
                        </li>
                    @endcan


                </ul>
            </div>
            <div class="right-arrow" id="right-arrow"><i data-feather="arrow-right"></i></div>
        </nav>
    </div>
</div>
