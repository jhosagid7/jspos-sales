<div>
    <div class="row pb-5">

        <div class="col-md-4">
            <div class="card card-absolute">
                <div class="card-header bg-primary">
                    <h5 class="txt-light">Asignar Roles</h5>
                </div>

                <div class="card-body">

                    <div class="table-responsive mt-3">
                        <table class="table table-responsive-md table-hover">
                            <thead class="thead-primary">
                                <tr>
                                    <th>Usuario</th>
                                    <th class="text-center">Role</th>
                                </tr>
                            </thead>
                            <tbody>
                                {{-- @forelse ($users as $user)
                                    <tr>
                                        <td class="text-primary">{{ $user->name }}</td>
                                        <td class="text-end">
                                            <select wire:change="assignRole({{ $user->id }}, $event.target.value)"
                                                class="form-select form-control-sm">
                                                <option value="0">Seleccionar</option>
                                                @foreach ($roles as $rol)
                                                    @if (Auth::user()->roles[0]->name == 'Admin')
                                                        <option value="{{ $rol->id }}"
                                                            {{ $user->hasRole($rol->name) ? 'selected' : '' }}>
                                                            {{ $rol->name }}
                                                        </option>
                                                    @else
                                                        @if ($rol->name != 'Admin')
                                                            <option value="{{ $rol->id }}"
                                                                {{ $user->hasRole($rol->name) ? 'selected' : '' }}>
                                                                {{ $rol->name }}
                                                            </option>
                                                        @else
                                                            <option value="{{ $rol->id }}" disabled>
                                                                {{ $rol->name }}
                                                            </option>
                                                        @endif
                                                    @endif
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">Sin usuarios</td>
                                    </tr>
                                @endforelse --}}
                                {{-- @forelse ($users as $user)
                                    <tr>
                                        <td class="text-primary">{{ $user->name }}</td>
                                        <td class="text-end">
                                            @if (Auth::user()->roles[0]->name == 'Admin' || Auth::user()->id == $user->id)
                                                <select
                                                    wire:change="assignRole({{ $user->id }}, $event.target.value)"
                                                    class="form-select form-control-sm">
                                                    <option value="0">Seleccionar</option>
                                                    @foreach ($roles as $rol)
                                                        @if (Auth::user()->roles[0]->name == 'Admin')
                                                            <option value="{{ $rol->id }}"
                                                                {{ $user->hasRole($rol->name) ? 'selected' : '' }}>
                                                                {{ $rol->name }}
                                                            </option>
                                                        @else
                                                            @if ($rol->name != 'Admin')
                                                                <option value="{{ $rol->id }}"
                                                                    {{ $user->hasRole($rol->name) ? 'selected' : '' }}>
                                                                    {{ $rol->name }}
                                                                </option>
                                                            @else
                                                                <option value="{{ $rol->id }}" disabled>
                                                                    {{ $rol->name }}
                                                                </option>
                                                            @endif
                                                        @endif
                                                    @endforeach
                                                </select>
                                            @else
                                                <span>No se puede editar</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">Sin usuarios</td>
                                    </tr>
                                @endforelse --}}

                                @forelse ($users as $user)
                                    <tr>
                                        <td class="text-primary">{{ $user->name }}</td>
                                        <td class="text-end">
                                            @if (Auth::user()->roles[0]->name == 'Admin')
                                                <select
                                                    wire:change="assignRole({{ $user->id }}, $event.target.value)"
                                                    class="form-select form-control-sm">
                                                    <option value="0">Seleccionar</option>
                                                    @foreach ($roles as $rol)
                                                        <option value="{{ $rol->id }}"
                                                            {{ $user->hasRole($rol->name) ? 'selected' : '' }}>
                                                            {{ $rol->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            @else
                                                @if ($user->hasRole('Admin'))
                                                    <span class="mr-6">No se puede editar</span>
                                                @else
                                                    <select
                                                        wire:change="assignRole({{ $user->id }}, $event.target.value)"
                                                        class="form-select form-control-sm">
                                                        <option value="0">Seleccionar</option>
                                                        @foreach ($roles as $rol)
                                                            @if ($rol->name != 'Admin')
                                                                <option value="{{ $rol->id }}"
                                                                    {{ $user->hasRole($rol->name) ? 'selected' : '' }}>
                                                                    {{ $rol->name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    </select>
                                                @endif
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">Sin usuarios</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>



                </div>
                <div class="card-footer d-flex justify-content-between p-1">
                    <span class="text-dark f-s-italic f-12">Para eliminar los roles del usuario elige
                        <b>Seleccionar</b></span>
                </div>
            </div>
        </div>



        <div class="col-md-6">
            <div class="card card-absolute">
                <div class="card-header bg-dark">
                    <h5 class="txt-light">Asignar Permisos</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-8">
                            <div class="input-group">
                                <span class="input-group-text">Roles</span>
                                <select wire:model.live='roleSelectedId' class="form-select form-control-sm">
                                    @foreach ($roles as $rol)
                                        <option value="{{ $rol->id }}">
                                            {{ $rol->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-4">
                            {{-- search --}}
                            <div class="job-filter mb-2">
                                <div class="faq-form">
                                    <input wire:model.live='search' class="form-control" type="text" id="inputSearch"
                                        placeholder="Buscar permiso [ F2 ]"><i class="search-icon"
                                        data-feather="search"></i>
                                </div>
                            </div>
                        </div>

                    </div>


                    <div class="table-responsive mt-3">
                        <table class="table table-responsive-md table-hover" id="tblPermissions">
                            <thead class="thead-primary">
                                <tr>
                                    <th>Descripción</th>
                                    <th class="text-end">
                                        <div class="form-check checkbox checkbox-success mb-0">
                                            <input wire:change="assignRevokeAllPermissions($event.target.checked)"
                                                class="form-check-input" id="checkAll" type="checkbox"
                                                @if ($role != null) {{ app('fun')->roleHasAllPermissions($role->name) ? 'checked' : '' }} @endif>
                                            <label class="form-check-label" for="checkAll">Asignar/Revocar Todos</label>
                                        </div>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($permisos as $permiso)
                                    <tr>
                                        <td class="text-primary">{{ $permiso->name }}</td>
                                        <td class="text-end">
                                            <div class="form-check checkbox checkbox-success mb-0">
                                                <input
                                                    wire:change="assignPermission({{ $permiso->id }}, $event.target.checked)"
                                                    class="form-check-input" id="permi{{ $permiso->id }}"
                                                    type="checkbox"
                                                    @if ($role != null) {{ $role->hasPermissionTo($permiso->name) ? 'checked' : '' }} @endif>
                                                <label class="form-check-label"
                                                    for="permi{{ $permiso->id }}">Seleccionar</label>
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
                    @if ($permisos != null && count($permisos) > 0)
                        <span>Total permisos: {{ count($permisos) }}</span>
                    @endif
                </div>
            </div>
        </div>



    </div>
    @push('my-scripts')
        <script>
            document.onkeydown = function(e) {

                //f2
                if (e.keyCode == '113') {
                    e.preventDefault()
                    document.getElementById('inputSearch').value = ''
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
                    title: actionType == 1 ? '¿CONFIRMAS ELIMINAR EL ROLE?' : '¿CONFIRMAS ELIMINAR EL PERMISO?',
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
                        if (actionType == 1)
                            Livewire.dispatch('destroyRole', {
                                id: id
                            })
                        else
                            Livewire.dispatch('destroyPermission', {
                                id: id
                            })
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

        #tblPermissions td {
            padding: 0.2rem !important;
        }
    </style>
</div>
