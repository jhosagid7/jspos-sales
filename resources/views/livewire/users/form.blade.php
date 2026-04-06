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
                    @module('module_commissions')
                    @if($this->isSeller($user->profile))
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
                    @endmodule
                    
                    {{-- Tab 5: Config. Crédito (Solo para vendedores) --}}
                    @module('module_credits')
                    @if($this->isSeller($user->profile))
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 5 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',5)" href="#">
                            <i class="fa fa-credit-card fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Config. Crédito</h6>
                                <small class="{{ $tab == 5 ? 'text-white' : 'text-muted' }}">Reglas de Crédito</small>
                            </div>
                        </a>
                    </li>
                    @endif
                    @endmodule

                    {{-- Tab 6: Bancos (Solo para vendedores) --}}
                    @if($this->isSeller($user->profile))
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 6 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',6)" href="#">
                            <i class="fa fa-university fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Bancos Habilitados</h6>
                                <small class="{{ $tab == 6 ? 'text-white' : 'text-muted' }}">Cuentas para Facturas</small>
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
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Teléfono</label>
                                    <input wire:model="user.phone" class="form-control" type="text" placeholder="Teléfono" maxlength="25">
                                    @error('user.phone') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">CC/NIT/RIF</label>
                                    <input wire:model="user.taxpayer_id" class="form-control" type="text" placeholder="Identificación" maxlength="45">
                                    @error('user.taxpayer_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-12 mt-3">
                                    <label class="form-label">Dirección</label>
                                    <input wire:model="user.address" class="form-control" type="text" placeholder="Dirección completa" maxlength="255">
                                    @error('user.address') <span class="text-danger">{{ $message }}</span> @enderror
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
                                <div class="col-sm-12 mt-3">
                                    <label class="form-label">Modo de Vista de Ventas</label>
                                    <select wire:model="user.sales_view_mode" class="form-control">
                                        <option value="">Predeterminado del Sistema</option>
                                        <option value="grid">Cuadrícula</option>
                                        <option value="list">Lista</option>
                                    </select>
                                    @error('user.sales_view_mode') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                @if($this->isSeller($user->profile))
                                <div class="col-sm-12 mt-3">
                                    <label class="form-label">
                                        <i class="fa fa-palette mr-1 text-muted"></i>
                                        Color Identificador del Vendedor
                                    </label>
                                    <div class="d-flex align-items-center gap-3" style="gap:12px;">
                                        <input wire:model="user.color" type="color"
                                               class="form-control form-control-color"
                                               style="width:60px; height:40px; padding:2px; cursor:pointer;"
                                               title="Selecciona un color pastel para identificar tus órdenes">
                                        @if($user->color)
                                        <span class="badge fs-6 px-3 py-2"
                                              style="background-color:{{ $user->color }}; color: #333; font-size:0.85rem; border:1px solid #ccc;">
                                            {{ $user->name ?: 'Vendedor' }}
                                        </span>
                                        <small class="text-muted">Vista previa de la etiqueta</small>
                                        @else
                                        <small class="text-muted">Selecciona un color para ver la vista previa</small>
                                        @endif
                                    </div>
                                    <small class="text-muted d-block mt-1">Este color se usa para identificar las órdenes de este vendedor en la lista de ventas. Se recomiendan colores pasteles.</small>
                                    @error('user.color') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                @endif
                            </div>

                        </div>
                    </div>

                    {{-- Tab 2: Roles --}}
                    <div class="tab-pane fade {{ $tab == 2 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="row g-2">
                                <div class="col-sm-12">
                                    <label class="form-label">Perfil / Rol <span class="txt-danger">*</span></label>
                                    <select wire:model.live="user.profile" class="form-select">
                                        <option value="0">Seleccionar</option>
                                        @foreach ($roles as $rol)
                                            <option value="{{ $rol->name }}">
                                                {{ $rol->name }}
                                            </option>
                                        @endforeach
                                    </select>
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
                    @module('module_commissions')
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
                    @endmodule

                    {{-- Tab 5: Config. Crédito --}}
                    @module('module_credits')
                    <div class="tab-pane fade {{ $tab == 5 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="row g-2">
                                {{-- Sección 1: Control de Crédito --}}
                                <div class="col-sm-12">
                                    <h6 class="text-info mb-3">
                                        <i class="fa fa-credit-card"></i> Control de Crédito
                                    </h6>
                                </div>

                                <div class="col-sm-12">
                                    <div class="form-check form-switch">
                                        <input wire:model="user.seller_allow_credit" class="form-check-input" type="checkbox" id="sellerAllowCreditSwitch">
                                        <label class="form-check-label" for="sellerAllowCreditSwitch">
                                            <strong>Permitir Crédito para sus Clientes</strong>
                                            <small class="d-block text-muted">Habilitar compras a crédito para los clientes de este vendedor</small>
                                        </label>
                                    </div>
                                    @error('user.seller_allow_credit') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Días de Crédito (Default)</label>
                                    <input wire:model="user.seller_credit_days" type="number" class="form-control" 
                                           placeholder="Ej: 15, 30, 60">
                                    <small class="text-muted">Plazo máximo por defecto para sus clientes</small>
                                    @error('user.seller_credit_days') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Límite de Crédito Default ($)</label>
                                    <input wire:model="user.seller_credit_limit" type="number" step="0.01" class="form-control" 
                                           placeholder="Ej: 10000.00">
                                    <small class="text-muted">Monto máximo por defecto para sus clientes</small>
                                    @error('user.seller_credit_limit') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                {{-- Sección 2: Reglas de Descuento/Recargo --}}
                                <div class="col-sm-12 mt-4">
                                    <h6 class="text-info mb-3">
                                        <i class="fa fa-percentage"></i> Reglas de Descuento/Recargo (Default)
                                    </h6>
                                    <p class="text-muted small">Configure las reglas que se aplicarán por defecto a los clientes de este vendedor, si el cliente no tiene reglas propias.</p>
                                </div>

                                <div class="col-sm-12">
                                    <button type="button" class="btn btn-sm btn-success mb-3" wire:click="addDiscountRule">
                                        <i class="fa fa-plus"></i> Agregar Regla
                                    </button>

                                    @if(count($discountRules) > 0)
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Desde (días)</th>
                                                    <th>Hasta (días)</th>
                                                     <th>Monto %</th>
                                                     <th>Tipo</th>
                                                     <th>Código</th>
                                                     <th>Descripción</th>
                                                     <th>Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($discountRules as $index => $rule)
                                                <tr>
                                                    <td>
                                                        <input wire:model="discountRules.{{ $index }}.days_from" 
                                                               type="number" class="form-control form-control-sm" min="0">
                                                    </td>
                                                    <td>
                                                        <input wire:model="discountRules.{{ $index }}.days_to" 
                                                               type="number" class="form-control form-control-sm" 
                                                               placeholder="∞">
                                                    </td>
                                                    <td>
                                                        <input wire:model="discountRules.{{ $index }}.discount_percentage" 
                                                               type="number" step="0.01" class="form-control form-control-sm">
                                                    </td>
                                                                 <td>
                                                         <select wire:model="discountRules.{{ $index }}.rule_type" 
                                                                 class="form-select form-select-sm">
                                                             <option value="early_payment">Pronto Pago</option>
                                                             <option value="overdue">Mora</option>
                                                         </select>
                                                     </td>
                                                     <td>
                                                         <input wire:model="discountRules.{{ $index }}.tag" 
                                                                type="text" class="form-control form-control-sm" 
                                                                placeholder="Ej: PP">
                                                     </td>
                                                     <td>
                                                         <input wire:model="discountRules.{{ $index }}.description" 
                                                                type="text" class="form-control form-control-sm" 
                                                                placeholder="Ej: Pronto pago 0-5 días">
                                                     </td>
                                                    <td class="text-center">
                                                        <button type="button" class="btn btn-sm btn-danger" 
                                                                wire:click="removeDiscountRule({{ $index }})">
                                                            <i class="fa fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    @else
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> No hay reglas configuradas. Haga clic en "Agregar Regla".
                                    </div>
                                    @endif
                                </div>

                                {{-- Sección 3: Descuento por Divisa --}}
                                <div class="col-sm-12 mt-4">
                                    <h6 class="text-info mb-3">
                                        <i class="fa fa-dollar-sign"></i> Descuento por Pago en USD
                                    </h6>
                                </div>

                                <div class="row g-2">
                                    <div class="col-sm-8 text-center">
                                        <label class="form-label">% Descuento por Pago en USD (Zelle/Efectivo)</label>
                                        <input wire:model="user.seller_usd_payment_discount" type="number" step="0.01" 
                                               class="form-control text-center" placeholder="Ej: 5.00">
                                        <small class="text-muted">Descuento aplicado si paga con Zelle o Dólar en efectivo</small>
                                        @error('user.seller_usd_payment_discount') <br><span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-sm-4 text-center">
                                        <label class="form-label">Código (Tag)</label>
                                        <input wire:model="user.seller_usd_payment_discount_tag" type="text" 
                                               class="form-control text-center" placeholder="Ej: PD">
                                        <small class="text-muted">Ej: PD</small>
                                        @error('user.seller_usd_payment_discount_tag') <br><span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Nota sobre jerarquía --}}
                                <div class="col-sm-12 mt-4">
                                    <div class="alert alert-warning">
                                        <i class="fa fa-info-circle"></i> <strong>Nota de Jerarquía:</strong>
                                        <ul>
                                            <li>Estas reglas aplican a todos los clientes de este vendedor.</li>
                                            <li>Si un Cliente tiene configuración específica, la configuración del Cliente TIENE PREFERENCIA sobre estas reglas.</li>
                                            <li>Si ni el Cliente ni el Vendedor tienen reglas, se usarán las del Sistema Global.</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endmodule

                    {{-- Tab 6: Bancos --}}
                    @if($this->isSeller($user->profile))
                    <div class="tab-pane fade {{ $tab == 6 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="row g-2">
                                <div class="col-sm-12">
                                    <h6 class="text-info mb-3">
                                        <i class="fa fa-university"></i> Cuentas Bancarias Autorizadas
                                    </h6>
                                    <p class="text-muted small">Seleccione las cuentas que aparecerán en la factura de este vendedor:</p>
                                </div>
                                <div class="col-sm-12">
                                    <div class="row g-3">
                                        @forelse($allBanks as $bank)
                                        <div class="col-lg-6 col-md-6 col-sm-12">
                                            <div class="bank-card-container">
                                                <input wire:model="selectedBanks" 
                                                       class="bank-checkbox" 
                                                       type="checkbox" 
                                                       value="{{ $bank->id }}" 
                                                       id="bankSelect{{ $bank->id }}">
                                                <label class="bank-card-label" for="bankSelect{{ $bank->id }}">
                                                    <div class="bank-card-header d-flex align-items-center mb-2">
                                                        <div class="bank-avatar-initial">{{ substr($bank->name, 0, 1) }}</div>
                                                        <div class="flex-grow-1 ms-2">
                                                            <strong class="bank-name text-uppercase">{{ $bank->name }}</strong>
                                                            <span class="badge {{ $bank->currency_code == 'USD' ? 'bg-success' : ($bank->currency_code == 'COP' ? 'bg-warning' : 'bg-primary') }} float-end" style="font-size: 8px;">{{ $bank->currency_code }}</span>
                                                        </div>
                                                    </div>
                                                    @if($bank->account_holder)
                                                    <div class="bank-holder mb-1">
                                                        <i class="fa fa-user-circle small text-muted me-1"></i>
                                                        <span class="small fw-bold text-dark">{{ $bank->account_holder }}</span>
                                                    </div>
                                                    @endif
                                                    <div class="bank-details p-2 bg-light rounded text-center" style="border: 1px dashed #ced4da;">
                                                        <code class="bank-account-number text-dark" style="font-size: 11px;">{{ $bank->account_number }}</code>
                                                    </div>
                                                    @if($bank->phone)
                                                    <div class="mt-2 text-end">
                                                        <span class="small badge text-muted bg-white border"><i class="fa fa-mobile ms-1"></i> {{ $bank->phone }}</span>
                                                    </div>
                                                    @endif
                                                    <div class="card-selection-marker">
                                                        <i class="fa fa-check-circle"></i>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        @empty
                                        <div class="col-12">
                                            <div class="alert alert-light-warning text-dark border-warning text-center p-4">
                                                <div class="mb-2"><i class="fa fa-university fa-3x text-warning opacity-25"></i></div>
                                                <h6>No hay bancos registrados en el sistema.</h6>
                                                <p class="small mb-0">Primero configure los bancos en el panel de configuración global.</p>
                                            </div>
                                        </div>
                                        @endforelse
                                    </div>
                                </div>
                                <style>
                                    .bank-card-container {
                                        position: relative;
                                    }
                                    .bank-checkbox {
                                        display: none;
                                    }
                                    .bank-card-label {
                                        display: block;
                                        background: #fff;
                                        border: 2px solid #e5e7eb;
                                        border-radius: 12px;
                                        padding: 15px;
                                        cursor: pointer;
                                        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
                                        position: relative;
                                        overflow: hidden;
                                        min-height: 140px;
                                        box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                                    }
                                    .bank-card-label:hover {
                                        border-color: #0380b2;
                                        transform: translateY(-3px);
                                        box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
                                    }
                                    .bank-checkbox:checked + .bank-card-label {
                                        background-color: #f0f9ff;
                                        border-color: #0380b2;
                                        box-shadow: 0 0 0 1px #0380b2 inset, 0 8px 16px -4px rgba(3, 128, 178, 0.2);
                                    }
                                    .bank-avatar-initial {
                                        width: 32px;
                                        height: 32px;
                                        background: #f3f4f6;
                                        color: #6b7280;
                                        border-radius: 50%;
                                        display: flex;
                                        align-items: center;
                                        justify-content: center;
                                        font-weight: bold;
                                        font-size: 14px;
                                        border: 1px solid #e5e7eb;
                                    }
                                    .bank-checkbox:checked + .bank-card-label .bank-avatar-initial {
                                        background: #0380b2;
                                        color: #fff;
                                        border-color: #0380b2;
                                    }
                                    .card-selection-marker {
                                        position: absolute;
                                        top: -20px;
                                        right: -20px;
                                        background: #0380b2;
                                        color: #fff;
                                        width: 40px;
                                        height: 40px;
                                        display: flex;
                                        align-items: flex-end;
                                        justify-content: flex-start;
                                        padding: 5px;
                                        border-radius: 50%;
                                        transform: rotate(45deg);
                                        transition: all 0.3s ease;
                                        opacity: 0;
                                    }
                                    .bank-checkbox:checked + .bank-card-label .card-selection-marker {
                                        opacity: 1;
                                        top: -15px;
                                        right: -15px;
                                    }
                                    .card-selection-marker i {
                                        transform: rotate(-45deg);
                                        font-size: 14px;
                                        margin-left: 5px;
                                        margin-bottom: 5px;
                                    }
                                </style>

                            </div>
                        </div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between p-3">
        <button class="btn btn-secondary" wire:click="cancelEdit">
            <i class="fas fa-times mr-1"></i> Cancelar
        </button>
        
        @if($user->id > 0)
            @can('users.edit')
            <button class="btn btn-primary" wire:click.prevent="Store">
                <i class="fas fa-check mr-1"></i> Actualizar
            </button>
            @endcan
        @else
            @can('users.create')
            <button class="btn btn-primary" wire:click.prevent="Store">
                <i class="fas fa-check mr-1"></i> Crear Usuario
            </button>
            @endcan
        @endif
    </div>
</div>
