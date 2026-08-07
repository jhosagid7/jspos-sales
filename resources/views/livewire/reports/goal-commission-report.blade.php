<div>
    <div class="card shadow-sm border-0 mb-4">
        <div class="card-header bg-primary text-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="card-title m-0 font-weight-bold">
                <i class="fas fa-bullseye me-2"></i> Reporte de Comisiones por Metas de Ventas
            </h5>
            <span class="badge bg-light text-primary font-weight-bold">
                Cortes Automáticos por Periodicidad
            </span>
        </div>

        <div class="card-body">
            {{-- Filtros --}}
            <div class="row g-3 mb-4 bg-light p-3 rounded border align-items-end">
                <div class="col-md-4">
                    <label class="form-label font-weight-bold small text-muted">Filtrar por Vendedor</label>
                    <select wire:model.live="sellerId" class="form-control form-control-sm">
                        <option value="all">Todos los Vendedores con Metas</option>
                        @foreach($allSellers as $s)
                            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->email }})</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label font-weight-bold small text-muted">Fecha de Referencia / Evaluación</label>
                    <input type="date" wire:model.live="referenceDate" class="form-control form-control-sm">
                </div>

                <div class="col-md-4 text-end">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                        <i class="fas fa-print me-1"></i> Imprimir Reporte
                    </button>
                </div>
            </div>

            {{-- Métricas de Resumen --}}
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <div class="card border-0 bg-info text-white shadow-sm h-100">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-uppercase font-weight-bold d-block text-white-50">Metas Evaluadas</small>
                                <h3 class="font-weight-bold m-0">{{ $totalGoalsEvaluated }}</h3>
                            </div>
                            <i class="fas fa-tasks fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card border-0 bg-success text-white shadow-sm h-100">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-uppercase font-weight-bold d-block text-white-50">Metas Alcanzadas</small>
                                <h3 class="font-weight-bold m-0">{{ $totalGoalsAchieved }}</h3>
                            </div>
                            <i class="fas fa-check-circle fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-3">
                    <div class="card border-0 bg-warning text-dark shadow-sm h-100">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-uppercase font-weight-bold d-block text-dark">Comisiones / Premios Ganados ($)</small>
                                <h3 class="font-weight-bold m-0 text-dark">$ {{ number_format($totalCommissionEarned, 2) }}</h3>
                            </div>
                            <i class="fas fa-award fa-2x text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Detalle por Vendedor --}}
            @forelse($evaluations as $eval)
                <div class="card border mb-4 shadow-sm">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                        <span class="font-weight-bold text-dark fs-6">
                            <i class="fas fa-user-tie me-2 text-primary"></i> {{ $eval['user_name'] }}
                        </span>
                        <span class="badge bg-success font-weight-bold fs-6">
                            Total Premios: $ {{ number_format($eval['total_earned'], 2) }}
                        </span>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle m-0 text-start">
                                <thead class="bg-light">
                                    <tr>
                                        <th>Meta</th>
                                        <th>Frecuencia</th>
                                        <th>Período Evaluado</th>
                                        <th>Meta ($)</th>
                                        <th>Ventas Acumuladas ($)</th>
                                        <th>Estatus</th>
                                        <th>Premio ($)</th>
                                        <th>Faltante ($)</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($eval['goals'] as $g)
                                        <tr>
                                            <td class="font-weight-bold text-dark">{{ $g['goal_name'] }}</td>
                                            <td>
                                                <span class="badge bg-secondary text-uppercase">{{ $g['periodicity'] }}</span>
                                            </td>
                                            <td class="small text-muted">
                                                {{ \Carbon\Carbon::parse($g['period_start'])->format('d/m/Y') }} al {{ \Carbon\Carbon::parse($g['period_end'])->format('d/m/Y') }}
                                            </td>
                                            <td><span class="badge bg-info text-dark font-weight-bold">$ {{ number_format($g['target_amount'], 2) }}</span></td>
                                            <td><strong class="text-primary">$ {{ number_format($g['total_sales'], 2) }}</strong></td>
                                            <td>
                                                @if($g['achieved'])
                                                    <span class="badge bg-success font-weight-bold py-1 px-2">
                                                        <i class="fas fa-trophy me-1"></i> ALCANZADA
                                                    </span>
                                                @else
                                                    <span class="badge bg-warning text-dark font-weight-bold py-1 px-2">
                                                        EN PROGRESO
                                                    </span>
                                                @endif
                                            </td>
                                            <td>
                                                <span class="font-weight-bold {{ $g['achieved'] ? 'text-success' : 'text-muted' }}">
                                                    $ {{ number_format($g['earned_reward'], 2) }}
                                                </span>
                                            </td>
                                            <td>
                                                @if($g['achieved'])
                                                    <span class="text-success small font-weight-bold"><i class="fas fa-check"></i> Meta Cumplida</span>
                                                @else
                                                    <span class="text-danger small font-weight-bold">$ {{ number_format($g['remaining_amount'], 2) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8" class="text-center text-muted py-3">Este vendedor no tiene metas asignadas.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-info text-center py-4">
                    No se encontraron vendedores con metas de ventas configuradas.
                </div>
            @endforelse
        </div>
    </div>
</div>
