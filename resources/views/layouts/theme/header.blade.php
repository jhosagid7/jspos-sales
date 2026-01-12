<nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
        <li class="nav-item">
            <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
            <a href="{{ route('sales') }}" class="nav-link">Ventas</a>
        </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
        <!-- Navbar Search -->
        <li class="nav-item">
            <a class="nav-link" data-widget="navbar-search" href="#" role="button">
                <i class="fas fa-search"></i>
            </a>
            <div class="navbar-search-block">
                <form class="form-inline">
                    <div class="input-group input-group-sm">
                        <input class="form-control form-control-navbar" type="search" placeholder="Search" aria-label="Search">
                        <div class="input-group-append">
                            <button class="btn btn-navbar" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                            <button class="btn btn-navbar" type="button" data-widget="navbar-search">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </li>

        <!-- License Warning -->
        @if(isset($license_days_remaining) && $license_days_remaining <= 5)
        <li class="nav-item">
            <a class="nav-link text-danger font-weight-bold" href="#" title="Su licencia vence pronto">
                <i class="fas fa-exclamation-triangle"></i>
                Licencia vence en {{ $license_days_remaining }} días
            </a>
        </li>
        @endif

        <!-- Notifications Dropdown Menu -->
        <!-- Purchases Notifications -->
        @if ($noty_purchases->count() > 0)
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-shopping-cart"></i>
                <span class="badge badge-danger navbar-badge">{{ $noty_purchases->count() }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="max-height: 300px; overflow-y: auto;">
                <span class="dropdown-item dropdown-header">{{ $noty_purchases->count() }} Cuentas por Pagar</span>
                <div class="dropdown-divider"></div>
                @if(isset($total_payables) && $total_payables > 0)
                <span class="dropdown-item dropdown-header text-danger font-weight-bold">Total Vencido: ${{ number_format($total_payables, 2) }}</span>
                <div class="dropdown-divider"></div>
                @endif
                @foreach ($noty_purchases as $npurchase)
                    <a href="{{ route('reports.accounts.payables', ['s' => $npurchase->supplier_id]) }}" class="dropdown-item">
                        <div class="media">
                            <div class="media-body">
                                <h3 class="dropdown-item-title font-weight-bold">
                                    {{ $npurchase->supplier->name }}
                                    <span class="float-right text-sm text-danger"><i class="fas fa-clock"></i> {{ app('fun')->overdue($npurchase->created_at, now()) - $credit_purchase_days }}d</span>
                                </h3>
                                <p class="text-sm">Compra #{{ $npurchase->id }}</p>
                                <p class="text-sm text-muted"><i class="fas fa-money-bill-wave mr-1"></i> Deuda: ${{ number_format($npurchase->debt, 2) }}</p>
                            </div>
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                @endforeach
            </div>
        </li>
        @endif

        <!-- Sales Notifications -->
        @if ($noty_sales->count() > 0)
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span class="badge badge-warning navbar-badge">{{ $noty_sales->count() }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="max-height: 300px; overflow-y: auto;">
                            <span class="dropdown-item dropdown-header">{{ $noty_sales->count() }} Notificaciones</span>
                            <div class="dropdown-divider"></div>
                            @if(isset($total_receivables) && $total_receivables > 0)
                            <span class="dropdown-item dropdown-header text-danger font-weight-bold">Total Vencido: ${{ number_format($total_receivables, 2) }}</span>
                            <div class="dropdown-divider"></div>
                            @endif
                @foreach ($noty_sales as $nsale)
                    <a href="{{ route('reports.accounts.receivable', ['c' => $nsale->customer_id]) }}" class="dropdown-item">
                        <div class="media">
                            <div class="media-body">
                                <h3 class="dropdown-item-title font-weight-bold">
                                    {{ $nsale->customer->name }}
                                    <span class="float-right text-sm text-danger"><i class="fas fa-clock"></i> {{ app('fun')->overdue($nsale->created_at, now()) - $credit_days }}d</span>
                                </h3>
                                <p class="text-sm">Venta #{{ $nsale->id }}</p>
                                <p class="text-sm text-muted"><i class="fas fa-hand-holding-usd mr-1"></i> Deuda: ${{ number_format($nsale->debt, 2) }}</p>
                            </div>
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                @endforeach
            </div>
        </li>
        @endif

        <!-- Commissions Notifications -->
        @if (isset($noty_commissions) && $noty_commissions->count() > 0)
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-hand-holding-usd"></i>
                <span class="badge badge-success navbar-badge">{{ $noty_commissions->count() }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right" style="max-height: 300px; overflow-y: auto;">
                <span class="dropdown-item dropdown-header">{{ $noty_commissions->count() }} Comisiones Pendientes</span>
                <div class="dropdown-divider"></div>
                @if(isset($total_commissions) && $total_commissions > 0)
                <span class="dropdown-item dropdown-header text-success font-weight-bold">Total Pendiente: ${{ number_format($total_commissions, 2) }}</span>
                <div class="dropdown-divider"></div>
                @endif
                @foreach ($noty_commissions as $ncomm)
                    <a href="{{ route('commissions') }}" class="dropdown-item">
                        <div class="media">
                            <div class="media-body">
                                <h3 class="dropdown-item-title font-weight-bold">
                                    {{ $ncomm->customer->name ?? 'Cliente N/A' }}
                                    <span class="float-right text-sm text-success">
                                        ${{ number_format($ncomm->final_commission_amount ?? 0, 2) }}
                                    </span>
                                </h3>
                                <p class="text-sm">Venta #{{ $ncomm->id }}</p>
                                <p class="text-sm text-muted">
                                    <i class="far fa-clock mr-1"></i> 
                                    {{ $ncomm->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>
                    </a>
                    <div class="dropdown-divider"></div>
                @endforeach
            </div>
        </li>
        @endif

        <!-- User Dropdown Menu -->
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-user"></i>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">
                    @guest Invitado @else {{ Auth()->user()->name }} @endguest
                </span>
                <div class="dropdown-divider"></div>
                <a href="{{ route('profile.edit') }}" class="dropdown-item">
                    <i class="fas fa-user mr-2"></i> Perfil
                </a>
                <div class="dropdown-divider"></div>
                <a href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="dropdown-item">
                    <i class="fas fa-sign-out-alt mr-2"></i> Salir
                </a>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                    @csrf
                </form>
            </div>
        </li>
        <li class="nav-item">
            <a class="nav-link" data-widget="control-sidebar" data-slide="true" href="#" role="button">
                <i class="fas fa-th-large"></i>
            </a>
        </li>
    </ul>
</nav>
