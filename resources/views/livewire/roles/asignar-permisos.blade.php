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
                    <div class="row mb-3 align-items-center">
                        <div class="col-sm-12 col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-light font-weight-bold">Rol a Configurar:</span>
                                <select wire:model.live='roleSelectedId' class="form-select font-weight-bold text-primary">
                                    @foreach ($roles as $rol)
                                        <option value="{{ $rol->id }}">
                                            {{ $rol->name }} ({{ $rol->permissions->count() }} permisos)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6 text-md-end mt-2 mt-md-0">
                            <button wire:click="clearRolePermissions" 
                                onclick="return confirm('¿Estás seguro de revocar todos los permisos de este rol?')"
                                class="btn btn-outline-danger btn-sm me-2">
                                <i class="fas fa-trash-alt me-1"></i> Revocar Todos
                            </button>
                            <button wire:click="applyTemplate('super_admin')" 
                                class="btn btn-outline-success btn-sm">
                                <i class="fas fa-check-double me-1"></i> Asignar Todos (100%)
                            </button>
                        </div>
                    </div>

                    <!-- BARRA DE PLANTILLAS RÁPIDAS (1 CLIC) Y GESTIÓN JSON -->
                    <div class="card border mb-4 shadow-sm" style="background: #f8fafc;">
                        <div class="card-header bg-white py-2 d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <div>
                                <span class="font-weight-bold text-dark fs-6">
                                    <i class="fas fa-magic text-warning me-1"></i> ⚡ Plantillas de Permisos Prediseñadas
                                </span>
                                <small class="text-muted d-block d-md-inline ms-md-2">Aplica perfiles o importa/exporta en JSON</small>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <!-- Exportar JSON -->
                                <button type="button" 
                                    wire:click="exportTemplate" 
                                    wire:loading.attr="disabled"
                                    class="btn btn-sm btn-outline-secondary d-flex align-items-center shadow-sm"
                                    title="Exportar la configuración de permisos del rol seleccionado a archivo JSON">
                                    <i class="fas fa-download me-1 text-primary"></i>
                                    <span class="fw-bold">Exportar JSON</span>
                                </button>

                                <!-- Importar JSON -->
                                <label class="btn btn-sm btn-outline-secondary d-flex align-items-center shadow-sm mb-0 cursor-pointer"
                                    style="cursor: pointer;"
                                    title="Importar un archivo JSON de permisos al rol seleccionado">
                                    <i class="fas fa-upload me-1 text-success"></i>
                                    <span class="fw-bold">Importar JSON</span>
                                    <input type="file" wire:model="templateFile" accept=".json" class="d-none">
                                </label>
                                
                                <div wire:loading wire:target="templateFile" class="spinner-border spinner-border-sm text-primary ms-1" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-2">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($templates as $key => $tpl)
                                    @if($key !== 'super_admin')
                                    <button type="button"
                                        wire:click="applyTemplate('{{ $key }}')"
                                        wire:loading.attr="disabled"
                                        class="btn btn-outline-{{ $tpl['color'] }} btn-sm d-flex align-items-center px-2 py-1 shadow-sm"
                                        style="font-size: 12.5px; border-radius: 8px;"
                                        title="{{ $tpl['description'] }}">
                                        <i class="fas fa-{{ $tpl['icon'] }} me-1"></i>
                                        <span class="fw-bold me-1">{{ $tpl['name'] }}</span>
                                        <span class="badge bg-{{ $tpl['color'] }} text-white" style="font-size: 10px;">{{ count($tpl['permissions']) }}</span>
                                    </button>
                                    @endif
                                @endforeach
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
                                                <label class="form-check-label" for="permi{{ $permiso->id }}" style="cursor: pointer;" title="{{ $permiso->description }}">
                                                    {{ $permiso->display_name }}
                                                    @if($permiso->description)
                                                        <small class="text-muted ms-1"><i class="fas fa-info-circle" style="font-size: 0.7em"></i></small>
                                                    @endif
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
