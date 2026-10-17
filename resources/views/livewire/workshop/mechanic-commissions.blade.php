<div>
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-coins text-warning mr-2"></i> Liquidación de Comisiones de Mecánicos
            </h3>
            <small class="text-muted">Mano de obra devengada y porcentaje liquidable por especialista técnico</small>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row">
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="font-weight-bold text-xs mb-1">Mecánico / Especialista</label>
                    <select wire:model.live="mechanicId" class="form-control form-control-sm">
                        <option value="0">Todos los mecánicos</option>
                        @foreach($mechanics as $m)
                            <option value="{{ $m->id }}">{{ $m->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="font-weight-bold text-xs mb-1">Desde</label>
                    <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm">
                </div>
                <div class="col-md-3 mb-2 mb-md-0">
                    <label class="font-weight-bold text-xs mb-1">Hasta</label>
                    <input type="date" wire:model.live="dateTo" class="form-control form-control-sm">
                </div>
                <div class="col-md-3">
                    <label class="font-weight-bold text-xs mb-1">Estado de Orden</label>
                    <select wire:model.live="statusFilter" class="form-control form-control-sm">
                        <option value="all">Todas las órdenes</option>
                        <option value="invoiced">Solo Facturadas / Cobradas</option>
                        <option value="delivered">Entregadas</option>
                        <option value="completed">Completadas</option>
                        <option value="in_progress">En Progreso</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- KPI Cards --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>${{ number_format($report['total_labor_charged'], 2) }}</h3>
                    <p>Total Mano de Obra ({{ $report['total_items_count'] }} tareas)</p>
                </div>
                <div class="icon"><i class="fas fa-tools"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>${{ number_format($report['total_commission_earned'], 2) }}</h3>
                    <p>Total Comisiones a Liquidar</p>
                </div>
                <div class="icon"><i class="fas fa-hand-holding-usd"></i></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="small-box bg-secondary">
                <div class="inner">
                    <h3>{{ count($report['by_mechanic']) }}</h3>
                    <p>Técnicos con Producción Activa</p>
                </div>
                <div class="icon"><i class="fas fa-users-cog"></i></div>
            </div>
        </div>
    </div>

    {{-- Resumen por Mecánico --}}
    <div class="card card-outline card-primary shadow-sm mb-3">
        <div class="card-header p-2">
            <h5 class="card-title font-weight-bold mb-0">Resumen por Mecánico</h5>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-striped table-hover mb-0 text-sm">
                <thead class="bg-light">
                    <tr>
                        <th>Mecánico</th>
                        <th class="text-center">Órdenes</th>
                        <th class="text-center">Servicios</th>
                        <th class="text-right">Mano de Obra Facturada</th>
                        <th class="text-right">Comisión Devengada</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['by_mechanic'] as $mSummary)
                        <tr>
                            <td class="font-weight-bold">{{ $mSummary['mechanic_name'] }}</td>
                            <td class="text-center">{{ $mSummary['orders_count'] }}</td>
                            <td class="text-center">{{ $mSummary['items_count'] }}</td>
                            <td class="text-right font-weight-bold">${{ number_format($mSummary['total_labor'], 2) }}</td>
                            <td class="text-right font-weight-bold text-success">${{ number_format($mSummary['total_commission'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-3 text-muted">No hay registros de comisiones para los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Detalle de Trabajos --}}
    <div class="card card-outline card-secondary shadow-sm">
        <div class="card-header p-2">
            <h5 class="card-title font-weight-bold mb-0">Detalle de Trabajos y Mano de Obra Realizada</h5>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped mb-0 text-sm">
                <thead class="bg-light">
                    <tr>
                        <th>Fecha / OT</th>
                        <th>Vehículo / Cliente</th>
                        <th>Mecánico</th>
                        <th>Servicio / Tarea</th>
                        <th class="text-right">Precio</th>
                        <th class="text-center">% Com.</th>
                        <th class="text-right">Monto Com.</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($report['items'] as $it)
                        <tr>
                            <td>
                                <span class="badge badge-primary">{{ $it->workOrder->order_number ?? 'N/A' }}</span>
                                <div class="text-muted text-xs mt-1">{{ $it->created_at->format('d/m/Y') }}</div>
                            </td>
                            <td>
                                <div class="font-weight-bold">{{ $it->workOrder->vehicle->full_name ?? 'N/A' }}</div>
                                <small class="text-muted">{{ $it->workOrder->customer->name ?? 'N/A' }}</small>
                            </td>
                            <td class="font-weight-bold">{{ $it->mechanic->name ?? 'N/A' }}</td>
                            <td>{{ $it->description }}</td>
                            <td class="text-right font-weight-bold">${{ number_format($it->subtotal, 2) }}</td>
                            <td class="text-center"><span class="badge badge-info">{{ number_format($it->mechanic_commission_percentage, 0) }}%</span></td>
                            <td class="text-right font-weight-bold text-success">${{ number_format($it->mechanic_commission_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-3 text-muted">No hay detalles de mano de obra en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>