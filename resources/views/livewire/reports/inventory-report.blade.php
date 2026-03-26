<div>
    <div class="row layout-top-spacing">
        <!-- Sidebar Options -->
        <div class="col-sm-12 col-md-3">
            <div class="card mb-3 shadow-sm border-0">
                <div class="p-1 card-header bg-dark text-white text-center rounded-top">
                    <h5 class="mb-0 text-white f-16"><i class="fas fa-filter"></i> Opciones de Inventario</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="f-14 font-weight-bold">Proveedor</label>
                        <select wire:model.live="supplier_id" class="form-control form-control-sm">
                            <option value="all">Todos los Proveedores</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="f-14 font-weight-bold">Categoría</label>
                        <select wire:model.live="category_id" class="form-control form-control-sm">
                            <option value="all">Todas las Categorías</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="f-14 font-weight-bold">Buscar Producto</label>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control form-control-sm" placeholder="Nombre o SKU...">
                    </div>

                    <div class="mt-4">
                        <button wire:click="openPdfPreview" class="btn btn-primary w-100 shadow-sm text-uppercase">
                            <i class="fas fa-file-pdf"></i> Imprimir Stock
                        </button>
                    </div>
                </div>
            </div>

            <!-- Column Config (Admin Style) -->
            <div class="card shadow-sm border-0 mb-3">
                <div class="p-1 card-header bg-primary text-white text-center rounded-top">
                    <h6 class="mb-0 text-white"><i class="fa fa-cog"></i> Configuración de Columnas</h6>
                </div>
                <div class="card-body p-2">
                    <div class="row">
                        @php
                            $columnLabels = [
                                'sku' => 'SKU / Código',
                                'name' => 'Nombre Producto',
                                'category' => 'Categoría',
                                'supplier' => 'Proveedor',
                                'stock' => 'Existencia Sistema',
                                'physical_inventory' => 'Físico (Campo Vacío)',
                                'cost' => 'Costo Unitario ($)',
                                'price' => 'Precio Venta ($)',
                                'utility_percent' => 'Utilidad % (UT. %)',
                                'valuation_cost' => 'Valuación Costo',
                                'valuation_price' => 'Valuación Venta'
                            ];
                        @endphp
                        @foreach($columnLabels as $key => $label)
                            <div class="col-12 mb-1">
                                <div class="custom-control custom-checkbox ml-2">
                                    <input type="checkbox" class="custom-control-input" id="col_{{ $key }}" wire:model.live="columns.{{ $key }}">
                                    <label class="custom-control-label f-12" for="col_{{ $key }}">{{ $label }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Signatures Config -->
            <div class="card shadow-sm border-0">
                <div class="p-1 card-header bg-info text-white text-center rounded-top">
                    <h6 class="mb-0 text-white"><i class="fa fa-pencil-alt"></i> Configuración de Firmas</h6>
                </div>
                <div class="card-body p-2">
                    <div class="row">
                        @php
                            $signatureLabels = [
                                'elaborado' => 'Elaborado por',
                                'autorizado' => 'Autorizado por',
                                'gerente' => 'Revisado Gerencia',
                                'auditoria' => 'Recibido Auditoría'
                            ];
                        @endphp
                        @foreach($signatureLabels as $key => $label)
                            <div class="col-12 mb-1">
                                <div class="custom-control custom-checkbox ml-2">
                                    <input type="checkbox" class="custom-control-input" id="sig_{{ $key }}" wire:model.live="signatures.{{ $key }}">
                                    <label class="custom-control-label f-12" for="sig_{{ $key }}">{{ $label }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="col-sm-12 col-md-9">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 font-weight-bold text-dark"><i class="fa fa-boxes"></i> Resultados de Inventario</h5>
                    <div class="badge badge-light-primary p-2">
                        Total Items: {{ $products->total() }}
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="thead-light">
                                <tr class="f-12 text-center text-uppercase">
                                    @if($columns['sku']) <th>SKU</th> @endif
                                    @if($columns['name']) <th class="text-left">Nombre</th> @endif
                                    @if($columns['category']) <th>Categoría</th> @endif
                                    @if($columns['supplier']) <th>Proveedor</th> @endif
                                    @if($columns['stock']) <th>Stock</th> @endif
                                    @if($columns['cost']) <th>Costo</th> @endif
                                    @if($columns['price']) <th>Precio</th> @endif
                                    @if($columns['utility_percent']) <th>UT. %</th> @endif
                                    @if($columns['valuation_cost']) <th>Val. Costo</th> @endif
                                    @if($columns['valuation_price']) <th>Val. Venta</th> @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr class="f-12 text-center align-middle">
                                        @if($columns['sku']) <td class="text-muted">{{ $product->sku }}</td> @endif
                                        @if($columns['name']) <td class="text-left font-weight-bold">{{ $product->name }}</td> @endif
                                        @if($columns['category']) <td>{{ $product->category->name }}</td> @endif
                                        @if($columns['supplier']) <td>{{ $product->supplier->name ?? 'N/A' }}</td> @endif
                                        @if($columns['stock']) 
                                            <td>
                                                <span class="badge {{ $product->stock_qty > 0 ? 'badge-light-success' : 'badge-light-danger' }} font-weight-bold">
                                                    {{ $product->stock_qty }}
                                                </span>
                                            </td> 
                                        @endif
                                        @if($columns['cost']) <td>${{ number_format($product->cost, 2) }}</td> @endif
                                        @if($columns['price']) <td>${{ number_format($product->price, 2) }}</td> @endif
                                        @if($columns['utility_percent']) 
                                            <td class="text-center font-weight-bold text-muted small">
                                                {{ $product->cost > 0 ? number_format((($product->price - $product->cost) / $product->cost) * 100, 2) . '%' : '0%' }}
                                            </td> 
                                        @endif
                                        @if($columns['valuation_cost']) 
                                            <td class="text-right font-weight-bold text-success">
                                                ${{ number_format($product->stock_qty * $product->cost, 2) }}
                                            </td> 
                                        @endif
                                        @if($columns['valuation_price']) 
                                            <td class="text-right font-weight-bold text-primary">
                                                ${{ number_format($product->stock_qty * $product->price, 2) }}
                                            </td> 
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="15" class="text-center py-4 text-muted">No se encontraron productos con los filtros aplicados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">
                            {{ $products->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PDF Viewer Modal --}}
    @if($showPdfModal)
    <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.7); z-index: 9999;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document" style="height: 90vh; max-width: 95vw; margin-top: 5vh;">
            <div class="modal-content border-0 shadow-lg" style="height: 100%;">
                <div class="modal-header bg-dark text-white p-2">
                    <h5 class="modal-title font-weight-bold ml-3"><i class="fa fa-file-pdf"></i> Previsualización: Reporte de Inventario Stock</h5>
                    <button type="button" class="btn-close btn-close-white mr-3" wire:click="closePdfPreview" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="height: calc(100% - 60px);">
                    @if($pdfUrl)
                        <iframe src="{{ $pdfUrl }}" style="width: 100%; height: 100%; border: none;"></iframe>
                    @else
                        <div class="d-flex flex-column justify-content-center align-items-center" style="height: 100%;">
                            <div class="spinner-border text-primary" role="status"></div>
                            <span class="mt-2 text-muted">Generando reporte de stock, por favor espere...</span>
                        </div>
                    @endif
                </div>
                <div class="modal-footer p-2 bg-light">
                    <button type="button" class="btn btn-secondary btn-sm rounded-pill px-4" wire:click="closePdfPreview">Cerrar</button>
                    <a href="{{ $pdfUrl }}&download=1" class="btn btn-danger btn-sm rounded-pill px-4"><i class="fa fa-download"></i> Descargar PDF</a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
