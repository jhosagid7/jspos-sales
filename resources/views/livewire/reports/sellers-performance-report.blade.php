<div>
    <div class="row">
        <!-- Sidebar - Opciones de Consulta -->
        <div class="col-sm-12 col-md-3">
            <div class="card mb-3">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light mb-0">Opciones de Vendedores</h5>
                </div>

                <div class="card-body">
                    <!-- Selector de Vendedores -->
                    <div class="mt-2">
                        <span class="f-14"><b>Filtrar Vendedores</b></span>
                        <div class="border p-2 rounded mt-1" style="max-height: 180px; overflow-y: auto; background-color: #f8f9fa;">
                            @forelse ($sellersList as $seller)
                                <div class="custom-control custom-checkbox mb-1">
                                    <input type="checkbox" class="custom-control-input" id="seller_{{ $seller->id }}" value="{{ $seller->id }}" wire:model.live="selectedSellers">
                                    <label class="custom-control-label f-12" for="seller_{{ $seller->id }}">{{ $seller->name }}</label>
                                </div>
                            @empty
                                <div class="text-center text-muted f-12 py-2">No se encontraron vendedores</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Tipo de Agrupación -->
                    <div class="mt-3">
                        <span class="f-14"><b>Agrupación de Tiempo (Gráfico)</b></span>
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
                        <span class="f-14"><b>Métrica de Gráfico</b></span>
                        <select wire:model.live="metric" class="form-control form-control-sm mt-1">
                            <option value="amount">Monto Ventas USD</option>
                            <option value="count">Cantidad de Facturas</option>
                            <option value="commission">Comisiones USD</option>
                            <option value="net_sales">Ventas Netas (Margen)</option>
                            <option value="pending_debt">Cartera de Deuda Pendiente</option>
                        </select>
                    </div>

                    <!-- Rango de Fechas -->
                    <div class="mt-3">
                        <span class="f-14"><b>Desde</b></span>
                        <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm mt-1">
                    </div>
                    <div class="mt-2">
                        <span class="f-14"><b>Hasta</b></span>
                        <input type="date" wire:model.live="dateTo" class="form-control form-control-sm mt-1">
                    </div>

                    <!-- Botones de Acción -->
                    <div class="mt-4">
                        <button wire:key="btn-sellers-search" wire:click.prevent="searchData" class="btn btn-dark w-100">
                            <i class="fa fa-chart-bar"></i> Analizar Desempeño
                        </button>
                        <button wire:key="btn-sellers-pdf" wire:click.prevent="openPdfPreview" class="btn btn-danger text-white w-100 mt-2" @if(!$showReport) disabled @endif>
                            <i class="fas fa-file-pdf"></i> Reporte PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Resultados -->
        <div class="col-sm-12 col-md-9">
            <div class="card card-absolute">
                <div class="card-header bg-dark">
                    <h5 class="txt-light">Desempeño y Análisis Comparativo de Vendedores</h5>
                </div>

                <div class="card-body">
                    <!-- Mensaje de instrucción -->
                    <div class="alert alert-info text-center {{ !$showReport ? '' : 'd-none' }}">
                        Selecciona los vendedores y filtros correspondientes en la barra lateral y haz clic en "Analizar Desempeño" para iniciar la visualización.
                    </div>

                    <!-- Panel de Resultados -->
                    <div class="{{ !$showReport ? 'd-none' : '' }}">
                        <!-- Gráfico Interactivo Highcharts -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0 text-dark"><i class="fa fa-chart-line text-primary"></i> Tendencia Histórica por Vendedor</h6>
                            </div>
                            <div class="card-body" wire:ignore>
                                <div id="sellersPerformanceChart" style="height: 350px; width: 100%;"></div>
                            </div>
                        </div>

                        @if($showReport && isset($reportData['kpis']))
                            <!-- KPIs de Resumen -->
                            <h5 class="txt-primary mb-3"><i class="fa fa-info-circle"></i> Métricas Consolidadas del Periodo</h5>
                            <div class="row">
                                <!-- Ventas Totales -->
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-left border-primary h-100">
                                        <div class="card-body p-3">
                                            <div class="f-12 text-muted uppercase font-weight-bold">Ventas Totales Brutas</div>
                                            <div class="f-20 font-weight-bold text-dark mt-1">${{ number_format($reportData['kpis']['total_sales'], 2) }}</div>
                                            <div class="f-11 text-muted mt-1">Suma del canal seleccionado</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Ventas Netas / Margen -->
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-left border-success h-100">
                                        <div class="card-body p-3">
                                            <div class="f-12 text-muted uppercase font-weight-bold">Margen Neto Real</div>
                                            <div class="f-20 font-weight-bold text-success mt-1">${{ number_format($reportData['kpis']['net_sales'], 2) }}</div>
                                            <div class="f-11 mt-1 text-success">
                                                <b>{{ number_format($reportData['kpis']['margin_percent'], 1) }}%</b> de retorno neto
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Comisiones Totales -->
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-left border-warning h-100">
                                        <div class="card-body p-3">
                                            <div class="f-12 text-muted uppercase font-weight-bold">Gasto en Comisiones</div>
                                            <div class="f-20 font-weight-bold text-warning mt-1">${{ number_format($reportData['kpis']['total_commission'], 2) }}</div>
                                            <div class="f-11 text-muted mt-1">Costo de incentivos de venta</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Cartera Activa -->
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-left border-danger h-100">
                                        <div class="card-body p-3">
                                            <div class="f-12 text-muted uppercase font-weight-bold">Deuda Pendiente / Vencida</div>
                                            <div class="f-18 font-weight-bold text-danger mt-1">
                                                ${{ number_format($reportData['kpis']['total_debt'], 2) }}
                                            </div>
                                            <div class="f-11 text-muted mt-1">
                                                Vencido: <b class="text-danger">${{ number_format($reportData['kpis']['total_overdue'], 2) }}</b>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla Comparativa Detallada -->
                            <h5 class="txt-primary mt-3 mb-2"><i class="fa fa-table"></i> Desglose de Rendimiento por Vendedor</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover mt-1">
                                    <thead class="text-white" style="background: #3b3f5c">
                                        <tr>
                                            <th class="table-th text-white">Vendedor</th>
                                            <th class="table-th text-white text-center">Ventas Brutas USD</th>
                                            <th class="table-th text-white text-center">Facturas</th>
                                            <th class="table-th text-white text-center">Comisiones USD</th>
                                            <th class="table-th text-white text-center">Venta Neta USD</th>
                                            <th class="table-th text-white text-center">% Margen</th>
                                            <th class="table-th text-white text-center">Clts Activos</th>
                                            <th class="table-th text-white text-center">Deuda Pendiente</th>
                                            <th class="table-th text-white text-center">Deuda Vencida</th>
                                            <th class="table-th text-white text-center">Atraso Promedio</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reportData['sellers'] as $sellerData)
                                            <tr>
                                                <td class="font-weight-bold bg-light">{{ $sellerData['name'] }}</td>
                                                <td class="text-center font-weight-bold">${{ number_format($sellerData['gross_sales'], 2) }}</td>
                                                <td class="text-center">{{ number_format($sellerData['invoices_count'], 0) }}</td>
                                                <td class="text-center">${{ number_format($sellerData['commissions'], 2) }}</td>
                                                <td class="text-center text-success font-weight-bold">${{ number_format($sellerData['net_sales'], 2) }}</td>
                                                <td class="text-center font-weight-bold text-primary">{{ number_format($sellerData['margin_percent'], 1) }}%</td>
                                                <td class="text-center">{{ $sellerData['active_customers'] }}</td>
                                                <td class="text-center text-dark">${{ number_format($sellerData['pending_debt'], 2) }}</td>
                                                <td class="text-center text-danger font-weight-bold">${{ number_format($sellerData['overdue_debt'], 2) }}</td>
                                                <td class="text-center">
                                                    @if($sellerData['avg_days_overdue'] > 0)
                                                        <span class="badge badge-danger">{{ number_format($sellerData['avg_days_overdue'], 1) }} días</span>
                                                    @else
                                                        <span class="badge badge-success">Al día</span>
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="10" class="text-center text-muted">No hay registros de ventas para los vendedores en este período.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if(!empty($reportData['sellers']))
                                        <tfoot>
                                            <tr class="bg-dark text-white font-weight-bold">
                                                <td class="text-left text-white">TOTAL CONSOLIDADO:</td>
                                                <td class="text-center text-white">${{ number_format($reportData['kpis']['total_sales'], 2) }}</td>
                                                <td class="text-center text-white">{{ number_format(collect($reportData['sellers'])->sum('invoices_count'), 0) }}</td>
                                                <td class="text-center text-white">${{ number_format($reportData['kpis']['total_commission'], 2) }}</td>
                                                <td class="text-center text-success">${{ number_format($reportData['kpis']['net_sales'], 2) }}</td>
                                                <td class="text-center text-primary">{{ number_format($reportData['kpis']['margin_percent'], 1) }}%</td>
                                                <td class="text-center text-white">{{ collect($reportData['sellers'])->sum('active_customers') }}</td>
                                                <td class="text-center text-white">${{ number_format($reportData['kpis']['total_debt'], 2) }}</td>
                                                <td class="text-center text-danger">${{ number_format($reportData['kpis']['total_overdue'], 2) }}</td>
                                                <td class="text-center text-white">-</td>
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
                        <h5 class="modal-title text-white mb-0"><i class="fas fa-file-pdf"></i> Vista Previa Reporte de Desempeño de Vendedores</h5>
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

        chartInstance = Highcharts.chart('sellersPerformanceChart', {
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
                    text: null
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
                valuePrefix: '$'
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
