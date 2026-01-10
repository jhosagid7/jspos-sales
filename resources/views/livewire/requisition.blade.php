<div>
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-6">
                    <h4 class="card-title"><b>{{ $componentName }}</b> | {{ $pageTitle }}</h4>
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
            </div>
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
                        <tr>
                            <td class="text-center">
                                <input type="checkbox" wire:model.live="selected" value="{{ $product->id }}">
                            </td>
                            <td>{{ $product->name }}</td>
                            <td>{{ $product->stock_qty }}</td>
                            <td>{{ $product->max_stock }}</td>
                            <td class="text-danger font-weight-bold">{{ $deficit }}</td>
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
