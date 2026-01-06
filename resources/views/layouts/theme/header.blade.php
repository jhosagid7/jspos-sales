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
        @if (session()->has('noty_purchases') || session()->has('noty_sales'))
        <li class="nav-item dropdown">
            <a class="nav-link" data-toggle="dropdown" href="#">
                <i class="far fa-bell"></i>
                <span class="badge badge-warning navbar-badge">
                    {{ (session('noty_purchases') ? session('noty_purchases')->count() : 0) + (session('noty_sales') ? session('noty_sales')->count() : 0) }}
                </span>
            </a>
            <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
                <span class="dropdown-item dropdown-header">Notificaciones</span>
                <div class="dropdown-divider"></div>
                @if (session()->has('noty_purchases'))
                    @foreach (session('noty_purchases') as $npurchase)
                        <a href="{{ route('reports.accounts.payables') }}" class="dropdown-item">
                            <i class="fas fa-envelope mr-2"></i> Compra #{{ $npurchase->id }}
                            <span class="float-right text-muted text-sm">{{ app('fun')->overdue($npurchase->created_at, now()) }} días</span>
                        </a>
                        <div class="dropdown-divider"></div>
                    @endforeach
                @endif
                @if (session()->has('noty_sales'))
                    @foreach (session('noty_sales') as $nsale)
                        <a href="{{ route('reports.accounts.receivable') }}" class="dropdown-item">
                            <i class="fas fa-file mr-2"></i> Venta #{{ $nsale->id }}
                            <span class="float-right text-muted text-sm">{{ app('fun')->overdue($nsale->created_at, now()) }} días</span>
                        </a>
                        <div class="dropdown-divider"></div>
                    @endforeach
                @endif
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
