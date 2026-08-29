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
                        <input wire:model="category.name" type="text"
                            class="form-control form-control-lg" placeholder="Name">
                        @error('category.name') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    @if(in_array('module_departments', config('tenant.modules', [])))
                    <div class="form-group mt-3">
                        <label>Departamento</label>
                        <div class="d-flex align-items-center">
                            <select wire:model="category.department_id" class="form-control form-control-lg">
                                <option value="">Seleccione Departamento</option>
                                @foreach(\App\Models\Department::orderBy('name')->get() as $dept)
                                    <option value="{{ $dept->id }}">{{ $dept->name }} ({{ strtoupper($dept->report_type) }})</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-outline-primary ml-2 btn-lg" wire:click="$toggle('btnCreateDept')">
                                <i class="fa fa-plus"></i>
                            </button>
                        </div>
                        @error('category.department_id') <span class="text-danger">{{ $message }}</span> @enderror
                    </div>

                    @if($btnCreateDept)
                    <div class="card border border-primary mt-3 p-3 bg-light">
                        <h6 class="text-primary font-weight-bold">Nuevo Departamento</h6>
                        <div class="form-group">
                            <label>Nombre</label>
                            <input wire:model="newDeptName" type="text" class="form-control" placeholder="Ej: Papelería">
                            @error('newDeptName') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="form-group">
                            <label>Tipo de Reporte</label>
                            <select wire:model="newDeptType" class="form-control">
                                <option value="local">LOCAL (Exento/Sin Impuestos)</option>
                                <option value="gravado">GRAVADO (Sujeto a Impuestos/Diseños)</option>
                            </select>
                            @error('newDeptType') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="d-flex justify-content-end mt-2">
                            <button type="button" class="btn btn-secondary btn-sm mr-2" wire:click="$set('btnCreateDept', false)">Cancelar</button>
                            <button type="button" class="btn btn-primary btn-sm" wire:click="saveDepartment">Guardar</button>
                        </div>
                    </div>
                    @endif
                    @endif

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
                                    <th>Name</th>
                                    @if(in_array('module_departments', config('tenant.modules', [])))
                                    <th>Departamento</th>
                                    @endif
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
                                    </td>
                                    <td>
                                        <div>{{$item->name }}</div>
                                    </td>
                                    @if(in_array('module_departments', config('tenant.modules', [])))
                                    <td>
                                        @if($item->department)
                                            <span class="badge {{ $item->department->report_type == 'gravado' ? 'badge-warning text-dark' : 'badge-primary' }} text-uppercase" style="font-size: 12px; font-weight: bold; padding: 6px 10px; border-radius: 4px;">
                                                {{ $item->department->name }} ({{ strtoupper($item->department->report_type) }})
                                            </span>
                                        @else
                                            <span class="badge badge-secondary text-uppercase" style="font-size: 12px; font-weight: bold; padding: 6px 10px; border-radius: 4px;">
                                                Sin Asignar (LOCAL)
                                            </span>
                                        @endif
                                    </td>
                                    @endif
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