<div>
    @if(!$is_creating)
    <div class="card">
        <div class="card-header">
            <div class="row">
                <div class="col-md-8">
                    <h4 class="card-title"><b>{{ $componentName }}</b> | {{ $pageTitle }}</h4>
                </div>
                <div class="col-md-4 text-right">
                    <button wire:click="create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Traspaso
                    </button>
                </div>
            </div>
            <div class="row mt-3">
                <div class="col-md-4">
                    <input type="text" wire:model.live="search" class="form-control" placeholder="Buscar por nota...">
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Origen</th>
                            <th>Destino</th>
                            <th>Items</th>
                            <th class="text-center">Enviado</th>
                            <th class="text-center">Recibido</th>
                            <th>Estado</th>
                            <th>Nota / Rechazo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $transfer)
                        <tr>
                            <td>{{ $transfer->id }}</td>
                            <td>{{ $transfer->created_at->format('d-m-Y H:i') }}</td>
                            <td>{{ $transfer->fromWarehouse->name }}</td>
                            <td>{{ $transfer->toWarehouse->name }}</td>
                            <td>{{ $transfer->details->count() }}</td>
                            <td class="text-center">
                               <b>{{ number_format($transfer->details->sum('quantity'), 0) }}</b>
                            </td>
                            <td class="text-center">
                               <span class="text-success"><b>{{ number_format($transfer->details->sum('received_quantity'), 0) }}</b></span>
                               @if($transfer->details->sum('rejected_quantity') > 0)
                                   <span class="text-danger">(-{{ number_format($transfer->details->sum('rejected_quantity'), 0) }})</span>
                               @endif
                            </td>
                            <td>
                                @if($transfer->status == 'completed')
                                    <span class="badge badge-success">Completado</span>
                                @elseif($transfer->status == 'completed_partial')
                                    <span class="badge badge-info">Parcial</span>
                                @elseif($transfer->status == 'dispatched')
                                    <span class="badge badge-primary">Despachado</span>
                                @elseif($transfer->status == 'pending')
                                    <span class="badge badge-warning">Pendiente</span>
                                @elseif($transfer->status == 'rejected')
                                    <span class="badge badge-danger">Rechazado</span>
                                @endif
                            </td>
                            <td>
                                {{ $transfer->note }}
                                @if($transfer->rejection_reason)
                                    <br><small class="text-danger">Motivo Rechazo: {{ $transfer->rejection_reason }}</small>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('transfers.pdf', $transfer->id) }}" target="_blank" class="btn btn-dark btn-sm" title="Ver PDF">
                                    <i class="fas fa-file-pdf"></i>
                                </a>
                                @if($transfer->status == 'pending')
                                    <button wire:click="dispatchTransferFromWeb({{ $transfer->id }})" 
                                            wire:confirm="¿Despachar este traspaso? El stock se descontará {{ term('warehouse_of') }} de origen."
                                            class="btn btn-primary btn-sm" title="Despachar Traspaso">
                                        <i class="fas fa-truck"></i> Despachar
                                    </button>
                                    <button wire:click="deleteTransfer({{ $transfer->id }})" 
                                            wire:confirm="¿Eliminar este traspaso pendiente?"
                                            class="btn btn-danger btn-sm" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                @elseif($transfer->status == 'dispatched')
                                    <button wire:click="approveTransfer({{ $transfer->id }})" 
                                            wire:confirm="¿Aprobar y recibir todo el traspaso correctamente?"
                                            class="btn btn-primary btn-sm" title="Aprobar (Todo OK)">
                                        <i class="fas fa-check"></i> Aprobar
                                    </button>
                                    <button wire:click="openReceiveModal({{ $transfer->id }})" 
                                            class="btn btn-info btn-sm" title="Editar / Recibir Parcial">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                    <button wire:click="rejectTransfer({{ $transfer->id }})" 
                                            wire:confirm="¿Rechazar todo el traspaso? El stock retornará a la planta."
                                            class="btn btn-warning btn-sm" title="Rechazar Todo">
                                        <i class="fas fa-times"></i> Rechazar
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="8" class="text-center">No hay traspasos registrados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $data->links() }}
        </div>
    </div>
    @else
    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="text-white">Nuevo Traspaso</h4>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ term('warehouse') }} Origen</label>
                        <select wire:model.live="from_warehouse_id" class="form-control">
                            <option value="">Seleccione {{ term('warehouse') }} Origen</option>
                            @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                        @error('from_warehouse_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>{{ term('warehouse') }} Destino</label>
                        <select wire:model="to_warehouse_id" class="form-control">
                            <option value="">Seleccione {{ term('warehouse') }} Destino</option>
                            @foreach($warehouses as $w)
                                @if($w->id != $from_warehouse_id)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endif
                            @endforeach
                        </select>
                        @error('to_warehouse_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Nota</label>
                        <input type="text" wire:model="note" class="form-control" placeholder="Nota opcional">
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="form-group position-relative">
                        <label class="d-flex justify-content-between">
                            <span>Buscar Producto</span>
                            <small class="text-muted font-weight-bold">
                                @if(!empty($from_warehouse_id))
                                    <i class="fas fa-check-circle text-success mr-1"></i> Filtrando solo productos con stock en {{ term('warehouse_lower') }} de origen
                                @else
                                    <i class="fas fa-exclamation-circle text-warning mr-1"></i> Requiere seleccionar {{ term('warehouse_lower') }} de origen
                                @endif
                            </small>
                        </label>
                        @if(empty($from_warehouse_id))
                            <div class="alert alert-warning py-2 px-3 small mb-2 d-flex align-items-center">
                                <i class="fas fa-exclamation-triangle mr-2"></i>
                                <span>Por favor <b>seleccione primero {{ term('warehouse_lower') }} de origen</b> para buscar productos disponibles.</span>
                            </div>
                        @endif
                        <input type="text" 
                            wire:model.live="product_search" 
                            class="form-control" 
                            placeholder="{{ empty($from_warehouse_id) ? 'Seleccione primero ' . term('warehouse_lower') . ' de origen...' : 'Buscar por nombre, SKU o código de barra...' }}"
                            {{ empty($from_warehouse_id) ? 'disabled' : '' }}>
                        @if(count($products_search_result) > 0)
                        <div class="list-group position-absolute w-100 shadow-lg" style="z-index: 1050; max-height: 280px; overflow-y: auto;">
                            @foreach($products_search_result as $product)
                            <a href="#" wire:click.prevent="addToCart({{ $product->id }})" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center py-2">
                                <div>
                                    <strong class="text-dark">{{ $product->name }}</strong>
                                    <span class="text-muted small ml-2">SKU: {{ $product->sku ?? 'S/N' }}</span>
                                </div>
                                <div>
                                    @if(isset($product->current_warehouse_stock))
                                        <span class="badge badge-success px-2 py-1 font-weight-bold" style="font-size: 0.8rem;">
                                            <i class="fas fa-cubes mr-1"></i> Stock: {{ number_format($product->current_warehouse_stock, 2) }}
                                        </span>
                                    @endif
                                </div>
                            </a>
                            @endforeach
                        </div>
                        @elseif(strlen($product_search) > 1 && !empty($from_warehouse_id))
                        <div class="list-group position-absolute w-100 shadow" style="z-index: 1050;">
                            <div class="list-group-item text-muted small py-2 text-center">
                                <i class="fas fa-info-circle mr-1"></i> No se encontraron productos con stock disponible en este {{ term('warehouse_lower') }} de origen.
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <table class="table table-bordered table-striped align-middle">
                        <thead class="bg-light">
                            <tr>
                                <th>Producto</th>
                                <th width="160" class="text-center">Stock en Origen</th>
                                <th width="160" class="text-center">Cantidad a Traspasar</th>
                                <th width="80" class="text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cart as $index => $item)
                            <tr>
                                <td class="font-weight-bold align-middle">{{ $item['name'] }}</td>
                                <td class="text-center align-middle">
                                    <span class="badge badge-info px-2 py-1" style="font-size: 0.85rem;">
                                        {{ number_format($item['stock'] ?? 0, 2) }}
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    <input type="number" step="any" min="0.01" max="{{ $item['stock'] ?? '' }}" class="form-control text-center font-weight-bold" value="{{ $item['qty'] }}" 
                                        wire:change="updateQty({{ $index }}, $event.target.value)"
                                        wire:blur="updateQty({{ $index }}, $event.target.value)">
                                </td>
                                <td class="text-center align-middle">
                                    <button wire:click="removeFromCart({{ $index }})" class="btn btn-outline-danger btn-sm" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted py-3">Agregue productos al traspaso</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    @error('cart') <span class="text-danger">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>
        <div class="card-footer text-right">
            <button wire:click="cancel" class="btn btn-secondary">Cancelar</button>
            <button wire:click="saveTransfer" class="btn btn-primary">Crear Traspaso</button>
        </div>
    </div>
    @endif

    <!-- Modal for Receiving -->
    <div wire:ignore.self class="modal fade" id="receiveModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title text-white">Recibir Traspaso</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Revise la mercancía que llegó y confirme la cantidad que se ingresará al almacén.</p>
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Enviado</th>
                                <th width="150">Recibido (Aceptado)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($receiving_details as $detailId => $detail)
                            <tr>
                                <td>{{ $detail['product_name'] }}</td>
                                <td class="text-center"><b>{{ $detail['requested'] }}</b></td>
                                <td>
                                    <input type="number" class="form-control" 
                                           wire:model="receiving_details.{{ $detailId }}.received"
                                           max="{{ $detail['requested'] }}" min="0">
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    
                    <div class="form-group mt-3">
                        <label>Motivo de Rechazo (Opcional, llenar si rechazó algo)</label>
                        <textarea class="form-control" wire:model="rejection_reason" rows="2" placeholder="Ej: Faltaron 5 botellones..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" wire:click="finalizeTransfer" class="btn btn-success">Guardar y Completar</button>
                </div>
            </div>
        </div>
    </div>
    
    @push('my-scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('transfer-added', (msg) => {
                noty(msg)
            })
            Livewire.on('error', (msg) => {
                noty(msg, 2)
            })
            Livewire.on('msg', (msg) => {
                noty(msg)
            })
            Livewire.on('show-receive-modal', () => {
                $('#receiveModal').modal('show')
            })
            Livewire.on('hide-receive-modal', () => {
                $('#receiveModal').modal('hide')
            })
        })
    </script>
    @endpush
</div>
