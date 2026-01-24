<div class="card">
    <div class="card-header">
        <div>
            @if ($editing && $user->id > 0)
                <h5>Editar Usuario | <small class="text-info">{{ $user->name }}</small></h5>
            @else
                <h5>Crear Nuevo Usuario</h5>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="row g-xl-5 g-3">
            {{-- Left Sidebar --}}
            <div class="col-xxl-3 col-xl-4 box-col-4e sidebar-left-wrapper">
                <ul class="nav flex-column nav-pills me-3" id="user-pills-tab" role="tablist">
                    {{-- Tab 1: Generales --}}
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 1 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',1)" href="#">
                            <i class="fa fa-info-circle fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Generales</h6>
                                <small class="{{ $tab == 1 ? 'text-white' : 'text-muted' }}">Información básica</small>
                            </div>
                        </a>
                    </li>
                    {{-- Tab 2: Roles (Solo si no es nuevo, o si queremos mostrarlo siempre) --}}
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 2 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',2)" href="#">
                            <i class="fa fa-user-tag fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Roles</h6>
                                <small class="{{ $tab == 2 ? 'text-white' : 'text-muted' }}">Permisos y Perfil</small>
                            </div>
                        </a>
                    </li>
                    {{-- Tab 3: Impresora --}}
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 3 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',3)" href="#">
                            <i class="fa fa-print fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Impresora</h6>
                                <small class="{{ $tab == 3 ? 'text-white' : 'text-muted' }}">Configuración local/red</small>
                            </div>
                        </a>
                    </li>
                    {{-- Tab 4: Comisiones (Solo para vendedores) --}}
                    @if($user->profile == 'Vendedor')
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 4 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',4)" href="#">
                            <i class="fa fa-percentage fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Comisiones</h6>
                                <small class="{{ $tab == 4 ? 'text-white' : 'text-muted' }}">Configuración vendedor</small>
                            </div>
                        </a>
                    </li>
                    @endif
                </ul>
            </div>

            {{-- Right Content --}}
            <div class="col-xxl-9 col-xl-8 box-col-8 position-relative">
                <div class="tab-content" id="user-pills-tabContent">
                    
                    {{-- Tab 1: Generales --}}
                    <div class="tab-pane fade {{ $tab == 1 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="row g-2">
                                <div class="col-sm-12">
                                    <label class="form-label">Nombre <span class="txt-danger">*</span></label>
                                    <input wire:model="user.name" class="form-control" type="text" placeholder="Nombre completo">
                                    @error('user.name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-12 mt-3">
                                    <label class="form-label">Email <span class="txt-danger">*</span></label>
                                    <input wire:model="user.email" class="form-control" type="email" placeholder="correo@ejemplo.com">
                                    @error('user.email') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-12 mt-3">
                                    <label class="form-label">Password <span class="txt-danger">*</span></label>
                                    <input wire:model="pwd" class="form-control" type="password" placeholder="Password">
                                    @error('pwd') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-12 mt-3">
                                    <label class="form-label">Confirmar Password <span class="txt-danger">*</span></label>
                                    <input wire:model="confirm_pwd" class="form-control" type="password" placeholder="Confirmar Password">
                                    @error('confirm_pwd') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-12 mt-3">
                                    <label class="form-label">Estatus <span class="txt-danger">*</span></label>
                                    <select wire:model="user.status" class="form-control">
                                        <option value="Active">Activo</option>
                                        <option value="Locked">Bloqueado</option>
                                    </select>
                                    @error('user.status') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 2: Roles --}}
                    <div class="tab-pane fade {{ $tab == 2 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="row g-2">
                                <div class="col-sm-12">
                                    <label class="form-label">Perfil / Rol <span class="txt-danger">*</span></label>
                                    @if (Auth::user()->hasRole('Super Admin') || Auth::user()->hasRole('Admin'))
                                        <select wire:model.live="user.profile" class="form-select">
                                            <option value="0">Seleccionar </option>
                                            @foreach ($roles as $rol)
                                                <option value="{{ $rol->name }}">
                                                    {{ $rol->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    @else 
                                        <select wire:model.live="user.profile" class="form-select">
                                            <option value="0">Seleccionar</option>
                                            @foreach ($roles as $rol)
                                                @if ($rol->name != 'Admin')
                                                    <option value="{{ $rol->name }}">
                                                        {{ $rol->name }}
                                                    </option>
                                                @endif
                                            @endforeach
                                        </select>
                                    @endif
                                    @error('user.profile') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-12 mt-2">
                                    <div class="alert alert-light-secondary" role="alert">
                                        <i class="fa fa-info-circle"></i> Al cambiar el perfil, recuerda hacer clic en <strong>Actualizar</strong> para aplicar los permisos.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 3: Impresora --}}
                    <div class="tab-pane fade {{ $tab == 3 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="form-check form-switch pl-0 mb-3">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" class="custom-control-input" id="isNetworkUser" wire:model.live="isNetwork">
                                    <label class="custom-control-label font-weight-bold" for="isNetworkUser">¿Es una impresora de red con contraseña?</label>
                                </div>
                            </div>

                            @if($isNetwork)
                                <div class="row">
                                    <div class="col-sm-6 form-group mt-2">
                                        <span class="form-label">IP o Host <span class="txt-danger">*</span></span>
                                        <input wire:model="printerHost" type="text" class="form-control" placeholder="Ej: 192.168.1.50">
                                        @error('printerHost') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-sm-6 form-group mt-2">
                                        <span class="form-label">Nombre Compartido <span class="txt-danger">*</span></span>
                                        <input wire:model="printerShare" type="text" class="form-control" placeholder="Ej: EPSON_TM">
                                        @error('printerShare') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-sm-6 form-group mt-2">
                                        <span class="form-label">Usuario</span>
                                        <input wire:model="user.printer_user" type="text" class="form-control" placeholder="Opcional">
                                    </div>
                                    <div class="col-sm-6 form-group mt-2">
                                        <span class="form-label">Contraseña</span>
                                        <input wire:model="user.printer_password" type="password" class="form-control" placeholder="Opcional">
                                    </div>
                                </div>
                            @else
                                <div class="form-group mt-2">
                                    <span>Impresora Asignada (Opcional)</span>
                                    <input wire:model="user.printer_name" type="text" class="form-control" placeholder="Ej: \\CAJA-1\EPSON o POS-58">
                                    @error('user.printer_name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            @endif

                            <div class="form-group mt-3">
                                <span>Ancho de Impresión</span>
                                <select wire:model="user.printer_width" class="form-control">
                                    <option value="80mm">80mm (Estándar)</option>
                                    <option value="58mm">58mm (Pequeña)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 4: Comisiones --}}
                    <div class="tab-pane fade {{ $tab == 4 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                             <div class="row mt-3">
                                <div class="col-sm-12">
                                    <h6 class="text-info">Configuración Vendedor Foráneo</h6>
                                </div>
                                <div class="col-sm-4 form-group mt-3">
                                    <span class="form-label">Comisión (%)</span>
                                    <input wire:model="commission_percent" class="form-control" type="number" step="0.01" min="0" max="100">
                                </div>
                                <div class="col-sm-4 form-group mt-3">
                                    <span class="form-label">Flete (%)</span>
                                    <input wire:model="freight_percent" class="form-control" type="number" step="0.01" min="0" max="100">
                                </div>
                                <div class="col-sm-4 form-group mt-3">
                                    <span class="form-label">Dif. Cambiario (%)</span>
                                    <input wire:model="exchange_diff_percent" class="form-control" type="number" step="0.01" min="0" max="1000">
                                </div>
                                <div class="col-sm-12 form-group mt-3">
                                    <span class="form-label">Lote Actual</span>
                                    <input wire:model="current_batch" class="form-control" type="text" placeholder="Ej: 1">
                                </div>

                                <div class="col-sm-12 mt-3">
                                    <h6 class="text-info">Sobrescribir Comisiones (Opcional)</h6>
                                </div>
                                
                                <div class="col-sm-6 form-group mt-2">
                                    <span class="form-label">Nivel 1: Días (<=)</span>
                                    <input wire:model="sellerCommission1Threshold" class="form-control" type="number" placeholder="Global">
                                </div>
                                <div class="col-sm-6 form-group mt-2">
                                    <span class="form-label">Nivel 1: Porcentaje (%)</span>
                                    <input wire:model="sellerCommission1Percentage" class="form-control" type="number" step="0.01" placeholder="Global">
                                </div>

                                <div class="col-sm-6 form-group mt-2">
                                    <span class="form-label">Nivel 2: Días (<=)</span>
                                    <input wire:model="sellerCommission2Threshold" class="form-control" type="number" placeholder="Global">
                                </div>
                                <div class="col-sm-6 form-group mt-2">
                                    <span class="form-label">Nivel 2: Porcentaje (%)</span>
                                    <input wire:model="sellerCommission2Percentage" class="form-control" type="number" step="0.01" placeholder="Global">
                                </div>
                                
                                <div class="col-sm-12 mt-3">
                                    <button class="btn btn-info btn-sm" wire:click="viewHistory({{ $user->id }})">
                                        <i class="fas fa-history"></i> Ver Historial
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between p-3">
        <button class="btn btn-secondary" wire:click="cancelEdit">
            <i class="fas fa-times mr-1"></i> Cancelar
        </button>
        
        <button class="btn btn-primary" wire:click.prevent="Store">
            <i class="fas fa-check mr-1"></i> {{ $user->id > 0 ? 'Actualizar' : 'Crear Usuario' }}
        </button>
    </div>
</div>
