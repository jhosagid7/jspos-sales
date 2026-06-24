<div>
    <div class="row">
        <!-- Sidebar - Opciones de Consulta -->
        <div class="col-sm-12 col-md-3">
            <div class="card mb-3">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light mb-0">Opciones</h5>
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
                        <span class="f-14"><b>Agrupación de Tiempo</b></span>
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
                        <span class="f-14"><b>Métrica de Análisis</b></span>
                        <select wire:model.live="metric" class="form-control form-control-sm mt-1">
                            <option value="amount">Monto Ventas USD</option>
                            <option value="count">Cantidad de Facturas</option>
                            <option value="commission">Comisiones USD</option>
                            <option value="net_sales">Ventas Netas (Margen)</option>
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
                        <button wire:key="btn-sales-search" wire:click.prevent="searchData" class="btn btn-dark w-100">
                            <i class="fa fa-chart-line"></i> Consultar Actividad
                        </button>
                        <button wire:key="btn-sales-pdf" wire:click.prevent="openPdfPreview" class="btn btn-danger text-white w-100 mt-2" @if(!$showReport) disabled @endif>
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
                    <h5 class="txt-light">Análisis de Ventas y Crecimiento</h5>
                </div>

                <div class="card-body">
                    <!-- Mensaje de instrucción -->
                    <div class="alert alert-info text-center {{ !$showReport ? '' : 'd-none' }}">
                        Selecciona los filtros correspondientes en la barra lateral y haz clic en "Consultar Actividad" para ver el análisis de crecimiento y los gráficos.
                    </div>

                    <!-- Panel de Resultados -->
                    <div class="{{ !$showReport ? 'd-none' : '' }}">
                        <!-- Gráfico Interactivo Highcharts -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0 text-dark"><i class="fa fa-chart-area text-primary"></i> Tendencia de Ventas del Periodo</h6>
                            </div>
                            <div class="card-body" wire:ignore>
                                <div id="salesAnalysisChart" style="height: 350px; width: 100%;"></div>
                            </div>
                        </div>

                        @if($showReport && isset($reportData['kpis']))
                            <!-- KPIs de Resumen -->
                            <h5 class="txt-primary mb-3"><i class="fa fa-info-circle"></i> Indicadores de Crecimiento del Periodo</h5>
                            <div class="row">
                                <!-- Ventas Totales -->
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-left border-primary h-100">
                                        <div class="card-body p-3">
                                            <div class="f-12 text-muted uppercase font-weight-bold">Ventas Totales</div>
                                            <div class="f-20 font-weight-bold text-dark mt-1">${{ number_format($reportData['kpis']['total_sales'], 2) }}</div>
                                            <div class="f-11 mt-1 {{ $reportData['kpis']['growth_class'] }}">
                                                <b>{{ $reportData['kpis']['growth_arrow'] }} {{ number_format(abs($reportData['kpis']['growth_percent']), 1) }}%</b> vs. periodo anterior
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Ventas Netas -->
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-left border-success h-100">
                                        <div class="card-body p-3">
                                            <div class="f-12 text-muted uppercase font-weight-bold">Ventas Netas (Margen)</div>
                                            <div class="f-20 font-weight-bold text-success mt-1">${{ number_format($reportData['kpis']['net_sales'], 2) }}</div>
                                            <div class="f-11 text-muted mt-1">Excluye comisiones</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Comisiones -->
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-left border-warning h-100">
                                        <div class="card-body p-3">
                                            <div class="f-12 text-muted uppercase font-weight-bold">Comisiones Devengadas</div>
                                            <div class="f-20 font-weight-bold text-warning mt-1">${{ number_format($reportData['kpis']['total_commission'], 2) }}</div>
                                            <div class="f-11 text-muted mt-1">Incentivos de vendedores</div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Ticket Promedio -->
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-left border-info h-100">
                                        <div class="card-body p-3">
                                            <div class="f-12 text-muted uppercase font-weight-bold">Ticket Promedio</div>
                                            <div class="f-20 font-weight-bold text-dark mt-1">${{ number_format($reportData['kpis']['avg_ticket'], 2) }}</div>
                                            <div class="f-11 text-muted mt-1">En un total de <b>{{ $reportData['kpis']['sales_count'] }}</b> facturas</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabla Comparativa Detallada -->
                            <h5 class="txt-primary mt-3 mb-2"><i class="fa fa-table"></i> Detalle Comparativo por Periodo</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover mt-1">
                                    <thead class="text-white" style="background: #3b3f5c">
                                        <tr>
                                            <th class="table-th text-white text-center">Periodo</th>
                                            <th class="table-th text-white text-center">Ventas USD</th>
                                            <th class="table-th text-white text-center">Facturas</th>
                                            <th class="table-th text-white text-center">Comisiones USD</th>
                                            <th class="table-th text-white text-center">Venta Neta USD</th>
                                            <th class="table-th text-white text-center">Crecimiento %</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $totalSales = 0;
                                            $totalCount = 0;
                                            $totalComm = 0;
                                            $totalNet = 0;
                                            $lastVal = null;
                                        @endphp
                                        @forelse($reportData['results'] as $rowIndex => $row)
                                            @php
                                                $totalSales += $row->total_amount;
                                                $totalCount += $row->sales_count;
                                                $totalComm += $row->total_commission;
                                                $totalNet += $row->net_sales;

                                                // Growth rate comparing to the previous row in the table
                                                $rowGrowth = 0;
                                                $rowGrowthArrow = '';
                                                $rowGrowthClass = 'text-muted';
                                                
                                                if ($lastVal !== null) {
                                                    if ($lastVal > 0) {
                                                        $rowGrowth = (($row->total_amount - $lastVal) / $lastVal) * 100;
                                                    } else {
                                                        $rowGrowth = $row->total_amount > 0 ? 100 : 0;
                                                    }

                                                    if ($rowGrowth > 0) {
                                                        $rowGrowthArrow = '↑ ';
                                                        $rowGrowthClass = 'text-success';
                                                    } elseif ($rowGrowth < 0) {
                                                        $rowGrowthArrow = '↓ ';
                                                        $rowGrowthClass = 'text-danger';
                                                    }
                                                }
                                                $lastVal = $row->total_amount;
                                            @endphp
                                            <tr>
                                                <td class="text-center font-weight-bold bg-light">{{ $row->period_label }}</td>
                                                <td class="text-center font-weight-bold">${{ number_format($row->total_amount, 2) }}</td>
                                                <td class="text-center">{{ number_format($row->sales_count, 0) }}</td>
                                                <td class="text-center">${{ number_format($row->total_commission, 2) }}</td>
                                                <td class="text-center text-success">${{ number_format($row->net_sales, 2) }}</td>
                                                <td class="text-center font-weight-bold {{ $rowGrowthClass }}">
                                                    @if($rowIndex === 0)
                                                        <span class="text-muted">Inicio</span>
                                                    @else
                                                        {{ $rowGrowthArrow }}{{ number_format(abs($rowGrowth), 1) }}%
                                                    @endif
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted">No hay registros de ventas en los periodos indicados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if(!empty($reportData['results']))
                                        <tfoot>
                                            <tr class="bg-dark text-white font-weight-bold">
                                                <td class="text-center">TOTAL ACUMULADO:</td>
                                                <td class="text-center text-white">${{ number_format($totalSales, 2) }}</td>
                                                <td class="text-center text-white">{{ number_format($totalCount, 0) }}</td>
                                                <td class="text-center text-white">${{ number_format($totalComm, 2) }}</td>
                                                <td class="text-center text-success">${{ number_format($totalNet, 2) }}</td>
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
                        <h5 class="modal-title text-white mb-0"><i class="fas fa-file-pdf"></i> Vista Previa Reporte de Análisis de Ventas</h5>
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
            color: '#1a237e'
        }));

        if (chartInstance) {
            chartInstance.destroy();
        }

        chartInstance = Highcharts.chart('salesAnalysisChart', {
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
                    fillOpacity: 0.1,
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
