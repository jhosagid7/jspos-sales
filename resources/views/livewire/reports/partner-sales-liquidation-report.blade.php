<div>
    <div class="row sales layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-md-12 col-12 layout-spacing">
            <div class="widget widget-chart-one">
            <div class="widget-heading d-flex justify-content-between align-items-center">
                <h4 class="card-title text-uppercase font-weight-bold mb-0">
                    <i class="fas fa-handshake text-primary mr-2"></i> Reporte de Ventas y Liquidación por {{ term('partner') }} / {{ term('warehouse') }} de Origen
                </h4>
                <div>
                    <button wire:click="openPdf" class="btn btn-outline-danger btn-sm shadow-sm">
                        <i class="fas fa-file-pdf mr-1"></i> Exportar PDF
                    </button>
                </div>
            </div>

            <div class="widget-content widget-content-area">
                {{-- KPI Cards --}}
                <div class="row mb-2">
                    <div class="col-xl-3 col-md-6 col-12 mb-2">
                        <div class="card border-0 shadow-sm rounded-lg bg-light text-center p-3 h-100">
                            <span class="text-muted font-weight-bold text-uppercase small">Stock en {{ term('warehouse') }} {{ term('partner') }}</span>
                            <h3 class="font-weight-bold text-info mt-1 mb-0">{{ number_format($kpis['origin_physical_stock'], 2) }}</h3>
                            <small class="text-muted">Unidades físicas sin traspasar</small>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 col-12 mb-2">
                        <div class="card border-0 shadow-sm rounded-lg bg-light text-center p-3 h-100">
                            <span class="text-muted font-weight-bold text-uppercase small">Stock Remanente en Tienda</span>
                            <h3 class="font-weight-bold text-warning mt-1 mb-0">{{ number_format($kpis['remaining_in_store'], 2) }}</h3>
                            <small class="text-muted">Unidades traspasadas por vender</small>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 col-12 mb-2">
                        <div class="card border-0 shadow-sm rounded-lg bg-light text-center p-3 h-100">
                            <span class="text-muted font-weight-bold text-uppercase small">Unidades Vendidas</span>
                            <h3 class="font-weight-bold text-primary mt-1 mb-0">{{ number_format($kpis['total_qty_sold'], 2) }}</h3>
                            <small class="text-muted">{{ $kpis['total_distinct_products'] }} productos diferentes</small>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6 col-12 mb-2">
                        <div class="card border-0 shadow-sm rounded-lg bg-light text-center p-3 h-100">
                            <span class="text-muted font-weight-bold text-uppercase small">Monto Total Facturado</span>
                            <h3 class="font-weight-bold text-dark mt-1 mb-0">${{ number_format($kpis['total_amount_sold'], 2) }}</h3>
                            <small class="text-muted">{{ $kpis['total_sales_count'] }} facturas vinculadas</small>
                        </div>
                    </div>
                </div>

                {{-- Profitability KPI Row --}}
                <div class="row mb-3">
                    <div class="col-xl-4 col-md-4 col-12 mb-2">
                        <div class="card border-0 shadow-sm rounded-lg bg-light text-center p-3 h-100" style="border-left: 4px solid #6c757d !important;">
                            <span class="text-muted font-weight-bold text-uppercase small">Costo Total Mercancía</span>
                            <h3 class="font-weight-bold text-secondary mt-1 mb-0">${{ number_format($kpis['total_cost_sold'] ?? 0, 2) }}</h3>
                            <small class="text-muted">Costo histórico de productos vendidos</small>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-4 col-12 mb-2">
                        <div class="card border-0 shadow-sm rounded-lg bg-light text-center p-3 h-100" style="border-left: 4px solid #28a745 !important;">
                            <span class="text-muted font-weight-bold text-uppercase small">Ganancia Bruta / Utilidad</span>
                            <h3 class="font-weight-bold {{ ($kpis['total_profit'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }} mt-1 mb-0">${{ number_format($kpis['total_profit'] ?? 0, 2) }}</h3>
                            <small class="text-muted">Ventas facturadas menos Costo total</small>
                        </div>
                    </div>
                    <div class="col-xl-4 col-md-4 col-12 mb-2">
                        <div class="card border-0 shadow-sm rounded-lg bg-light text-center p-3 h-100" style="border-left: 4px solid #17a2b8 !important;">
                            <span class="text-muted font-weight-bold text-uppercase small">Margen de Ganancia Promedio</span>
                            <h3 class="font-weight-bold {{ ($kpis['margin_percentage'] ?? 0) >= 0 ? 'text-info' : 'text-danger' }} mt-1 mb-0">{{ number_format($kpis['margin_percentage'] ?? 0, 2) }}%</h3>
                            <small class="text-muted">Rentabilidad sobre ventas totales</small>
                        </div>
                    </div>
                </div>

                {{-- View Mode Pills Tabs --}}
                <div class="d-flex flex-wrap align-items-center mb-3">
                    <div class="btn-group btn-group-toggle shadow-sm flex-wrap" data-toggle="buttons">
                        <button type="button" wire:click="$set('viewMode', 'summary')" class="btn btn-sm {{ $viewMode === 'summary' ? 'btn-primary font-weight-bold active' : 'btn-outline-secondary bg-white' }}">
                            <i class="fas fa-layer-group mr-1"></i> Ventas: Resumen
                        </button>
                        <button type="button" wire:click="$set('viewMode', 'detailed')" class="btn btn-sm {{ $viewMode === 'detailed' ? 'btn-primary font-weight-bold active' : 'btn-outline-secondary bg-white' }}">
                            <i class="fas fa-list-ul mr-1"></i> Ventas: Detallado
                        </button>
                        <button type="button" wire:click="$set('viewMode', 'origin_stock')" class="btn btn-sm {{ $viewMode === 'origin_stock' ? 'btn-info font-weight-bold active text-white' : 'btn-outline-info bg-white' }}">
                            <i class="fas fa-warehouse mr-1"></i> Stock en {{ term('warehouse') }} {{ term('partner_of') }}
                            <span class="badge badge-light ml-1 text-dark">{{ number_format($kpis['origin_physical_stock'], 0) }}</span>
                        </button>
                        <button type="button" wire:click="$set('viewMode', 'consignment_stock')" class="btn btn-sm {{ $viewMode === 'consignment_stock' ? 'btn-warning font-weight-bold active text-dark' : 'btn-outline-warning bg-white text-dark' }}">
                            <i class="fas fa-boxes mr-1"></i> Stock Consignado en Tienda
                            <span class="badge badge-dark ml-1 text-white">{{ number_format($kpis['remaining_in_store'], 0) }}</span>
                        </button>
                    </div>
                </div>

                {{-- Interactive Filters --}}
                <div class="card bg-light border-0 p-3 mb-4 rounded-lg">
                    <div class="row align-items-end">
                        <div class="col-md-2 col-sm-6 mb-2">
                            <label class="font-weight-bold small text-muted">Fecha Desde</label>
                            <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm" {{ $viewMode === 'origin_stock' ? 'disabled' : '' }}>
                            @if($viewMode === 'origin_stock')
                                <small class="text-muted d-block font-italic">No aplica a stock físico</small>
                            @endif
                        </div>
                        <div class="col-md-2 col-sm-6 mb-2">
                            <label class="font-weight-bold small text-muted">Fecha Hasta</label>
                            <input type="date" wire:model.live="dateTo" class="form-control form-control-sm" {{ $viewMode === 'origin_stock' ? 'disabled' : '' }}>
                            @if($viewMode === 'origin_stock')
                                <small class="text-muted d-block font-italic">Snapshot actual</small>
                            @endif
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="font-weight-bold small text-muted">{{ term('partner') }} / {{ term('warehouse') }} de Origen</label>
                            <select wire:model.live="origin_warehouse_id" class="form-control form-control-sm">
                                <option value="all">-- Todos los {{ term('partners') }} / Consignatarios --</option>
                                @foreach($partnerWarehouses as $wh)
                                    <option value="{{ $wh->id }}">
                                        {{ $wh->name }} {{ $wh->partner_name ? '('.$wh->partner_name.')' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 col-sm-6 mb-2">
                            <label class="font-weight-bold small text-muted">Almacén Tienda / Destino</label>
                            <select wire:model.live="destination_warehouse_id" class="form-control form-control-sm" {{ $viewMode === 'origin_stock' ? 'disabled' : '' }}>
                                <option value="all">-- Todos los Almacenes de Venta --</option>
                                @foreach($destinationWarehouses as $wh)
                                    <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                @endforeach
                            </select>
                            @if($viewMode === 'origin_stock')
                                <small class="text-muted d-block font-italic">Solo aplica a ventas/tienda</small>
                            @endif
                        </div>
                        <div class="col-md-2 col-sm-12 mb-2">
                            <label class="font-weight-bold small text-muted">Buscar Producto</label>
                            <div class="input-group input-group-sm">
                                <input type="text" wire:model.live.debounce.300ms="searchProduct" class="form-control" placeholder="Nombre o SKU...">
                                @if(!empty($searchProduct))
                                    <div class="input-group-append">
                                        <button wire:click="$set('searchProduct', '')" class="btn btn-outline-secondary" type="button">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    @if($partnerWarehouses->isEmpty())
                        <div class="alert alert-info mt-2 mb-0 d-flex align-items-center">
                            <i class="fas fa-info-circle mr-2 fa-lg"></i>
                            <div>
                                <strong>Nota:</strong> Aún no has designado {{ term('warehouses_lower') }} como {{ term('partners_lower') }}. Para que el sistema sepa qué {{ term('warehouses_lower') }} representan {{ term('partners_lower') }}, ve al menú <strong>{{ term('warehouses') }}</strong>, crea o edita el {{ term('warehouse_lower') }} y activa la casilla <em>"¿{{ term('warehouse') }} de {{ term('partner') }} / Consignación?"</em>.
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Table Content --}}
                @if($viewMode === 'summary')
                    {{-- Summary Table --}}
                    <div class="table-responsive shadow-sm rounded">
                        <table class="table table-bordered table-hover table-striped mb-0">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th>{{ term('partner') }} / {{ term('warehouse') }} de Origen</th>
                                    <th>Código / Barra</th>
                                    <th>Producto</th>
                                    <th class="text-right">Unidades Vendidas</th>
                                    <th class="text-right">Precio Prom. ($)</th>
                                    <th class="text-right">Total Facturado ($)</th>
                                    <th class="text-right">Costo Prom. ($)</th>
                                    <th class="text-right">Costo Total ($)</th>
                                    <th class="text-right">Ganancia ($)</th>
                                    <th class="text-right">Margen (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $row)
                                    <tr>
                                        <td>
                                            <span class="badge badge-primary font-weight-bold p-1">
                                                <i class="fas fa-warehouse mr-1"></i> {{ $row->origin_warehouse_name ?? 'Almacén Principal' }}
                                            </span>
                                        </td>
                                        <td><code>{{ $row->product_barcode ?? 'S/C' }}</code></td>
                                        <td class="font-weight-bold">{{ $row->product_name }}</td>
                                        <td class="text-right font-weight-bold text-info">{{ number_format($row->total_quantity, 2) }}</td>
                                        <td class="text-right">${{ number_format($row->avg_unit_price, 2) }}</td>
                                        <td class="text-right font-weight-bold text-dark">${{ number_format($row->total_sales_amount, 2) }}</td>
                                        <td class="text-right text-muted">${{ number_format($row->avg_unit_cost, 2) }}</td>
                                        <td class="text-right font-weight-bold text-secondary">${{ number_format($row->total_cost_amount, 2) }}</td>
                                        <td class="text-right font-weight-bold {{ $row->total_profit >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($row->total_profit, 2) }}</td>
                                        <td class="text-right">
                                            <span class="badge {{ $row->margin_percentage >= 20 ? 'badge-success' : ($row->margin_percentage > 0 ? 'badge-warning' : 'badge-danger') }}">
                                                {{ number_format($row->margin_percentage, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center py-4 text-muted">
                                            <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                                            No se encontraron ventas para los filtros seleccionados en este período.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($items->isNotEmpty())
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="3" class="text-right text-uppercase">Totales de Selección:</td>
                                    <td class="text-right text-info">{{ number_format($kpis['total_qty_sold'], 2) }}</td>
                                    <td></td>
                                    <td class="text-right text-dark">${{ number_format($kpis['total_amount_sold'], 2) }}</td>
                                    <td></td>
                                    <td class="text-right text-secondary">${{ number_format($kpis['total_cost_sold'], 2) }}</td>
                                    <td class="text-right {{ $kpis['total_profit'] >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($kpis['total_profit'], 2) }}</td>
                                    <td class="text-right">
                                        <span class="badge {{ $kpis['margin_percentage'] >= 20 ? 'badge-success' : ($kpis['margin_percentage'] > 0 ? 'badge-warning' : 'badge-danger') }}">
                                            {{ number_format($kpis['margin_percentage'], 1) }}%
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                @elseif($viewMode === 'detailed')
                    {{-- Detailed Table --}}
                    <div class="table-responsive shadow-sm rounded">
                        <table class="table table-bordered table-hover table-striped mb-0">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Factura</th>
                                    <th>Cliente</th>
                                    <th>{{ term('partner') }} / {{ term('warehouse') }} Origen</th>
                                    <th>Producto</th>
                                    <th class="text-right">Cantidad</th>
                                    <th class="text-right">P. Unitario ($)</th>
                                    <th class="text-right">Total ($)</th>
                                    <th class="text-right">Costo Unit. ($)</th>
                                    <th class="text-right">Costo Total ($)</th>
                                    <th class="text-right">Ganancia ($)</th>
                                    <th class="text-right">Margen (%)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $row)
                                    @php
                                        $uCost = $row->unit_cost ?? ($row->product->cost ?? 0);
                                        $tCost = $row->total_cost ?? ($row->quantity * $uCost);
                                        $profit = $row->profit ?? ($row->total_price - $tCost);
                                        $margin = $row->total_price > 0 ? ($profit / $row->total_price) * 100 : 0;
                                    @endphp
                                    <tr>
                                        <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</td>
                                        <td>
                                            <span class="badge badge-secondary font-weight-bold">
                                                #{{ $row->sale->invoice_number ?? $row->sale_id }}
                                            </span>
                                        </td>
                                        <td>{{ $row->sale->customer->name ?? 'Cliente Mostrador' }}</td>
                                        <td>
                                            <span class="badge badge-info p-1">
                                                <i class="fas fa-warehouse mr-1"></i> {{ $row->originWarehouse->name ?? 'Directo Tienda' }}
                                            </span>
                                        </td>
                                        <td>{{ $row->product->name ?? 'Producto' }}</td>
                                        <td class="text-right font-weight-bold text-primary">{{ number_format($row->quantity, 2) }}</td>
                                        <td class="text-right">${{ number_format($row->unit_price, 2) }}</td>
                                        <td class="text-right font-weight-bold text-dark">${{ number_format($row->total_price, 2) }}</td>
                                        <td class="text-right text-muted">${{ number_format($uCost, 2) }}</td>
                                        <td class="text-right font-weight-bold text-secondary">${{ number_format($tCost, 2) }}</td>
                                        <td class="text-right font-weight-bold {{ $profit >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($profit, 2) }}</td>
                                        <td class="text-right">
                                            <span class="badge {{ $margin >= 20 ? 'badge-success' : ($margin > 0 ? 'badge-warning' : 'badge-danger') }}">
                                                {{ number_format($margin, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center py-4 text-muted">
                                            <i class="fas fa-info-circle fa-2x mb-2 d-block"></i>
                                            No se encontraron registros de detalle para este período.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($items->isNotEmpty())
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="5" class="text-right text-uppercase">Totales de Selección:</td>
                                    <td class="text-right text-primary">{{ number_format($kpis['total_qty_sold'], 2) }}</td>
                                    <td></td>
                                    <td class="text-right text-dark">${{ number_format($kpis['total_amount_sold'], 2) }}</td>
                                    <td></td>
                                    <td class="text-right text-secondary">${{ number_format($kpis['total_cost_sold'], 2) }}</td>
                                    <td class="text-right {{ $kpis['total_profit'] >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($kpis['total_profit'], 2) }}</td>
                                    <td class="text-right">
                                        <span class="badge {{ $kpis['margin_percentage'] >= 20 ? 'badge-success' : ($kpis['margin_percentage'] > 0 ? 'badge-warning' : 'badge-danger') }}">
                                            {{ number_format($kpis['margin_percentage'], 1) }}%
                                        </span>
                                    </td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                @elseif($viewMode === 'origin_stock')
                    {{-- Physical Origin Stock Table --}}
                    <div class="table-responsive shadow-sm rounded">
                        <table class="table table-bordered table-hover table-striped mb-0">
                            <thead class="bg-info text-white">
                                <tr>
                                    <th>{{ term('partner') }} / {{ term('warehouse') }} de Origen</th>
                                    <th>Responsable / Contacto</th>
                                    <th>Código / SKU</th>
                                    <th>Categoría</th>
                                    <th>Producto</th>
                                    <th class="text-right">Costo Unit. ($)</th>
                                    <th class="text-right">Stock Disponible</th>
                                    <th class="text-right">Valor Total ($)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $row)
                                    @php
                                        $totalVal = ($row->stock_qty ?? 0) * ($row->product->cost ?? 0);
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-info font-weight-bold p-1">
                                                <i class="fas fa-warehouse mr-1"></i> {{ $row->warehouse->name ?? 'N/A' }}
                                            </span>
                                        </td>
                                        <td>{{ $row->warehouse->partner_name ?? '-' }}</td>
                                        <td><code>{{ $row->product->sku ?? 'S/C' }}</code></td>
                                        <td><span class="badge badge-secondary">{{ $row->product->category->name ?? 'General' }}</span></td>
                                        <td class="font-weight-bold">{{ $row->product->name ?? 'Producto' }}</td>
                                        <td class="text-right">${{ number_format($row->product->cost ?? 0, 2) }}</td>
                                        <td class="text-right font-weight-bold text-info">{{ number_format($row->stock_qty, 2) }}</td>
                                        <td class="text-right font-weight-bold text-success">${{ number_format($totalVal, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center py-4 text-muted">
                                            <i class="fas fa-boxes fa-2x mb-2 d-block"></i>
                                            No hay existencias físicas registradas en {{ term('warehouses_of') }} {{ term('partners_of') }} para los filtros seleccionados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                @elseif($viewMode === 'consignment_stock')
                    {{-- Consignment Stock in Store (Layers awaiting sale) --}}
                    <div class="table-responsive shadow-sm rounded">
                        <table class="table table-bordered table-hover table-striped mb-0">
                            <thead class="bg-warning text-dark font-weight-bold">
                                <tr>
                                    <th>{{ term('partner') }} / Origen</th>
                                    <th>Tienda Destino</th>
                                    <th>N° Traspaso</th>
                                    <th>Fecha Ingreso</th>
                                    <th>Código / SKU</th>
                                    <th>Producto</th>
                                    <th class="text-right">Traspasado</th>
                                    <th class="text-right">Vendido</th>
                                    <th class="text-right">Remanente Tienda</th>
                                    <th class="text-right">Costo Base ($)</th>
                                    <th class="text-right">Valor Remanente ($)</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($items as $row)
                                    @php
                                        $unitCost = (float) ($row->unit_cost ?: ($row->cost_price ?: ($row->product->cost ?? 0)));
                                        $valRem = (float) ($row->remaining_quantity ?? 0) * $unitCost;
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="badge badge-info p-1">
                                                <i class="fas fa-warehouse mr-1"></i> {{ $row->originWarehouse->name ?? 'Origen' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-secondary p-1">
                                                <i class="fas fa-store mr-1"></i> {{ $row->destinationWarehouse->name ?? 'Tienda' }}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge badge-dark">#{{ $row->transfer_id }}</span>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($row->created_at)->format('d/m/Y H:i') }}</td>
                                        <td><code>{{ $row->product->sku ?? 'S/C' }}</code></td>
                                        <td class="font-weight-bold">{{ $row->product->name ?? 'Producto' }}</td>
                                        <td class="text-right font-weight-bold">{{ number_format($row->initial_quantity, 2) }}</td>
                                        <td class="text-right text-muted">{{ number_format($row->consumed_quantity, 2) }}</td>
                                        <td class="text-right font-weight-bold text-warning">{{ number_format($row->remaining_quantity, 2) }}</td>
                                        <td class="text-right">${{ number_format($unitCost, 2) }}</td>
                                        <td class="text-right font-weight-bold text-success">${{ number_format($valRem, 2) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center py-4 text-muted">
                                            <i class="fas fa-check-circle fa-2x mb-2 d-block text-success"></i>
                                            No hay stock en consignación pendiente de venta en las tiendas para los filtros seleccionados.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                            @if($items->isNotEmpty())
                            @php
                                $sumInit = $items->sum('initial_quantity');
                                $sumConsumed = $items->sum('consumed_quantity');
                                $sumRem = $items->sum('remaining_quantity');
                                $sumValRem = $items->sum(function($item) {
                                    return (float) ($item->remaining_quantity ?? 0) * (float) ($item->unit_cost ?: ($item->cost_price ?: ($item->product->cost ?? 0)));
                                });
                            @endphp
                            <tfoot class="bg-light font-weight-bold">
                                <tr>
                                    <td colspan="6" class="text-right text-uppercase">Totales de Selección:</td>
                                    <td class="text-right">{{ number_format($sumInit, 2) }}</td>
                                    <td class="text-right text-muted">{{ number_format($sumConsumed, 2) }}</td>
                                    <td class="text-right text-warning">{{ number_format($sumRem, 2) }}</td>
                                    <td></td>
                                    <td class="text-right text-success">${{ number_format($sumValRem, 2) }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                @endif

                {{-- Pagination Links --}}
                <div class="mt-3 d-flex justify-content-between align-items-center">
                    <div>
                        {{ $items->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PDF Modal Viewer --}}
    @if($showPdfModal)
    <div class="modal fade show d-block" tabindex="-1" role="dialog" style="background: rgba(0,0,0,0.6);">
        <div class="modal-dialog modal-xl" role="document" style="height: 90vh;">
            <div class="modal-content h-100">
                <div class="modal-header bg-dark text-white py-2">
                    <h5 class="modal-title"><i class="fas fa-file-pdf mr-1 text-danger"></i> Liquidación de Ventas por {{ term('partner') }} - PDF</h5>
                    <button type="button" class="close text-white" wire:click="$set('showPdfModal', false)">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0 h-100">
                    <iframe src="{{ $pdfUrl }}" class="w-100 h-100" frameborder="0"></iframe>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
</div>