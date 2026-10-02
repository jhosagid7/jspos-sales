<div>
    <div class="row sales layout-top-spacing">
        <div class="col-sm-12">
            <div class="widget widget-chart-one">
                <div class="widget-heading d-flex justify-content-between align-items-center flex-wrap">
                    <h4 class="card-title mb-2">
                        <b>RENTABILIDAD Y PRECIOS MENSUALES POR PRODUCTO</b>
                    </h4>
                    @if($product_id)
                    <div class="btn-group ml-auto mb-2">
                        <button class="btn btn-primary" wire:click="openPdfPreview">
                            <i class="fas fa-file-pdf"></i> Previsualizar / Imprimir PDF
                        </button>
                    </div>
                    @endif
                </div>

                <div class="widget-content">
                    <div class="row">
                        <!-- BUSCADOR DE PRODUCTO INTELIGENTE -->
                        <div class="col-sm-12 col-md-5">
                            <div class="form-group position-relative" 
                                x-data="{ 
                                    selectedIndex: -1,
                                    get itemCount() {
                                        return this.$refs.resultsList ? this.$refs.resultsList.children.length : 0;
                                    },
                                    navigate(direction) {
                                        if (this.itemCount === 0) return;
                                        if (direction === 'down') {
                                            this.selectedIndex = (this.selectedIndex < this.itemCount - 1) ? this.selectedIndex + 1 : 0;
                                        } else {
                                            this.selectedIndex = (this.selectedIndex > 0) ? this.selectedIndex - 1 : this.itemCount - 1;
                                        }
                                    },
                                    selectItem() {
                                        if (this.selectedIndex >= 0 && this.$refs.resultsList) {
                                            const item = this.$refs.resultsList.children[this.selectedIndex];
                                            if (item) item.click();
                                        }
                                    }
                                }">
                                
                                <label class="font-weight-bold">Producto (Nombre o SKU)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" 
                                        class="form-control" 
                                        placeholder="Escribe para buscar producto..."
                                        wire:model.live.debounce.300ms="search"
                                        @keydown.arrow-down.prevent="navigate('down')"
                                        @keydown.arrow-up.prevent="navigate('up')"
                                        @keydown.enter.prevent="selectItem()"
                                        @focus="$wire.searchProducts()"
                                    >
                                    @if($product_id)
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-danger" title="Limpiar selección" wire:click="$set('product_id', null); $set('search', ''); $set('selected_product_name', ''); $wire.calculateReport()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    @endif
                                </div>

                                <!-- Resultados de Busqueda -->
                                @if(!empty($products_results))
                                <ul x-ref="resultsList" class="list-group position-absolute w-100 shadow-lg search-results-list" style="z-index: 1050; max-height: 280px; overflow-y: auto;">
                                    @foreach($products_results as $index => $p)
                                    <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-2" 
                                        wire:click="selectProduct({{ $p['id'] }})" 
                                        style="cursor: pointer;"
                                        :class="{ 'bg-primary text-white': selectedIndex === {{ $index }} }"
                                        @mouseenter="selectedIndex = {{ $index }}">
                                        <div>
                                            <span class="badge badge-dark mr-1">{{ $p['sku'] ?? 'S/SKU' }}</span>
                                            <strong class="text-uppercase">{{ $p['name'] }}</strong>
                                        </div>
                                        <span class="badge badge-success">${{ number_format($p['price'], 2) }}</span>
                                    </li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>
                        </div>

                        <!-- FECHA DESDE -->
                        <div class="col-sm-6 col-md-2">
                            <div class="form-group">
                                <label class="font-weight-bold">Desde</label>
                                <input type="date" class="form-control" wire:model.live="dateFrom">
                            </div>
                        </div>

                        <!-- FECHA HASTA -->
                        <div class="col-sm-6 col-md-2">
                            <div class="form-group">
                                <label class="font-weight-bold">Hasta</label>
                                <input type="date" class="form-control" wire:model.live="dateTo">
                            </div>
                        </div>

                        <!-- ALMACEN -->
                        <div class="col-sm-12 col-md-3">
                            <div class="form-group">
                                <label class="font-weight-bold">Almacén / Sucursal</label>
                                <select class="form-control" wire:model.live="selected_warehouse_id">
                                    <option value="all">Todos los Almacenes</option>
                                    @foreach($warehouses as $wh)
                                    <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- ACCESOS RÁPIDOS DE FECHA Y FILTROS -->
                    <div class="row align-items-center mb-3">
                        <div class="col-md-8 col-sm-12 mb-2">
                            <span class="font-weight-bold mr-2 text-muted"><i class="fas fa-calendar-alt"></i> Rango rápido:</span>
                            <div class="btn-group btn-group-sm" role="group">
                                <button type="button" class="btn btn-outline-secondary" wire:click="setPreset('this_year')">Año Actual</button>
                                <button type="button" class="btn btn-outline-secondary" wire:click="setPreset('since_may')">Desde Mayo</button>
                                <button type="button" class="btn btn-outline-secondary" wire:click="setPreset('last_6_months')">Últimos 6 Meses</button>
                                <button type="button" class="btn btn-outline-secondary" wire:click="setPreset('last_3_months')">Últimos 3 Meses</button>
                            </div>
                        </div>
                        <div class="col-md-4 col-sm-12 mb-2 text-md-right">
                            <div class="custom-control custom-switch d-inline-block">
                                <input type="checkbox" class="custom-control-input" id="switchHideZero" wire:model.live="hideZeroMonths">
                                <label class="custom-control-label font-weight-normal text-muted" for="switchHideZero">
                                    Ocultar meses sin ventas
                                </label>
                            </div>
                        </div>
                    </div>

                    @if($product_id)
                    <!-- TARJETAS DE TOTALES GLOBALES (KPIs) -->
                    <div class="row mt-3 mb-4">
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="card bg-info text-white shadow-sm p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-uppercase font-weight-bold">Unidades Vendidas</small>
                                        <h3 class="mb-0 font-weight-bold">{{ number_format($totals['total_qty'], 2) }}</h3>
                                    </div>
                                    <i class="fas fa-boxes fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="card bg-primary text-white shadow-sm p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-uppercase font-weight-bold">Venta Total Facturada</small>
                                        <h3 class="mb-0 font-weight-bold">${{ number_format($totals['total_sold'], 2) }}</h3>
                                    </div>
                                    <i class="fas fa-cash-register fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="card bg-secondary text-white shadow-sm p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-uppercase font-weight-bold">Costo Total Mercancía</small>
                                        <h3 class="mb-0 font-weight-bold">${{ number_format($totals['total_cost'], 2) }}</h3>
                                    </div>
                                    <i class="fas fa-dolly-flatbed fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <div class="card {{ $totals['total_profit'] >= 0 ? 'bg-success' : 'bg-danger' }} text-white shadow-sm p-3 rounded">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="text-uppercase font-weight-bold">Ganancia Neta (Margen)</small>
                                        <h3 class="mb-0 font-weight-bold">
                                            ${{ number_format($totals['total_profit'], 2) }}
                                            <span style="font-size: 0.6em;">({{ number_format($totals['margin_percent'], 2) }}%)</span>
                                        </h3>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TABLA DETALLADA POR MES -->
                    <div class="table-responsive mt-2">
                        <table class="table table-bordered table-striped table-hover mb-4">
                            <thead class="bg-dark text-white">
                                <tr class="text-center">
                                    <th style="width: 14%;">Mes / Período</th>
                                    <th style="width: 18%;">Precio(s) Costo</th>
                                    <th style="width: 24%;">Precio(s) Venta</th>
                                    <th style="width: 10%;">Cant. Vendida</th>
                                    <th style="width: 11%;">Total Costo</th>
                                    <th style="width: 11%;">Total Venta</th>
                                    <th style="width: 12%;">Ganancia</th>
                                    <th style="width: 10%;">Margen %</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($monthlyData as $row)
                                <tr class="text-center align-middle">
                                    <td class="font-weight-bold text-left pl-3">
                                        <i class="far fa-calendar-alt text-primary mr-1"></i> {{ $row['period'] }}
                                    </td>
                                    
                                    <!-- PRECIOS DE COSTO -->
                                    <td class="text-left">
                                        @if($row['has_cost_changes'])
                                            <div class="d-flex flex-column gap-1">
                                                <span class="badge badge-warning mb-1" title="Hubo variaciones de costo en el mes">
                                                    <i class="fas fa-exchange-alt"></i> Varió en el mes
                                                </span>
                                                @foreach($row['cost_breakdown'] as $cb)
                                                    <small class="text-muted">
                                                        • ${{ number_format($cb['cost'], 2) }} 
                                                        <span class="text-dark">({{ number_format($cb['qty'], 0) }} unds - {{ $cb['source'] }})</span>
                                                    </small>
                                                @endforeach
                                                <small class="font-weight-bold text-primary border-top pt-1">
                                                    Prom: ${{ number_format($row['cost_unit'], 2) }}
                                                </small>
                                            </div>
                                        @else
                                            <span class="font-weight-bold">${{ number_format($row['cost_unit'], 2) }}</span>
                                        @endif
                                    </td>

                                    <!-- PRECIOS DE VENTA -->
                                    <td class="text-left">
                                        @if(empty($row['price_breakdown']))
                                            <span class="text-muted font-italic">Sin ventas</span>
                                        @elseif($row['has_price_changes'])
                                            <div class="d-flex flex-column">
                                                <span class="badge badge-info mb-1" title="El producto se vendió a distintos precios en este mes">
                                                    <i class="fas fa-tags"></i> Múltiples Precios
                                                </span>
                                                @foreach($row['price_breakdown'] as $pb)
                                                    <span class="badge badge-light border text-dark mb-1 text-left">
                                                        <b>${{ number_format($pb['price'], 2) }}</b> 
                                                        <span class="text-muted">({{ number_format($pb['qty'], 2) }} unds &rarr; ${{ number_format($pb['subtotal'], 2) }})</span>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @else
                                            <span class="font-weight-bold text-success" style="font-size: 1.05em;">
                                                ${{ number_format($row['price_breakdown'][0]['price'], 2) }}
                                            </span>
                                        @endif
                                    </td>

                                    <!-- CANTIDAD VENDIDA -->
                                    <td class="font-weight-bold {{ $row['sold_qty'] > 0 ? 'text-primary' : 'text-muted' }}">
                                        {{ number_format($row['sold_qty'], 2) }}
                                    </td>

                                    <!-- TOTAL COSTO -->
                                    <td>
                                        ${{ number_format($row['cost_amount'], 2) }}
                                    </td>

                                    <!-- TOTAL VENTA -->
                                    <td class="font-weight-bold">
                                        ${{ number_format($row['sold_amount'], 2) }}
                                    </td>

                                    <!-- GANANCIA -->
                                    <td class="font-weight-bold {{ $row['profit_amount'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        ${{ number_format($row['profit_amount'], 2) }}
                                    </td>

                                    <!-- MARGEN % -->
                                    <td>
                                        @if($row['sold_amount'] > 0)
                                            <span class="badge {{ $row['margin_percent'] >= 30 ? 'badge-success' : ($row['margin_percent'] > 0 ? 'badge-warning' : 'badge-danger') }} p-2">
                                                {{ number_format($row['margin_percent'], 2) }}%
                                            </span>
                                        @else
                                            <span class="text-muted">0.00%</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        No se encontraron registros para el rango de fechas seleccionado.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            <tfoot class="bg-light font-weight-bold text-center border-top-2" style="font-size: 1.05em;">
                                <tr>
                                    <th class="text-left pl-3 text-uppercase">SUMATORIA TOTAL:</th>
                                    <th>-</th>
                                    <th>-</th>
                                    <th class="text-primary">{{ number_format($totals['total_qty'], 2) }}</th>
                                    <th>${{ number_format($totals['total_cost'], 2) }}</th>
                                    <th class="text-dark">${{ number_format($totals['total_sold'], 2) }}</th>
                                    <th class="{{ $totals['total_profit'] >= 0 ? 'text-success' : 'text-danger' }}">
                                        ${{ number_format($totals['total_profit'], 2) }}
                                    </th>
                                    <th>
                                        <span class="badge badge-dark p-2">
                                            {{ number_format($totals['margin_percent'], 2) }}%
                                        </span>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-info text-center mt-4 p-4 shadow-sm">
                        <i class="fas fa-search fa-3x mb-3 text-info"></i>
                        <h5><b>Selecciona un producto para visualizar el reporte</b></h5>
                        <p class="mb-0 text-muted">Utiliza el buscador superior para escribir el nombre, código o SKU del producto y consultar su historial de precios y rentabilidad mensual.</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL PDF PREVIEW -->
    @if($showPdfModal)
    <div class="modal fade show d-block" tabindex="-1" style="background-color: rgba(0,0,0,0.6);" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 90%; height: 90vh;">
            <div class="modal-content h-100 shadow-lg">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-file-pdf mr-2"></i> Reporte de Rentabilidad Mensual - {{ $selected_product_name }}
                    </h5>
                    <button type="button" class="close text-white" wire:click="closePdfPreview" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0" style="height: calc(100% - 120px);">
                    <iframe src="{{ $pdfUrl }}" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" wire:click="closePdfPreview">Cerrar</button>
                    <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-primary">
                        <i class="fas fa-external-link-alt"></i> Abrir en Pestaña Nueva
                    </a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
