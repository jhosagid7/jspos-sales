<div>
    <div class="row">
        <!-- Sidebar - Opciones de Consulta -->
        <div class="col-sm-12 col-md-3">
            <div class="card mb-3 shadow-sm border-0">
                <div class="p-2 card-header bg-dark">
                    <h5 class="text-center text-white mb-0 f-16"><i class="fas fa-users-cog"></i> Opciones de Operadores</h5>
                </div>

                <div class="card-body">
                    <!-- Selector de Operadores -->
                    <div class="mt-2">
                        <span class="f-14 font-weight-bold text-dark">Filtrar Operadores</span>
                        <div class="border p-2 rounded mt-1" style="max-height: 180px; overflow-y: auto; background-color: #f8f9fa;">
                            @forelse ($operatorsList as $operator)
                                <div class="custom-control custom-checkbox mb-1">
                                    <input type="checkbox" class="custom-control-input" id="operator_{{ $operator->id }}" value="{{ $operator->id }}" wire:model.live="selectedOperators">
                                    <label class="custom-control-label f-12" for="operator_{{ $operator->id }}">{{ $operator->name }}</label>
                                </div>
                            @empty
                                <div class="text-center text-muted f-12 py-2">No se encontraron operadores</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Tipo de Agrupación -->
                    <div class="mt-3">
                        <span class="f-14 font-weight-bold text-dark">Agrupación de Tiempo (Gráfico)</span>
                        <select wire:model.live="periodType" class="form-control form-control-sm mt-1">
                            <option value="daily">Diario</option>
                            <option value="weekly">Semanal</option>
                            <option value="biweekly">Quincenal (15 Días)</option>
                            <option value="monthly">Mensual</option>
                            <option value="yearly">Anual</option>
                        </select>
                    </div>

                    <!-- Métrica de Análisis -->
                    <div class="mt-3">
                        <span class="f-14 font-weight-bold text-dark">Métrica de Gráfico</span>
                        <select wire:model.live="metric" class="form-control form-control-sm mt-1">
                            <option value="precision_score">Score de Precisión %</option>
                            <option value="invoices_count">Facturas Emitidas</option>
                            <option value="amount_usd">Monto Facturado USD</option>
                            <option value="modified_count">Facturas Modificadas</option>
                            <option value="voided_count">Facturas Anuladas</option>
                            <option value="returned_count">Facturas con Devolución</option>
                        </select>
                    </div>

                    <!-- Rango de Fechas -->
                    <div class="mt-3">
                        <span class="f-14 font-weight-bold text-dark">Desde</span>
                        <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm mt-1">
                    </div>
                    <div class="mt-2">
                        <span class="f-14 font-weight-bold text-dark">Hasta</span>
                        <input type="date" wire:model.live="dateTo" class="form-control form-control-sm mt-1">
                    </div>

                    <!-- Botones de Acción -->
                    <div class="mt-4">
                        <button wire:key="btn-ops-search" wire:click.prevent="searchData" class="btn btn-dark w-100 font-weight-bold shadow-sm">
                            <i class="fa fa-chart-line"></i> Analizar Eficiencia
                        </button>
                        <button wire:key="btn-ops-pdf" wire:click.prevent="openPdfPreview" class="btn btn-danger text-white w-100 mt-2 font-weight-bold shadow-sm" @if(!$showReport) disabled @endif>
                            <i class="fas fa-file-pdf"></i> Reporte PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Resultados -->
        <div class="col-sm-12 col-md-9">
            <div class="card card-absolute shadow-sm border-0">
                <div class="card-header bg-dark">
                    <h5 class="text-white mb-0"><i class="fas fa-chart-bar"></i> Eficiencia y Precisión de Operadores de Facturación</h5>
                </div>

                <div class="card-body">
                    <!-- Mensaje de instrucción -->
                    <div class="alert alert-info text-center shadow-sm border-0 {{ !$showReport ? '' : 'd-none' }}">
                        Selecciona los operadores y filtros correspondientes en la barra lateral y haz clic en "Analizar Eficiencia" para iniciar la visualización.
                    </div>

                    <!-- Panel de Resultados -->
                    <div class="{{ !$showReport ? 'd-none' : '' }}">
                        <!-- Gráfico Interactivo Highcharts -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-header bg-light py-2 border-0">
                                <h6 class="mb-0 text-dark font-weight-bold"><i class="fa fa-chart-area text-indigo"></i> Tendencia de Desempeño por Operador</h6>
                            </div>
                            <div class="card-body" wire:ignore>
                                <div id="operatorsPrecisionChart" style="height: 350px; width: 100%;"></div>
                            </div>
                        </div>

                        @if($showReport && isset($reportData['kpis']))
                            <!-- KPIs de Resumen -->
                            <h5 class="text-dark font-weight-bold mb-3"><i class="fa fa-info-circle text-primary"></i> Métricas Consolidadas del Periodo</h5>
                            <div class="row">
                                <!-- Facturas Procesadas -->
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-0 border-left border-primary h-100">
                                        <div class="card-body p-3">
                                            <div class="f-11 text-muted uppercase font-weight-bold">Facturas Emitidas</div>
                                            <div class="f-20 font-weight-bold text-dark mt-1">{{ number_format($reportData['kpis']['total_sales'], 0) }}</div>
                                            <div class="f-11 text-muted mt-1">Total de transacciones</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Monto Facturado -->
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-0 border-left border-success h-100">
                                        <div class="card-body p-3">
                                            <div class="f-11 text-muted uppercase font-weight-bold">Monto Facturado</div>
                                            <div class="f-20 font-weight-bold text-success mt-1">${{ number_format($reportData['kpis']['total_amount'], 2) }}</div>
                                            <div class="f-11 text-muted mt-1">Ventas brutas USD</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Score de Precisión Promedio -->
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-0 border-left border-info h-100">
                                        <div class="card-body p-3">
                                            <div class="f-11 text-muted uppercase font-weight-bold">Score Precisión Prom.</div>
                                            <div class="f-20 font-weight-bold text-info mt-1">{{ number_format($reportData['kpis']['avg_precision_score'], 2) }}%</div>
                                            <div class="f-11 mt-1 text-info">
                                                Calidad general del canal
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Facturas con Errores -->
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-0 border-left border-danger h-100">
                                        <div class="card-body p-3">
                                            <div class="f-11 text-muted uppercase font-weight-bold">Facturas con Incidencias</div>
                                            <div class="f-20 font-weight-bold text-danger mt-1">
                                                {{ number_format($reportData['kpis']['total_errors'], 0) }}
                                            </div>
                                            <div class="f-11 text-muted mt-1">
                                                Mod: <b>{{ $reportData['kpis']['total_modified'] }}</b> | Anul: <b>{{ $reportData['kpis']['total_voided'] }}</b> | Dev: <b>{{ $reportData['kpis']['total_returned'] }}</b>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla Comparativa Detallada -->
                            <h5 class="text-dark font-weight-bold mt-3 mb-2"><i class="fa fa-table text-primary"></i> Desglose de Eficiencia por Operador</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover mt-1">
                                    <thead class="text-white" style="background: #3b3f5c">
                                        <tr>
                                            <th class="table-th text-white">Operador</th>
                                            <th class="table-th text-white text-center">Facturas Emitidas</th>
                                            <th class="table-th text-white text-center">Monto Ventas USD</th>
                                            <th class="table-th text-white text-center">Modificadas</th>
                                            <th class="table-th text-white text-center">Anuladas</th>
                                            <th class="table-th text-white text-center">Devueltas</th>
                                            <th class="table-th text-white text-center">Incidencias</th>
                                            <th class="table-th text-white text-center">Score Precisión</th>
                                            <th class="table-th text-white text-center">Días Activos</th>
                                            <th class="table-th text-white text-center">Eficiencia Diaria</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reportData['operators'] as $operatorData)
                                            <tr>
                                                <td class="font-weight-bold bg-light">{{ $operatorData['name'] }}</td>
                                                <td class="text-center font-weight-bold">{{ number_format($operatorData['total_sales'], 0) }}</td>
                                                <td class="text-center font-weight-bold">${{ number_format($operatorData['total_amount'], 2) }}</td>
                                                <td class="text-center">{{ number_format($operatorData['modified_count'], 0) }}</td>
                                                <td class="text-center text-danger">{{ number_format($operatorData['voided_count'], 0) }}</td>
                                                <td class="text-center text-warning">{{ number_format($operatorData['returned_count'], 0) }}</td>
                                                <td class="text-center">{{ number_format($operatorData['errors_count'], 0) }}</td>
                                                <td class="text-center font-weight-bold">
                                                    <span class="badge badge-{{ $operatorData['precision_score'] >= 95 ? 'success' : ($operatorData['precision_score'] >= 85 ? 'warning' : 'danger') }} py-1 px-2 f-12">
                                                        {{ number_format($operatorData['precision_score'], 2) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center">{{ $operatorData['active_days'] }}</td>
                                                <td class="text-center font-weight-bold text-primary">
                                                    {{ number_format($operatorData['efficiency'], 1) }} fact/día
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center text-muted">No hay registros de facturación para los operadores en este período.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if(!empty($reportData['operators']))
                                        <tfoot>
                                            <tr class="bg-dark text-white font-weight-bold">
                                                <td class="text-left text-white">TOTAL CONSOLIDADO:</td>
                                                <td class="text-center text-white">{{ number_format($reportData['kpis']['total_sales'], 0) }}</td>
                                                <td class="text-center text-white">${{ number_format($reportData['kpis']['total_amount'], 2) }}</td>
                                                <td class="text-center text-white">{{ number_format($reportData['kpis']['total_modified'], 0) }}</td>
                                                <td class="text-center text-danger">{{ number_format($reportData['kpis']['total_voided'], 0) }}</td>
                                                <td class="text-center text-warning">{{ number_format($reportData['kpis']['total_returned'], 0) }}</td>
                                                <td class="text-center text-white">{{ number_format($reportData['kpis']['total_errors'], 0) }}</td>
                                                <td class="text-center text-white">
                                                    <span class="badge badge-{{ $reportData['kpis']['avg_precision_score'] >= 95 ? 'success' : ($reportData['kpis']['avg_precision_score'] >= 85 ? 'warning' : 'danger') }} py-1 px-2 f-12">
                                                        {{ number_format($reportData['kpis']['avg_precision_score'], 2) }}%
                                                    </span>
                                                </td>
                                                <td class="text-center text-white">{{ collect($reportData['operators'])->sum('active_days') }}</td>
                                                <td class="text-center text-white">
                                                    @php
                                                        $sumDays = collect($reportData['operators'])->sum('active_days');
                                                        $consolidatedEff = $sumDays > 0 ? $reportData['kpis']['total_sales'] / $sumDays : 0;
                                                    @endphp
                                                    {{ number_format($consolidatedEff, 1) }} fact/día
                                                </td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Visor PDF -->
    @if ($showPdfModal)
        <div class="modal fade show" tabindex="-1" role="dialog" style="display: block; background: rgba(0,0,0,0.5); z-index: 1050;">
            <div class="modal-dialog modal-xl" role="document" style="max-width: 90%; height: 90vh; margin: 30px auto;">
                <div class="modal-content" style="height: 100%;">
                    <div class="modal-header bg-dark p-2 text-white d-flex justify-content-between align-items-center">
                        <h5 class="modal-title text-white mb-0"><i class="fas fa-file-pdf"></i> Vista Previa Reporte de Eficiencia de Operadores</h5>
                        <button type="button" class="close text-white" wire:click.prevent="closePdfPreview" aria-label="Close" style="outline: none;">
                            <span aria-hidden="true" style="font-size: 24px;">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0" style="height: calc(100% - 50px); overflow: hidden;">
                        <iframe src="{{ $pdfUrl }}" style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Load Highcharts library if not loaded -->
<script src="https://code.highcharts.com/highcharts.js"></script>

@script
<script>
    let chartInstance = null;

    $wire.on('updateChart', (data) => {
        let labels = data.labels;
        let datasets = data.datasets;

        let seriesData = datasets.map(set => ({
            name: set.name,
            data: set.data,
            color: set.color
        }));

        if (chartInstance) {
            chartInstance.destroy();
        }

        let metricLabel = '';
        let metric = @this.metric;
        if (metric === 'precision_score') {
            metricLabel = 'Score de Precisión %';
        } else if (metric === 'invoices_count') {
            metricLabel = 'Facturas Emitidas';
        } else if (metric === 'amount_usd') {
            metricLabel = 'Monto USD';
        } else if (metric === 'modified_count') {
            metricLabel = 'Facturas Modificadas';
        } else if (metric === 'voided_count') {
            metricLabel = 'Facturas Anuladas';
        } else if (metric === 'returned_count') {
            metricLabel = 'Facturas con Devolución';
        }

        chartInstance = Highcharts.chart('operatorsPrecisionChart', {
            chart: {
                type: 'areaspline',
                backgroundColor: 'transparent'
            },
            title: {
                text: null
            },
            xAxis: {
                categories: labels,
                labels: {
                    style: {
                        color: '#555',
                        fontSize: '10px'
                    }
                }
            },
            yAxis: {
                title: {
                    text: metricLabel,
                    style: {
                        color: '#555',
                        fontWeight: 'bold'
                    }
                },
                labels: {
                    style: {
                        color: '#555',
                        fontSize: '10px'
                    }
                }
            },
            tooltip: {
                shared: true,
                formatter: function() {
                    let s = '<b>' + this.x + '</b><br/>';
                    this.points.forEach(point => {
                        let val = point.y;
                        if (metric === 'amount_usd') {
                            val = '$' + val.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
                        } else if (metric === 'precision_score') {
                            val = val.toFixed(2) + '%';
                        } else {
                            val = val.toLocaleString('en-US', { maximumFractionDigits: 0 });
                        }
                        s += '<span style="color:' + point.color + '">\u25CF</span> ' + point.series.name + ': <b>' + val + '</b><br/>';
                    });
                    return s;
                }
            },
            plotOptions: {
                areaspline: {
                    fillOpacity: 0.05,
                    lineWidth: 2,
                    marker: {
                        enabled: true,
                        radius: 4,
                        states: {
                            hover: {
                                enabled: true
                            }
                        }
                    }
                }
            },
            credits: {
                enabled: false
            },
            series: seriesData
        });
    });
</script>
@endscript
