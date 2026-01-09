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
                                    @if($item->product->image)
                                        <img src="{{ asset('storage/' . $item->product->image) }}" alt="Product Image" class="img-size-50">
                                    @else
                                        <img src="https://via.placeholder.com/50" alt="Product Image" class="img-size-50">
                                    @endif
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
                    <canvas id="salesChart" style="min-height: 250px; height: 250px; max-height: 250px; max-width: 100%;"></canvas>
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
        </div>
    </div>


<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('livewire:init', () => {
        const ctx = document.getElementById('salesChart').getContext('2d');
        const salesChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: @json($salesChartData['labels']),
                datasets: [
                    {
                        label: 'Ventas Contado',
                        data: @json($salesChartData['cash']),
                        backgroundColor: 'rgba(40, 167, 69, 0.9)', // Green
                        borderColor: 'rgba(40, 167, 69, 0.8)',
                        pointRadius: false,
                        pointColor: '#28a745',
                        pointStrokeColor: '#28a745',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: '#28a745',
                        fill: false
                    },
                    {
                        label: 'Ventas Crédito',
                        data: @json($salesChartData['credit']),
                        backgroundColor: 'rgba(255, 193, 7, 0.9)', // Yellow
                        borderColor: 'rgba(255, 193, 7, 0.8)',
                        pointRadius: false,
                        pointColor: '#ffc107',
                        pointStrokeColor: '#ffc107',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: '#ffc107',
                        fill: false
                    },
                    {
                        label: 'Ganancias',
                        data: @json($profitChartData['data']),
                        backgroundColor: 'rgba(60, 141, 188, 0.9)', // Blue
                        borderColor: 'rgba(60, 141, 188, 0.8)',
                        pointRadius: false,
                        pointColor: '#3b8bba',
                        pointStrokeColor: 'rgba(60,141,188,1)',
                        pointHighlightFill: '#fff',
                        pointHighlightStroke: 'rgba(60,141,188,1)',
                        fill: false
                    }
                ]
            },
            options: {
                maintainAspectRatio: false,
                responsive: true,
                legend: {
                    display: true
                },
                scales: {
                    xAxes: [{
                        gridLines: {
                            display: false,
                        }
                    }],
                    yAxes: [{
                        gridLines: {
                            display: false,
                        }
                    }]
                }
            }
        });
    });
</script>
</div>