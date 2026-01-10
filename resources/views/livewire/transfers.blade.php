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
                            <th>Estado</th>
                            <th>Nota</th>
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
                            <td>
                                <span class="badge badge-{{ $transfer->status == 'completed' ? 'success' : ($transfer->status == 'pending' ? 'warning' : 'danger') }}">
                                    {{ ucfirst($transfer->status) }}
                                </span>
                            </td>
                            <td>{{ $transfer->note }}</td>
                            <td>
                                @if($transfer->status == 'pending')
                                    <button wire:click="finalizeTransfer({{ $transfer->id }})" 
                                            wire:confirm="¿Está seguro de completar este traspaso? Esto sumará el stock al destino."
                                            class="btn btn-success btn-sm" title="Completar Traspaso">
                                        <i class="fas fa-check"></i>
                                    </button>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center">No hay traspasos registrados</td>
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
                        <label>Origen</label>
                        <select wire:model="from_warehouse_id" class="form-control">
                            <option value="">Seleccione Origen</option>
                            @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                        @error('from_warehouse_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label>Destino</label>
                        <select wire:model="to_warehouse_id" class="form-control">
                            <option value="">Seleccione Destino</option>
                            @foreach($warehouses as $w)
                            <option value="{{ $w->id }}">{{ $w->name }}</option>
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
                    <div class="form-group">
                        <label>Buscar Producto</label>
                        <input type="text" wire:model.live="product_search" class="form-control" placeholder="Buscar por nombre o SKU...">
                        @if(count($products_search_result) > 0)
                        <div class="list-group position-absolute w-100" style="z-index: 1000;">
                            @foreach($products_search_result as $product)
                            <a href="#" wire:click.prevent="addToCart({{ $product->id }})" class="list-group-item list-group-item-action">
                                {{ $product->name }} ({{ $product->sku }})
                            </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="row mt-3">
                <div class="col-md-12">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th width="150">Cantidad</th>
                                <th width="100">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($cart as $index => $item)
                            <tr>
                                <td>{{ $item['name'] }}</td>
                                <td>
                                    <input type="number" class="form-control" value="{{ $item['qty'] }}" 
                                        wire:change="updateQty({{ $index }}, $event.target.value)">
                                </td>
                                <td>
                                    <button wire:click="removeFromCart({{ $index }})" class="btn btn-danger btn-sm">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="text-center">Agregue productos al traspaso</td>
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
            <button wire:click="saveTransfer" class="btn btn-primary">Guardar Traspaso</button>
        </div>
    </div>
    @endif
    
    @push('my-scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('transfer-added', (msg) => {
                noty(msg)
            })
            Livewire.on('error', (msg) => {
                noty(msg, 2) // 2 for error/warning
            })
        })
    </script>
    @endpush
</div>
