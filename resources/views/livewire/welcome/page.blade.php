<div>
    <div class="row">
        <!-- KPI Cards -->
        <div class="col-lg col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>${{ number_format($totalSalesToday, 2) }}</h3>
                    <p>Ventas de Hoy</p>
                </div>
                <div class="icon">
                    <i class="fas fa-cash-register"></i>
                </div>
                <a href="{{ route('sales') }}" class="small-box-footer">Ir a Ventas <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg col-6">
            <div class="small-box bg-info">
                <div class="inner">
                    <h3>${{ number_format($totalSalesMonth, 2) }}</h3>
                    <p>Ventas del Mes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <a href="{{ route('reports.sales') }}" class="small-box-footer">Ver Reporte <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>${{ number_format($totalPurchasesMonth, 2) }}</h3>
                    <p>Compras del Mes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <a href="{{ route('purchases') }}" class="small-box-footer">Ir a Compras <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>${{ number_format($totalReceivables, 2) }}</h3>
                    <p>Cuentas por Cobrar</p>
                </div>
                <div class="icon">
                    <i class="fas fa-hand-holding-usd"></i>
                </div>
                <a href="{{ route('reports.accounts.receivable') }}" class="small-box-footer">Ver Cuentas <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
        <div class="col-lg col-6">
            <div class="small-box bg-purple">
                <div class="inner">
                    <h3>${{ number_format($pendingCommissions, 2) }}</h3>
                    <p>Comisiones Pendientes</p>
                </div>
                <div class="icon">
                    <i class="fas fa-percentage"></i>
                </div>
                <a href="{{ route('commissions') }}" class="small-box-footer">Gestionar <i class="fas fa-arrow-circle-right"></i></a>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Left Column: Recent Sales -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header border-0">
                    <h3 class="card-title">Ventas Recientes</h3>
                    <div class="card-tools">
                        <a href="{{ route('sales') }}" class="btn btn-tool btn-sm">
                            <i class="fas fa-plus"></i> Nueva Venta
                        </a>
                    </div>
                </div>
                <div class="card-body table-responsive p-0">
                    <table class="table table-striped table-valign-middle">
                        <thead>
                        <tr>
                            <th>Factura</th>
                            <th>Cliente</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($recentSales as $sale)
                            <tr>
                                <td>{{ $sale->invoice_number ?? $sale->id }}</td>
                                <td>{{ $sale->customer->name ?? 'N/A' }}</td>
                                <td>${{ number_format($sale->total, 2) }}</td>
                                <td>
                                    @if($sale->status == 'paid')
                                        <span class="badge badge-success">Pagado</span>
                                    @elseif($sale->status == 'pending')
                                        <span class="badge badge-warning">Pendiente</span>
                                    @else
                                        <span class="badge badge-danger">{{ ucfirst($sale->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $sale->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Right Column: Top Products & Low Stock -->
        <div class="col-lg-4">
            <!-- Top Products -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Productos Más Vendidos</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="products-list product-list-in-card pl-2 pr-2">
                        @foreach($topProducts as $item)
                            <li class="item">
                                <div class="product-img">
                                    @php
                                        $image = $item->product->photo;
                                        if ($image && !str_starts_with($image, 'http')) {
                                            $image = asset($image);
                                        }
                                    @endphp
                                    <img src="{{ $image }}" alt="Product Image" class="img-size-50">
                                </div>
                                <div class="product-info">
                                    <a href="javascript:void(0)" class="product-title">{{ Str::limit($item->product->name, 20) }}
                                        <span class="badge badge-info float-right">{{ $item->total_qty }} Und</span></a>
                                    <span class="product-description">
                                        {{ $item->product->barcode }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Low Stock Alerts -->
            <div class="card">
                <div class="card-header bg-danger">
                    <h3 class="card-title">Alertas de Stock Bajo</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="products-list product-list-in-card pl-2 pr-2">
                        @foreach($lowStockProducts as $product)
                            <li class="item">
                                <div class="product-info ml-0">
                                    <a href="javascript:void(0)" class="product-title">{{ $product->name }}
                                        <span class="badge badge-warning float-right">{{ $product->stock_qty }} Left</span></a>
                                    <span class="product-description">
                                        Min: {{ $product->low_stock }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>

    </div>

    <div class="row">
        <!-- Charts -->
        <div class="col-lg-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Ventas vs Ganancias (Últimos 7 Días)</h3>
                </div>
                <div class="card-body">
                    <div id="salesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></div>
                </div>
            </div>
        </div>

        <!-- Extra Widgets -->
        <div class="col-lg-4">


            <!-- Top Suppliers -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Top Proveedores</h3>
                </div>
                <div class="card-body p-0">
                    <ul class="products-list product-list-in-card pl-2 pr-2">
                        @foreach($topSuppliers as $item)
                            <li class="item">
                                <div class="product-info ml-0">
                                    <a href="javascript:void(0)" class="product-title">{{ $item->supplier->name ?? 'N/A' }}
                                        <span class="badge badge-primary float-right">${{ number_format($item->total_purchased, 2) }}</span></a>
                                    <span class="product-description">
                                        {{ $item->supplier->phone ?? '' }}
                                    </span>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>

            <!-- Top Sellers Chart -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Top Vendedores (Ganancia)</h3>
                    <div class="card-tools">
                        <select id="chartTypeSelector" class="form-control form-control-sm" onchange="changeChartType(this.value)">
                            <option value="column">Columnas</option>
                            <option value="bar" selected>Barras</option>
                            <option value="pie">Pastel</option>
                            <option value="donut">Dona</option>
                        </select>
                    </div>
                </div>
                <div class="card-body">
                    <div id="topSellersChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></div>
                </div>
            </div>
        </div>
    </div>


    <script src="https://code.highcharts.com/highcharts.js"></script>
    <script src="https://code.highcharts.com/modules/exporting.js"></script>
    <script src="https://code.highcharts.com/modules/export-data.js"></script>
    <script src="https://code.highcharts.com/modules/accessibility.js"></script>

    <script>
        let topSellersChart; // Global variable to access the chart

        document.addEventListener('livewire:init', () => {
            
            Highcharts.chart('salesChart', {
                chart: {
                    type: 'spline'
                },
                title: {
                    text: ''
                },
                xAxis: {
                    categories: @json($salesChartData['labels']),
                    crosshair: true
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Monto ($)'
                    }
                },
                tooltip: {
                    headerFormat: '<span style="font-size:10px">{point.key}</span><table>',
                    pointFormat: '<tr><td style="color:{series.color};padding:0">{series.name}: </td>' +
                        '<td style="padding:0"><b>${point.y:.2f}</b></td></tr>',
                    footerFormat: '</table>',
                    shared: true,
                    useHTML: true
                },
                plotOptions: {
                    column: {
                        pointPadding: 0.2,
                        borderWidth: 0
                    }
                },
                series: [{
                    name: 'Ventas Contado',
                    data: @json($salesChartData['cash']),
                    color: '#28a745'
                }, {
                    name: 'Ventas Crédito',
                    data: @json($salesChartData['credit']),
                    color: '#ffc107'
                }, {
                    name: 'Ganancias',
                    data: @json($profitChartData['data']),
                    color: '#3b8bba'
                }]
            });

            const topSellersData = @json($topSellersChartData ?? []);
            
            // Initialize Top Sellers Chart
            topSellersChart = Highcharts.chart('topSellersChart', {
                chart: {
                    type: 'bar'
                },
                title: {
                    text: ''
                },
                xAxis: {
                    type: 'category'
                },
                yAxis: {
                    min: 0,
                    title: {
                        text: 'Ganancia ($)'
                    }
                },
                tooltip: {
                    pointFormat: '<b>${point.y:.2f}</b>'
                },
                plotOptions: {
                    series: {
                        borderWidth: 0,
                        dataLabels: {
                            enabled: true,
                            format: '${point.y:.1f}'
                        }
                    },
                    pie: {
                        allowPointSelect: true,
                        cursor: 'pointer',
                        dataLabels: {
                            enabled: true,
                            format: '<b>{point.name}</b>: {point.percentage:.1f} %'
                        }
                    }
                },
                series: [{
                    name: 'Ganancia',
                    colorByPoint: true,
                    data: topSellersData,
                    showInLegend: false
                }]
            });
        });

        function changeChartType(type) {
            if (!topSellersChart) return;

            let newType = type;
            let options = {};

            if (type === 'donut') {
                newType = 'pie';
                options = {
                    innerSize: '50%'
                };
            } else {
                options = {
                    innerSize: '0%'
                };
            }

            topSellersChart.update({
                chart: {
                    type: newType
                },
                plotOptions: {
                    pie: options
                }
            });
        }
    </script>
</div>