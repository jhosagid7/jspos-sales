<div>
    <div class="row">

        <div class="col-md-8">
            <div class="card height-equal">
                <div class="card-header border-l-primary border-2">
                    <div class="row">
                        <div class="col-sm-12 col-md-8">
                            <h4>Proveedores</h4>
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
                        @can('suppliers.create')
                        <div class="contact-edit chat-alert" wire:click='Add'><i class="icon-plus"></i></div>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-responsive-md table-hover">
                            <thead class="thead-primary">
                                <tr>
                                    <th width="30%">Proveedor</th>
                                    <th width="40%">Dirección</th>
                                    <th width="30%">Teléfono</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($suppliers as $item)
                                <tr>
                                    <td>
                                        <div>{{$item->name }}</div>
                                    </td>
                                    <td>{{$item->address }}</td>
                                    <td>{{$item->phone }}</td>
                                    <td class="text-center">


                                        <div class="btn-group btn-group-pill" role="group" aria-label="Basic example">
                                            @can('suppliers.edit')
                                            <button class="btn btn-light btn-sm" wire:click="Edit({{ $item->id }})"><i
                                                    class="fa fa-edit fa-2x"></i>

                                            </button>
                                            @endcan
                                            @can('suppliers.delete')
                                            @if(!$item->products()->exists())
                                            <button class="btn btn-light btn-sm"
                                                onclick="Confirm('suppliers',{{ $item->id }})">
                                                <i class="fa fa-trash fa-2x"></i>
                                            </button>
                                            @endif
                                            @endcan
                                        </div>

                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3">No hay proveedores</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer p-1">
                    {{$suppliers->links()}}
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-absolute">
                <div class="card-header bg-primary">
                    <h5 class="txt-light">{{ $editing ? 'Editar Proveedor' : 'Crear Proveedor' }}</h5>
                </div>

                <div class="card-body">

                    <div class="form-group">
                        <label>Nombre</label>
                        <input wire:model.defer="supplier.name" id='inputFocus' type="text"
                            class="form-control form-control-lg" placeholder="nombre">
                        @error('supplier.name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dirección <span class="txt-danger">*</span></label>
                        <input wire:model="supplier.address" class="form-control" type="text">
                        @error('supplier.address')
                        <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input wire:model.defer="supplier.phone" type="text" class="form-control form-control-lg"
                            placeholder="teléfono" maxlength="15">
                        @error('supplier.phone') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>



                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button class="btn btn-light  hidden {{$editing ? 'd-block' : 'd-none' }}"
                        wire:click="cancelEdit">Cancelar
                    </button>

                    @if($editing)
                        @can('suppliers.edit')
                        <button class="btn btn-info  save" wire:click="Store">Actualizar</button>
                        @endcan
                    @else
                        @can('suppliers.create')
                        <button class="btn btn-info  save" wire:click="Store">Guardar</button>
                        @endcan
                    @endif
                </div>
            </div>
        </div>

    </div>
    @push('my-scripts')

    <script>
        document.addEventListener('livewire:init', () => {   
               
               Livewire.on('init-new', (event) => {
                  document.getElementById('inputFocus').focus()
                })
            })
    </script>

    @endpush

</div>