<div>
    <div class="row align-items-center mb-4">
        <div class="col">
            <h2 class="h5 page-title"><i class="fas fa-key text-primary me-2"></i> Historial de Autorizaciones de Crédito</h2>
            <p class="text-muted mb-0">Registro de solicitudes de PIN para ventas a crédito denegadas por sistema.</p>
        </div>
    </div>

    <div class="card shadow border-0">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6 mb-2">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Buscar por cliente o código PIN...">
                    </div>
                </div>
                <div class="col-md-3 mb-2">
                    <select wire:model.live="status" class="form-select">
                        <option value="">Todos los Estados</option>
                        <option value="pending">Pendiente (Válido)</option>
                        <option value="used">Usado (Aprobado)</option>
                        <option value="expired">Expirado</option>
                    </select>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle">
                    <thead class="bg-light">
                        <tr>
                            <th>Fecha</th>
                            <th>Cliente</th>
                            <th>Solicitante</th>
                            <th>Monto</th>
                            <th>Código PIN</th>
                            <th>Estado</th>
                            <th>Aprobado Por</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($authorizations as $auth)
                            <tr>
                                <td>
                                    <div class="font-weight-bold">{{ $auth->created_at->format('d/m/Y') }}</div>
                                    <div class="text-muted small">{{ $auth->created_at->format('h:i A') }}</div>
                                </td>
                                <td>
                                    <div class="font-weight-bold">{{ $auth->customer->name ?? 'Cliente Desconocido' }}</div>
                                    <div class="text-muted small">ID: {{ $auth->customer->identification_number ?? 'N/A' }}</div>
                                </td>
                                <td>{{ $auth->requestedBy->name ?? 'Sistema' }}</td>
                                <td><span class="font-weight-bold">${{ number_format($auth->amount_requested, 2) }}</span></td>
                                <td>
                                    <span class="badge bg-light text-dark border p-2" style="font-family: monospace; letter-spacing: 2px;">{{ $auth->pin_code }}</span>
                                </td>
                                <td>
                                    @if($auth->status === 'used')
                                        <span class="badge bg-success">Usado</span>
                                        @if($auth->sale_id)
                                            <div class="small text-muted mt-1 font-weight-bold">Factura #{{ $auth->sale_id }}</div>
                                        @endif
                                    @elseif($auth->status === 'expired')
                                        <span class="badge bg-danger">Expirado</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Pendiente</span>
                                        <div class="small text-muted mt-1">Expira: {{ $auth->expires_at->format('h:i A') }}</div>
                                    @endif
                                </td>
                                <td>
                                    @if($auth->approved_by_id)
                                        <span class="text-primary font-weight-bold"><i class="fas fa-user-check me-1"></i> {{ $auth->approvedBy->name ?? 'Desconocido' }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center py-4">
                                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                                    <p class="text-muted mb-0">No se encontraron registros de autorización.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-4">
                {{ $authorizations->links() }}
            </div>
        </div>
    </div>
</div>
