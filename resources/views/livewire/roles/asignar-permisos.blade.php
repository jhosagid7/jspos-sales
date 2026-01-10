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

        <div class="col-md-8">
            <div class="card card-absolute">
                <div class="card-header bg-dark">
                    <h5 class="txt-light">Asignar Permisos</h5>
                </div>

                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-sm-12 col-md-6">
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
                        <div class="col-sm-12 col-md-6">
                            <div class="d-flex justify-content-end align-items-center">
                                <div class="form-check checkbox checkbox-success mb-0 mr-3">
                                    <input wire:change="assignRevokeAllPermissions($event.target.checked)"
                                        class="form-check-input" id="checkAll" type="checkbox"
                                        @if ($role != null) {{ app('fun')->roleHasAllPermissions($role->name) ? 'checked' : '' }} @endif>
                                    <label class="form-check-label" for="checkAll">Asignar/Revocar Todos</label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        @foreach($groupedPermissions as $group)
                        <div class="col-md-6 col-lg-4 mb-4">
                            <div class="card h-100 border shadow-sm">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 font-weight-bold text-primary">{{ $group['name'] }}</h6>
                                </div>
                                <div class="card-body p-2">
                                    <ul class="list-group list-group-flush">
                                        @foreach($group['permissions'] as $permiso)
                                        <li class="list-group-item p-1 border-0">
                                            <div class="form-check checkbox checkbox-primary mb-0">
                                                <input
                                                    wire:change="assignPermission({{ $permiso->id }}, $event.target.checked)"
                                                    class="form-check-input" id="permi{{ $permiso->id }}"
                                                    type="checkbox"
                                                    @if ($role != null) {{ $role->hasPermissionTo($permiso->name) ? 'checked' : '' }} @endif>
                                                <label class="form-check-label" for="permi{{ $permiso->id }}" style="cursor: pointer;">
                                                    {{ $permiso->display_name }}
                                                </label>
                                            </div>
                                        </li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                        @endforeach
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
            document.addEventListener('livewire:init', () => {
                Livewire.on('init-new', (event) => {
                    document.getElementById('inputFocus').focus()
                })
            })
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
