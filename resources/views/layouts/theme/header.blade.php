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

        <!-- Notifications Dropdown Menu -->
        <!-- Purchases Notifications -->
        @if ($noty_purchases->count() > 0)
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="fas fa-shopping-cart"></i>
                <span class="badge badge-danger navbar-badge">{{ $noty_purchases->count() }}</span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">{{ $noty_purchases->count() }} Cuentas por Pagar</span>
                <div class="dropdown-divider"></div>
                @foreach ($noty_purchases as $npurchase)
                    <a href="{{ route('reports.accounts.payables', ['s' => $npurchase->supplier_id]) }}" class="dropdown-item">
                        <div class="media">
                            <div class="media-body">
                                <h3 class="dropdown-item-title font-weight-bold">
                                    {{ $npurchase->supplier->name }}
                                    <span class="float-right text-sm text-danger"><i class="fas fa-clock"></i> {{ app('fun')->overdue($npurchase->created_at, now()) }}d</span>
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
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">{{ $noty_sales->count() }} Cuentas por Cobrar</span>
                <div class="dropdown-divider"></div>
                @foreach ($noty_sales as $nsale)
                    <a href="{{ route('reports.accounts.receivable', ['c' => $nsale->customer_id]) }}" class="dropdown-item">
                        <div class="media">
                            <div class="media-body">
                                <h3 class="dropdown-item-title font-weight-bold">
                                    {{ $nsale->customer->name }}
                                    <span class="float-right text-sm text-danger"><i class="fas fa-clock"></i> {{ app('fun')->overdue($nsale->created_at, now()) }}d</span>
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
    </ul>
</nav>
