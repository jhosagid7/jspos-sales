<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>Crear Nuevo Cargo / Ajuste de Entrada</b>
                </h4>
                <ul class="tabs tab-pills">
                    <li>
                        <a href="{{ route('cargos') }}" class="tabmenu bg-dark text-white">Ir al Listado</a>
                    </li>
                </ul>
            </div>

            <div class="widget-content widget-content-area">
                <div class="row">
                    <!-- Cabecera del Cargo -->
                    <div class="col-sm-12 col-md-3">
                        <div class="form-group">
                            <label>Depósito Destino</label>
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
                            <input type="text" wire:model="motive" class="form-control" placeholder="Ej: Inventario Inicial, Promo...">
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
                                                    <!-- Botón oculto visualmente pero funcional si se quiere usar -->
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

                <!-- Detalle del Cargo (Carrito) -->
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
                                            <td>
                                                {{ $item['name'] }}
                                                @if($item['is_variable'])
                                                    <div class="mt-1">
                                                        <span class="badge badge-info">Variable</span>
                                                        <button wire:click="openVariableModal({{ $productId }})" class="btn btn-sm btn-primary ml-2">
                                                            <i class="fas fa-plus"></i> Agregar Item
                                                        </button>
                                                        @if(count($item['items']) > 0)
                                                            <ul class="list-unstyled mt-2 pl-2 border-left">
                                                                @foreach($item['items'] as $idx => $vItem)
                                                                    <li class="d-flex justify-content-between align-items-center mb-1">
                                                                        <small>
                                                                            <b>{{ $vItem['weight'] }} kg</b> 
                                                                            @if($vItem['color']) | {{ $vItem['color'] }} @endif
                                                                            @if($vItem['batch']) | Lote: {{ $vItem['batch'] }} @endif
                                                                        </small>
                                                                        <a href="javascript:void(0)" wire:click="removeVariableItem({{ $productId }}, {{ $idx }})" class="text-danger ml-2">
                                                                            <i class="fas fa-times"></i>
                                                                        </a>
                                                                    </li>
                                                                @endforeach
                                                            </ul>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="text-center">
                                                @if($item['is_variable'])
                                                    <input type="text" class="form-control text-center" value="{{ $item['quantity'] }}" disabled style="width: 100px; margin: 0 auto;">
                                                    <small class="text-muted">Auto</small>
                                                @else
                                                    <input type="number" 
                                                           class="form-control text-center" 
                                                           value="{{ $item['quantity'] }}" 
                                                           wire:change="updateQuantity({{ $productId }}, $event.target.value)"
                                                           style="width: 100px; margin: 0 auto;">
                                                @endif
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
                                            <td colspan="5" class="text-center">No hay productos agregados al cargo</td>
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
                            GUARDAR CARGO
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

    <!-- Modal Variable Item -->
    <div class="modal fade" id="modalVariableItem" tabindex="-1" role="dialog" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">Agregar Item / Bobina</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <label>Peso (Kg)</label>
                            <input type="number" id="vw_weight" wire:model="vw_weight" wire:keydown.enter="addVariableItem" class="form-control" placeholder="0.00" autofocus>
                            @error('vw_weight') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-sm-12 mb-3">
                            <label>Color (Opcional)</label>
                            <input type="text" wire:model="vw_color" wire:keydown.enter="addVariableItem" class="form-control" placeholder="Ej: Rojo">
                        </div>
                        <div class="col-sm-12 mb-3">
                            <label>Lote (Opcional)</label>
                            <input type="text" wire:model="vw_batch" wire:keydown.enter="addVariableItem" class="form-control" placeholder="Lote #">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" wire:click="addVariableItem">Agregar</button>
                </div>
            </div>
        </div>
    </div>

<script>
    document.addEventListener('livewire:initialized', () => {
         Livewire.on('show-variable-modal', () => {
             $('#modalVariableItem').modal('show');
             setTimeout(() => {
                 $('#vw_weight').focus();
             }, 500);
         });

         Livewire.on('focus-weight', () => {
             setTimeout(() => {
                 $('#vw_weight').val(''); 
                 $('#vw_weight').focus();
             }, 300);
         });
    });

    document.addEventListener('DOMContentLoaded', function() {
        window.livewire.on('noty', msg => {
            noty(msg)
        });
    });
</script>
