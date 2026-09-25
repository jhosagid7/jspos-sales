<div>
    <div class="layout-px-spacing">
        <div class="row layout-top-spacing">
            <!-- Header Section -->
            <div class="col-12 mb-3">
                <div class="card shadow-sm border-0" style="border-radius: 12px; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #fff;">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                            <div>
                                <h4 class="font-weight-bold text-white mb-1 d-flex align-items-center">
                                    <i class="fas fa-shield-alt text-warning me-2"></i> Control de Menús y Módulos por Usuario
                                    <span class="badge badge-warning text-dark ms-3 font-weight-bold" style="font-size: 0.75rem;">SUPER ADMIN</span>
                                </h4>
                                <p class="text-white-50 mb-0 small">
                                    Habilita o deshabilita individualmente qué módulos del sistema puede ver y utilizar cada empleado en el Menú Lateral y en la Cinta de Accesos Directos.
                                </p>
                            </div>
                            <div class="d-flex align-items-center gap-2">
                                <a href="{{ route('settings') }}" class="btn btn-outline-light btn-sm px-3">
                                    <i class="fas fa-arrow-left me-1"></i> Volver a Configuración
                                </a>
                                <button wire:click="save" wire:loading.attr="disabled" class="btn btn-success btn-sm px-4 font-weight-bold shadow">
                                    <span wire:loading.remove wire:target="save"><i class="fas fa-save me-1"></i> Guardar Permisos</span>
                                    <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin me-1"></i> Guardando...</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Selector & Action Controls Card -->
            <div class="col-12 mb-4">
                <div class="card shadow-sm border-0" style="border-radius: 12px;">
                    <div class="card-body p-3">
                        <div class="row align-items-center g-3">
                            <!-- Selector de Usuario -->
                            <div class="col-xl-4 col-lg-5 col-md-6 col-12">
                                <label class="font-weight-bold small text-muted text-uppercase mb-1">
                                    <i class="fas fa-user-circle me-1 text-primary"></i> Seleccionar Usuario del Sistema:
                                </label>
                                <select wire:model.live="selectedUserId" class="form-control font-weight-bold" style="border-radius: 8px;">
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}">
                                            {{ $u->name }} ({{ $u->roles->pluck('name')->implode(', ') ?: 'Sin Rol' }}) - {{ $u->email }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Estado del Usuario -->
                            <div class="col-xl-4 col-lg-4 col-md-6 col-12 text-md-center text-start">
                                @if($selectedUser)
                                    <div class="d-inline-flex flex-column align-items-md-center align-items-start">
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="font-weight-bold text-dark">{{ $selectedUser->name }}</span>
                                            <span class="badge badge-primary px-2 py-1">{{ $selectedUser->roles->pluck('name')->implode(', ') ?: 'Sin Rol' }}</span>
                                        </div>
                                        <div class="mt-1 small">
                                            @if($hasCustomConfig)
                                                <span class="badge badge-success px-2 py-1">
                                                    <i class="fas fa-check-circle me-1"></i> Configuración Personalizada Activa ({{ count($allowedMenus) }} de {{ $totalCatalogCount }} menús)
                                                </span>
                                            @else
                                                <span class="badge badge-secondary px-2 py-1">
                                                    <i class="fas fa-layer-group me-1"></i> Permisos por Defecto de su Rol ({{ count($allowedMenus) }} de {{ $totalCatalogCount }} menús)
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Botones de Acción Rápida -->
                            <div class="col-xl-4 col-lg-3 col-md-12 col-12 d-flex justify-content-md-end justify-content-start align-items-center gap-2 flex-wrap">
                                <button wire:click="selectAll" class="btn btn-outline-primary btn-sm" title="Marcar todos los menús">
                                    <i class="fas fa-check-double me-1"></i> Todos
                                </button>
                                <button wire:click="deselectAll" class="btn btn-outline-secondary btn-sm" title="Desmarcar todos los menús">
                                    <i class="fas fa-times me-1"></i> Ninguno
                                </button>
                                <button wire:click="resetToRoleDefaults" wire:confirm="¿Deseas restablecer los permisos de este usuario a los valores por defecto de su rol?" class="btn btn-outline-warning text-dark btn-sm font-weight-bold" title="Borrar personalización y usar rol">
                                    <i class="fas fa-undo-alt me-1"></i> Restablecer por Rol
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Search Bar Filter -->
            <div class="col-12 mb-3">
                <div class="input-group shadow-sm" style="border-radius: 8px; overflow: hidden;">
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                    <input 
                        type="text" 
                        wire:model.live.debounce.250ms="search" 
                        class="form-control border-start-0 py-2" 
                        placeholder="Buscar menú, reporte o módulo en tiempo real... (ej: ventas, compras, caja, inventario)"
                    >
                    @if(!empty($search))
                        <button wire:click="$set('search', '')" class="btn btn-outline-secondary border-start-0" type="button">
                            <i class="fas fa-times"></i>
                        </button>
                    @endif
                </div>
            </div>

            <!-- Categorized Menu Cards -->
            <div class="col-12">
                @if(empty($groupedCatalog))
                    <div class="card shadow-sm border-0 p-5 text-center text-muted" style="border-radius: 12px;">
                        <i class="fas fa-search fa-3x mb-3 text-secondary opacity-50"></i>
                        <h5>No se encontraron menús que coincidan con "{{ $search }}".</h5>
                        <p class="mb-0 small">Prueba con otra búsqueda o borra el filtro.</p>
                    </div>
                @else
                    @foreach($groupedCatalog as $category => $items)
                        <div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
                            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="fas fa-folder-open text-primary fs-5"></i>
                                    <h6 class="font-weight-bold text-dark text-uppercase mb-0" style="letter-spacing: 0.5px;">
                                        {{ $category }}
                                    </h6>
                                    <span class="badge badge-light text-muted border px-2 py-1 ms-1 font-weight-bold">
                                        {{ count($items) }}
                                    </span>
                                </div>
                                <div class="d-flex align-items-center gap-2">
                                    <button wire:click="selectCategory('{{ $category }}')" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size: 0.75rem;">
                                        Activar Categoría
                                    </button>
                                    <button wire:click="deselectCategory('{{ $category }}')" class="btn btn-xs btn-outline-secondary py-1 px-2" style="font-size: 0.75rem;">
                                        Desactivar
                                    </button>
                                </div>
                            </div>
                            <div class="card-body p-3">
                                <div class="row g-2">
                                    @foreach($items as $item)
                                        @php
                                            $isAllowed = in_array($item['key'], $allowedMenus);
                                        @endphp
                                        <div class="col-xl-4 col-lg-6 col-md-6 col-12 mb-2">
                                            <div 
                                                wire:click="toggleMenu('{{ $item['key'] }}')" 
                                                class="menu-perm-card d-flex align-items-center p-2 rounded border h-100 cursor-pointer transition {{ $isAllowed ? 'border-primary bg-light' : 'bg-white opacity-75' }}"
                                                style="cursor: pointer; user-select: none; border-width: {{ $isAllowed ? '2px' : '1px' }} !important;"
                                            >
                                                <!-- Switch / Checkbox visual -->
                                                <div class="form-check form-switch me-2 ms-1">
                                                    <input 
                                                        class="form-check-input" 
                                                        type="checkbox" 
                                                        role="switch" 
                                                        id="switch_{{ $item['key'] }}" 
                                                        {{ $isAllowed ? 'checked' : '' }}
                                                        style="cursor: pointer;"
                                                        onclick="event.stopPropagation();"
                                                        wire:click="toggleMenu('{{ $item['key'] }}')"
                                                    >
                                                </div>

                                                <!-- Icono con color -->
                                                <div class="rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 38px; height: 38px; background: rgba(0,0,0,0.04); color: {{ $item['color'] ?? '#007bff' }}; font-size: 1.1rem;">
                                                    <i class="{{ $item['icon'] }}"></i>
                                                </div>

                                                <!-- Textos y Ruta -->
                                                <div class="flex-grow-1 overflow-hidden">
                                                    <div class="font-weight-bold text-dark text-truncate" style="font-size: 0.88rem;">
                                                        {{ $item['label'] }}
                                                    </div>
                                                    <div class="text-muted small text-truncate d-flex align-items-center gap-1" style="font-size: 0.72rem;">
                                                        <code>{{ $item['route'] }}</code>
                                                        @if(!empty($item['module']))
                                                            <span class="badge badge-light text-secondary border px-1" style="font-size: 0.65rem;">{{ $item['module'] }}</span>
                                                        @endif
                                                    </div>
                                                </div>

                                                <!-- Estado Visual -->
                                                <div class="ms-2 flex-shrink-0">
                                                    @if($isAllowed)
                                                        <span class="badge badge-success px-2 py-1" style="font-size: 0.7rem;">Visible</span>
                                                    @else
                                                        <span class="badge badge-secondary px-2 py-1" style="font-size: 0.7rem;">Oculto</span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Sticky Bottom Save Bar -->
            <div class="col-12 mt-2 mb-5">
                <div class="card shadow-lg border-0" style="border-radius: 12px; background: #fff;">
                    <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <i class="fas fa-info-circle text-info fs-5"></i>
                            <span class="small text-muted">
                                Al guardar, el usuario <strong>{{ $selectedUser->name ?? '' }}</strong> solo podrá ver los <strong>{{ count($allowedMenus) }}</strong> menús seleccionados tanto en su barra lateral como en su cinta de atajos.
                            </span>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            <button wire:click="resetToRoleDefaults" class="btn btn-outline-secondary btn-sm px-3">
                                Restablecer
                            </button>
                            <button wire:click="save" wire:loading.attr="disabled" class="btn btn-success px-4 font-weight-bold shadow-sm">
                                <span wire:loading.remove wire:target="save"><i class="fas fa-save me-1"></i> Guardar Cambios</span>
                                <span wire:loading wire:target="save"><i class="fas fa-spinner fa-spin me-1"></i> Guardando...</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
