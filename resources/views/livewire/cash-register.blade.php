<div>
    <div class="card">
        <div class="card-header">
            <h5>Gestión de Caja Registradora</h5>
        </div>
        <div class="card-body">
            <div class="row g-xl-5 g-3">
                {{-- Sidebar de Pestañas --}}
                <div class="col-xxl-3 col-xl-4 box-col-4e sidebar-left-wrapper">
                    <ul class="sidebar-left-icons nav nav-pills" id="cash-register-pills-tab" role="tablist">
                        @if($hasOpenRegister)
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 1 ? 'active' : '' }}" wire:click.prevent="$set('tab',1)"
                                href="#close-register" role="tab">
                                <div class="nav-rounded">
                                    <div class="product-icons">
                                        <svg class="stroke-icon">
                                            <use href="../assets/svg/icon-sprite.svg#stroke-ecommerce"></use>
                                        </svg>
                                    </div>
                                </div>
                                <div class="product-tab-content">
                                    <h6>Cerrar Caja</h6>
                                    <p>Arqueo y cierre de turno</p>
                                </div>
                            </a>
                        </li>
                        @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('cash-register.open') }}">
                                <div class="nav-rounded">
                                    <div class="product-icons">
                                        <svg class="stroke-icon">
                                            <use href="../assets/svg/icon-sprite.svg#stroke-home"></use>
                                        </svg>
                                    </div>
                                </div>
                                <div class="product-tab-content">
                                    <h6>Abrir Caja</h6>
                                    <p>Iniciar nuevo turno</p>
                                </div>
                            </a>
                        </li>
                        @endif

                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 2 ? 'active' : '' }}" wire:click.prevent="$set('tab',2)"
                                href="#history-register" role="tab">
                                <div class="nav-rounded">
                                    <div class="product-icons">
                                        <svg class="stroke-icon">
                                            <use href="../assets/svg/icon-sprite.svg#stroke-calendar"></use>
                                        </svg>
                                    </div>
                                </div>
                                <div class="product-tab-content">
                                    <h6>Historial de Cajas</h6>
                                    <p>Consultar cierres anteriores</p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Contenido de las Pestañas --}}
                <div class="col-xxl-9 col-xl-8 box-col-8 position-relative">
                    <div class="tab-content">
                        
                        {{-- TAB 1: CERRAR CAJA --}}
                        @if($hasOpenRegister)
                        <div class="tab-pane fade {{ $tab == 1 ? 'active show' : '' }}" id="close-register" role="tabpanel">
                            <div class="sidebar-body">
                                @if (session()->has('error'))
                                    <div class="alert alert-danger">{{ session('error') }}</div>
                                @endif
                                @error('general')
                                    <div class="alert alert-danger">{{ $message }}</div>
                                @enderror

                                <div class="row">
                                    <div class="col-md-12">
                                        <form wire:submit.prevent="closeRegister">
                                            <div class="table-responsive mb-4">
                                                <table class="table table-bordered table-hover">
                                                    <thead class="bg-light">
                                                        <tr>
                                                            <th>Moneda</th>
                                                            <th>Saldo Sistema (Esperado)</th>
                                                            <th width="25%">Efectivo Contado (Real)</th>
                                                            <th>Diferencia</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($currencies as $currency)
                                                        <tr>
                                                            <td class="align-middle">
                                                                <span class="fw-bold">{{ $currency->label }}</span>
                                                                <small class="text-muted">({{ $currency->symbol }})</small>
                                                            </td>
                                                            <td class="align-middle">
                                                                {{ $currency->symbol }} {{ number_format($expectedAmounts[$currency->code] ?? 0, 2) }}
                                                            </td>
                                                            <td>
                                                                <div class="input-group input-group-sm">
                                                                    <span class="input-group-text">{{ $currency->symbol }}</span>
                                                                    <input type="number" step="0.01" class="form-control" 
                                                                           wire:model.live="countedAmounts.{{ $currency->code }}">
                                                                </div>
                                                                @error("countedAmounts.{$currency->code}") 
                                                                    <small class="text-danger">{{ $message }}</small> 
                                                                @enderror
                                                            </td>
                                                            <td class="align-middle">
                                                                @php
                                                                    $diff = $differences[$currency->code] ?? 0;
                                                                    $class = $diff == 0 ? 'text-success' : ($diff < 0 ? 'text-danger' : 'text-primary');
                                                                    $icon = $diff == 0 ? 'fa-check' : ($diff < 0 ? 'fa-minus-circle' : 'fa-plus-circle');
                                                                @endphp
                                                                <span class="{{ $class }} fw-bold">
                                                                    <i class="fas {{ $icon }}"></i>
                                                                    {{ $currency->symbol }} {{ number_format($diff, 2) }}
                                                                </span>
                                                            </td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>

                                            <div class="card border-info border-2 mb-4">
                                                <div class="card-header bg-info p-2">
                                                    <h5 class="m-0 text-white">
                                                        <i class="fas fa-money-bill-wave"></i> VENTAS DEL DÍA (Informativo)
                                                    </h5>
                                                </div>
                                                <div class="card-body">
                                                    <div class="row">
                                                        {{-- Efectivo --}}
                                                        <div class="col-md-4 mb-3">
                                                            <div class="card h-100 border-primary">
                                                                <div class="card-header bg-light-primary p-2">
                                                                    <h6 class="mb-0 text-primary"><i class="fas fa-money-bill"></i> Efectivo</h6>
                                                                </div>
                                                                <div class="card-body p-2">
                                                                    @if (!empty($salesByCurrency['cash']))
                                                                        <table class="table table-sm table-borderless mb-0">
                                                                            @foreach ($salesByCurrency['cash'] as $currencyCode => $amount)
                                                                                <tr>
                                                                                    <td class="text-muted">{{ $currencyCode }}:</td>
                                                                                    <td class="text-end fw-bold">{{ number_format($amount, 2) }}</td>
                                                                                </tr>
                                                                            @endforeach
                                                                        </table>
                                                                    @else
                                                                        <p class="text-muted mb-0 text-center">Sin movimientos</p>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>

                                                        {{-- Banco --}}
                                                        <div class="col-md-4 mb-3">
                                                            <div class="card h-100 border-primary">
                                                                <div class="card-header bg-light-primary p-2">
                                                                    <h6 class="mb-0 text-primary"><i class="fas fa-university"></i> Banco</h6>
                                                                </div>
                                                                <div class="card-body p-2">
                                                                    @if (!empty($salesByCurrency['deposit']))
                                                                        @foreach ($salesByCurrency['deposit'] as $key => $value)
                                                                            <div class="mb-2 border-bottom pb-1">
                                                                                <small class="fw-bold text-dark">{{ $key }}</small>
                                                                                <table class="table table-sm table-borderless mb-0">
                                                                                    @foreach ($value as $currencyCode => $amount)
                                                                                        <tr>
                                                                                            <td class="text-muted ps-2">{{ $currencyCode }}:</td>
                                                                                            <td class="text-end fw-bold">{{ number_format($amount, 2) }}</td>
                                                                                        </tr>
                                                                                    @endforeach
                                                                                </table>
                                                                            </div>
                                                                        @endforeach
                                                                    @else
                                                                        <p class="text-muted mb-0 text-center">Sin movimientos</p>
                                                                    @endif
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="row mb-4">
                                                <div class="col-md-6">
                                                    <div class="card bg-light">
                                                        <div class="card-body text-dark">
                                                            <h5 class="card-title">Resumen General</h5>
                                                            @php
                                                                $primaryCurrency = \App\Models\Currency::where('is_primary', true)->first();
                                                                $primarySymbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                                                            @endphp
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <span>Total Esperado:</span>
                                                                <span class="fw-bold">{{ $primarySymbol }}{{ number_format($totalExpected, 2) }}</span>
                                                            </div>
                                                            <div class="d-flex justify-content-between mb-2">
                                                                <span>Total Contado:</span>
                                                                <span class="fw-bold">{{ $primarySymbol }}{{ number_format($totalCounted, 2) }}</span>
                                                            </div>
                                                            <hr>
                                                            <div class="d-flex justify-content-between">
                                                                <span class="h5">Diferencia Total:</span>
                                                                <span class="h3 fw-bold {{ $totalDifference < 0 ? 'text-danger' : ($totalDifference > 0 ? 'text-primary' : 'text-success') }}">{{ $primarySymbol }}{{ number_format($totalDifference, 2) }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="col-md-6">
                                                    <div class="card mb-3">
                                                        <div class="card-header bg-primary text-white py-2">
                                                            <h6 class="mb-0 text-white"><i class="fas fa-info-circle me-2"></i> Instrucciones</h6>
                                                        </div>
                                                        <div class="card-body py-2">
                                                            <ol class="ps-3 mb-2 small">
                                                                <li>Cuente el dinero físico.</li>
                                                                <li>Ingrese montos en "Efectivo Contado".</li>
                                                                <li>Verifique diferencias.</li>
                                                                <li>Agregue notas si es necesario.</li>
                                                            </ol>
                                                            <textarea class="form-control form-control-sm mb-2" rows="3" wire:model="notes" placeholder="Notas de cierre..."></textarea>
                                                        </div>
                                                    </div>
                                                    <button type="submit" class="btn btn-danger btn-lg w-100" onclick="confirm('¿Está seguro de cerrar la caja? Esta acción no se puede deshacer.') || event.stopImmediatePropagation()">
                                                        <i class="fas fa-lock me-2"></i> CERRAR CAJA Y FINALIZAR TURNO
                                                    </button>
                                                </div>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        {{-- TAB 2: HISTORIAL --}}
                        <div class="tab-pane fade {{ $tab == 2 ? 'active show' : '' }}" id="history-register" role="tabpanel">
                            <div class="sidebar-body">
                                <div class="row mb-3 g-2">
                                    <div class="col-md-3">
                                        <label class="form-label">Desde</label>
                                        <input type="date" wire:model.live="dateFrom" class="form-control">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Hasta</label>
                                        <input type="date" wire:model.live="dateTo" class="form-control">
                                    </div>
                                    @can('cash_register.view_all')
                                    <div class="col-md-3">
                                        <label class="form-label">Usuario</label>
                                        <select wire:model.live="selectedUser" class="form-control">
                                            <option value="">Todos los usuarios</option>
                                            @foreach($users as $user)
                                                <option value="{{ $user->id }}">{{ $user->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    @endcan
                                    <div class="col-md-3">
                                        <label class="form-label">Buscar</label>
                                        <input type="text" wire:model.live="search" class="form-control" placeholder="ID o Notas...">
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped">
                                        <thead class="bg-light">
                                            <tr>
                                                <th>ID</th>
                                                <th>Usuario</th>
                                                <th>Apertura</th>
                                                <th>Cierre</th>
                                                <th>Esperado</th>
                                                <th>Contado</th>
                                                <th>Diferencia</th>
                                                <th>Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($history as $register)
                                            <tr>
                                                <td>#{{ $register->id }}</td>
                                                <td>{{ $register->user->name }}</td>
                                                <td>{{ $register->opening_date ? \Carbon\Carbon::parse($register->opening_date)->format('d/m/Y H:i') : '-' }}</td>
                                                <td>{{ $register->closing_date ? \Carbon\Carbon::parse($register->closing_date)->format('d/m/Y H:i') : '-' }}</td>
                                                <td>${{ number_format($register->total_expected_amount, 2) }}</td>
                                                <td>${{ number_format($register->total_counted_amount, 2) }}</td>
                                                <td class="{{ $register->difference_amount < 0 ? 'text-danger' : 'text-success' }}">
                                                    ${{ number_format($register->difference_amount, 2) }}
                                                </td>
                                                <td>
                                                    <button wire:click="viewDetails({{ $register->id }})" class="btn btn-sm btn-info">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="8" class="text-center">No se encontraron registros</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </div>
                                @if(count($history) > 0)
                                    <div class="mt-3">
                                        {{ $history->links() }}
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal de Detalles --}}
    <div wire:ignore.self class="modal fade" id="cashRegisterDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title text-white">Detalles de Caja #{{ $selectedRegister->id ?? '' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($selectedRegister)
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <p><strong>Usuario:</strong> {{ $selectedRegister->user->name }}</p>
                                <p><strong>Apertura:</strong> {{ $selectedRegister->opening_date }}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Cierre:</strong> {{ $selectedRegister->closing_date }}</p>
                                <p><strong>Estado:</strong> {{ ucfirst($selectedRegister->status) }}</p>
                            </div>
                        </div>
                        
                        <h6 class="border-bottom pb-2">Resumen Financiero</h6>
                        <table class="table table-sm table-bordered mb-3">
                            <thead class="bg-light">
                                <tr>
                                    <th>Concepto</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total Apertura</td>
                                    <td>${{ number_format($selectedRegister->total_opening_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Total Esperado</td>
                                    <td>${{ number_format($selectedRegister->total_expected_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td>Total Contado</td>
                                    <td>${{ number_format($selectedRegister->total_counted_amount, 2) }}</td>
                                </tr>
                                <tr>
                                    <td><strong>Diferencia</strong></td>
                                    <td class="{{ $selectedRegister->difference_amount < 0 ? 'text-danger' : 'text-success' }}">
                                        <strong>${{ number_format($selectedRegister->difference_amount, 2) }}</strong>
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        @if($selectedRegister->closing_notes)
                            <div class="alert alert-info">
                                <strong>Notas de Cierre:</strong><br>
                                {{ $selectedRegister->closing_notes }}
                            </div>
                        @endif
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-modal', (id) => {
                $('#' + id).modal('show');
            });
        });
    </script>
</div>
