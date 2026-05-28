<div wire:poll.8s>
    {{-- Content Header --}}
    <div class="content-header pb-2">
        <div class="container-fluid">
            <div class="row align-items-center">
                <div class="col-sm-6">
                    <h3 class="m-0 text-dark fw-bold">
                        <i class="fas fa-check-double text-primary me-2"></i>
                        Aprobaciones de Tasas Especiales
                    </h3>
                    <p class="text-muted mb-0" style="font-size: 0.85rem;">
                        Gestione de forma centralizada y en tiempo real las solicitudes de tasas de cambio personalizadas.
                    </p>
                </div>
                <div class="col-sm-6 text-sm-right mt-2 mt-sm-0">
                    <span class="badge bg-light text-primary border p-2 fw-semibold">
                        <i class="fas fa-sync-alt fa-spin me-1"></i> Sincronización Automática Activa (8s)
                    </span>
                </div>
            </div>
        </div>
    </div>

    {{-- Stats Row --}}
    <div class="container-fluid mb-4">
        <div class="row g-3">
            {{-- Pending --}}
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <div wire:click="$set('status', 'pending')" class="card border-0 shadow-sm overflow-hidden h-100 card-clickable {{ $status === 'pending' ? 'card-active-warning' : '' }}" style="border-radius: 12px; background: linear-gradient(135deg, #fffcf6 0%, #fff9e6 100%); border-left: 5px solid #ffc107 !important;">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase tracking-wider fw-bold text-warning" style="font-size: 0.72rem; letter-spacing: 1px;">Pendientes</span>
                            <h2 class="fw-extrabold text-dark mb-0 mt-1">{{ $pendingTodayCount }}</h2>
                        </div>
                        <div class="bg-warning bg-opacity-10 p-2 rounded-circle text-warning">
                            <i class="fas fa-hourglass-half fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Approved --}}
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <div wire:click="$set('status', 'approved')" class="card border-0 shadow-sm overflow-hidden h-100 card-clickable {{ $status === 'approved' ? 'card-active-success' : '' }}" style="border-radius: 12px; background: linear-gradient(135deg, #f6fff9 0%, #e6fff0 100%); border-left: 5px solid #28a745 !important;">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase tracking-wider fw-bold text-success" style="font-size: 0.72rem; letter-spacing: 1px;">Aprobadas (Caja)</span>
                            <h2 class="fw-extrabold text-dark mb-0 mt-1">{{ $approvedTodayCount }}</h2>
                        </div>
                        <div class="bg-success bg-opacity-10 p-2 rounded-circle text-success">
                            <i class="fas fa-unlock fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Used --}}
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <div wire:click="$set('status', 'used')" class="card border-0 shadow-sm overflow-hidden h-100 card-clickable {{ $status === 'used' ? 'card-active-info' : '' }}" style="border-radius: 12px; background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-left: 5px solid #0284c7 !important;">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase tracking-wider fw-bold text-info" style="font-size: 0.72rem; letter-spacing: 1px; color: #0284c7 !important;">Cobradas Hoy</span>
                            <h2 class="fw-extrabold text-dark mb-0 mt-1">{{ $usedTodayCount }}</h2>
                        </div>
                        <div class="bg-info bg-opacity-10 p-2 rounded-circle text-info" style="color: #0284c7 !important;">
                            <i class="fas fa-check-double fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Rejected --}}
            <div class="col-12 col-sm-6 col-md-4 col-xl-2">
                <div wire:click="$set('status', 'rejected')" class="card border-0 shadow-sm overflow-hidden h-100 card-clickable {{ $status === 'rejected' ? 'card-active-danger' : '' }}" style="border-radius: 12px; background: linear-gradient(135deg, #fff5f5 0%, #ffe3e3 100%); border-left: 5px solid #dc3545 !important;">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase tracking-wider fw-bold text-danger" style="font-size: 0.72rem; letter-spacing: 1px;">Rechazadas</span>
                            <h2 class="fw-extrabold text-dark mb-0 mt-1">{{ $rejectedTodayCount }}</h2>
                        </div>
                        <div class="bg-danger bg-opacity-10 p-2 rounded-circle text-danger">
                            <i class="fas fa-times-circle fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Avg Rate --}}
            <div class="col-12 col-sm-6 col-md-8 col-xl-4">
                <div class="card border-0 shadow-sm overflow-hidden h-100" style="border-radius: 12px; background: linear-gradient(135deg, #f6faff 0%, #e6f2ff 100%); border-left: 5px solid #007bff !important;">
                    <div class="card-body p-3 d-flex align-items-center justify-content-between">
                        <div>
                            <span class="text-uppercase tracking-wider fw-bold text-primary" style="font-size: 0.72rem; letter-spacing: 1px;">Tasa Promedio Aprobada</span>
                            <h2 class="fw-extrabold text-dark mb-0 mt-1">{{ number_format($averageRateToday, 2) }} <small class="fs-6">Bs.</small></h2>
                        </div>
                        <div class="bg-primary bg-opacity-10 p-3 rounded-circle text-primary">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="container-fluid">
        <div class="card border-0 shadow-sm" style="border-radius: 12px;">
            <div class="card-body p-4">
                {{-- Filters --}}
                <div class="row g-3 mb-4">
                    <div class="col-12 col-md-4">
                        <label class="form-label fw-semibold text-secondary" style="font-size: 0.82rem;">Búsqueda Rápida</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0"><i class="fas fa-search text-muted"></i></span>
                            <input type="text" class="form-control bg-light border-start-0 text-start" placeholder="Buscar por cajero, cliente, motivo, tasa..." wire:model.live="search">
                        </div>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold text-secondary" style="font-size: 0.82rem;">Estado</label>
                        <select class="form-control form-select bg-light" wire:model.live="status">
                            <option value="">(Todos)</option>
                            <option value="pending">Pendientes</option>
                            <option value="approved">Aprobados</option>
                            <option value="rejected">Rechazados</option>
                            <option value="used">Utilizados</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label fw-semibold text-secondary" style="font-size: 0.82rem;">Desde</label>
                        <input type="date" class="form-control bg-light" wire:model.live="dateFrom">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label fw-semibold text-secondary" style="font-size: 0.82rem;">Hasta</label>
                        <input type="date" class="form-control bg-light" wire:model.live="dateTo">
                    </div>
                </div>

                {{-- Table Grid --}}
                <div class="table-responsive" style="border-radius: 8px;">
                    <table class="table table-hover align-middle border mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="py-3 px-3 text-uppercase text-secondary fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Cajero / Operador</th>
                                <th class="py-3 px-3 text-uppercase text-secondary fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px;">Venta / Cliente</th>
                                <th class="py-3 px-3 text-uppercase text-secondary fw-bold text-center" style="font-size: 0.72rem; letter-spacing: 0.5px;">Tasa Propuesta</th>
                                <th class="py-3 px-3 text-uppercase text-secondary fw-bold text-center" style="font-size: 0.72rem; letter-spacing: 0.5px;">Código OTP</th>
                                <th class="py-3 px-3 text-uppercase text-secondary fw-bold" style="font-size: 0.72rem; letter-spacing: 0.5px; width: 30%;">Justificación</th>
                                <th class="py-3 px-3 text-uppercase text-secondary fw-bold text-center" style="font-size: 0.72rem; letter-spacing: 0.5px;">Estado</th>
                                <th class="py-3 px-3 text-uppercase text-secondary fw-bold text-center" style="font-size: 0.72rem; letter-spacing: 0.5px; width: 18%;">Acciones / Auditoría</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $row)
                                <tr class="transition">
                                    {{-- Requester --}}
                                    <td class="py-3 px-3">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-primary bg-opacity-10 text-primary rounded-circle p-2 me-2 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                                                <i class="fas fa-user-tie"></i>
                                            </div>
                                            <div>
                                                <span class="d-block fw-bold text-dark mb-0">{{ $row->user->name ?? 'Operador' }}</span>
                                                <small class="text-muted d-block" style="font-size: 0.72rem;">{{ $row->user->email ?? '' }}</small>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Sale/Customer --}}
                                    <td class="py-3 px-3">
                                        <span class="d-block fw-bold text-primary mb-0">Venta #{{ $row->sale_id ?? 'N/A' }}</span>
                                        <small class="text-secondary d-block fw-semibold" style="font-size: 0.76rem;">
                                            Cliente: {{ $row->sale->customer->name ?? 'N/A' }}
                                        </small>
                                        <small class="text-muted d-block" style="font-size: 0.7rem;">
                                            Total: ${{ number_format($row->sale->total ?? 0, 2) }}
                                        </small>
                                    </td>

                                    {{-- Proposed rate --}}
                                    <td class="py-3 px-3 text-center">
                                        <span class="fs-5 fw-extrabold text-danger d-block">
                                            {{ number_format($row->custom_rate, 2) }}
                                        </span>
                                        <small class="text-muted d-block" style="font-size: 0.68rem;">Bs. por USD</small>
                                    </td>

                                    {{-- OTP Code --}}
                                    <td class="py-3 px-3 text-center">
                                        @if($row->status === 'pending')
                                            <span class="badge bg-light border border-success text-success py-2 px-3 fw-bold fs-6" style="font-family: monospace; letter-spacing: 1px; border-radius: 6px;">
                                                {{ $row->token }}
                                            </span>
                                        @else
                                            <span class="text-muted" style="font-size: 0.8rem; font-family: monospace;">
                                                {{ $row->token ?: 'N/A' }}
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Reason --}}
                                    <td class="py-3 px-3">
                                        <div class="p-2 bg-light border-start border-3 border-warning rounded" style="font-size: 0.8rem; line-height: 1.4; color: #495057;">
                                            "{{ $row->reason }}"
                                        </div>
                                        <small class="text-muted d-block mt-1" style="font-size: 0.68rem;">
                                            Solicitado: {{ $row->created_at->diffForHumans() }} ({{ $row->created_at->format('d-m Y-d H:i') }})
                                        </small>
                                    </td>

                                    {{-- Status --}}
                                    <td class="py-3 px-3 text-center">
                                        @php
                                            $badgeColor = 'secondary';
                                            $statusText = 'Desconocido';
                                            switch($row->status) {
                                                case 'pending': $badgeColor = 'warning text-dark'; $statusText = 'Pendiente'; break;
                                                case 'approved': $badgeColor = 'success'; $statusText = 'Aprobado'; break;
                                                case 'rejected': $badgeColor = 'danger'; $statusText = 'Rechazado'; break;
                                                case 'used': $badgeColor = 'info'; $statusText = 'Utilizado'; break;
                                            }
                                        @endphp
                                        <span class="badge bg-{{ $badgeColor }} py-2 px-3 fw-bold" style="border-radius: 30px; font-size: 0.72rem; min-width: 80px;">
                                            {{ $statusText }}
                                        </span>
                                    </td>

                                    {{-- Actions --}}
                                    <td class="py-3 px-3 text-center">
                                        @if($row->status === 'pending')
                                            <div class="d-flex flex-column gap-2 justify-content-center align-items-stretch" style="max-width: 130px; margin: 0 auto;">
                                                <button type="button" wire:click="approveRequest({{ $row->id }})" class="btn btn-sm btn-success text-white py-1 shadow-sm fw-bold">
                                                    <i class="fas fa-check me-1"></i> Aprobar
                                                </button>
                                                <button type="button" wire:click="rejectRequest({{ $row->id }})" class="btn btn-sm btn-outline-danger py-1 shadow-sm fw-bold">
                                                    <i class="fas fa-times me-1"></i> Rechazar
                                                </button>
                                            </div>
                                        @else
                                            <div class="text-start" style="font-size: 0.75rem; color: #6c757d;">
                                                @if($row->approver)
                                                    <span class="d-block fw-semibold"><i class="fas fa-shield-alt text-secondary"></i> Supervisor:</span>
                                                    <span class="d-block text-truncate">{{ $row->approver->name }}</span>
                                                    <small class="d-block text-muted" style="font-size: 0.65rem;">{{ $row->updated_at->format('d-m-Y H:i') }}</small>
                                                @else
                                                    <span class="text-muted italic"><i class="fas fa-info-circle me-1"></i> Sin auditoría</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <div class="mb-2"><i class="fas fa-inbox fa-3x text-muted opacity-50"></i></div>
                                        <h6 class="fw-semibold text-secondary">No hay solicitudes encontradas</h6>
                                        <p class="mb-0 text-muted" style="font-size: 0.8rem;">
                                            No hay registros cargados con los filtros seleccionados.
                                        </p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                {{-- Pagination Links --}}
                <div class="mt-4 d-flex justify-content-between align-items-center">
                    <div>
                        <small class="text-muted">
                            Mostrando registros del {{ $records->firstItem() ?? 0 }} al {{ $records->lastItem() ?? 0 }} de un total de {{ $records->total() }}
                        </small>
                    </div>
                    <div>
                        {{ $records->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Style tweaks --}}
    <style>
        .transition {
            transition: background-color 0.2s ease;
        }
        .transition:hover {
            background-color: rgba(0, 123, 255, 0.015) !important;
        }
        .tracking-wider {
            letter-spacing: 0.05em;
        }
        .card-clickable {
            cursor: pointer;
            transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
        }
        .card-clickable:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 16px rgba(0,0,0,0.08) !important;
        }
        .card-active-warning {
            box-shadow: 0 0 0 3px rgba(255, 193, 7, 0.25) !important;
            border: 1px solid #ffc107 !important;
        }
        .card-active-success {
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.25) !important;
            border: 1px solid #28a745 !important;
        }
        .card-active-info {
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.25) !important;
            border: 1px solid #0284c7 !important;
        }
        .card-active-danger {
            box-shadow: 0 0 0 3px rgba(220, 53, 69, 0.25) !important;
            border: 1px solid #dc3545 !important;
        }
    </style>
</div>
