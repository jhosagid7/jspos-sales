<div>
    <div class="row">
        <!-- Sidebar - Filtros de Consulta (Estilo Estándar del Sistema) -->
        <div class="col-sm-12 col-md-3 mb-3">
            <div class="card shadow-sm border-0">
                <div class="p-2 card-header bg-dark">
                    <h5 class="text-center text-white mb-0 font-weight-bold">
                        <i class="fas fa-filter me-1"></i> Filtros de Reporte
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Selector de Vendedores -->
                    <div class="mt-2">
                        <label class="form-label font-weight-bold small text-muted">Vendedor Evaluado</label>
                        <select wire:model.live="sellerId" class="form-control form-control-sm">
                            <option value="all">-- Todos los Vendedores --</option>
                            @foreach($allSellers as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Fecha de Referencia -->
                    <div class="mt-3">
                        <label class="form-label font-weight-bold small text-muted">Fecha de Evaluación</label>
                        <input type="date" wire:model.live="referenceDate" class="form-control form-control-sm">
                    </div>

                    <!-- Accesos Rápidos de Fecha -->
                    <div class="mt-3 d-flex">
                        <button wire:click.prevent="setToday" class="btn btn-outline-secondary btn-sm flex-fill me-1">
                            <i class="fa fa-calendar-day me-1"></i> Hoy
                        </button>
                        <button wire:click.prevent="setYesterday" class="btn btn-outline-secondary btn-sm flex-fill">
                            Ayer
                        </button>
                    </div>

                    <hr class="my-3">

                    <!-- Botones PDF (Vista Previa y Descarga) -->
                    <div class="d-flex gap-2">
                        <button type="button" wire:click="openPdfPreview" wire:loading.attr="disabled" class="btn btn-outline-info btn-sm flex-fill font-weight-bold">
                            <span wire:loading.remove wire:target="openPdfPreview"><i class="fas fa-eye me-1"></i> Vista Previa</span>
                            <span wire:loading wire:target="openPdfPreview"><i class="fas fa-spinner fa-spin me-1"></i> Cargando...</span>
                        </button>

                        <button type="button" wire:click="exportPdf" wire:loading.attr="disabled" class="btn btn-outline-danger btn-sm flex-fill font-weight-bold">
                            <span wire:loading.remove wire:target="exportPdf"><i class="fas fa-file-pdf me-1"></i> PDF</span>
                            <span wire:loading wire:target="exportPdf"><i class="fas fa-spinner fa-spin me-1"></i> PDF...</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Área Principal de Resultados -->
        <div class="col-sm-12 col-md-9">
            <!-- Header Card de Resumen -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="card-header bg-primary text-white py-2 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">
                        <i class="fas fa-trophy me-2"></i> Reporte de Comisiones por Metas de Ventas
                    </h6>
                    <span class="badge bg-light text-primary font-weight-bold">
                        Cortes Automáticos por Periodicidad
                    </span>
                </div>
            </div>

            <!-- Métricas KPI (Cards) -->
            <div class="row mb-3">
                <div class="col-md-4 mb-2">
                    <div class="card border-0 bg-info text-white shadow-sm">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-uppercase font-weight-bold d-block text-white-50">Metas Evaluadas</small>
                                <h3 class="font-weight-bold m-0">{{ $totalGoalsEvaluated }}</h3>
                            </div>
                            <i class="fas fa-tasks fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-2">
                    <div class="card border-0 bg-success text-white shadow-sm">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-uppercase font-weight-bold d-block text-white-50">Metas Alcanzadas</small>
                                <h3 class="font-weight-bold m-0">{{ $totalGoalsAchieved }}</h3>
                            </div>
                            <i class="fas fa-check-circle fa-2x text-white-50"></i>
                        </div>
                    </div>
                </div>

                <div class="col-md-4 mb-2">
                    <div class="card border-0 bg-warning text-dark shadow-sm">
                        <div class="card-body py-3 d-flex align-items-center justify-content-between">
                            <div>
                                <small class="text-uppercase font-weight-bold d-block text-dark">Premios Ganados ($)</small>
                                <h3 class="font-weight-bold m-0 text-dark">$ {{ number_format($totalCommissionEarned, 2) }}</h3>
                            </div>
                            <i class="fas fa-award fa-2x text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Listado por Vendedor -->
            @forelse($evaluations as $eval)
                <div class="card shadow-sm border-0 mb-3">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2">
                        <span class="font-weight-bold">
                            <i class="fas fa-user-tie me-2 text-warning"></i> {{ $eval['user_name'] }}
                        </span>
                        <span class="badge bg-success font-weight-bold">
                            Total Premios: $ {{ number_format($eval['total_earned'], 2) }}
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover table-striped align-middle m-0 text-start">
                                <thead class="table-dark small">
                                    <tr>
                                        <th>Meta</th>
                                        <th>Frecuencia</th>
                                        <th>Período Evaluado</th>
                                        <th class="text-end">Meta ($)</th>
                                        <th class="text-end">Ventas ($)</th>
                                        <th class="text-center">Estatus</th>
                                        <th class="text-end">Premio ($)</th>
                                        <th class="text-end">Faltante ($)</th>
                                    </tr>
                                </thead>
                                <tbody class="small">
                                    @foreach($eval['goals'] as $g)
                                        <tr>
                                            <td class="font-weight-bold text-dark">{{ $g['goal_name'] }}</td>
                                            <td>
                                                <span class="badge bg-primary text-uppercase">{{ $g['periodicity'] }}</span>
                                            </td>
                                            <td class="text-muted">
                                                {{ \Carbon\Carbon::parse($g['period_start'])->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($g['period_end'])->format('d/m/Y') }}
                                            </td>
                                            <td class="text-end font-weight-bold">$ {{ number_format($g['target_amount'], 2) }}</td>
                                            <td class="text-end font-weight-bold text-primary">$ {{ number_format($g['total_sales'], 2) }}</td>
                                            <td class="text-center">
                                                @if($g['achieved'])
                                                    <span class="badge bg-success font-weight-bold py-1 px-2">
                                                        <i class="fas fa-trophy me-1"></i> ALCANZADA
                                                    </span>
                                                @else
                                                    <span class="badge bg-secondary font-weight-bold py-1 px-2">
                                                        EN PROGRESO
                                                    </span>
                                                @endif
                                            </td>
                                            <td class="text-end font-weight-bold {{ $g['achieved'] ? 'text-success' : 'text-muted' }}">
                                                $ {{ number_format($g['earned_reward'], 2) }}
                                            </td>
                                            <td class="text-end font-weight-bold">
                                                @if($g['achieved'])
                                                    <span class="text-success"><i class="fas fa-check-circle"></i> Cumplida</span>
                                                @else
                                                    <span class="text-danger">$ {{ number_format($g['remaining_amount'], 2) }}</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            @empty
                <div class="alert alert-warning text-center py-4 shadow-sm">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2 d-block text-warning"></i>
                    No se encontraron vendedores con metas activas asignadas para la fecha seleccionada.
                </div>
            @endforelse
        </div>
    </div>

    <!-- Modal Visor PDF (Estilo Estándar del Sistema) -->
    @if ($showPdfModal)
        <div class="modal fade show" tabindex="-1" role="dialog" style="display: block; background: rgba(0,0,0,0.5); z-index: 1050;">
            <div class="modal-dialog modal-xl" role="document" style="max-width: 90%; height: 90vh; margin: 30px auto;">
                <div class="modal-content" style="height: 100%;">
                    <div class="modal-header bg-dark p-2 text-white d-flex justify-content-between align-items-center">
                        <h5 class="modal-title text-white mb-0">
                            <i class="fas fa-file-pdf text-danger me-2"></i> Vista Previa — Reporte de Comisiones por Metas
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <button type="button" class="btn btn-sm btn-outline-light me-2" wire:click="exportPdf">
                                <i class="fa fa-download me-1"></i> Descargar PDF
                            </button>
                            <button type="button" class="close text-white" wire:click.prevent="closePdfPreview" aria-label="Close" style="outline: none; background: transparent; border: none;">
                                <span aria-hidden="true" style="font-size: 24px; color: white;">&times;</span>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body p-0" style="height: calc(100% - 55px); overflow: hidden;">
                        <iframe src="{{ $pdfUrl }}" style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
