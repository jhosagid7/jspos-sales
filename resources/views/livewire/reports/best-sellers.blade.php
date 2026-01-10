    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title"><b>Reporte de Productos Más Vendidos</b></h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-3">
                            <label>Fecha Desde</label>
                            <input type="date" wire:model.live="dateFrom" class="form-control">
                        </div>
                        <div class="col-sm-3">
                            <label>Fecha Hasta</label>
                            <input type="date" wire:model.live="dateTo" class="form-control">
                        </div>
                        <div class="col-sm-3">
                            <label>Tipo de Gráfico</label>
                            <select wire:model.live="chartType" class="form-control">
                                <option value="column">Barras</option>
                                <option value="pie">Pastel</option>
                                <option value="doughnut">Dona</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-12">
                            <div class="card card-primary card-outline">
                                <div class="card-body" wire:ignore>
                                    <div id="container1" style="min-width: 310px; height: 400px; margin: 0 auto"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-body">
                                    <div class="row invoice-info">
                                        <div class="col-sm-3 invoice-col">
                                            <strong>Datos de Busqueda:</strong><br>
                                            <address>
                                                <strong>Desde: </strong> {{ \Carbon\Carbon::parse($dateFrom)->format('d-m-Y') }}<br>
                                                <strong>Hasta: </strong> {{ \Carbon\Carbon::parse($dateTo)->format('d-m-Y') }}<br>
                                                <strong>Operador: </strong>{{ auth()->user()->name }} <br>
                                                <strong>Tipo Busqueda: </strong>{{ $isDetailed ? 'Detallado' : 'Top 10' }} <br>
                                            </address>
                                        </div>
                                        <div class="col-sm-2 invoice-col">
                                            <strong>Descripcion:</strong><br>
                                            <address>
                                                <strong>Ventas</strong><br>
                                                <strong>Costos</strong><br>
                                                <strong>Productos vendidos</strong><br>
                                                <strong>Categorias Encontradas</strong><br>
                                                <strong>Productos Encontrados</strong><br>
                                            </address>
                                        </div>
                                        <div class="col-sm-2 invoice-col">
                                            <strong>Totales</strong><br>
                                            <address>
                                                <strong>$ {{ number_format($data->sum('total_sales'), 2) }}</strong><br>
                                                <strong>$ {{ number_format($data->sum('total_cost'), 2) }}</strong><br>
                                                <strong> {{ number_format($data->sum('total_qty')) }}</strong><br>
                                                <strong> {{ $data->pluck('category')->unique()->count() }}</strong><br>
                                                <strong> {{ $data->count() }}</strong><br>
                                            </address>
                                        </div>
                                        <div class="col-sm-2 invoice-col">
                                            <strong>Utilidad</strong><br>
                                            <address>
                                                <strong>$ {{ number_format($data->sum('profit'), 2) }}</strong><br>
                                            </address>
                                        </div>
                                        <div class="col-sm-1 invoice-col">
                                            <strong>%Prom</strong><br>
                                            <address>
                                                @php
                                                    $totalSales = $data->sum('total_sales');
                                                    $totalProfit = $data->sum('profit');
                                                    $avgMargin = $totalSales > 0 ? ($totalProfit / $totalSales) * 100 : 0;
                                                @endphp
                                                <strong>{{ number_format($avgMargin, 2) }}%</strong><br>
                                            </address>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card">
                                <div class="card-header">
                                    <h3 class="card-title">Detalle de Productos</h3>
                                </div>
                                <div class="card-body table-responsive p-0">
                                    <table class="table table-hover text-nowrap">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Producto</th>
                                                <th>Categoría</th>
                                                <th>Cant. Vendida</th>
                                                <th>Total Ventas</th>
                                                <th>% Cant.</th>
                                                <th>% Ventas</th>
                                                <th>Progreso (Cant.)</th>
                                                <th>Label</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($data as $index => $item)
                                                <tr>
                                                    <td>{{ $index + 1 }}</td>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            @if($item->image)
                                                                <img src="{{ $item->image }}" alt="Product Image" class="img-circle img-size-32 mr-2">
                                                            @endif
                                                            <span>{{ $item->name }}</span>
                                                        </div>
                                                    </td>
                                                    <td>{{ $item->category }}</td>
                                                    <td>{{ number_format($item->total_qty) }}</td>
                                                    <td>${{ number_format($item->total_sales, 2) }}</td>
                                                    <td>{{ number_format($item->percentage_qty, 2) }}%</td>
                                                    <td>{{ number_format($item->percentage_sales, 2) }}%</td>
                                                    <td style="vertical-align: middle;">
                                                        <div class="progress progress-xs">
                                                            @php
                                                                $color = 'success';
                                                                if ($item->percentage_qty <= 25) { $color = 'danger'; }
                                                                elseif ($item->percentage_qty <= 50) { $color = 'warning'; }
                                                                elseif ($item->percentage_qty <= 75) { $color = 'info'; }
                                                            @endphp
                                                            <div class="progress-bar bg-{{ $color }}" style="width: {{ $item->percentage_qty }}%"></div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-{{ $color }}">{{ number_format($item->percentage_qty, 2) }}%</span>
                                                    </td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="9" class="text-center">No se encontraron resultados</td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr>
                                                <th colspan="3" class="text-right">Totales:</th>
                                                <th>{{ number_format($data->sum('total_qty')) }}</th>
                                                <th>${{ number_format($data->sum('total_sales'), 2) }}</th>
                                                <th colspan="4"></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/data.js"></script>
    <script src="https://code.highcharts.com/modules/drilldown.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>

    <script>
        document.addEventListener('livewire:init', () => {
            
            const renderChart = (data, type) => {
                const seriesData = JSON.parse(data.series);
                const drilldownData = JSON.parse(data.drilldown);

                let chartType = type;
                let extraPlotOptions = {};

                if (type === 'doughnut') {
                    chartType = 'pie';
                    extraPlotOptions = {
                        pie: {
                            innerSize: '50%',
                            depth: 45
                        }
                    };
                }

                Highcharts.chart('container1', {
                    chart: {
                        type: chartType
                    },
                    title: {
                        align: 'left',
                        text: 'Top Productos Más Vendidos'
                    },
                    subtitle: {
                        align: 'left',
                        text: 'Haz click aqui si para ver perfil del desarrollador. Source: <a href="https://github.com/jhosagid7" target="_blank">jhonnypirela.dev</a>'
                    },
                    accessibility: {
                        announceNewData: {
                            enabled: true
                        }
                    },
                    xAxis: {
                        type: 'category'
                    },
                    yAxis: {
                        title: {
                            text: 'Total percent market share'
                        }
                    },
                    legend: {
                        enabled: false
                    },
                    plotOptions: {
                        series: {
                            borderWidth: 0,
                            dataLabels: {
                                enabled: true,
                                format: '<b style="font-size:10px">{point.name}: {point.y:.1f}%</b>'
                            }
                        },
                        ...extraPlotOptions
                    },
                    tooltip: {
                        headerFormat: '<span style="font-size:18px">{series.name}</span><br>',
                        pointFormat: '<span style="color:{point.color}; font-size:12px">{point.name}</span>: <b style="font-size:18px">{point.y:.2f}%</b><br /><br />'
                    },
                    series: [{
                        name: 'CATEGORIA',
                        colorByPoint: true,
                        data: seriesData
                    }],
                    drilldown: {
                        breadcrumbs: {
                            position: {
                                align: 'right'
                            }
                        },
                        series: drilldownData
                    }
                });
            };

            // Initial render
            renderChart(@json($highchartsData), '{{ $chartType }}');

            // Listen for updates
            Livewire.on('chart-updated', ({ data, type }) => {
                renderChart(data, type);
            });
        });
    </script>
</div>
