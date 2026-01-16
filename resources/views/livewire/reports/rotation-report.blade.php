<div class="row layout-top-spacing">
    <div class="col-xl-12 col-lg-12 col-md-12 col-12 layout-spacing">
        <div class="widget-content-area br-4">
            <div class="widget-header">
                <div class="row">
                    <div class="col-xl-12 text-center">
                        <h5><b>Análisis de Rotación de Inventario</b></h5>
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-sm-12 col-md-3">
                    <div class="form-group">
                        <label>Buscar</label>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Nombre del producto...">
                    </div>
                </div>
                <div class="col-sm-12 col-md-3">
                    <div class="form-group">
                        <label>Categoría</label>
                        <select wire:model.live="categoryId" class="form-control">
                            <option value="0">Todas</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-3">
                    <div class="form-group">
                        <label>Proveedor</label>
                        <select wire:model.live="supplierId" class="form-control">
                            <option value="0">Todos</option>
                            @foreach($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-3">
                    <div class="form-group">
                        <label>Cliente</label>
                        <select wire:model.live="customerId" class="form-control">
                            <option value="0">Todos</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-3">
                    <div class="form-group">
                        <label>Fecha Inicio</label>
                        <input type="date" wire:model.live="dateFrom" class="form-control flatpickr">
                    </div>
                </div>
                <div class="col-sm-12 col-md-3">
                    <div class="form-group">
                        <label>Fecha Fin</label>
                        <input type="date" wire:model.live="dateTo" class="form-control flatpickr">
                    </div>
                </div>
                <div class="col-sm-12 col-md-3">
                    <div class="form-group">
                        <label>Días de Cobertura</label>
                        <input type="number" wire:model.live="coverageDays" class="form-control" min="1">
                    </div>
                </div>
                <div class="col-sm-12 col-md-3">
                    <div class="form-group">
                        <label>Estado</label>
                        <select wire:model.live="status" class="form-control">
                            <option value="">Todos</option>
                            <option value="high">Alta Rotacion</option>
                            <option value="low">Baja Rotacion</option>
                            <option value="none">Sin Movimiento</option>
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-12 mt-3 text-right">
                    <span wire:loading wire:target="toggleSelectAll" class="mr-2 text-muted">Procesando...</span>
                    @if(is_array($selectedProducts) && count($selectedProducts) > 0)
                        <span class="mr-3 text-info font-weight-bold">{{ count($selectedProducts) }} Seleccionados</span>
                    @endif
                    <button wire:click="generatePdf" class="btn btn-danger mb-2">
                        <i class="fas fa-file-pdf"></i> Generar PDF
                    </button>
                    <button wire:click="createPurchaseOrder" class="btn btn-primary mb-2 ml-2">
                        <i class="fas fa-shopping-cart"></i> Generar Orden
                    </button>
                </div>
            </div>

            <div class="table-responsive mt-4">
                <table class="table table-bordered table-striped mt-1">
                    <thead class="text-white" style="background: #3b3f5c">
                        <tr>
                            <th class="table-th text-white text-center">
                                <!-- Checkbox removed -->
                            </th>
                            <th class="table-th text-white">Producto</th>
                            <th class="table-th text-white text-center">Stock Actual</th>
                            <th class="table-th text-white text-center">Vendido (Rango)</th>
                            <th class="table-th text-white text-center">Velocidad (u/día)</th>
                            <th class="table-th text-white text-center">Demanda ({{ $coverageDays }} días)</th>
                            <th class="table-th text-white text-center">Sugerencia Compra</th>
                            <th class="table-th text-white text-center">Cobertura (Días)</th>
                            <th class="table-th text-white text-center">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $product)
                            <tr wire:key="row-{{ $product->id }}">
                                <td class="text-center">
                                    <input type="checkbox" wire:model.live="selectedProducts" value="{{ $product->id }}">
                                </td>
                                <td>
                                    <h6 class="mb-0">{{ $product->name }}</h6>
                                </td>
                                <td class="text-center">
                                    <h6 class="mb-0">{{ $product->stock_qty }}</h6>
                                </td>
                                <td class="text-center">
                                    <h6 class="mb-0">{{ $product->total_sold }}</h6>
                                </td>
                                <td class="text-center">
                                    <h6 class="mb-0">{{ $product->velocity }}</h6>
                                </td>
                                <td class="text-center">
                                    <h6 class="mb-0">{{ $product->monthly_demand }}</h6>
                                </td>
                                <td class="text-center">
                                    <h6 class="mb-0 text-primary fw-bold">{{ $product->suggested_order }}</h6>
                                </td>
                                <td class="text-center">
                                    @if($product->coverage_days > 365)
                                        <span class="badge badge-info">> 1 Año</span>
                                    @else
                                        <h6 class="mb-0">{{ $product->coverage_days }}</h6>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-{{ $product->status_color }}">
                                        {{ $product->rotation_status }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center">No hay datos disponibles</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
                {{ $data->links() }}
            </div>
        </div>
    </div>
</div>
