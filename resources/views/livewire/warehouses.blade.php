<div>
    <div class="row">
        <div class="col-md-4">
            <div class="card card-absolute">
                <div class="card-header bg-primary">
                    <h5 class="txt-light">{{ $selected_id > 0 ? ('Editar ' . term('warehouse')) : ('Crear ' . term('warehouse')) }}</h5>
                </div>

                <div class="card-body">

                    <div class="form-group">
                        <label>Nombre</label>
                        <input wire:model="name" id='inputFocus' type="text"
                            class="form-control form-control-lg text-uppercase" placeholder="Nombre {{ term('warehouse_of') }}"
                            style="text-transform: uppercase;"
                            @cannot('warehouses.create') disabled @endcannot
                            @if($selected_id > 0) @cannot('warehouses.edit') disabled @endcannot @endif
                            >
                        @error('name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label>Dirección</label>
                        <input wire:model="address" type="text"
                            class="form-control form-control-lg" placeholder="Dirección (Opcional)"
                             @cannot('warehouses.create') disabled @endcannot
                             @if($selected_id > 0) @cannot('warehouses.edit') disabled @endcannot @endif
                            >
                        @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group">
                        <label>Estatus</label>
                        <select wire:model="is_active" class="form-control"
                             @cannot('warehouses.create') disabled @endcannot
                             @if($selected_id > 0) @cannot('warehouses.edit') disabled @endcannot @endif
                            >
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                        @error('is_active') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="form-group border rounded p-3 bg-light mb-3">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_partner_warehouse"
                                wire:model.live="is_partner_warehouse"
                                @cannot('warehouses.create') disabled @endcannot
                                @if($selected_id > 0) @cannot('warehouses.edit') disabled @endcannot @endif
                            >
                            <label class="custom-control-label font-weight-bold text-dark" for="is_partner_warehouse">
                                <i class="fa fa-handshake-o text-primary"></i> ¿{{ term('warehouse') }} de {{ term('partner') }} / Consignación?
                            </label>
                        </div>
                        <small class="text-muted d-block mt-1">
                            Marque esta casilla si este {{ term('warehouse_lower') }} pertenece a un {{ term('partner_lower') }} o consignatario para trazabilidad de ventas FIFO.
                        </small>
                    </div>

                    @if($is_partner_warehouse)
                    <div class="form-group">
                        <label class="font-weight-bold text-primary">Nombre del {{ term('partner') }} / Consignatario</label>
                        <input wire:model="partner_name" type="text"
                            class="form-control form-control-lg border-primary" placeholder="Ej: {{ term('partner') }} 1 - Carlos"
                            style="text-transform: capitalize;"
                            @cannot('warehouses.create') disabled @endcannot
                            @if($selected_id > 0) @cannot('warehouses.edit') disabled @endcannot @endif
                        >
                        <small class="text-muted">Nombre {{ term('partner_of') }} para liquidaciones y reportes.</small>
                        @error('partner_name') <span class="text-danger d-block">{{ $message }}</span> @enderror
                    </div>
                    @endif

                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button class="btn btn-light {{ $selected_id > 0 ? 'd-block' : 'd-none' }}"
                        wire:click="resetUI">Cancelar
                    </button>

                    @if($selected_id > 0)
                        @can('warehouses.edit')
                            <button class="btn btn-info save" wire:click="Update">
                                Actualizar
                            </button>
                        @endcan
                    @else
                        @can('warehouses.create')
                            <button class="btn btn-info save" wire:click="Store">
                                Guardar
                            </button>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-8">
            <div class="card height-equal">
                <div class="card-header border-l-primary border-2">
                    <div class="row">
                        <div class="col-sm-12 col-md-8">
                            <h4>{{ term('warehouses') }}</h4>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            {{-- search --}}
                            <div class="job-filter mb-2">
                                <div class="faq-form">
                                    <input wire:model.live='search' class="form-control" type="text"
                                        placeholder="Buscar.."><i class="search-icon" data-feather="search"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-responsive-md table-hover text-center">
                            <thead class="thead-primary">
                                <tr>
                                    <th>Nombre</th>
                                    <th>Tipo</th>
                                    <th>Dirección</th>
                                    <th>Estatus</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $item)
                                <tr>
                                    <td class="text-left pl-3">
                                        <span class="font-weight-bold">{{ $item->name }}</span>
                                        @if($item->is_partner_warehouse && $item->partner_name)
                                            <div class="small text-muted"><i class="fa fa-user text-primary"></i> {{ $item->partner_name }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->is_partner_warehouse)
                                            <span class="badge badge-warning text-dark"><i class="fa fa-handshake-o"></i> {{ term('partner') }} / Consig.</span>
                                        @else
                                            <span class="badge badge-light text-muted"><i class="fa fa-building"></i> Tienda / Propio</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->address ?? '-' }}</td>
                                    <td>
                                        <span class="badge badge-{{ $item->is_active ? 'success' : 'danger' }}">
                                            {{ $item->is_active ? 'Activo' : 'Inactivo' }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-pill" role="group" aria-label="Basic example">
                                            @can('warehouses.edit')
                                            <button class="btn btn-light btn-sm" wire:click="Edit({{ $item->id }})">
                                                <i class="fa fa-edit fa-2x"></i>
                                            </button>
                                            @endcan
                                            
                                            @can('warehouses.delete')
                                            <button class="btn btn-light btn-sm" onclick="Confirm({{ $item->id }})">
                                                <i class="fa fa-trash fa-2x"></i>
                                            </button>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4">No hay depósitos registrados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer p-1">
                    {{ $data->links() }}
                </div>
            </div>
        </div>
    </div>
    @push('my-scripts')
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('warehouse-added', (msg) => {
                noty(msg)
            })
            Livewire.on('warehouse-updated', (msg) => {
                noty(msg)
            })
            Livewire.on('warehouse-deleted', (msg) => {
                noty(msg)
            })
        })

        function Confirm(id) {
            swal({
                title: '¿CONFIRMAS ELIMINAR EL REGISTRO?',
                text: "",
                icon: "warning",
                buttons: true,
                dangerMode: true,
            }).then((willDelete) => {
                if (willDelete) {
                    Livewire.dispatch('deleteRow', {warehouse: id})
                }
            });
        }
    </script>
    @endpush
</div>
