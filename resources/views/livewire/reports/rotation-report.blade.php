<div>
    <div class="row layout-top-spacing">
        <!-- Panel de Filtros -->
        <div class="col-12 layout-spacing">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white p-3">
                    <h5 class="mb-0 text-white"><i class="fas fa-filter mr-2"></i> Opciones de Análisis y Filtros</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Búsqueda -->
                        <div class="col-sm-12 col-md-3 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Buscar Producto</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Nombre o SKU...">
                            </div>
                        </div>

                        <!-- Categorías -->
                        <div class="col-sm-12 col-md-3 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Categoría</label>
                            <select wire:model.live="categoryId" class="form-control form-control-sm">
                                <option value="0">Todas las Categorías</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Proveedores -->
                        <div class="col-sm-12 col-md-3 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Proveedor</label>
                            <select wire:model.live="supplierId" class="form-control form-control-sm">
                                <option value="0">Todos los Proveedores</option>
                                @foreach($suppliers as $supplier)
                                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Clientes -->
                        <div class="col-sm-12 col-md-3 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Cliente</label>
                            <select wire:model.live="customerId" class="form-control form-control-sm">
                                <option value="0">Todos los Clientes</option>
                                @foreach($customers as $customer)
                                    <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Rango de Fechas -->
                        <div class="col-sm-12 col-md-3 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Desde</label>
                            <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-12 col-md-3 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Hasta</label>
                            <input type="date" wire:model.live="dateTo" class="form-control form-control-sm">
                        </div>

                        <!-- Proyección -->
                        <div class="col-sm-12 col-md-3 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Días de Cobertura de Compra</label>
                            <input type="number" wire:model.live="coverageDays" class="form-control form-control-sm" min="1">
                        </div>

                        <!-- Estado Rotación -->
                        <div class="col-sm-12 col-md-3 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Estado de Rotación</label>
                            <select wire:model.live="status" class="form-control form-control-sm">
                                <option value="">Todos los Estados</option>
                                <option value="high">Alta Rotación</option>
                                <option value="low">Baja Rotación</option>
                                <option value="none">Sin Movimiento</option>
                            </select>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="row mt-3">
                        <div class="col-12 text-right">
                            <span wire:loading wire:target="generatePdf, createPurchaseOrder" class="mr-3 text-muted"><i class="fas fa-spinner fa-spin"></i> Procesando...</span>
                            @if(is_array($selectedProducts) && count($selectedProducts) > 0)
                                <span class="mr-3 text-info font-weight-bold"><i class="fas fa-check-circle"></i> {{ count($selectedProducts) }} Seleccionados</span>
                            @endif
                            <button wire:click="generatePdf" class="btn btn-danger btn-sm">
                                <i class="fas fa-file-pdf"></i> Exportar PDF (Landscape)
                            </button>
                            <button wire:click="createPurchaseOrder" class="btn btn-primary btn-sm ml-2">
                                <i class="fas fa-shopping-cart"></i> Generar Orden de Compra
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Indicadores KPIs Principales -->
        <div class="col-12 layout-spacing">
            <div class="row">
                <!-- KPI 1: Capital de Inventario -->
                <div class="col-sm-12 col-md-3 mb-3">
                    <div class="card shadow-sm border-left border-primary h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Capital en Inventario</div>
                                <div class="bg-primary-light p-1 rounded-circle"><i class="fas fa-boxes text-primary"></i></div>
                            </div>
                            <div class="f-20 font-weight-bold text-dark mt-2">${{ number_format($totalCapital, 2) }}</div>
                            <div class="f-10 text-muted mt-1">Valor de stock a costo base</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 2: Capital Ocioso -->
                <div class="col-sm-12 col-md-3 mb-3">
                    <div class="card shadow-sm border-left border-danger h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Capital Ocioso (Sin Mov)</div>
                                <div class="bg-danger-light p-1 rounded-circle"><i class="fas fa-exclamation-triangle text-danger"></i></div>
                            </div>
                            <div class="f-20 font-weight-bold text-danger mt-2">${{ number_format($idleCapital, 2) }}</div>
                            <div class="f-10 text-muted mt-1">Stock de productos sin ventas</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 3: Margen Bruto Total -->
                <div class="col-sm-12 col-md-3 mb-3">
                    <div class="card shadow-sm border-left border-success h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Ganancia Bruta Ventas</div>
                                <div class="bg-success-light p-1 rounded-circle"><i class="fas fa-hand-holding-usd text-success"></i></div>
                            </div>
                            <div class="f-20 font-weight-bold text-success mt-2">${{ number_format($totalMargin, 2) }}</div>
                            <div class="f-10 text-muted mt-1">Margen total generado hoy</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 4: Margen Promedio % -->
                <div class="col-sm-12 col-md-3 mb-3">
                    <div class="card shadow-sm border-left border-info h-100">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Margen Promedio (%)</div>
                                <div class="bg-info-light p-1 rounded-circle"><i class="fas fa-percentage text-info"></i></div>
                            </div>
                            <div class="f-20 font-weight-bold text-dark mt-2">{{ number_format($avgMarginPercent, 2) }}%</div>
                            <div class="f-10 text-muted mt-1">Margen ponderado en ventas</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos de Análisis Visual -->
        <div class="col-12 layout-spacing">
            <div class="row">
                <!-- Gráfico Donut ABC -->
                <div class="col-sm-12 col-md-6 mb-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body" wire:ignore>
                            <div id="rotationAbcChart" style="height: 300px; width: 100%;"></div>
                        </div>
                    </div>
                </div>

                <!-- Gráfico de Barras Top 10 Rentabilidad -->
                <div class="col-sm-12 col-md-6 mb-3">
                    <div class="card shadow-sm border-0 h-100">
                        <div class="card-body" wire:ignore>
                            <div id="rotationProfitChart" style="height: 300px; width: 100%;"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Datos Principal -->
        <div class="col-12 layout-spacing">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white"><i class="fas fa-table mr-2"></i> Matriz de Rotación y Rentabilidad</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <thead class="text-white" style="background: #3b3f5c">
                                <tr>
                                    <th style="width: 40px;" class="text-center"></th>
                                    <th class="text-left text-white">Producto</th>
                                    <th style="width: 70px;" class="text-center text-white">Clase ABC</th>
                                    <th class="text-center text-white">Stock Actual</th>
                                    <th class="text-center text-white">Valor Stock (Costo)</th>
                                    <th class="text-center text-white">Vendido (U)</th>
                                    <th class="text-center text-white">Ventas (USD)</th>
                                    <th class="text-center text-white">Margen USD</th>
                                    <th class="text-center text-white">Margen %</th>
                                    <th class="text-center text-white">Velocidad (u/día)</th>
                                    <th class="text-center text-white">Sugerencia Compra</th>
                                    <th class="text-center text-white">Cobertura (Días)</th>
                                    <th class="text-center text-white">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($data as $product)
                                    <tr wire:key="row-{{ $product->id }}">
                                        <td class="text-center align-middle">
                                            <input type="checkbox" wire:model.live="selectedProducts" value="{{ $product->id }}">
                                        </td>
                                        <td class="align-middle">
                                            <span class="font-weight-bold text-dark">{{ $product->name }}</span>
                                        </td>
                                        <td class="text-center align-middle">
                                            @if($product->abc_class === 'A')
                                                <span class="badge badge-success px-2 py-1" style="background-color: #2ec4b6 !important;">A</span>
                                            @elseif($product->abc_class === 'B')
                                                <span class="badge badge-warning px-2 py-1" style="background-color: #ff9f1c !important; color: white;">B</span>
                                            @else
                                                <span class="badge badge-danger px-2 py-1" style="background-color: #e71d36 !important;">C</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle font-weight-bold">{{ $product->stock_qty }}</td>
                                        <td class="text-center align-middle text-muted">${{ number_format($product->stock_value, 2) }}</td>
                                        <td class="text-center align-middle font-weight-bold">{{ $product->total_sold }}</td>
                                        <td class="text-center align-middle">${{ number_format($product->sales_usd, 2) }}</td>
                                        <td class="text-center align-middle text-success font-weight-bold">
                                            @if($product->margin_usd > 0)
                                                +${{ number_format($product->margin_usd, 2) }}
                                            @elseif($product->margin_usd < 0)
                                                <span class="text-danger">-${{ number_format(abs($product->margin_usd), 2) }}</span>
                                            @else
                                                $0.00
                                            @endif
                                        </td>
                                        <td class="text-center align-middle align-middle">
                                            @if($product->margin_percent > 0)
                                                <span class="text-success font-weight-bold">{{ $product->margin_percent }}%</span>
                                            @elseif($product->margin_percent < 0)
                                                <span class="text-danger font-weight-bold">{{ $product->margin_percent }}%</span>
                                            @else
                                                0%
                                            @endif
                                        </td>
                                        <td class="text-center align-middle text-muted">{{ $product->velocity }}</td>
                                        <td class="text-center align-middle font-weight-bold text-primary">{{ $product->suggested_order }}</td>
                                        <td class="text-center align-middle align-middle">
                                            @if($product->coverage_days > 365)
                                                <span class="badge badge-info">> 1 Año</span>
                                            @else
                                                <span>{{ $product->coverage_days }} días</span>
                                            @endif
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge badge-{{ $product->status_color }} px-2 py-1">
                                                {{ $product->rotation_status }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="13" class="text-center py-4 text-muted">No se encontraron datos de rotación con los filtros actuales.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white border-0 py-3">
                    {{ $data->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Librería Highcharts -->
<script src="https://code.highcharts.com/highcharts.js"></script>

@script
<script>
    let abcChart = null;
    let profitChart = null;

    function renderCharts(abcData, topProfitData) {
        // Destroy existing charts to prevent memory leaks
        if (abcChart) { abcChart.destroy(); }
        if (profitChart) { profitChart.destroy(); }

        // 1. ABC Donut Chart
        abcChart = Highcharts.chart('rotationAbcChart', {
            chart: {
                type: 'pie',
                backgroundColor: 'transparent'
            },
            title: {
                text: 'Distribución ABC de Inventario',
                style: { fontSize: '14px', fontWeight: 'bold', color: '#1b55e2' }
            },
            subtitle: {
                text: 'Clasificado por participación de venta (Pareto 80/15/5)'
            },
            plotOptions: {
                pie: {
                    innerSize: '60%',
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.y} prods.'
                    }
                }
            },
            credits: { enabled: false },
            series: [{
                name: 'Cantidad de Productos',
                colorByPoint: true,
                data: abcData
            }]
        });

        // 2. Top 10 Profitable Products Chart
        let categories = topProfitData.map(p => p.name);
        let values = topProfitData.map(p => p.margin);

        profitChart = Highcharts.chart('rotationProfitChart', {
            chart: {
                type: 'bar',
                backgroundColor: 'transparent'
            },
            title: {
                text: 'Top 10 Productos con Mayor Ganancia Bruta',
                style: { fontSize: '14px', fontWeight: 'bold', color: '#1b55e2' }
            },
            subtitle: {
                text: 'Ganancia neta en USD (Venta - Costo)'
            },
            xAxis: {
                categories: categories,
                labels: { style: { fontSize: '9px' } }
            },
            yAxis: {
                title: { text: 'Margen USD' }
            },
            legend: { enabled: false },
            credits: { enabled: false },
            series: [{
                name: 'Margen USD',
                data: values,
                color: '#1a237e'
            }]
        });
    }

    $wire.on('updateRotationCharts', (event) => {
        let abcData, topProfitData;
        if (event && event.detail) {
            abcData = event.detail.abcData;
            topProfitData = event.detail.topProfitData;
        } else if (event && event.abcData) {
            abcData = event.abcData;
            topProfitData = event.topProfitData;
        }
        
        if (abcData && topProfitData) {
            renderCharts(abcData, topProfitData);
        }
    });
</script>
@endscript
