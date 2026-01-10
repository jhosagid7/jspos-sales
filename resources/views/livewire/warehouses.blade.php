<div>
    <div class="row">
        <div class="col-md-4">
            <div class="card card-absolute">
                <div class="card-header bg-primary">
                    <h5 class="txt-light">{{ $selected_id > 0 ? 'Editar Depósito' : 'Crear Depósito' }}</h5>
                </div>

                <div class="card-body">

                    <div class="form-group">
                        <label>Nombre</label>
                        <input wire:model="name" id='inputFocus' type="text"
                            class="form-control form-control-lg" placeholder="Nombre del depósito"
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
                            <h4>Depósitos</h4>
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
                                    <th>Dirección</th>
                                    <th>Estatus</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($data as $item)
                                <tr>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ $item->address }}</td>
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
