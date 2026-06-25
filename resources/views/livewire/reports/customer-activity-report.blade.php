<div>
    <div class="row">
        <!-- Sidebar - Opciones de Consulta -->
        <div class="col-sm-12 col-md-3">
            <div class="card mb-3">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light mb-0">Opciones</h5>
                </div>

                <div class="card-body">
                    <!-- Buscador y Selector de Clientes -->
                    <div class="mt-2">
                        <span class="f-14"><b>Buscar Clientes</b></span>
                        <input type="text" wire:model.live.debounce.300ms="searchCustomer" class="form-control form-control-sm mt-1 mb-2" placeholder="Escriba nombre de cliente...">
                        
                        <div class="border p-2 rounded" style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa;">
                            @forelse ($customersList as $customer)
                                <div class="custom-control custom-checkbox mb-1">
                                    <input type="checkbox" class="custom-control-input" id="cust_{{ $customer->id }}" value="{{ $customer->id }}" wire:model.live="selectedCustomers">
                                    <label class="custom-control-label f-12" for="cust_{{ $customer->id }}">{{ $customer->name }}</label>
                                </div>
                            @empty
                                <div class="text-center text-muted f-12 py-2">No se encontraron clientes</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Tipo de Periodo -->
                    <div class="mt-3">
                        <span class="f-14"><b>Agrupación de Tiempo</b></span>
                        <select wire:model.live="periodType" class="form-control form-control-sm mt-1">
                            <option value="weekly">Semanal</option>
                            <option value="monthly">Mensual</option>
                            <option value="quarterly">Trimestral</option>
                            <option value="yearly">Anual</option>
                        </select>
                    </div>

                    <!-- Métrica a Analizar -->
                    <div class="mt-3">
                        <span class="f-14"><b>Métrica de Análisis</b></span>
                        <select wire:model.live="metric" class="form-control form-control-sm mt-1">
                            <option value="amount">Monto de Compras (USD)</option>
                            <option value="count">Cantidad de Compras (Facturas)</option>
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
                        <button wire:key="btn-activity-search" wire:click.prevent="searchData" class="btn btn-dark w-100">
                            <i class="fa fa-chart-line"></i> Consultar Actividad
                        </button>
                        <button wire:key="btn-activity-pdf" wire:click.prevent="openPdfPreview" class="btn btn-danger text-white w-100 mt-2" @if(!$showReport || empty($selectedCustomers)) disabled @endif>
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
                    <h5 class="txt-light">Análisis de Compras del Cliente</h5>
                </div>

                <div class="card-body">
                    <!-- Mensaje de instrucción -->
                    <div class="alert alert-info text-center {{ (!$showReport || empty($selectedCustomers)) ? '' : 'd-none' }}">
                        Selecciona uno o más clientes en la barra lateral y haz clic en "Consultar Actividad" para ver los gráficos y tablas de tendencias.
                    </div>

                    <!-- Panel de Resultados -->
                    <div class="{{ (!$showReport || empty($selectedCustomers)) ? 'd-none' : '' }}">
                        <!-- Gráfico Interactivo -->
                        <div class="card mb-4 shadow-sm border-0">
                            <div class="card-header bg-light py-2">
                                <h6 class="mb-0 text-dark"><i class="fa fa-chart-area text-primary"></i> Tendencia de Compras del Periodo</h6>
                            </div>
                            <div class="card-body" wire:ignore>
                                <div style="height: 350px; position: relative;">
                                    <canvas id="activityChart"></canvas>
                                </div>
                            </div>
                        </div>

                        @if($showReport && !empty($selectedCustomers) && isset($reportData['kpis']))
                            <!-- KPIs Rápidos por Cliente -->
                            <h5 class="txt-primary mb-3"><i class="fa fa-info-circle"></i> Indicadores de Resumen</h5>
                            <div class="row">
                                @foreach ($reportData['kpis'] as $custId => $kpi)
                                    <div class="col-md-4 mb-3">
                                        <div class="card shadow-sm border-left border-primary">
                                            <div class="card-body p-3">
                                                <div class="f-13 font-weight-bold text-dark text-truncate" title="{{ $kpi['name'] }}">
                                                    {{ $kpi['name'] }}
                                                </div>
                                                <hr class="my-2">
                                                <div class="d-flex justify-content-between f-12 mb-1">
                                                    <span class="text-muted">Total USD:</span>
                                                    <span class="font-weight-bold">${{ number_format($kpi['total_amount'], 2) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between f-12 mb-1">
                                                    <span class="text-muted">Nro. Compras:</span>
                                                    <span class="font-weight-bold">{{ $kpi['sales_count'] }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between f-12 mb-1">
                                                    <span class="text-muted">Ticket Promedio:</span>
                                                    <span class="font-weight-bold">${{ number_format($kpi['avg_ticket'], 2) }}</span>
                                                </div>
                                                <div class="d-flex justify-content-between f-12">
                                                    <span class="text-muted">Última Compra:</span>
                                                    <span class="font-weight-bold text-info">{{ $kpi['last_purchase_at'] }}</span>
                                                </div>
                                                <div class="mt-2 pt-2 border-top">
                                                    <span class="f-11 font-weight-bold text-dark"><i class="fa fa-star text-warning"></i> Top Productos:</span>
                                                    <ul class="pl-3 mb-0 f-11" style="list-style-type: square;">
                                                        @forelse($kpi['top_products'] as $prod)
                                                            <li class="text-truncate" title="{{ $prod->product_name }}">
                                                                {{ $prod->product_name }} ({{ number_format($prod->total_qty, 0) }} uds)
                                                            </li>
                                                        @empty
                                                            <li class="text-muted list-unstyled">Ninguno</li>
                                                        @endforelse
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <!-- Tabla Comparativa Detallada -->
                            <h5 class="txt-primary mt-3 mb-2"><i class="fa fa-table"></i> Detalle Comparativo por Periodo</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped table-hover mt-1">
                                    <thead class="text-white" style="background: #3b3f5c">
                                        <tr>
                                            <th class="table-th text-white text-center" style="width: 15%;">Periodo</th>
                                            @foreach ($reportData['kpis'] as $custId => $kpi)
                                                <th class="table-th text-white text-center">{{ $kpi['name'] }}</th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @php
                                            $grandTotals = array_fill_keys(array_keys($reportData['kpis']), 0);
                                        @endphp
                                        @forelse($reportData['labels'] as $labelIndex => $periodLabel)
                                            <tr>
                                                <td class="text-center font-weight-bold bg-light">{{ $periodLabel }}</td>
                                                @foreach ($reportData['kpis'] as $custId => $kpi)
                                                    @php
                                                        $val = $reportData['datasets'][array_search($kpi['name'], array_column($reportData['datasets'], 'label'))]['data'][$labelIndex] ?? 0;
                                                        $grandTotals[$custId] += $val;
                                                    @endphp
                                                    <td class="text-center font-weight-bold">
                                                        @if($metric === 'count')
                                                            {{ number_format($val, 0) }}
                                                        @else
                                                            ${{ number_format($val, 2) }}
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="20" class="text-center text-muted">No hay registros de compras en los periodos indicados.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if(!empty($reportData['labels']))
                                        <tfoot>
                                            <tr class="bg-dark text-white font-weight-bold">
                                                <td class="text-center">TOTAL ACUMULADO:</td>
                                                @foreach ($reportData['kpis'] as $custId => $kpi)
                                                    <td class="text-center text-white">
                                                        @if($metric === 'count')
                                                            {{ number_format($grandTotals[$custId], 0) }}
                                                        @else
                                                            ${{ number_format($grandTotals[$custId], 2) }}
                                                        @endif
                                                    </td>
                                                @endforeach
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
                        <h5 class="modal-title text-white mb-0"><i class="fas fa-file-pdf"></i> Vista Previa Reporte de Actividad</h5>
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

<!-- Carga de Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

@script
<script>
    let ctx = document.getElementById('activityChart')?.getContext('2d');
    let myChart = null;

    $wire.on('updateChart', (event, ...args) => {
        let labels, datasets;
        if (event && event.detail) {
            labels = event.detail.labels;
            datasets = event.detail.datasets;
        } else if (event && event.labels) {
            labels = event.labels;
            datasets = event.datasets;
        } else if (Array.isArray(event) && args.length > 0 && Array.isArray(args[0])) {
            labels = event;
            datasets = args[0];
        }

        if (!labels || !datasets) {
            console.error('Failed to extract labels or datasets from event:', event, args);
            return;
        }

        if (!ctx) {
            ctx = document.getElementById('activityChart')?.getContext('2d');
        }
        if (!ctx) return;

        if (myChart) {
            myChart.destroy();
        }

        myChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    mode: 'index',
                    intersect: false
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            color: '#555',
                            font: { size: 10 }
                        },
                        grid: { color: '#eaeaea' }
                    },
                    x: {
                        ticks: {
                            color: '#555',
                            font: { size: 9 }
                        },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top',
                        labels: {
                            color: '#333',
                            font: { size: 10, weight: 'bold' }
                        }
                    },
                    tooltip: {
                        padding: 10,
                        backgroundColor: 'rgba(0, 0, 0, 0.8)'
                    }
                }
            }
        });
    });
</script>
@endscript
