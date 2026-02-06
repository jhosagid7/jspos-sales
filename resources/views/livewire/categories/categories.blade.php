<div>
    <div class="row">
        <div class="col-md-4">
            <div class="card card-absolute">
                <div class="card-header bg-primary">
                    <h5 class="txt-light">{{ $editing ? 'Editar Categoria' : 'Crear Categoria' }}</h5>
                </div>

                <div class="card-body">

                    <div class="form-group">
                        <label>Name</label>
                        <input wire:model.defer="category.name" id='inputFocus' type="text"
                            class="form-control form-control-lg" placeholder="Name">
                        @error('category.name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <div class="input-group mt-5 mb-3">
                        <label class="custom-file-label">Image</label>
                        <div class="custom-file">
                            <input wire:model="upload" type="file" class="custom-file-input"
                                accept="image/x-png,image/jpeg,image/jpg">
                        </div>
                        @error('category.image') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    <!-- picture preview -->
                    @if( $upload!=null )
                    <div class="form-group mt-2">
                        <img class="img-fluid rounded" src="{{ $upload->temporaryUrl() }}" width="100">
                        <h6 class="text-muted">New Pic</h6>
                    </div>
                    @elseif($category->id !=null)
                    <div class="form-group mt-2">
                        <img class="img-fluid rounded" src="{{ $savedImg }}" width="100">
                        <h6 class="text-muted">Current Pic</h6>
                    </div>
                    @endif


                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button class="btn btn-light  hidden {{$editing ? 'd-block' : 'd-none' }}"
                        wire:click="cancelEdit">Cancelar
                    </button>

                    @if($editing)
                        @can('categories.edit')
                        <button class="btn btn-info  save" wire:click="Store">Actualizar</button>
                        @endcan
                    @else
                        @can('categories.create')
                        <button class="btn btn-info  save" wire:click="Store">Guardar</button>
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
                            <h4>Categorías</h4>
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
                        @can('categories.create')
                        <div class="contact-edit chat-alert" wire:click='Add'><i class="icon-plus"></i></div>
                        @endcan
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-responsive-md table-hover  text-center">
                            <thead class="thead-primary">
                                <tr>
                                    <th class="text-center" width="100">Image</th>
                                    <th width="60%">Name</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($categories as $item)
                                <tr>
                                    <td class="text-center">
                                        <div class="product-box">
                                            <div class="product-img">
                                                <img alt="photo" class="img-fluid rounded"
                                                    src="{{ asset($item->picture) }}"
                                                    data-src="{{ asset($item->picture) }}">
                                            </div>
                                        </div>

                                        {{-- <img class="img-fluid rounded" src="{{ $item->picture }}" alt="pic"
                                            width="50"> --}}
                                    </td>
                                    <td>
                                        <div>{{$item->name }}</div>
                                    </td>
                                    <td>


                                        <div class="btn-group btn-group-pill" role="group" aria-label="Basic example">
                                            @can('categories.edit')
                                            <button class="btn btn-light btn-sm" wire:click="Edit({{ $item->id }})"><i
                                                    class="fa fa-edit fa-2x"></i>

                                            </button>
                                            @endcan
                                            @can('categories.delete')
                                            @if(!$item->products()->exists())
                                            <button class="btn btn-light btn-sm" onclick="Confirm({{ $item->id }})">
                                                <i class="fa fa-trash fa-2x"></i>
                                            </button>
                                            @endif
                                            @endcan
                                        </div>

                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3">No hay categorías</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer p-1">
                    {{$categories->links()}}
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
            function Confirm(rowId) {          
            swal({
                    title: '¿CONFIRMAS ELIMINAR EL REGISTRO?',
                    text: "",
                    icon: "warning",
                    buttons: true,         
                    dangerMode: true,
                    buttons: {
                    cancel: "Cancelar",
                    catch: {
                        text: "Aceptar"
                    }
                    },
                }).then((willDestroy) => {
                    if (willDestroy) {
                        Livewire.dispatch('Destroy', {id: rowId })
                    }
                });
        
             }
    </script>

    @endpush

</div>