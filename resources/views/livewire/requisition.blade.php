<div>
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="card-title"><b>{{ $componentName }}</b> | {{ $pageTitle }}</h4>
                </div>
                <div class="col-sm-12 col-md-3">
                    <div class="form-group">
                        <label>Buscar</label>
                        <input type="text" wire:model.live="search" class="form-control" placeholder="Nombre o Código">
                    </div>
                </div>
                <div class="col-sm-12 col-md-3">
                    <div class="form-group">
                        <label>Proveedor</label>
                        <select wire:model.live="supplier_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach ($suppliers as $supplier)
                                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="col-sm-12 col-md-3 d-flex align-items-center pt-3">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" wire:model.live="showAll" class="custom-control-input" id="customSwitch1">
                        <label class="custom-control-label" for="customSwitch1">Mostrar Todo</label>
                    </div>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-12 text-right">
                    <button wire:click="createOrder" class="btn btn-primary" {{ empty($selected) ? 'disabled' : '' }}>
                        <i class="fas fa-shopping-cart"></i> Generar Órdenes
                    </button>
            </div>
            
            @if(!empty($selected))
                @php
                    $orderedProducts = \App\Models\Product::whereIn('id', $selected)->get()->sortBy(function($p) {
                        return array_search($p->id, $this->selected);
                    });
                @endphp
                <div class="row mt-3 border-top pt-3">
                    <div class="col-12">
                        <label class="font-weight-bold text-muted f-12 mb-1">
                            <i class="fas fa-sort text-warning mr-1"></i> Secuencia / Orden de Productos Seleccionados (Arrastra para reordenar, define el orden de la Orden de Compra)
                        </label>
                        <div class="bg-light p-2 rounded">
                            <div class="list-group list-group-flush" x-data="{ draggingIndex: null }">
                                @foreach($orderedProducts->values() as $index => $p)
                                    <div class="list-group-item d-flex justify-content-between align-items-center py-1 bg-white border rounded mb-1"
                                         draggable="true"
                                         x-on:dragstart="draggingIndex = {{ $index }}"
                                         x-on:dragover.prevent=""
                                         x-on:drop="if(draggingIndex !== null && draggingIndex !== {{ $index }}) { $wire.reorderProducts(draggingIndex, {{ $index }}); draggingIndex = null; }"
                                         style="cursor: grab;">
                                        <div class="d-flex align-items-center">
                                            <i class="fas fa-grip-vertical text-muted mr-3" style="font-size: 14px;"></i>
                                            <span class="badge badge-secondary mr-2" style="font-size: 10px;">{{ $index + 1 }}</span>
                                            <span class="font-weight-bold text-dark f-12">{{ $p->name }}</span>
                                        </div>
                                        <div>
                                            <button type="button" wire:click="moveProductUp({{ $p->id }})" class="btn btn-xs btn-outline-secondary p-1" style="font-size: 10px; line-height: 1;" {{ $index === 0 ? 'disabled' : '' }}>
                                                <i class="fas fa-arrow-up"></i>
                                            </button>
                                            <button type="button" wire:click="moveProductDown({{ $p->id }})" class="btn btn-xs btn-outline-secondary p-1" style="font-size: 10px; line-height: 1;" {{ $index === count($selected) - 1 ? 'disabled' : '' }}>
                                                <i class="fas fa-arrow-down"></i>
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @endif
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th width="50">Select</th>
                            <th>Producto</th>
                            <th>Stock Actual</th>
                            <th>Stock Máximo</th>
                            <th>Déficit (A Comprar)</th>
                            <th>Proveedor Sugerido</th>
                            <th>Costo Unitario</th>
                            <th>Costo Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $product)
                        @php
                            $deficit = $product->max_stock - $product->stock_qty;
                            $bestSupplier = $product->getCheapestSupplier();
                            $cost = $bestSupplier ? $bestSupplier->cost : 0;
                            $totalCost = $deficit * $cost;
                        @endphp
                        <tr wire:key="product-{{ $product->id }}">
                            <td class="text-center">
                                <input type="checkbox" wire:model.live="selected" value="{{ $product->id }}">
                            </td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->stock_qty }}</td>
                            <td>{{ $product->max_stock }}</td>
                            <td class="font-weight-bold">
                                @if($deficit > 0)
                                    <span class="text-danger"><i class="fa fa-arrow-down"></i> {{ $deficit }}</span>
                                @elseif($deficit < 0)
                                    <span class="text-primary"><i class="fa fa-arrow-up"></i> {{ abs($deficit) }}</span>
                                @else
                                    <span class="text-success"><i class="fa fa-check"></i> 0</span>
                                @endif
                            </td>
                            <td>
                                @if($bestSupplier && $bestSupplier->supplier)
                                    {{ $bestSupplier->supplier->name }}
                                @else
                                    <span class="text-muted">N/A</span>
                                @endif
                            </td>
                            <td>${{ number_format($cost, 2) }}</td>
                            <td>${{ number_format($totalCost, 2) }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No hay productos con stock bajo</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $data->links() }}
        </div>
    </div>
    </div>
</div>
