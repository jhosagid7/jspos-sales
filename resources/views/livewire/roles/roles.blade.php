<div>
    <div class="row">

        <div class="col-md-4">
            <div class="card card-absolute">
                <div class="card-header bg-info">
                    <h5 class="txt-light">Roles</h5>
                </div>

                <div class="card-body">
                    @error('roleName') <span class="text-danger">{{ $message }}</span> @enderror
                    <div class="input-group">
                        <span class="input-group-text" style="cursor: pointer">
                            {{$roleId == null ? 'Nuevo Role' : 'Editar Role' }}
                        </span>
                        <input class="form-control @error('roleName') border-danger @enderror" wire:model='roleName'
                            type="text" placeholder="Focus [ F1 ]" id="roleName">
                        <span wire:click="{{$roleId == null ? 'createRole' : 'updateRole' }}" class="input-group-text"
                            style="cursor: pointer" {{$roleName===null ? 'disabled' : '' }}>
                            <i class="fas fa-save"></i>
                        </span>
                        <span wire:click="cancelRoleEdit" class="input-group-text" style="cursor: pointer"
                            {{$roleId==null ? 'hidden' : '' }}>
                            <i class="fas fa-times"></i>
                        </span>
                    </div>


                    <div class="table-responsive mt-3">
                        <table class="table table-responsive-md table-hover">
                            <thead class="thead-primary">
                                <tr>
                                    <th>Descripción</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($roles as $rol)
                                <tr>
                                    <td class="text-primary">{{$rol->name }}</td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-pill" role="group" aria-label="Basic example">
                                            <button class="btn btn-light btn-sm" wire:click="Edit({{ $rol->id }})"><i
                                                    class="fas fa-edit"></i>

                                            </button>

                                            <button class="btn btn-light btn-sm"
                                                onclick="confirmDestroy(1,{{ $rol->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center">No hay roles registrados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>



                </div>
                <div class="card-footer d-flex justify-content-between">
                    {{-- <button class="btn btn-light  hidden">Cancelar </button>
                    <button class="btn btn-light  save">Guardar</button> --}}
                </div>
            </div>
        </div>



        <div class="col-md-6 mb-5">
            <div class="card card-absolute">
                <div class="card-header bg-dark">
                    <h5 class="txt-light">Permisos</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-8">
                            @error('permissionName') <span class="text-danger">{{ $message }}</span> @enderror
                            <div class="input-group">
                                <span class="input-group-text" style="cursor: pointer">
                                    {{$permissionId == null ? 'Nuevo Permiso' : 'Editar Permiso' }}
                                </span>
                                <input class="form-control @error('permissionName') border-danger @enderror"
                                    wire:model='permissionName' type="text" placeholder="Focus [ F2 ]"
                                    id="permissionName">
                                <span wire:click="{{$permissionId ==null ? 'createPermission' : 'updatePermission' }}"
                                    class="input-group-text" style="cursor: pointer" {{$permissionName==null
                                    ? 'disabled' : '' }}>
                                    <i class="fas fa-save"></i>
                                </span>
                                <span wire:click="cancelPermissionEdit" class="input-group-text" style="cursor: pointer"
                                    {{$permissionId==null ? 'hidden' : '' }}>
                                    <i class="fas fa-times"></i>
                                </span>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-4">
                            {{-- search --}}
                            <div class="job-filter mb-2">
                                <div class="faq-form">
                                    <input wire:model.live='search' class="form-control" type="text" id="inputSearch"
                                        placeholder="Buscar permiso [ F3 ]"><i class="search-icon"
                                        data-feather="search"></i>
                                </div>
                            </div>
                        </div>

                    </div>


                    <div class="table-responsive mt-3">
                        <table class="table table-responsive-md table-hover">
                            <thead class="thead-primary">
                                <tr>
                                    <th>Descripción</th>
                                    <th class="text-end"></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($permisos as $permiso)
                                <tr>
                                    <td class="text-primary">{{$permiso->name }}</td>
                                    <td class="text-end">
                                        <div class="btn-group btn-group-pill" role="group" aria-label="Basic example">
                                            <button class="btn btn-light btn-sm"
                                                wire:click="EditPermission({{ $permiso->id }})"><i
                                                    class="fas fa-edit"></i>

                                            </button>

                                            <button class="btn btn-light btn-sm"
                                                onclick="confirmDestroy(2,{{ $permiso->id }})">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="2" class="text-center">No hay roles registrados</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer p-1">

                </div>
            </div>
        </div>



    </div>
    @push('my-scripts')

    <script>
        document.onkeydown = function(e) {   

        // f1 
        if (e.keyCode == '112') { 
            e.preventDefault()
            document.getElementById('roleName').value =''
            document.getElementById('roleName').focus()
        }

        if (e.keyCode == '113') { 
            e.preventDefault()
            document.getElementById('permissionName').value =''
            document.getElementById('permissionName').focus()
        }

        if (e.keyCode == '114') { 
            e.preventDefault()
            document.getElementById('inputSearch').value =''
            document.getElementById('inputSearch').focus()
        }

      
    }

    document.addEventListener('livewire:init', () => {   
               
               Livewire.on('init-new', (event) => {
                  document.getElementById('inputFocus').focus()
                })
    })


    function confirmDestroy(actionType = 1, id) {
        swal({
        title: actionType == 1 ? '¿CONFIRMAS ELIMINAR EL ROLE?' : '¿CONFIRMAS ELIMINAR EL PERMISO?' ,
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
      }).then((willCancel) => {
        if (willCancel) {
            if(actionType == 1)
                Livewire.dispatch('destroyRole', {id: id})
            else
                Livewire.dispatch('destroyPermission', {id: id} )
        }
      });
    }

    </script>

    @endpush
    <style>
        .rfx {
            display: none !important
        }

        .breadcrumb-item .rest {
            display: none !important
        }

        .breadcrumb-item>.active {
            display: none !important
        }

        .icon-location-pin {
            display: none !important
        }
    </style>
</div>