<div>
    <div class="row sales layout-top-spacing">
        <div class="col-sm-12 col-md-4">
            <div class="widget widget-chart-one">
                <div class="widget-heading">
                    <h4 class="card-title">
                        <b>{{ $selected_id ? 'Editar Meta' : 'Configurar Meta' }}</b>
                    </h4>
                </div>
                <div class="widget-content">
                    <div class="form-group position-relative">
                        <label>Producto Terminado de Soplado</label>
                        <div class="input-group">
                            <input type="text" wire:model.live.debounce.300ms="search_product" class="form-control" placeholder="Buscar producto..." {{ $selected_id ? 'disabled' : '' }}>
                            @if($product_id && !$selected_id)
                                <button class="btn btn-outline-danger" wire:click="$set('product_id', null); $set('search_product', '')">
                                    <i class="fa fa-times"></i>
                                </button>
                            @endif
                        </div>
                        @error('product_id') <span class="text-danger small">{{ $message }}</span> @enderror
                        @if(!empty($product_results))
                            <ul class="list-group w-100 shadow" style="position: absolute; z-index: 999;">
                                @foreach($product_results as $p)
                                    <li wire:click="selectProduct({{ $p->id }}, '{{ $p->name }}')" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" style="cursor: pointer;">
                                        <span>{{ $p->name }}</span>
                                        <span class="badge badge-primary">Elegir</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="form-group">
                        <label>Producción Mínima Esperada (Turno 8h)</label>
                        <input type="number" wire:model="min_target" class="form-control" placeholder="Ej. 3500">
                        @error('min_target') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label>Producción Máxima Esperada (Turno 8h)</label>
                        <input type="number" wire:model="max_target" class="form-control" placeholder="Ej. 4000">
                        @error('max_target') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>

                    <button wire:click="store" class="btn btn-primary btn-block">
                        {{ $selected_id ? 'Actualizar Meta' : 'Guardar Meta' }}
                    </button>
                    @if($selected_id)
                        <button wire:click="cancelEdit" class="btn btn-outline-secondary btn-block mt-2">Cancelar Edición</button>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-8">
            <div class="widget widget-chart-one">
                <div class="widget-heading">
                    <h4 class="card-title">
                        <b>Metas de Producción por Turno</b>
                    </h4>
                </div>
                <div class="row mb-3 mt-3">
                    <div class="col-sm-12">
                        <input type="text" wire:model.live="search" class="form-control" placeholder="Buscar por producto...">
                    </div>
                </div>
                <div class="widget-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="text-white" style="background: #3B3F5C">
                                <tr>
                                    <th class="table-th text-white">PRODUCTO / ENVASE</th>
                                    <th class="table-th text-white text-center">META MÍNIMA</th>
                                    <th class="table-th text-white text-center">META MÁXIMA</th>
                                    <th class="table-th text-white text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($targets as $t)
                                <tr>
                                    <td><h6>{{ $t->product->name ?? 'Producto Eliminado' }}</h6></td>
                                    <td class="text-center"><h6>{{ number_format($t->min_target, 0) }} unds</h6></td>
                                    <td class="text-center"><h6>{{ number_format($t->max_target, 0) }} unds</h6></td>
                                    <td class="text-center">
                                        <button wire:click="edit({{ $t->id }})" class="btn btn-primary btn-sm mr-1" title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="confirm('¿Está seguro de eliminar esta meta?') || event.stopImmediatePropagation()" wire:click="delete({{ $t->id }})" class="btn btn-danger btn-sm" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center">No hay metas configuradas.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-2">
                        {{ $targets->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
