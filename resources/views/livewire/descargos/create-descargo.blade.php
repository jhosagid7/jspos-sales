<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>Crear Nuevo Descargo / Ajuste de Salida</b>
                </h4>
                <ul class="tabs tab-pills">
                    <li>
                        <a href="{{ route('descargos') }}" class="tabmenu bg-dark text-white">Ir al Listado</a>
                    </li>
                </ul>
            </div>

            <div class="widget-content widget-content-area">
                <div class="row">
                    <!-- Cabecera del Descargo -->
                    <div class="col-sm-12 col-md-3">
                        <div class="form-group">
                            <label>Depósito Origen</label>
                            <select wire:model="warehouse_id" class="form-control">
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                            @error('warehouse_id') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <div class="form-group">
                            <label>Fecha</label>
                            <input type="datetime-local" wire:model="date" class="form-control">
                            @error('date') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <div class="form-group">
                            <label>Motivo</label>
                            <input type="text" wire:model="motive" class="form-control" placeholder="Ej: Merma, Regalo, Daño...">
                            @error('motive') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <div class="form-group">
                            <label>Autorizado Por</label>
                            <input type="text" wire:model="authorized_by" class="form-control" placeholder="Nombre del supervisor">
                            @error('authorized_by') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>

                <hr>

                <!-- Buscador -->
                <div class="row mb-4">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label>Buscar Productos (Nombre o SKU)</label>
                            <div class="input-group">
                                <input type="text" 
                                       wire:model.live="search" 
                                       wire:keydown.arrow-down="keyDown('ArrowDown')"
                                       wire:keydown.arrow-up="keyDown('ArrowUp')"
                                       wire:keydown.enter="keyDown('Enter')"
                                       class="form-control" 
                                       placeholder="Escribe para buscar..." 
                                       autofocus>
                                <div class="input-group-append">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                            </div>
                        </div>

                        <!-- Resultados de Búsqueda -->
                        @if(count($searchResults) > 0)
                            <div class="table-responsive" style="max-height: 200px; overflow-y: auto; position: absolute; z-index: 999; background: white; border: 1px solid #ddd; width: 96%;">
                                <table class="table table-hover mb-0">
                                    <tbody>
                                        @foreach($searchResults as $index => $product)
                                            <tr class="{{ $index === $selectedIndex ? 'bg-info text-white' : '' }}" style="cursor: pointer;" wire:click="addToCart({{ $product->id }})">
                                                <td>{{ $product->name }}</td>
                                                <td>{{ $product->sku }}</td>
                                                <td class="text-right">
                                                    <button class="btn btn-primary btn-sm" style="display: none;">
                                                        <i class="fas fa-plus"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Detalle del Descargo (Carrito) -->
                <div class="row">
                    <div class="col-sm-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mt-1">
                                <thead class="text-white" style="background: #3b3f5c">
                                    <tr>
                                        <th class="table-th text-white text-center">SKU</th>
                                        <th class="table-th text-white">PRODUCTO</th>
                                        <th class="table-th text-white text-center">CANTIDAD</th>
                                        <th class="table-th text-white text-right">COSTO</th>
                                        <th class="table-th text-white text-right">TOTAL</th>
                                        <th class="table-th text-white text-center">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($cart as $productId => $item)
                                        <tr>
                                            <td class="text-center">{{ $item['sku'] }}</td>
                                            <td>{{ $item['name'] }}</td>
                                            <td class="text-center">
                                                <input type="number" 
                                                       class="form-control text-center" 
                                                       value="{{ $item['quantity'] }}" 
                                                       wire:change="updateQuantity({{ $productId }}, $event.target.value)"
                                                       style="width: 100px; margin: 0 auto;">
                                            </td>
                                            <td class="text-right">${{ number_format($item['cost'], 2) }}</td>
                                            <td class="text-right">${{ number_format($item['quantity'] * $item['cost'], 2) }}</td>
                                            <td class="text-center">
                                                <button wire:click="removeFromCart({{ $productId }})" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">No hay productos agregados al descargo</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label>Comentarios / Notas Adicionales</label>
                            <textarea wire:model="comments" class="form-control" rows="3"></textarea>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-sm-12 text-center">
                        <button wire:click="save" class="btn btn-primary btn-lg" {{ count($cart) < 1 ? 'disabled' : '' }}>
                            GUARDAR DESCARGO
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        window.livewire.on('noty', msg => {
            noty(msg)
        });
    });
</script>
