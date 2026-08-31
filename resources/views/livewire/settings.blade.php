<div>
    <div class="card">
        <div class="card-header">
            <h5>Configuraciones del Sistema</h5>
        </div>
        <div class="card-body">
            <div class="row g-xl-5 g-3">
                {{-- Sidebar de Pestañas --}}
                <div class="col-xxl-3 col-xl-4 box-col-4e sidebar-left-wrapper">
                    <ul class="nav flex-column nav-pills me-3" id="settings-pills-tab" role="tablist">
                        @role('Super Admin')
                        {{-- Tab 1: Configuración General --}}
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 1 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',1)" href="#">
                                <i class="fa fa-cogs fa-2x text-primary"></i>
                                <div>
                                    <h6 class="mb-0 text-primary">General</h6>
                                    <small class="{{ $tab == 1 ? 'text-white' : 'text-muted' }}">Empresa y contacto</small>
                                </div>
                            </a>
                        </li>

                        {{-- Tab 2: Configuración de Ventas --}}
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 2 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',2)" href="#">
                                <i class="fa fa-shopping-cart fa-2x text-info"></i>
                                <div>
                                    <h6 class="mb-0 text-info">Ventas</h6>
                                    <small class="{{ $tab == 2 ? 'text-white' : 'text-muted' }}">Créditos y confirmación</small>
                                </div>
                            </a>
                        </li>
                        @endrole

                        {{-- Tab 3: Configuración de Monedas --}}
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 3 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',3)" href="#">
                                <i class="fa fa-coins fa-2x text-success"></i>
                                <div>
                                    <h6 class="mb-0 text-success">Monedas</h6>
                                    <small class="{{ $tab == 3 ? 'text-white' : 'text-muted' }}">Monedas y tasas</small>
                                </div>
                            </a>
                        </li>

                        {{-- Tab 4: Configuración de Bancos --}}
                        @module('module_advanced_payments')
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 4 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',4)" href="#">
                                <i class="fa fa-university fa-2x text-danger"></i>
                                <div>
                                    <h6 class="mb-0 text-danger">Bancos</h6>
                                    <small class="{{ $tab == 4 ? 'text-white' : 'text-muted' }}">Bancos y monedas</small>
                                </div>
                            </a>
                        </li>
                        @endmodule

                        @role('Super Admin')
                        {{-- Tab 5: Configuración de Comisiones --}}
                        @module('module_commissions')
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 5 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',5)" href="#">
                                <i class="fa fa-chart-line fa-2x text-primary"></i>
                                <div>
                                    <h6 class="mb-0 text-primary">Comisiones</h6>
                                    <small class="{{ $tab == 5 ? 'text-white' : 'text-muted' }}">Reglas globales</small>
                                </div>
                            </a>
                        </li>
                        @endmodule

                        {{-- Tab 6: Configuración de Compras --}}
                        @module('module_purchases')
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 6 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',6)" href="#">
                                <i class="fa fa-shopping-bag fa-2x text-info"></i>
                                <div>
                                    <h6 class="mb-0 text-info">Compras</h6>
                                    <small class="{{ $tab == 6 ? 'text-white' : 'text-muted' }}">Inteligencia de Compras</small>
                                </div>
                            </a>
                        </li>
                        @endmodule
                        {{-- Tab 7: Configuración Móvil --}}
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 7 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',7)" href="#">
                                <i class="fa fa-mobile fa-2x text-secondary"></i>
                                <div>
                                    <h6 class="mb-0 text-secondary">Móvil</h6>
                                    <small class="{{ $tab == 7 ? 'text-white' : 'text-muted' }}">Escáner y Cámara</small>
                                </div>
                            </a>
                        </li>
                        {{-- Tab 8: Configuración de Producción --}}
                        @module('module_production')
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 8 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',8)" href="#">
                                <i class="fa fa-industry fa-2x text-dark"></i>
                                <div>
                                    <h6 class="mb-0 text-dark">Producción</h6>
                                    <small class="{{ $tab == 8 ? 'text-white' : 'text-muted' }}">Emails y reportes</small>
                                </div>
                            </a>
                        </li>
                        @endmodule
                        
                        {{-- Tab 9: Configuración de Crédito Global --}}
                        @module('module_credits')
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 9 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',9)" href="#">
                                <i class="fa fa-credit-card fa-2x text-success"></i>
                                <div>
                                    <h6 class="mb-0 text-success">Crédito Global</h6>
                                    <small class="{{ $tab == 9 ? 'text-white' : 'text-muted' }}">Reglas por defecto</small>
                                </div>
                            </a>
                        </li>
                        @endmodule
                        
                        {{-- Tab 10: Actualización Masiva de Precios --}}
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 10 ? 'active show' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab', 10)" href="#">
                                <i class="fa fa-percent fa-2x text-danger"></i>
                                <div>
                                    <h6 class="mb-0 text-danger">Precios Masivos</h6>
                                    <small class="{{ $tab == 10 ? 'text-white' : 'text-muted' }}">Aumentos por Lote</small>
                                </div>
                            </a>
                        </li>
                        {{-- Tab 11: Catálogo --}}
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 11 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',11)" href="#">
                                <i class="fa fa-book fa-2x text-primary"></i>
                                <div>
                                    <h6 class="mb-0 text-primary">Catálogo</h6>
                                    <small class="{{ $tab == 11 ? 'text-white' : 'text-muted' }}">Configuración de PDF</small>
                                </div>
                            </a>
                        </li>

                        {{-- Tab 12: Configuración de Tesorería --}}
                        @module('module_treasury')
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 12 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',12)" href="#">
                                <i class="fa fa-university fa-2x text-info"></i>
                                <div>
                                    <h6 class="mb-0 text-info">Tesorería</h6>
                                    <small class="{{ $tab == 12 ? 'text-white' : 'text-muted' }}">Cortes y Categorías</small>
                                </div>
                            </a>
                        </li>
                        @endmodule

                        {{-- Tab 13: Anulación de Licencia (Local Overrides) --}}
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 13 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',13)" href="#">
                                <i class="fa fa-key fa-2x text-warning"></i>
                                <div>
                                    <h6 class="mb-0 text-warning">Licencia Local</h6>
                                    <small class="{{ $tab == 13 ? 'text-white' : 'text-muted' }}">Anulación Super Admin</small>
                                </div>
                            </a>
                        </li>
                        @endrole

                        {{-- Tab 14: Mi Licencia (Client view) --}}
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 14 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',14)" href="#">
                                <i class="fa fa-id-card fa-2x text-success"></i>
                                <div>
                                    <h6 class="mb-0 text-success">Mi Licencia</h6>
                                    <small class="{{ $tab == 14 ? 'text-white' : 'text-muted' }}">Estado y Módulos</small>
                                </div>
                            </a>
                        </li>

                        @role('Super Admin')
                        {{-- Tab 15: Configuración de Tickets --}}
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 15 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',15)" href="#">
                                <i class="fa fa-receipt fa-2x text-warning"></i>
                                <div>
                                    <h6 class="mb-0 text-warning">Tickets</h6>
                                    <small class="{{ $tab == 15 ? 'text-white' : 'text-muted' }}">Formato e Impresión</small>
                                </div>
                            </a>
                        </li>

                        {{-- Tab 16: Configuración de Facturas PDF --}}
                        <li class="nav-item mb-2">
                            <a class="nav-link {{ $tab == 16 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                               wire:click.prevent="$set('tab',16)" href="#">
                                <i class="fa fa-file-pdf fa-2x text-danger"></i>
                                <div>
                                    <h6 class="mb-0 text-danger">Facturas PDF</h6>
                                    <small class="{{ $tab == 16 ? 'text-white' : 'text-muted' }}">Formato y Secciones</small>
                                </div>
                            </a>
                        </li>
                        @endrole
                    </ul>
                </div>

                {{-- Contenido de las Pestañas --}}
                <div class="col-xxl-9 col-xl-8 box-col-8 position-relative">
                    <div class="tab-content" id="settings-pills-tabContent">
                        
                        {{-- TAB 1: CONFIGURACIÓN GENERAL --}}
                        <div class="tab-pane fade {{ $tab == 1 ? 'active show' : '' }}" id="general-settings" role="tabpanel"
                            aria-labelledby="general-settings-tab">
                            <div class="sidebar-body">
                                <form class="row g-3">
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">EMPRESA <span class="txt-danger">*</span></label>
                                        <input wire:model="businessName" type="text" class="form-control" maxlength="150">
                                        @error('businessName') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">TELÉFONO</label>
                                        <input wire:model="phone" type="text" class="form-control" maxlength="20">
                                        @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">CC / NIT <span class="txt-danger">*</span></label>
                                        <input wire:model="taxpayerId" type="text" class="form-control" maxlength="35">
                                        @error('taxpayerId') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-3">
                                        <label class="form-label">IVA / VAT <span class="txt-danger">*</span></label>
                                        <input wire:model="vat" type="text" class="form-control">
                                        @error('vat') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-3">
                                         <label class="form-label">N° de Decimales <span class="txt-danger">*</span></label>
                                         <input wire:model="decimals" type="text" class="form-control">
                                         @error('decimals') <span class="text-danger">{{ $message }}</span> @enderror
                                     </div>

                                     <div class="col-sm-12 col-md-6">
                                         <label class="form-label">FECHA CORTE FÓRMULA RECARGOS</label>
                                         <input wire:model="sequentialCutOffDate" type="datetime-local" class="form-control">
                                         @error('sequentialCutOffDate') <span class="text-danger">{{ $message }}</span> @enderror
                                     </div>

                                     <div class="col-sm-12 col-md-6">
                                         <label class="form-label">PLANTILLA DE ETIQUETAS POR DEFECTO</label>
                                         <select wire:model="defaultLabelTemplate" class="form-control">
                                             <option value="standard">Plantilla Estándar (Código de Barras - 4x7)</option>
                                             <option value="large_qr">Plantilla Grande (Código QR - 3x6)</option>
                                         </select>
                                         @error('defaultLabelTemplate') <span class="text-danger">{{ $message }}</span> @enderror
                                     </div>

                                     <div class="col-sm-12 col-md-6">
                                         <label class="form-label">MODO DE ASIGNACIÓN DE VENDEDOR EN VENTAS</label>
                                         <select wire:model="sellerAssignmentMode" class="form-control">
                                             <option value="customer_assigned">Vendedor Asignado al Cliente (Automático / Estándar)</option>
                                             <option value="manual_select">Selección Manual de Vendedor en Caja (Arbitraria)</option>
                                             <option value="both">Híbrido (Preseleccionar Vendedor del Cliente pero permitir cambio)</option>
                                         </select>
                                         @error('sellerAssignmentMode') <span class="text-danger">{{ $message }}</span> @enderror
                                     </div>

                                     <div class="col-sm-12 col-md-6">
                                         <label class="form-label">MODELO DE CÁLCULO DE COMISIONES</label>
                                         <select wire:model="commissionCalculationMode" class="form-control">
                                             <option value="percentage_threshold">Comisión Porcentual Individual por Venta (Estándar)</option>
                                             <option value="tiered_goals">Comisiones por Metas Acumuladas de Ventas (Niveles/Premios)</option>
                                             <option value="both">Permitir Ambos Modelos de Comisión</option>
                                         </select>
                                         @error('commissionCalculationMode') <span class="text-danger">{{ $message }}</span> @enderror
                                     </div>

                                    <div class="col-sm-12 col-md-12">
                                        <div class="form-check form-switch pl-0">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="isNetwork" wire:model.live="isNetwork">
                                                <label class="custom-control-label" for="isNetwork">¿Es una impresora de red con contraseña?</label>
                                            </div>
                                        </div>
                                    </div>

                                    @if($isNetwork)
                                        <div class="col-sm-12 col-md-6">
                                            <label class="form-label">IP o Nombre del Equipo <span class="txt-danger">*</span></label>
                                            <input wire:model="printerHost" type="text" class="form-control" placeholder="Ej: 192.168.1.50">
                                            @error('printerHost') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <label class="form-label">Nombre Compartido <span class="txt-danger">*</span></label>
                                            <input wire:model="printerShare" type="text" class="form-control" placeholder="Ej: EPSON_TM">
                                            @error('printerShare') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <label class="form-label">Usuario</label>
                                            <input wire:model="printerUser" type="text" class="form-control" placeholder="Opcional">
                                            @error('printerUser') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-sm-12 col-md-6">
                                            <label class="form-label">Contraseña</label>
                                            <input wire:model="printerPassword" type="password" class="form-control" placeholder="Opcional">
                                            @error('printerPassword') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    @else
                                        <div class="col-sm-12 col-md-6">
                                            <label class="form-label">IMPRESORA <span class="txt-danger">*</span></label>
                                            <input wire:model="printerName" type="text" class="form-control" maxlength="55">
                                            @error('printerName') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    @endif

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">ANCHO DE IMPRESIÓN</label>
                                        <select wire:model="printerWidth" class="form-control">
                                            <option value="80mm">80mm (Estándar)</option>
                                            <option value="58mm">58mm (Pequeña)</option>
                                        </select>
                                        @error('printerWidth') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">CITY</label>
                                        <input wire:model="city" class="form-control" type="text" maxlength="255">
                                        @error('city') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">WEBSITE</label>
                                        <input wire:model="website" type="text" class="form-control" placeholder="www.website.com" maxlength="99">
                                        @error('website') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">LEYENDA</label>
                                        <input wire:model="leyend" type="text" class="form-control" maxlength="99">
                                        @error('leyend') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label">EMAILS PARA COPIAS DE SEGURIDAD (Separados por coma)</label>
                                        <textarea wire:model="backupEmails" class="form-control" cols="30" rows="2" placeholder="ejemplo@correo.com, otro@correo.com"></textarea>
                                        @error('backupEmails') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">EMAIL ALERTAS VENCIMIENTO</label>
                                        <input wire:model="licenseNotificationEmail" type="email" class="form-control" placeholder="alertas@empresa.com">
                                        @error('licenseNotificationEmail') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">EMAIL SOLICITUD RENOVACIÓN</label>
                                        <input wire:model="licenseRequestEmail" type="email" class="form-control" placeholder="ventas@empresa.com">
                                        @error('licenseRequestEmail') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">WHATSAPP SOLICITUD RENOVACIÓN (Incluir código país)</label>
                                        <input wire:model="licenseRequestPhone" type="text" class="form-control" placeholder="584141234567">
                                        @error('licenseRequestPhone') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">LOGO DE LA EMPRESA</label>
                                        <input type="file" wire:model="logo" accept="image/png, image/jpeg, image/jpg" class="form-control">
                                        @error('logo') <span class="text-danger">{{ $message }}</span> @enderror

                                        <div class="mt-2">
                                            @if ($logo)
                                                <img src="{{ $logo->temporaryUrl() }}" alt="Logo Preview" class="img-thumbnail" style="max-height: 100px;">
                                            @elseif($logo_preview)
                                            <img src="{{ asset('storage/' . $logo_preview) }}" alt="Current Logo" class="img-thumbnail" style="max-height: 100px;" onerror="this.onerror=null;this.src='{{ asset('logo/logo.jpg') }}';">
                                            @endif
                                        </div>
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label">DIRECCIÓN</label>
                                        <textarea wire:model="address" class="form-control" cols="30" rows="2"></textarea>
                                        @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-primary" wire:click.prevent="saveConfig" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="saveConfig">Guardar Configuración</span>
                                            <span wire:loading wire:target="saveConfig">Guardando...</span>
                                        </button>
                                    </div>
                                </form>

                                @livewire('settings.commission-goals-manager')
                            </div>
                        </div>

                        {{-- TAB 2: CONFIGURACIÓN DE VENTAS --}}
                        <div class="tab-pane fade {{ $tab == 2 ? 'active show' : '' }}" id="sales-settings" role="tabpanel"
                            aria-labelledby="sales-settings-tab">
                            <div class="sidebar-body">
                                <form class="row g-3">
                                    @module('module_credits')
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">VENTAS CRÉDITO (DÍAS) <span class="txt-danger">*</span></label>
                                        <input wire:model="creditDays" type="number" class="form-control">
                                        @error('creditDays') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    @endmodule

                                    @module('module_purchases')
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">COMPRAS CRÉDITO (DÍAS)</label>
                                        <input wire:model="creditPurchaseDays" class="form-control" type="text" maxlength="255">
                                        @error('creditPurchaseDays') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    @endmodule

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">DEPÓSITO PREDETERMINADO</label>
                                        <select wire:model="defaultWarehouseId" class="form-control">
                                            <option value="">Seleccionar Depósito</option>
                                            @foreach($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                        @error('defaultWarehouseId') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">MODO DE VISTA DE VENTAS (PREDETERMINADO)</label>
                                        <select wire:model="salesViewMode" class="form-control">
                                            <option value="grid">Cuadrícula (Imágenes Grandes)</option>
                                            <option value="list">Lista (Compacta)</option>
                                        </select>
                                        @error('salesViewMode') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6 mt-3 mt-md-0">
                                        <label class="form-label">MOSTRAR ETIQUETA DE TASA DE CAMBIO (VENTAS)</label>
                                        <div class="form-check form-switch pl-0">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="salesShowRateBadge" wire:model="salesShowRateBadge">
                                                <label class="custom-control-label" for="salesShowRateBadge">Mostrar etiqueta "Tasa: 900.00" cuando se factura en otra moneda</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-12 col-md-6 mt-3 mt-md-0">
                                        <label class="form-label">CONTROLES DE COMISIONES Y FLETES (VENTAS)</label>
                                        <div class="form-check form-switch pl-0">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="salesShowCommissions" wire:model="salesShowCommissions">
                                                <label class="custom-control-label" for="salesShowCommissions">Mostrar control "Aplicar Comisiones"</label>
                                            </div>
                                            <div class="custom-control custom-switch mt-2">
                                                <input type="checkbox" class="custom-control-input" id="salesShowFreight" wire:model="salesShowFreight">
                                                <label class="custom-control-label" for="salesShowFreight">Mostrar control "Aplicar Solo Flete"</label>
                                            </div>
                                            <div class="custom-control custom-switch mt-2">
                                                <input type="checkbox" class="custom-control-input" id="salesShowBreakdownFreight" wire:model="salesShowBreakdownFreight">
                                                <label class="custom-control-label" for="salesShowBreakdownFreight">Mostrar control "Desglosar Flete"</label>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-12 col-md-6 mt-3 mt-md-0">
                                        <label class="form-label">SELECTORES DE DEPÓSITO Y CHOFER (VENTAS)</label>
                                        <div class="form-check form-switch pl-0">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="salesShowWarehouse" wire:model="salesShowWarehouse">
                                                <label class="custom-control-label" for="salesShowWarehouse">Mostrar selector de "Tienda Principal" (Depósito)</label>
                                            </div>
                                            <div class="custom-control custom-switch mt-2">
                                                <input type="checkbox" class="custom-control-input" id="salesShowDriver" wire:model="salesShowDriver">
                                                <label class="custom-control-label" for="salesShowDriver">Mostrar selector de "Chofer"</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">VALIDAR STOCK RESERVADO</label>
                                        <div class="form-check form-switch pl-0">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="checkStockReservation" wire:model="checkStockReservation">
                                                <label class="custom-control-label" for="checkStockReservation">Activar alerta de pedidos pendientes</label>
                                            </div>
                                            @error('checkStockReservation') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">CLIENTE POR DEFECTO (POS)</label>
                                        <div class="form-check form-switch pl-0">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="autoSelectDefaultCustomer" wire:model="autoSelectDefaultCustomer">
                                                <label class="custom-control-label" for="autoSelectDefaultCustomer">Auto-seleccionar cliente genérico en ventas</label>
                                            </div>
                                            @error('autoSelectDefaultCustomer') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>


                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">MODO CAJA COMPARTIDA (Oficina)</label>
                                        <div class="form-check form-switch pl-0">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="enableSharedCashRegister" wire:model="enableSharedCashRegister">
                                                <label class="custom-control-label" for="enableSharedCashRegister">Permitir venta sin caja propia</label>
                                            </div>
                                            <small class="text-muted">Si se activa, los vendedores sin caja abierta podrán vender usando la última caja abierta disponible.</small>
                                            @error('enableSharedCashRegister') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">TIEMPO PARA EDITAR (HH:MM:SS)</label>
                                        <input wire:model="salesEditTimeout" type="text" class="form-control" placeholder="00:30:00">
                                        <small class="text-muted">Tiempo máximo para editar facturas (Vendedores). Formato: Horas:Minutos:Segundos.</small>
                                        @error('salesEditTimeout') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label">CODIGO DE CONFIRMACION</label>
                                        <textarea wire:model="confirmationCode" class="form-control" cols="30" rows="2"></textarea>
                                        @error('confirmationCode') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-primary" wire:click.prevent="saveConfig" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="saveConfig">Guardar Configuración</span>
                                            <span wire:loading wire:target="saveConfig">Guardando...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- TAB 3: CONFIGURACIÓN DE MONEDAS --}}
                        <div class="tab-pane fade {{ $tab == 3 ? 'active show' : '' }}" id="currencies-settings" role="tabpanel"
                            aria-labelledby="currencies-settings-tab">
                            <div class="sidebar-body">
                                {{-- Global Rates Section --}}
                                <div class="card bg-light border-0 mb-4">
                                    <div class="card-body">
                                        <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap: 10px;">
                                            <h6 class="mb-0 text-primary"><i class="fa fa-globe me-2"></i>Tasas Globales de Referencia</h6>
                                            @php
                                                $bcv = floatval($bcvRate);
                                                $binReal = floatval($binanceRate);
                                                $markup = floatval($binanceMarkupPoints);
                                                
                                                $gapReal = $bcv > 0 ? (($binReal - $bcv) / $bcv) * 100 : 0;
                                                $gapApplied = $bcv > 0 ? ((($binReal + $markup) - $bcv) / $bcv) * 100 : 0;
                                            @endphp
                                            @if($bcv > 0)
                                                <div class="d-flex align-items-center" style="gap: 8px;">
                                                    <span class="badge px-3 py-2 font-weight-bold" style="font-size: 0.85rem; border-radius: 30px; background-color: #f0f4f8; color: #1e3a8a; border: 1px solid #dbeafe;">
                                                        <i class="fa fa-percent me-1 text-primary"></i> Dif. Real: {{ $gapReal >= 0 ? '+' : '' }}{{ number_format($gapReal, 2) }}%
                                                    </span>
                                                    <span class="badge px-3 py-2 font-weight-bold" style="font-size: 0.85rem; border-radius: 30px; background-color: #ecfdf5; color: #065f46; border: 1px solid #d1fae5;">
                                                        <i class="fa fa-calculator me-1 text-success"></i> Dif. Aplicado: {{ $gapApplied >= 0 ? '+' : '' }}{{ number_format($gapApplied, 2) }}%
                                                    </span>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="row g-3 align-items-end">
                                            <div class="col-md-3">
                                                <label class="form-label">Tasa BCV (Bs.)</label>
                                                <input wire:model.live.debounce.300ms="bcvRate" type="number" step="0.000001" class="form-control" placeholder="0.00">
                                            </div>
                                            <div class="col-md-3">
                                                <label class="form-label">Tasa Binance Real (Bs.)</label>
                                                <input wire:model.live.debounce.300ms="binanceRate" type="number" step="0.000001" class="form-control" placeholder="0.00">
                                            </div>
                                            <div class="col-md-2">
                                                <label class="form-label">Ajuste (Bs.)</label>
                                                <input wire:model.live.debounce.300ms="binanceMarkupPoints" type="number" step="0.000001" class="form-control" placeholder="0.00">
                                            </div>
                                            <div class="col-md-4 d-flex gap-2">
                                                <button wire:click="saveGlobalRates" class="btn btn-success flex-grow-1">
                                                    <i class="fa fa-save me-1"></i> Guardar Tasas
                                                </button>
                                                <button wire:click="viewRateHistory" class="btn btn-info text-white">
                                                    <i class="fa fa-history"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="text-muted mt-2 d-block">
                                            <i class="fa fa-info-circle"></i> Estas tasas y su ajuste se usarán como referencia precargada al registrar pagos en Bolívares.
                                        </small>
                                    </div>
                                </div>
                                <div class="row g-3">
                                    <div class="col-12">
                                        <h6 class="mb-3">Moneda Principal</h6>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="primaryCurrency">Seleccionar Moneda Principal</label>
                                                <select wire:model="primaryCurrency" class="form-control">
                                                    <option value="">Seleccione una moneda</option>
                                                    @foreach ($currencies as $currency)
                                                        <option value="{{ $currency->code }}" {{ $currency->code == $primaryCurrency ? 'selected' : '' }}>
                                                            {{ $currency->code }} ({{ $currency->label }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 d-flex align-items-end">
                                                <button wire:click="setPrimaryCurrency" class="btn btn-primary">Guardar Moneda Principal</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <hr>
                                        <h6 class="mb-3">Agregar Moneda Secundaria</h6>
                                        <div class="row g-2">
                                            <div class="col-md-3">
                                                <input wire:model="newCurrencyCode" type="text" class="form-control" placeholder="Código (ISO 4217)">
                                            </div>
                                            <div class="col-md-3">
                                                <input wire:model="newCurrencyLabel" type="text" class="form-control" placeholder="Label">
                                            </div>
                                            <div class="col-md-2">
                                                <input wire:model="newCurrencySymbol" type="text" class="form-control" placeholder="Símbolo">
                                            </div>
                                            <div class="col-md-2">
                                                <input wire:model="newExchangeRate" type="number" step="0.000001" class="form-control" placeholder="Tasa de Cambio">
                                            </div>
                                            <div class="col-md-2">
                                                <button wire:click="addCurrency" class="btn btn-primary w-100">Agregar</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <h6 class="mb-3 mt-3">Monedas Configuradas</h6>
                                        <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Label</th>
                                                        <th>Símbolo</th>
                                                        <th>Tasa de Cambio</th>
                                                        <th>Principal</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($currencies as $currency)
                                                        <tr>
                                                            <td>{{ $currency->code }}</td>
                                                            <td>{{ $currency->label }}</td>
                                                            <td>{{ $currency->symbol }}</td>
                                                            <td>
                                                                <div class="input-group input-group-sm">
                                                                    <input type="number" step="0.000001" 
                                                                        class="form-control" 
                                                                        wire:model="editableRates.{{ $currency->id }}"
                                                                        {{ $currency->is_primary ? 'disabled' : '' }}>
                                                                    @if(!$currency->is_primary)
                                                                        <button class="btn btn-primary" wire:click="updateCurrencyRate({{ $currency->id }})">
                                                                            <i class="fa fa-save"></i>
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <td>
                                                                @if($currency->is_primary)
                                                                    <span class="badge bg-success">Principal</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if(!$currency->is_primary)
                                                                    <button wire:click="deleteCurrency('{{ $currency->id }}')" class="btn btn-danger btn-sm">
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                @endif
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- TAB 4: CONFIGURACIÓN DE BANCOS --}}
                        @module('module_advanced_payments')
                        <div class="tab-pane fade {{ $tab == 4 ? 'active show' : '' }}" id="banks-settings" role="tabpanel"
                            aria-labelledby="banks-settings-tab">
                            <div class="sidebar-body">
                                <div class="col-12">
                                    <h6 class="mb-3">{{ $selectedBankId ? 'Editar Banco' : 'Agregar Nuevo Banco' }}</h6>
                                    <div class="row g-2">
                                        <div class="col-md-3">
                                            <label>Nombre del Banco</label>
                                            <input wire:model="newBankName" type="text" class="form-control" placeholder="Nombre (Banesco, Mercantil...)">
                                            @error('newBankName') <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label>Titular de la Cuenta</label>
                                            <input wire:model="account_holder" type="text" class="form-control" placeholder="Nombre del titular">
                                            @error('account_holder') <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-2">
                                            <label>Moneda del Banco</label>
                                            <select wire:model="newBankCurrency" class="form-control">
                                                <option value="">Moneda</option>
                                                @foreach ($currencies as $currency)
                                                    <option value="{{ $currency->code }}">{{ $currency->code }}</option>
                                                @endforeach
                                            </select>
                                            @error('newBankCurrency') <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-3">
                                            <label>Número de Cuenta</label>
                                            <input wire:model="newBankAccountNumber" type="text" class="form-control" placeholder="0102...">
                                            @error('newBankAccountNumber') <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-2">
                                            <label>Cédula de Identidad</label>
                                            <input wire:model="newBankCedula" type="text" class="form-control" placeholder="V-12345678">
                                            @error('newBankCedula') <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-2">
                                            <label>Pago Móvil</label>
                                            <input wire:model="newBankPhone" type="text" class="form-control" placeholder="0414...">
                                            @error('newBankPhone') <span class="text-danger small">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-md-2">
                                            <div class="custom-control custom-checkbox mt-4 pt-1">
                                                <input type="checkbox" wire:model.live="newBankIsTracked" class="custom-control-input" id="newBankIsTracked">
                                                <label class="custom-control-label font-weight-bold" for="newBankIsTracked">Auditado</label>
                                            </div>
                                        </div>
                                        @if($newBankIsTracked)
                                            <div class="col-md-2">
                                                <label>Saldo Inicial</label>
                                                <input wire:model="newBankInitialBalance" type="number" step="0.01" class="form-control" placeholder="0.00">
                                                @error('newBankInitialBalance') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-2">
                                                <label>Fecha de Inicio</label>
                                                <input wire:model="newBankInitialBalanceDate" type="date" class="form-control">
                                                @error('newBankInitialBalanceDate') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>
                                        @endif
                                        <div class="col-12 text-end mt-3">
                                            @if($selectedBankId)
                                                <button wire:click="resetBankForm" class="btn btn-outline-secondary me-2">
                                                    Cancelar
                                                </button>
                                                <button wire:click="addBank" class="btn btn-info text-white">
                                                    <i class="fa fa-save me-1"></i> Actualizar Banco
                                                </button>
                                            @else
                                                <button wire:click="addBank" class="btn btn-primary">
                                                    <i class="fa fa-plus me-1"></i> Agregar Banco
                                                </button>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <h6 class="mb-3 mt-3">Bancos Configurados</h6>
                                    <div class="table-responsive">
                                        <table class="table table-hover table-bordered">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th>Banco</th>
                                                    <th>Titular</th>
                                                    <th>Cuenta</th>
                                                    <th>Cédula</th>
                                                    <th>Pago Móvil</th>
                                                    <th>Moneda</th>
                                                    <th>Seguimiento</th>
                                                    <th>Saldo Actual</th>
                                                    <th class="text-center">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($banks as $bank)
                                                    <tr>
                                                        <td><strong class="text-primary">{{ $bank->name }}</strong></td>
                                                        <td>{{ $bank->account_holder }}</td>
                                                        <td>{{ $bank->account_number }}</td>
                                                        <td>{{ $bank->cedula }}</td>
                                                        <td>{{ $bank->phone }}</td>
                                                        <td><span class="badge bg-light text-dark">{{ $bank->currency_code }}</span></td>
                                                        <td>
                                                            @if($bank->is_tracked)
                                                                <span class="badge bg-success text-white">SÍ (Auditado)</span>
                                                                <div class="text-muted small mt-1">Ini: ${{ number_format($bank->initial_balance, 2) }}</div>
                                                                <div class="text-muted small">Desde: {{ $bank->initial_balance_date ? $bank->initial_balance_date->format('d/m/Y') : '-' }}</div>
                                                            @else
                                                                <span class="badge bg-secondary text-white">NO</span>
                                                            @endif
                                                        </td>
                                                        <td><strong>${{ number_format($bank->current_balance, 2) }}</strong></td>
                                                        <td class="text-center">
                                                            <div class="btn-group">
                                                                <button wire:click="editBank({{ $bank->id }})" class="btn btn-outline-primary btn-sm">
                                                                    <i class="fa fa-edit"></i>
                                                                </button>
                                                                <button wire:click="deleteBank({{ $bank->id }})" class="btn btn-outline-danger btn-sm"
                                                                    onclick="confirm('¿Eliminar este banco?') || event.stopImmediatePropagation()">
                                                                    <i class="fa fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endmodule

                        {{-- TAB 5: CONFIGURACIÓN DE COMISIONES --}}
                        @module('module_commissions')
                        <div class="tab-pane fade {{ $tab == 5 ? 'active show' : '' }}" id="commissions-settings" role="tabpanel"
                            aria-labelledby="commissions-settings-tab">
                            <div class="sidebar-body">
                                <form class="row g-3">
                                    <div class="col-12">
                                        <div class="alert alert-light-primary" role="alert">
                                            <i class="fas fa-info-circle"></i> Estas reglas se aplicarán si el Vendedor o el Cliente no tienen una configuración específica.
                                        </div>
                                    </div>
                                    
                                    <h6 class="mb-2">Nivel 1 (Pronto Pago)</h6>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">Días Límite (<=)</label>
                                        <input wire:model="globalCommission1Threshold" type="number" class="form-control" placeholder="Ej: 15">
                                        @error('globalCommission1Threshold') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">Porcentaje (%)</label>
                                        <input wire:model="globalCommission1Percentage" type="number" step="0.01" class="form-control" placeholder="Ej: 8">
                                        @error('globalCommission1Percentage') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-12"><hr></div>

                                    <h6 class="mb-2">Nivel 2 (Pago Tardío)</h6>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">Días Límite (<=)</label>
                                        <input wire:model="globalCommission2Threshold" type="number" class="form-control" placeholder="Ej: 30">
                                        @error('globalCommission2Threshold') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">Porcentaje (%)</label>
                                        <input wire:model="globalCommission2Percentage" type="number" step="0.01" class="form-control" placeholder="Ej: 4">
                                        @error('globalCommission2Percentage') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-primary" wire:click.prevent="saveConfig" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="saveConfig">Guardar Configuración</span>
                                            <span wire:loading wire:target="saveConfig">Guardando...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endmodule

                        {{-- TAB 6: CONFIGURACIÓN DE COMPRAS --}}
                        @module('module_purchases')
                        <div class="tab-pane fade {{ $tab == 6 ? 'active show' : '' }}" id="purchasing-settings" role="tabpanel"
                            aria-labelledby="purchasing-settings-tab">
                            <div class="sidebar-body">
                                <form class="row g-3">
                                    <div class="col-12">
                                        <div class="alert alert-light-primary" role="alert">
                                            <i class="fas fa-info-circle"></i> Configura cómo el sistema sugiere las cantidades a comprar.
                                        </div>
                                    </div>
                                    
                                    <h6 class="mb-2">Inteligencia de Compras</h6>
                                    
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">Modo de Cálculo</label>
                                        <select wire:model="purchasingCalculationMode" class="form-control">
                                            <option value="recent">Tendencia Reciente (Últimos meses)</option>
                                            <option value="seasonal">Estacional (Mismo periodo año anterior)</option>
                                        </select>
                                        <small class="text-muted">
                                            @if($purchasingCalculationMode == 'recent')
                                                Basar sugerencia en el promedio de ventas reciente. Ideal para empezar.
                                            @else
                                                Basar sugerencia en las ventas del año pasado. Ideal con historial.
                                            @endif
                                        </small>
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">Días de Cobertura Deseados</label>
                                        <input wire:model="purchasingCoverageDays" type="number" class="form-control" placeholder="Ej: 15">
                                        <small class="text-muted">¿Para cuántos días de venta quieres tener stock?</small>
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-primary" wire:click.prevent="saveConfig" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="saveConfig">Guardar Configuración</span>
                                            <span wire:loading wire:target="saveConfig">Guardando...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endmodule

                        {{-- TAB 7: CONFIGURACIÓN MÓVIL --}}
                        <div class="tab-pane fade {{ $tab == 7 ? 'active show' : '' }}" id="mobile-settings" role="tabpanel"
                            aria-labelledby="mobile-settings-tab">
                            <div class="sidebar-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <div class="alert alert-light-info" role="alert">
                                            <i class="fas fa-info-circle"></i> Instrucciones para habilitar el escáner de cámara en dispositivos móviles.
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <h5 class="mb-3">Configuración de Chrome Flags (Solo Localhost)</h5>
                                        <p>Para usar la cámara en una red local (sin HTTPS), debes configurar Chrome en tu celular:</p>
                                        
                                        <ol class="list-group list-group-numbered mb-3">
                                            <li class="list-group-item">Abre <strong>Chrome</strong> en tu celular.</li>
                                            <li class="list-group-item">Escribe en la barra de direcciones: <code>chrome://flags</code></li>
                                            <li class="list-group-item">Busca: <strong>"Insecure origins treated as secure"</strong></li>
                                            <li class="list-group-item">Cambia a <strong>Enabled</strong>.</li>
                                            <li class="list-group-item">
                                                En el cuadro de texto, escribe la IP de tu servidor:<br>
                                                <div class="input-group mt-2">
                                                    <input type="text" class="form-control" value="{{ request()->root() }}" readonly>
                                                    <button class="btn btn-outline-secondary" type="button" onclick="navigator.clipboard.writeText('{{ request()->root() }}')">
                                                        <i class="fa fa-copy"></i> Copiar
                                                    </button>
                                                </div>
                                            </li>
                                            <li class="list-group-item">Toca el botón <strong>Relaunch</strong> para reiniciar Chrome.</li>
                                        </ol>
                                        
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Esta configuración es necesaria solo si accedes por IP (ej. 192.168.x.x). Si usas un dominio seguro (HTTPS), no es necesario.
                                        </div>
                                    </div>

                                    {{-- Cloning Commands Legend --}}
                                    <div class="col-12 mt-4">
                                        <div class="card border-0 shadow-sm" style="background: #f8f9fa; border-radius: 15px;">
                                            <div class="card-body">
                                                <h5 class="mb-3 text-primary"><i class="fas fa-copy me-2"></i> Leyenda de Comandos de Clonación (Escáner)</h5>
                                                <p class="text-muted small mb-4">Puedes escanear códigos QR o escribir estos comandos directamente en los buscadores para duplicar documentos.</p>
                                                
                                                <div class="table-responsive">
                                                    <table class="table table-sm table-hover" style="font-size: 0.85rem;">
                                                        <thead class="text-uppercase text-muted" style="font-size: 0.7rem;">
                                                            <tr>
                                                                <th>Documento</th>
                                                                <th>Comandos Soportados</th>
                                                                <th>Ejemplo</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <tr>
                                                                <td><strong>Ventas / Facturas</strong></td>
                                                                <td><code>VENTA</code>, <code>FACTURA</code>, <code>SALE</code>, <code>VT</code></td>
                                                                <td class="text-info">VENTA:10</td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Órdenes de Pedido</strong></td>
                                                                <td><code>ORDEN</code>, <code>ORD</code>, <code>OR</code></td>
                                                                <td class="text-info">ORDEN:45</td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Cargos / Entradas</strong></td>
                                                                <td><code>CARGO</code>, <code>ENTRADA</code>, <code>AJUSTE</code></td>
                                                                <td class="text-info">ENTRADA:15</td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Descargos / Salidas</strong></td>
                                                                <td><code>DESCARGO</code>, <code>SALIDA</code></td>
                                                                <td class="text-info">SALIDA:5</td>
                                                            </tr>
                                                            <tr>
                                                                <td><strong>Compras / OC</strong></td>
                                                                <td><code>PURCHASE</code>, <code>COMPRA</code>, <code>OC</code></td>
                                                                <td class="text-info">COMPRA:101</td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                                
                                                <div class="mt-3 p-3 bg-white" style="border-radius: 10px; border-left: 4px solid #007bff;">
                                                    <small class="text-muted">
                                                        <strong>Nota:</strong> Los comandos son insensibles a mayúsculas y aceptan separadores como <code>:</code>, <code>-</code> o simplemente el número pegado (ej: <code>venta10</code>).
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- TAB 8: CONFIGURACIÓN DE PRODUCCIÓN --}}
                        @module('module_production')
                        <div class="tab-pane fade {{ $tab == 8 ? 'active show' : '' }}" id="production-settings" role="tabpanel"
                            aria-labelledby="production-settings-tab">
                            <div class="sidebar-body">
                                <form class="row g-3">
                                    <div class="col-12">
                                        <div class="alert alert-light-primary" role="alert">
                                            <i class="fas fa-info-circle"></i> Configura el envío de reportes de producción por correo electrónico.
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-12 col-md-6 mb-3">
                                        <label class="form-label text-primary fw-bold">ALMACÉN SOPLADOS (PLANTA) <span class="txt-danger">*</span></label>
                                        <select wire:model="sopladosWarehouseId" class="form-control">
                                            <option value="">Seleccionar Planta Soplados</option>
                                            @foreach($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Donde se fabrican botellones y PET.</small>
                                        @error('sopladosWarehouseId') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6 mb-3">
                                        <label class="form-label text-info fw-bold">ALMACÉN BOLSAS (PLANTA) <span class="txt-danger">*</span></label>
                                        <select wire:model="bolsasWarehouseId" class="form-control">
                                            <option value="">Seleccionar Planta Bolsas</option>
                                            @foreach($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Donde se fabrican bolsas.</small>
                                        @error('bolsasWarehouseId') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-12 mb-3">
                                        <label class="form-label text-warning fw-bold">ALMACÉN CENTRAL DE INSUMOS (MATERIA PRIMA)</label>
                                        <select wire:model="productionMaterialsWarehouseId" class="form-control">
                                            <option value="">Descontar de la misma Planta (Defecto)</option>
                                            @foreach($warehouses as $warehouse)
                                                <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Si se selecciona, el consumo de materia prima se descontará de aquí, sin importar la planta de producción.</small>
                                        @error('productionMaterialsWarehouseId') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 mt-3">
                                        <h5 class="text-uppercase text-primary fw-bold">Reporte de Producción (Fábrica de Bolsas)</h5>
                                        <hr class="mt-1 mb-3">
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label">DESTINATARIOS (Separados por coma)</label>
                                        <textarea wire:model="productionEmailRecipients" class="form-control" cols="30" rows="2" placeholder="ejemplo@correo.com, jefe@correo.com"></textarea>
                                        <small class="text-muted">Estos correos recibirán el PDF de producción.</small>
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label">DESTINATARIOS DE ADMINISTRACIÓN (Separados por coma)</label>
                                        <textarea wire:model="bagsAdminEmailRecipients" class="form-control" cols="30" rows="2" placeholder="admin1@correo.com, admin2@correo.com"></textarea>
                                        <small class="text-muted">Estos correos de administración recibirán tanto la planilla original como la aprobada cuando se confirme el Cargo.</small>
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label">ASUNTO DEL CORREO</label>
                                        <input wire:model="productionEmailSubject" type="text" class="form-control" placeholder="[SALUDO], Reporte Diario de Producción - [FECHA] (Lote #[PRODUCCION_ID]) - [EMPRESA]">
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label">CUERPO DEL CORREO</label>
                                        <textarea wire:model="productionEmailBody" class="form-control" cols="30" rows="10" placeholder="[SALUDO],

Adjunto a este correo electrónico se encuentra el reporte oficial detallado correspondiente a la jornada de producción del [FECHA].

A continuación, se presenta un resumen de los lotes procesados y consolidados durante este turno:

==================================================
📝 DATOS GENERALES DE LA ORDEN DE TRABAJO
==================================================
• Lote de Producción: #[PRODUCCION_ID]
• Fecha de Cierre: [FECHA]
• Operador a Cargo del Reporte: [USUARIO]
• Empresa / Planta: [EMPRESA]

==================================================
📊 TOTALES DE PLANTA
==================================================
• Cantidad Total Producida: [CANTIDAD_TOTAL] unidades
• Peso Total de Material Procesado: [PESO_TOTAL] Kg

==================================================
📦 DESGLOSE POR PRODUCTO Y TIPO DE MATERIAL
==================================================
[RESUMEN_DETALLES]

*(El detalle técnico por bobina individual, tipo de resina (Original/Recuperado), y mermas de extrusión y soplado se encuentra desglosado en el PDF adjunto).*

==================================================
🔍 OBSERVACIONES Y EVENTUALIDADES DE JORNADA
==================================================
[NOTA]

--------------------------------------------------
Este es un reporte automático emitido por el Sistema de Control de Producción y Ventas de [EMPRESA].

Quedamos atentos a cualquier consulta técnica o administrativa.

Atentamente,
Departamento de Control de Calidad y Manufactura
[EMPRESA]"></textarea>
                                        <div class="alert alert-light-info mt-2">
                                            <small>
                                                <b>Variables Disponibles para Bolsas:</b><br>
                                                <code>[FECHA]</code> : Fecha de producción (ej: Lunes, 12 de Enero de 2026)<br>
                                                <code>[SALUDO]</code> : Saludo automático (Buenos días / tardes / noches)<br>
                                                <code>[USUARIO]</code> : Nombre del operador que envía el correo<br>
                                                <code>[PRODUCCION_ID]</code> : ID/Lote de Producción<br>
                                                <code>[CANTIDAD_TOTAL]</code> : Cantidad total producida (unidades)<br>
                                                <code>[PESO_TOTAL]</code> : Peso total procesado (Kg)<br>
                                                <code>[RESUMEN_DETALLES]</code> : Resumen de productos y tipo de material (Original/Recuperado)<br>
                                                <code>[NOTA]</code> : Observaciones registradas por planta<br>
                                                <code>[EMPRESA]</code> : Nombre de la empresa
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 mt-4">
                                        <h5 class="text-uppercase text-primary fw-bold">Reporte de Cierre de Turno (Soplados / Botellones)</h5>
                                        <hr class="mt-1 mb-3">
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label">DESTINATARIOS (Separados por coma)</label>
                                        <textarea wire:model="sopladosEmailRecipients" class="form-control" cols="30" rows="2" placeholder="ejemplo@correo.com, jefe@correo.com"></textarea>
                                        <small class="text-muted">Estos correos recibirán el reporte detallado del turno cerrado.</small>
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label">ASUNTO DEL CORREO</label>
                                        <input wire:model="sopladosEmailSubject" type="text" class="form-control" placeholder="[SALUDO], Reporte del Turno de Soplado - [FECHA] ([TIPO_TURNO]) - [EMPRESA]">
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label">CUERPO DEL CORREO</label>
                                        <textarea wire:model="sopladosEmailBody" class="form-control" cols="30" rows="10" placeholder="Plantilla del mensaje..."></textarea>
                                        <div class="alert alert-light-info mt-2">
                                            <small>
                                                <b>Variables Disponibles para Soplados:</b><br>
                                                <code>[FECHA]</code> : Fecha del turno cerrado (ej: Martes, 16 de Junio de 2026)<br>
                                                <code>[SALUDO]</code> : Saludo automático (Buenos días / tardes / noches)<br>
                                                <code>[USUARIO]</code> : Nombre del operador que cierra el turno<br>
                                                <code>[TIPO_TURNO]</code> : Tipo de turno (Diurno / Nocturno)<br>
                                                <code>[HORA_INICIO]</code> : Hora de apertura del turno (ej: 06:00 AM)<br>
                                                <code>[HORA_FIN]</code> : Hora de cierre del turno (ej: 06:00 PM)<br>
                                                <code>[ALMACEN]</code> : Planta / Almacén del turno<br>
                                                <code>[OPERADORES]</code> : Nombres de los operadores activos en el turno<br>
                                                <code>[BUENA_CANTIDAD]</code> : Cantidad total de 1ra y 2da calidad producida (unidades)<br>
                                                <code>[DESECHADA_CANTIDAD]</code> : Cantidad total defectuosa (merma)<br>
                                                <code>[TOTAL_PRODUCIDO]</code> : Total de piezas procesadas (buena + defectuosa)<br>
                                                <code>[EFICIENCIA]</code> : Porcentaje de eficiencia/rendimiento (Yield)<br>
                                                <code>[RESUMEN_PRODUCCION]</code> : Detalle de botellones y envases soplados<br>
                                                <code>[RESUMEN_MATERIALES]</code> : Detalle de materias primas consumidas (Kg)<br>
                                                <code>[NOTA]</code> : Observaciones del supervisor de turno<br>
                                                <code>[EMPRESA]</code> : Nombre de la empresa
                                            </small>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-primary" wire:click.prevent="saveConfig" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="saveConfig">Guardar Configuración</span>
                                            <span wire:loading wire:target="saveConfig">Guardando...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endmodule

                        {{-- TAB 9: CONFIGURACIÓN DE CRÉDITO GLOBAL --}}
                        @module('module_credits')
                        <div class="tab-pane fade {{ $tab == 9 ? 'active show' : '' }}" id="credit-settings" role="tabpanel"
                            aria-labelledby="credit-settings-tab">
                            <div class="sidebar-body">
                                <form class="row g-2">
                                    {{-- Sección 1: Control de Crédito --}}
                                    <div class="col-sm-12">
                                        <h6 class="text-info mb-3">
                                            <i class="fa fa-credit-card"></i> Control de Crédito (Global)
                                        </h6>
                                        <p class="text-muted small">Estos valores se aplicarán si el Cliente o Vendedor no tienen su propia configuración.</p>
                                    </div>

                                    <div class="col-sm-12">
                                        <div class="form-check form-switch">
                                            <input wire:model="globalAllowCredit" class="form-check-input" type="checkbox" id="globalAllowCreditSwitch">
                                            <label class="form-check-label" for="globalAllowCreditSwitch">
                                                <strong>Permitir Venta a Crédito por defecto</strong>
                                            </label>
                                        </div>
                                        @error('globalAllowCredit') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-6 mt-3">
                                        <label class="form-label">Días de Crédito (Base)</label>
                                        <input wire:model="globalCreditDays" type="number" class="form-control" 
                                               placeholder="Ej: 15">
                                        @error('globalCreditDays') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-6 mt-3">
                                        <label class="form-label">Límite de Crédito Base ($)</label>
                                        <input wire:model="globalCreditLimit" type="number" step="0.01" class="form-control" 
                                               placeholder="Ej: 1000.00">
                                        @error('globalCreditLimit') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    {{-- Sección 2: Reglas de Descuento/Recargo --}}
                                    <div class="col-sm-12 mt-4">
                                        <h6 class="text-info mb-3">
                                            <i class="fa fa-percentage"></i> Reglas de Descuento/Recargo (Globales)
                                        </h6>
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
                                                        <th>Desde</th>
                                                        <th>Hasta</th>
                                                        <th>% Desc</th>
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
                                                                   placeholder="Ej: Pronto pago base">
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
                                        <div class="row">
                                            <div class="col-sm-8 text-center">
                                                <label class="form-label">% Descuento por Pago en USD (Zelle/Efectivo)</label>
                                                <input wire:model="globalUsdPaymentDiscount" type="number" step="0.01" 
                                                       class="form-control" placeholder="Ej: 5.00">
                                                <small class="text-muted">Valor por defecto si no especifica el cliente/vendedor</small>
                                                @error('globalUsdPaymentDiscount') <br><span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-sm-4 text-center">
                                                <label class="form-label">Código (Tag)</label>
                                                <input wire:model="globalUsdPaymentDiscountTag" type="text" 
                                                       class="form-control text-center" placeholder="Ej: PD">
                                                <small class="text-muted">Ej: PD</small>
                                                @error('globalUsdPaymentDiscountTag') <br><span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <button class="btn btn-primary" wire:click.prevent="saveConfig" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="saveConfig">Guardar Configuración</span>
                                            <span wire:loading wire:target="saveConfig">Guardando...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endmodule
                        
                        {{-- TAB 10: ACTUALIZACIÓN MASIVA DE PRECIOS --}}
                        <div class="tab-pane fade {{ $tab == 10 ? 'active show' : '' }}" id="bulk-price-settings" role="tabpanel"
                            aria-labelledby="bulk-price-settings-tab">
                            <div class="sidebar-body">
                                <livewire:settings.bulk-price-update />
                            </div>
                        </div>

                        {{-- TAB 11: CONFIGURACIÓN DE CATÁLOGO --}}
                        <div class="tab-pane fade {{ $tab == 11 ? 'active show' : '' }}" id="catalogue-settings" role="tabpanel"
                            aria-labelledby="catalogue-settings-tab">
                            <div class="sidebar-body">
                                <form class="row g-3">
                                    <div class="col-12">
                                        <div class="alert alert-light-primary" role="alert">
                                            <i class="fas fa-info-circle"></i> Configura la visibilidad de los precios en el catálogo de productos PDF.
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label text-uppercase">Precio de Venta</label>
                                        <div class="form-check form-switch pl-0">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="catalogueShowPrices" wire:model="catalogueShowPrices">
                                                <label class="custom-control-label" for="catalogueShowPrices">Mostrar precio público en el PDF</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label text-uppercase">Precio Base (Referencia)</label>
                                        <div class="form-check form-switch pl-0">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="catalogueShowBasePrices" wire:model="catalogueShowBasePrices">
                                                <label class="custom-control-label" for="catalogueShowBasePrices">Mostrar precio base/costo de referencia</label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <button class="btn btn-primary" wire:click.prevent="saveConfig" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="saveConfig">Guardar Configuración</span>
                                            <span wire:loading wire:target="saveConfig">Guardando...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- TAB 12: CONFIGURACIÓN DE TESORERÍA --}}
                        @module('module_treasury')
                        <div class="tab-pane fade {{ $tab == 12 ? 'active show' : '' }}" id="treasury-settings" role="tabpanel"
                            aria-labelledby="treasury-settings-tab">
                            <div class="sidebar-body">
                                <form class="row g-3">
                                    <div class="col-12">
                                        <div class="alert alert-light-primary" role="alert">
                                            <i class="fas fa-university"></i> Configura los parámetros globales de la Tesorería y Auditoría Bancaria.
                                        </div>
                                    </div>
                                    
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label text-uppercase font-weight-bold">Hora de Corte Diario Automático</label>
                                        <input wire:model="treasuryCutoffHour" type="text" class="form-control" placeholder="17:00">
                                        <small class="text-muted">Hora en que el sistema generará los cortes de caja del día de forma automática (Formato HH:MM, ej: 17:00 para las 5:00 PM).</small>
                                        @error('treasuryCutoffHour') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label text-uppercase font-weight-bold">Arqueo Diario Automático</label>
                                        <div class="form-check form-switch pl-0 mt-2">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="treasuryAutoClose" wire:model="treasuryAutoClose">
                                                <label class="custom-control-label" for="treasuryAutoClose">Habilitar cierre de bancos automatizado en scheduler</label>
                                            </div>
                                        </div>
                                        <small class="text-muted">Si se desactiva, los cierres diarios de bancos deberán realizarse manualmente en la sección de Tesorería.</small>
                                        @error('treasuryAutoClose') <span class="text-danger small">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-12 mt-4">
                                        <button class="btn btn-primary" wire:click.prevent="saveConfig" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="saveConfig">Guardar Configuración</span>
                                            <span wire:loading wire:target="saveConfig">Guardando...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                        @endmodule

                        {{-- TAB 13: ANULACIÓN DE LICENCIA (LOCAL OVERRIDES) --}}
                        @role('Super Admin')
                        <div class="tab-pane fade {{ $tab == 13 ? 'active show' : '' }}" id="local-overrides" role="tabpanel">
                            <div class="sidebar-body">
                                <h5>Panel de Anulación (Local Overrides)</h5>
                                <p class="text-muted">Como Super Admin, puedes forzar la activación o desactivación de cualquier módulo ignorando lo estipulado en la licencia generada.</p>
                                
                                <div class="alert alert-warning">
                                    <i class="fa fa-exclamation-triangle"></i> Los cheques aquí encendidos habilitarán el módulo sin importar la licencia. Los apagados dejarán que la licencia decida (o puedes forzarlos a apagado cambiando la lógica en código, por ahora on = forzado activado, off = no forzado).
                                    <br><small><i>Nota: Actualmente, si el check está encendido, el módulo se considera habilitado para todo el sistema.</i></small>
                                </div>

                                <div class="row mt-4">
                                    @php
                                        // Re-use the same list of available modules from LicenseGenerator or hardcode the keys
                                        $overrideModules = [
                                            'module_credits' => 'Créditos y Cuentas por Cobrar',
                                            'module_purchases' => 'Compras a Proveedores',
                                            'module_multi_warehouse' => 'Múltiples Depósitos y Traspasos',
                                            'module_advanced_payments' => 'Pagos en Divisas y Zelle',
                                            'module_advanced_products' => 'Productos Variables y Tallas',
                                            'module_labels' => 'Etiquetas de Código de Barras',
                                            'module_roles' => 'Control Granular de Roles',
                                            'module_whatsapp' => 'Integración WhatsApp API',
                                            'module_commissions' => 'Comisiones a Vendedores',
                                            'module_production' => 'Manufactura y Producción',
                                            'module_soplados' => 'Producción de Soplados',
                                            'module_bolsas' => 'Fábrica de Bolsas',
                                            'module_delivery' => 'Despacho y Mapa de Rutas',
                                            'module_updates' => 'Actualizaciones del Sistema',
                                            'module_backups' => 'Copias de Seguridad (Backups)',
                                            'module_strategic_analysis' => 'Análisis Estratégico',
                                            'module_weekly_income' => 'Reporte Semanal de Ingresos',
                                            'module_monthly_income' => 'Reporte Mensual de Ingresos',
                                            'module_customer_report' => 'Reporte de Clientes',
                                            'module_customer_activity' => 'Actividad de Clientes',
                                            'module_sales_analysis' => 'Análisis de Ventas',
                                            'module_seller_performance' => 'Desempeño de Vendedores',
                                            'module_operator_efficiency' => 'Eficiencia de Operadores',
                                            'module_differential_audit' => 'Auditoría de Diferencial',
                                            'module_cash_flow' => 'Flujo y Cobranza',
                                            'module_collection_audit' => 'Auditoría de Cobranza',
                                            'module_invoice_audit' => 'Auditoría de Facturas',
                                            'module_credit_auth_history' => 'Historial Auth Créditos'
                                        ];

                                        $pureLicenseModules = config('tenant.modules');
                                        if ($pureLicenseModules === null) {
                                            $pureLicenseModules = app(\App\Services\LicenseService::class)->checkLicense()['modules'] ?? [];
                                        }
                                    @endphp

                                    @foreach($overrideModules as $key => $name)
                                    @php
                                        $isLicensed = in_array($key, $pureLicenseModules);
                                    @endphp
                                    <div class="col-md-6 mb-3">
                                        <div class="form-group">
                                            <label class="font-weight-bold" for="override_{{ $key }}">
                                                @if($isLicensed)
                                                    <i class="fas fa-check-circle text-success me-1" title="Incluido en la Licencia"></i>
                                                @else
                                                    <i class="fas fa-lock text-secondary me-1" title="No Incluido en la Licencia"></i>
                                                @endif
                                                {{ $name }}
                                            </label>
                                            <select wire:model.defer="localOverrides.{{ $key }}" class="form-control" id="override_{{ $key }}">
                                                <option value="">Predeterminado (Según Licencia)</option>
                                                <option value="1">Forzar Activado</option>
                                                <option value="0">Forzar Desactivado</option>
                                            </select>
                                        </div>
                                    </div>
                                    @endforeach
                                </div>

                                <div class="row mt-4">
                                    <div class="col-sm-12 text-center">
                                        <button wire:click.prevent="saveOverrides" class="btn btn-warning btn-lg px-5">
                                            <i class="fas fa-save"></i> Guardar Anulaciones (Overrides)
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endrole

                        {{-- TAB 14: MI LICENCIA (VISTA DEL CLIENTE) --}}
                        <div class="tab-pane fade {{ $tab == 14 ? 'active show' : '' }}" id="my-license" role="tabpanel">
                            <div class="sidebar-body">
                                @php
                                    $licenseStatus = app(\App\Services\LicenseService::class)->checkLicense();
                                    $configModel = \App\Models\Configuration::first();
                                    
                                    $allModules = [
                                        'module_credits' => 'Créditos y Cuentas por Cobrar',
                                        'module_purchases' => 'Compras a Proveedores',
                                        'module_multi_warehouse' => 'Múltiples Depósitos y Traspasos',
                                        'module_advanced_payments' => 'Pagos en Divisas y Zelle',
                                        'module_advanced_products' => 'Productos Variables y Tallas',
                                        'module_labels' => 'Etiquetas de Código de Barras',
                                        'module_roles' => 'Control Granular de Roles',
                                        'module_whatsapp' => 'Integración WhatsApp API',
                                        'module_commissions' => 'Comisiones a Vendedores',
                                        'module_production' => 'Manufactura y Producción',
                                        'module_soplados' => 'Producción de Soplados',
                                        'module_bolsas' => 'Fábrica de Bolsas',
                                        'module_delivery' => 'Despacho y Mapa de Rutas',
                                        'module_updates' => 'Actualizaciones del Sistema',
                                        'module_backups' => 'Copias de Seguridad (Backups)',
                                        'module_strategic_analysis' => 'Análisis Estratégico',
                                        'module_weekly_income' => 'Reporte Semanal de Ingresos',
                                        'module_monthly_income' => 'Reporte Mensual de Ingresos',
                                        'module_customer_report' => 'Reporte de Clientes',
                                        'module_customer_activity' => 'Actividad de Clientes',
                                        'module_sales_analysis' => 'Análisis de Ventas',
                                        'module_seller_performance' => 'Desempeño de Vendedores',
                                        'module_operator_efficiency' => 'Eficiencia de Operadores',
                                        'module_differential_audit' => 'Auditoría de Diferencial',
                                        'module_cash_flow' => 'Flujo y Cobranza',
                                        'module_collection_audit' => 'Auditoría de Cobranza',
                                        'module_invoice_audit' => 'Auditoría de Facturas',
                                        'module_credit_auth_history' => 'Historial Auth Créditos',
                                        'module_departments' => 'Departamentos de Productos',
                                        'module_services' => 'Servicios y Precios Variables',
                                        'module_pos_optimizations' => 'Optimizaciones del POS',
                                        'module_seller_grouped' => 'Reporte Agrupado por Vendedor'
                                    ];
                                @endphp

                                <h5>Resumen de Mi Licencia</h5>
                                <p class="text-muted">Aquí puedes ver el estado actual de tu suscripción y los módulos a los que tienes acceso.</p>
                                
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <div class="card bg-light border-0 shadow-sm">
                                            <div class="card-body text-center">
                                                <h6 class="text-uppercase text-muted fw-bold">Estado</h6>
                                                <h4 class="{{ $licenseStatus['status'] == 'active' ? 'text-success' : 'text-danger' }} fw-bold mb-0">
                                                    {{ $licenseStatus['status'] == 'active' ? 'Activa' : 'Vencida / Inválida' }}
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-light border-0 shadow-sm">
                                            <div class="card-body text-center">
                                                <h6 class="text-uppercase text-muted fw-bold">Días Restantes</h6>
                                                <h4 class="{{ $licenseStatus['days_remaining'] > 15 ? 'text-primary' : 'text-warning' }} fw-bold mb-0">
                                                    {{ $licenseStatus['days_remaining'] }} <small class="text-muted">días</small>
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="card bg-light border-0 shadow-sm">
                                            <div class="card-body text-center">
                                                <h6 class="text-uppercase text-muted fw-bold">Tipo de Plan</h6>
                                                <h4 class="text-info fw-bold mb-0">
                                                    {{ ucfirst($configModel->plan_type ?? 'Premium') }}
                                                </h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <h6 class="fw-bold mb-3 border-bottom pb-2">Módulos de tu Plan</h6>
                                <div class="row">
                                    @foreach($allModules as $key => $name)
                                        @php
                                            $hasModule = $configModel ? $configModel->hasAddon($key) : false;
                                        @endphp
                                        <div class="col-md-6 mb-2">
                                            <div class="d-flex align-items-center p-2 rounded {{ $hasModule ? 'bg-white shadow-sm border-left-success' : 'bg-light text-muted' }}" style="border-left: 4px solid {{ $hasModule ? '#28a745' : '#dee2e6' }};">
                                                @if($hasModule)
                                                    <i class="fas fa-check-circle text-success fs-5 me-3"></i>
                                                @else
                                                    <i class="fas fa-lock text-secondary fs-5 me-3"></i>
                                                @endif
                                                <span class="{{ $hasModule ? 'fw-bold' : '' }}">{{ $name }}</span>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        {{-- TAB 15: CONFIGURACIÓN DE TICKETS --}}
                        <div class="tab-pane fade {{ $tab == 15 ? 'active show' : '' }}" id="tickets-settings" role="tabpanel"
                            aria-labelledby="tickets-settings-tab">
                            <div class="sidebar-body">
                                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                                    <div>
                                        <h5 class="mb-1 text-primary fw-bold"><i class="fa fa-receipt me-2"></i>Configuración de Tickets Térmicos</h5>
                                        <p class="text-muted mb-0 small">Personaliza la apariencia, contenido e impresión automática para cada tipo de ticket térmico ESC/POS del sistema.</p>
                                    </div>
                                    <button wire:click.prevent="saveConfig" class="btn btn-primary shadow-sm">
                                        <i class="fa fa-save me-2"></i>Guardar Cambios
                                    </button>
                                </div>

                                <div class="row g-4">
                                    {{-- 1. Ticket de Ventas (POS) --}}
                                    <div class="col-12">
                                        <div class="card shadow-sm border-0 mb-0">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-shopping-cart text-primary"></i>
                                                    <span class="fw-bold text-dark">Ticket de Venta (POS)</span>
                                                </div>
                                                <span class="badge bg-primary text-white">Ventas y Facturación</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row g-3">
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketSalesAutoPrint" wire:model="ticketSettings.sales.auto_print">
                                                            <label class="custom-control-label fw-bold text-primary" for="ticketSalesAutoPrint">Auto-imprimir al cobrar venta</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Si se apaga, no imprime automáticamente pero permite reimprimir manual.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketSalesCompanyData" wire:model="ticketSettings.sales.show_company_data">
                                                            <label class="custom-control-label" for="ticketSalesCompanyData">Datos de la empresa (Cabecera)</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Nombre, RIF/NIT, dirección y teléfono.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketSalesSubtotal" wire:model="ticketSettings.sales.show_subtotal">
                                                            <label class="custom-control-label" for="ticketSalesSubtotal">Línea de Subtotal</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra el desglose del subtotal antes de impuesto.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketSalesTax" wire:model="ticketSettings.sales.show_tax">
                                                            <label class="custom-control-label" for="ticketSalesTax">Línea de IVA / Impuesto</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra el desglose del monto de IVA.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketSalesCashChange" wire:model="ticketSettings.sales.show_cash_change">
                                                            <label class="custom-control-label" for="ticketSalesCashChange">Efectivo y Cambio (Vuelto)</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra el monto entregado en efectivo y el cambio devuelto.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketSalesFooterMessage" wire:model="ticketSettings.sales.show_footer_message">
                                                            <label class="custom-control-label" for="ticketSalesFooterMessage">Mensaje de Leyenda / Gracias</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra el mensaje de agradecimiento configurado.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketSalesWebsite" wire:model="ticketSettings.sales.show_website">
                                                            <label class="custom-control-label" for="ticketSalesWebsite">Sitio Web en pie de ticket</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra la URL del sitio web de la empresa.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketSalesQr" wire:model="ticketSettings.sales.show_qr">
                                                            <label class="custom-control-label" for="ticketSalesQr">Código QR (Scan para Clonar)</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra el código QR para clonar la venta con la app móvil.</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 2. Ticket de Pedidos / Cotizaciones --}}
                                    <div class="col-12">
                                        <div class="card shadow-sm border-0 mb-0">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-clipboard-list text-info"></i>
                                                    <span class="fw-bold text-dark">Ticket de Pedidos / Cotizaciones</span>
                                                </div>
                                                <span class="badge bg-info text-white">Preventa y Cotización</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row g-3">
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketOrdersCompanyData" wire:model="ticketSettings.orders.show_company_data">
                                                            <label class="custom-control-label" for="ticketOrdersCompanyData">Datos de la empresa (Cabecera)</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Nombre, RIF/NIT, dirección y teléfono.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketOrdersSubtotal" wire:model="ticketSettings.orders.show_subtotal">
                                                            <label class="custom-control-label" for="ticketOrdersSubtotal">Línea de Subtotal</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra el desglose del subtotal antes de impuesto.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketOrdersTax" wire:model="ticketSettings.orders.show_tax">
                                                            <label class="custom-control-label" for="ticketOrdersTax">Línea de IVA / Impuesto</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra el desglose del monto de IVA.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketOrdersCashChange" wire:model="ticketSettings.orders.show_cash_change">
                                                            <label class="custom-control-label" for="ticketOrdersCashChange">Efectivo y Cambio</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra forma de pago y cambio si aplica.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketOrdersFooterMessage" wire:model="ticketSettings.orders.show_footer_message">
                                                            <label class="custom-control-label" for="ticketOrdersFooterMessage">Mensaje de Leyenda / Gracias</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra la leyenda o mensaje en el pie.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketOrdersWebsite" wire:model="ticketSettings.orders.show_website">
                                                            <label class="custom-control-label" for="ticketOrdersWebsite">Sitio Web en pie de ticket</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra la URL del sitio web de la empresa.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketOrdersQr" wire:model="ticketSettings.orders.show_qr">
                                                            <label class="custom-control-label" for="ticketOrdersQr">Código QR (Scan para Clonar)</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra el código QR para procesar el pedido.</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 3. Ticket de Abonos / Pagos de Clientes --}}
                                    <div class="col-md-6">
                                        <div class="card shadow-sm border-0 mb-0 h-100">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-hand-holding-usd text-success"></i>
                                                    <span class="fw-bold text-dark">Ticket de Abonos (Clientes)</span>
                                                </div>
                                                <span class="badge bg-success text-white">Cobranzas</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="d-flex flex-column gap-3">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="ticketPaymentsCompanyData" wire:model="ticketSettings.payments.show_company_data">
                                                        <label class="custom-control-label" for="ticketPaymentsCompanyData">Datos de la empresa (Cabecera)</label>
                                                    </div>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="ticketPaymentsDebt" wire:model="ticketSettings.payments.show_debt">
                                                        <label class="custom-control-label" for="ticketPaymentsDebt">Deuda actual / Crédito liquidado</label>
                                                    </div>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="ticketPaymentsFooterMessage" wire:model="ticketSettings.payments.show_footer_message">
                                                        <label class="custom-control-label" for="ticketPaymentsFooterMessage">Mensaje de Leyenda / Gracias</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 4. Ticket de Pagos a Proveedores (Egresos) --}}
                                    <div class="col-md-6">
                                        <div class="card shadow-sm border-0 mb-0 h-100">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-file-invoice-dollar text-danger"></i>
                                                    <span class="fw-bold text-dark">Ticket de Pagos a Proveedores</span>
                                                </div>
                                                <span class="badge bg-danger text-white">Egresos</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="d-flex flex-column gap-3">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="ticketPayablesCompanyData" wire:model="ticketSettings.payables.show_company_data">
                                                        <label class="custom-control-label" for="ticketPayablesCompanyData">Datos de la empresa (Cabecera)</label>
                                                    </div>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="ticketPayablesDebt" wire:model="ticketSettings.payables.show_debt">
                                                        <label class="custom-control-label" for="ticketPayablesDebt">Deuda actual con el proveedor</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 5. Ticket de Corte / Arqueo de Caja --}}
                                    <div class="col-md-6">
                                        <div class="card shadow-sm border-0 mb-0 h-100">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-cash-register text-warning"></i>
                                                    <span class="fw-bold text-dark">Ticket de Corte de Caja</span>
                                                </div>
                                                <span class="badge bg-warning text-dark">Arqueos</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="d-flex flex-column gap-3">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="ticketCashCountCompanyData" wire:model="ticketSettings.cash_count.show_company_data">
                                                        <label class="custom-control-label" for="ticketCashCountCompanyData">Datos de la empresa (Cabecera)</label>
                                                    </div>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="ticketCashCountSalesBreakdown" wire:model="ticketSettings.cash_count.show_sales_breakdown">
                                                        <label class="custom-control-label" for="ticketCashCountSalesBreakdown">Desglose de Ventas del Día</label>
                                                    </div>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="ticketCashCountPaymentsBreakdown" wire:model="ticketSettings.cash_count.show_payments_breakdown">
                                                        <label class="custom-control-label" for="ticketCashCountPaymentsBreakdown">Desglose de Cobranzas / Pagos Recibidos</label>
                                                    </div>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="ticketCashCountWallet" wire:model="ticketSettings.cash_count.show_wallet">
                                                        <label class="custom-control-label" for="ticketCashCountWallet">Movimientos Billetera / Custodia</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 6. Ticket de Historial de Pagos de Venta --}}
                                    <div class="col-md-6">
                                        <div class="card shadow-sm border-0 mb-0 h-100">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-history text-secondary"></i>
                                                    <span class="fw-bold text-dark">Ticket de Historial de Pagos</span>
                                                </div>
                                                <span class="badge bg-secondary text-white">Historial</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="d-flex flex-column gap-3">
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="ticketPaymentHistoryCompanyData" wire:model="ticketSettings.payment_history.show_company_data">
                                                        <label class="custom-control-label" for="ticketPaymentHistoryCompanyData">Datos de la empresa (Cabecera)</label>
                                                    </div>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="ticketPaymentHistoryReturns" wire:model="ticketSettings.payment_history.show_returns">
                                                        <label class="custom-control-label" for="ticketPaymentHistoryReturns">Notas de Crédito / Devoluciones</label>
                                                    </div>
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" class="custom-control-input" id="ticketPaymentHistoryDueAlert" wire:model="ticketSettings.payment_history.show_due_alert">
                                                        <label class="custom-control-label" for="ticketPaymentHistoryDueAlert">Alerta de Cuenta Vencida y Días de Atraso</label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 7. Ticket Interno / Comanda Contable --}}
                                    <div class="col-12">
                                        <div class="card shadow-sm border-0 mb-0">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-calculator text-dark"></i>
                                                    <span class="fw-bold text-dark">Ticket Interno / Comprobante Contable</span>
                                                </div>
                                                <span class="badge bg-dark text-white">Uso Administrativo</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row g-3">
                                                    <div class="col-md-6">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketInternalHeader" wire:model="ticketSettings.internal.show_header">
                                                            <label class="custom-control-label" for="ticketInternalHeader">Encabezado de Comprobante Contable Interno</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra "*** COMPROBANTE CONTABLE INTERNO *** (NO ENTREGAR AL CLIENTE)".</small>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="ticketInternalCharges" wire:model="ticketSettings.internal.show_charges_breakdown">
                                                            <label class="custom-control-label" for="ticketInternalCharges">Desglose de Cargos Adicionales</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra desglose de Comisiones, Recargos, Fletes y Diferencia Cambiaria.</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                                    <button wire:click.prevent="saveConfig" class="btn btn-primary px-4 py-2 shadow-sm">
                                        <i class="fa fa-save me-2"></i>Guardar Configuración de Tickets
                                    </button>
                                </div>
                            </div>
                        </div>

                        {{-- TAB 16: CONFIGURACIÓN DE FACTURAS Y REPORTES PDF --}}
                        <div class="tab-pane fade {{ $tab == 16 ? 'active show' : '' }}" id="pdf-settings" role="tabpanel"
                            aria-labelledby="pdf-settings-tab">
                            <div class="sidebar-body">
                                <div class="d-flex align-items-center justify-content-between mb-4 border-bottom pb-3">
                                    <div>
                                        <h5 class="mb-1 text-danger fw-bold"><i class="fa fa-file-pdf me-2"></i>Configuración de Facturas y Documentos PDF</h5>
                                        <p class="text-muted mb-0 small">Personaliza la apariencia, membretes, totales y secciones visibles en todas las facturas y reportes PDF del sistema.</p>
                                    </div>
                                    <button wire:click.prevent="saveConfig" class="btn btn-primary shadow-sm">
                                        <i class="fa fa-save me-2"></i>Guardar Cambios
                                    </button>
                                </div>

                                <div class="row g-4">
                                    {{-- 1. Factura de Venta Contado (Pagada) --}}
                                    <div class="col-12">
                                        <div class="card shadow-sm border-0 mb-0">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-file-text text-success"></i>
                                                    <span class="fw-bold text-dark">Factura de Venta Contado (Pagada)</span>
                                                </div>
                                                <span class="badge bg-success text-white">Ventas Pagadas</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row g-3">
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesPaidLogo" wire:model="pdfSettings.sales_paid.show_logo">
                                                            <label class="custom-control-label" for="pdfSalesPaidLogo">Mostrar Logotipo</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra el logo de la empresa en la cabecera superior.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesPaidCompanyData" wire:model="pdfSettings.sales_paid.show_company_data">
                                                            <label class="custom-control-label" for="pdfSalesPaidCompanyData">Datos de la Empresa</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Nombre de la empresa en el encabezado.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesPaidSellerData" wire:model="pdfSettings.sales_paid.show_seller_data">
                                                            <label class="custom-control-label" for="pdfSalesPaidSellerData">Vendedor y Operador</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Muestra el nombre del vendedor y operador asignado.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesPaidSellerBanks" wire:model="pdfSettings.sales_paid.show_seller_banks">
                                                            <label class="custom-control-label" for="pdfSalesPaidSellerBanks">Cuentas Bancarias</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Cuentas bancarias asociadas al vendedor / empresa.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesPaidSubtotal" wire:model="pdfSettings.sales_paid.show_subtotal">
                                                            <label class="custom-control-label" for="pdfSalesPaidSubtotal">Línea de Subtotal / Base</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Línea de subtotal / base imponible antes de impuesto.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesPaidTax" wire:model="pdfSettings.sales_paid.show_tax">
                                                            <label class="custom-control-label" for="pdfSalesPaidTax">Línea de IVA / Impuesto</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Línea del porcentaje y monto total de IVA.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesPaidSignatureBox" wire:model="pdfSettings.sales_paid.show_signature_box">
                                                            <label class="custom-control-label" for="pdfSalesPaidSignatureBox">Recuadro de Firma y Sello</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Línea "FIRMA, SELLO Y FECHA DE RECIBO".</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesPaidAmountInWords" wire:model="pdfSettings.sales_paid.show_amount_in_words">
                                                            <label class="custom-control-label" for="pdfSalesPaidAmountInWords">Monto en Letras</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Monto total expresado en palabras.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesPaidNotes" wire:model="pdfSettings.sales_paid.show_notes">
                                                            <label class="custom-control-label" for="pdfSalesPaidNotes">Notas y Observaciones</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Notas, políticas o términos agregados a la venta.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesPaidQr" wire:model="pdfSettings.sales_paid.show_qr">
                                                            <label class="custom-control-label" for="pdfSalesPaidQr">Código QR (Scan para Clonar)</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Código QR para clonación rápida de factura.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesPaidFooterCode" wire:model="pdfSettings.sales_paid.show_footer_code">
                                                            <label class="custom-control-label" for="pdfSalesPaidFooterCode">Código de Control / Auditoría</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Código alfanumérico en el pie de página (ej. OFJPF0...).</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 2. Factura de Venta a Crédito (Pendiente) --}}
                                    <div class="col-12">
                                        <div class="card shadow-sm border-0 mb-0">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-credit-card text-warning"></i>
                                                    <span class="fw-bold text-dark">Factura de Venta a Crédito (Pendiente)</span>
                                                </div>
                                                <span class="badge bg-warning text-dark">Créditos y Cuentas por Cobrar</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row g-3">
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesCreditLogo" wire:model="pdfSettings.sales_credit.show_logo">
                                                            <label class="custom-control-label" for="pdfSalesCreditLogo">Mostrar Logotipo</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Logo de la empresa en la cabecera.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesCreditCompanyData" wire:model="pdfSettings.sales_credit.show_company_data">
                                                            <label class="custom-control-label" for="pdfSalesCreditCompanyData">Datos de la Empresa</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Nombre de la empresa en cabecera.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesCreditSellerData" wire:model="pdfSettings.sales_credit.show_seller_data">
                                                            <label class="custom-control-label" for="pdfSalesCreditSellerData">Vendedor y Operador</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Vendedor y operador asignado.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesCreditSellerBanks" wire:model="pdfSettings.sales_credit.show_seller_banks">
                                                            <label class="custom-control-label" for="pdfSalesCreditSellerBanks">Cuentas Bancarias</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Cuentas bancarias asociadas.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesCreditSubtotal" wire:model="pdfSettings.sales_credit.show_subtotal">
                                                            <label class="custom-control-label" for="pdfSalesCreditSubtotal">Línea de Subtotal / Base</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Subtotal / base imponible.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesCreditTax" wire:model="pdfSettings.sales_credit.show_tax">
                                                            <label class="custom-control-label" for="pdfSalesCreditTax">Línea de IVA / Impuesto</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Porcentaje y monto de IVA.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesCreditSignatureBox" wire:model="pdfSettings.sales_credit.show_signature_box">
                                                            <label class="custom-control-label" for="pdfSalesCreditSignatureBox">Recuadro de Firma y Sello</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Firma, sello y fecha de recibo.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesCreditAmountInWords" wire:model="pdfSettings.sales_credit.show_amount_in_words">
                                                            <label class="custom-control-label" for="pdfSalesCreditAmountInWords">Monto en Letras</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Monto total en palabras.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesCreditNotes" wire:model="pdfSettings.sales_credit.show_notes">
                                                            <label class="custom-control-label" for="pdfSalesCreditNotes">Notas y Observaciones</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Notas y condiciones del crédito.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesCreditQr" wire:model="pdfSettings.sales_credit.show_qr">
                                                            <label class="custom-control-label" for="pdfSalesCreditQr">Código QR (Scan para Clonar)</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Código QR de clonación.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfSalesCreditFooterCode" wire:model="pdfSettings.sales_credit.show_footer_code">
                                                            <label class="custom-control-label" for="pdfSalesCreditFooterCode">Código de Control / Auditoría</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Código alfanumérico en el pie de página (ej. OFJPF0...).</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 3. Pedidos y Cotizaciones --}}
                                    <div class="col-12">
                                        <div class="card shadow-sm border-0 mb-0">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-list-alt text-info"></i>
                                                    <span class="fw-bold text-dark">Pedidos y Cotizaciones</span>
                                                </div>
                                                <span class="badge bg-info text-white">Preventa y Presupuestos</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row g-3">
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfOrdersLogo" wire:model="pdfSettings.orders.show_logo">
                                                            <label class="custom-control-label" for="pdfOrdersLogo">Mostrar Logotipo</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Logo en cabecera del presupuesto.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfOrdersCompanyData" wire:model="pdfSettings.orders.show_company_data">
                                                            <label class="custom-control-label" for="pdfOrdersCompanyData">Datos de la Empresa</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Nombre de la empresa.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfOrdersSellerData" wire:model="pdfSettings.orders.show_seller_data">
                                                            <label class="custom-control-label" for="pdfOrdersSellerData">Vendedor y Operador</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Vendedor y operador.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfOrdersSellerBanks" wire:model="pdfSettings.orders.show_seller_banks">
                                                            <label class="custom-control-label" for="pdfOrdersSellerBanks">Cuentas Bancarias</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Cuentas para transferencias.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfOrdersSubtotal" wire:model="pdfSettings.orders.show_subtotal">
                                                            <label class="custom-control-label" for="pdfOrdersSubtotal">Línea de Subtotal / Base</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Subtotal de la cotización.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfOrdersTax" wire:model="pdfSettings.orders.show_tax">
                                                            <label class="custom-control-label" for="pdfOrdersTax">Línea de IVA / Impuesto</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">IVA calculado.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfOrdersSignatureBox" wire:model="pdfSettings.orders.show_signature_box">
                                                            <label class="custom-control-label" for="pdfOrdersSignatureBox">Recuadro de Firma y Sello</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Firma y sello de aceptación.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfOrdersAmountInWords" wire:model="pdfSettings.orders.show_amount_in_words">
                                                            <label class="custom-control-label" for="pdfOrdersAmountInWords">Monto en Letras</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Total en letras.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfOrdersNotes" wire:model="pdfSettings.orders.show_notes">
                                                            <label class="custom-control-label" for="pdfOrdersNotes">Notas y Términos</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Validez del presupuesto y condiciones comerciales.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfOrdersQr" wire:model="pdfSettings.orders.show_qr">
                                                            <label class="custom-control-label" for="pdfOrdersQr">Código QR (Scan para Clonar)</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">QR de clonación rápida.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfOrdersFooterCode" wire:model="pdfSettings.orders.show_footer_code">
                                                            <label class="custom-control-label" for="pdfOrdersFooterCode">Código de Control / Auditoría</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Código alfanumérico en el pie de página (ej. OFJPF0...).</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 4. Órdenes de Compra a Proveedores --}}
                                    <div class="col-12">
                                        <div class="card shadow-sm border-0 mb-0">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-truck text-dark"></i>
                                                    <span class="fw-bold text-dark">Órdenes de Compra a Proveedores</span>
                                                </div>
                                                <span class="badge bg-dark text-white">Compras y Abastecimiento</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row g-3">
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfPurchaseOrdersLogo" wire:model="pdfSettings.purchase_orders.show_logo">
                                                            <label class="custom-control-label" for="pdfPurchaseOrdersLogo">Mostrar Logotipo</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Logo en cabecera de la orden.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfPurchaseOrdersCompanyData" wire:model="pdfSettings.purchase_orders.show_company_data">
                                                            <label class="custom-control-label" for="pdfPurchaseOrdersCompanyData">Datos de la Empresa</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Nombre de la empresa.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfPurchaseOrdersSubtotal" wire:model="pdfSettings.purchase_orders.show_subtotal">
                                                            <label class="custom-control-label" for="pdfPurchaseOrdersSubtotal">Línea de Subtotal / Base</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Subtotal de compra.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfPurchaseOrdersTax" wire:model="pdfSettings.purchase_orders.show_tax">
                                                            <label class="custom-control-label" for="pdfPurchaseOrdersTax">Línea de IVA / Impuesto</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">IVA estimado.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfPurchaseOrdersSignatureBox" wire:model="pdfSettings.purchase_orders.show_signature_box">
                                                            <label class="custom-control-label" for="pdfPurchaseOrdersSignatureBox">Recuadro de Firma y Sello</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Firma de autorización de compra.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfPurchaseOrdersAmountInWords" wire:model="pdfSettings.purchase_orders.show_amount_in_words">
                                                            <label class="custom-control-label" for="pdfPurchaseOrdersAmountInWords">Monto en Letras</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Total en letras.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfPurchaseOrdersNotes" wire:model="pdfSettings.purchase_orders.show_notes">
                                                            <label class="custom-control-label" for="pdfPurchaseOrdersNotes">Notas y Términos</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Instrucciones de entrega y pago.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfPurchaseOrdersFooterCode" wire:model="pdfSettings.purchase_orders.show_footer_code">
                                                            <label class="custom-control-label" for="pdfPurchaseOrdersFooterCode">Código de Control / Auditoría</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Código alfanumérico en el pie de página (ej. OFJPF0...).</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 5. Notas de Débito y Comprobantes de Cargo --}}
                                    <div class="col-12">
                                        <div class="card shadow-sm border-0 mb-0">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-money text-secondary"></i>
                                                    <span class="fw-bold text-dark">Notas de Débito y Comprobantes de Cargo</span>
                                                </div>
                                                <span class="badge bg-secondary text-white">Ajustes y Notas</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row g-3">
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfDebitNotesLogo" wire:model="pdfSettings.debit_notes.show_logo">
                                                            <label class="custom-control-label" for="pdfDebitNotesLogo">Mostrar Logotipo</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Logo en cabecera.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfDebitNotesCompanyData" wire:model="pdfSettings.debit_notes.show_company_data">
                                                            <label class="custom-control-label" for="pdfDebitNotesCompanyData">Datos de la Empresa</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Nombre de la empresa.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfDebitNotesSubtotal" wire:model="pdfSettings.debit_notes.show_subtotal">
                                                            <label class="custom-control-label" for="pdfDebitNotesSubtotal">Línea de Subtotal / Base</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Subtotal de la nota de débito.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfDebitNotesTax" wire:model="pdfSettings.debit_notes.show_tax">
                                                            <label class="custom-control-label" for="pdfDebitNotesTax">Línea de IVA / Impuesto</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">IVA calculado.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfDebitNotesSignatureBox" wire:model="pdfSettings.debit_notes.show_signature_box">
                                                            <label class="custom-control-label" for="pdfDebitNotesSignatureBox">Recuadro de Firma y Sello</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Firma y sello.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfDebitNotesAmountInWords" wire:model="pdfSettings.debit_notes.show_amount_in_words">
                                                            <label class="custom-control-label" for="pdfDebitNotesAmountInWords">Monto en Letras</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Total en letras.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfDebitNotesNotes" wire:model="pdfSettings.debit_notes.show_notes">
                                                            <label class="custom-control-label" for="pdfDebitNotesNotes">Notas y Observaciones</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Notas de motivo de cargo/débito.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfDebitNotesFooterCode" wire:model="pdfSettings.debit_notes.show_footer_code">
                                                            <label class="custom-control-label" for="pdfDebitNotesFooterCode">Código de Control / Auditoría</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Código alfanumérico en el pie de página (ej. OFJPF0...).</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {{-- 6. Reportes PDF Generales --}}
                                    <div class="col-12">
                                        <div class="card shadow-sm border-0 mb-0">
                                            <div class="card-header bg-light py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center gap-2">
                                                    <i class="fa fa-bar-chart text-primary"></i>
                                                    <span class="fw-bold text-dark">Reportes PDF Generales</span>
                                                </div>
                                                <span class="badge bg-primary text-white">Arqueos, Estados de Cuenta, etc.</span>
                                            </div>
                                            <div class="card-body p-3">
                                                <div class="row g-3">
                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfReportsLogo" wire:model="pdfSettings.reports.show_logo">
                                                            <label class="custom-control-label" for="pdfReportsLogo">Mostrar Logotipo</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Logo en cabecera de los reportes PDF.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfReportsCompanyData" wire:model="pdfSettings.reports.show_company_data">
                                                            <label class="custom-control-label" for="pdfReportsCompanyData">Datos de Empresa en Encabezado</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Nombre, RIF/NIT y dirección en el reporte.</small>
                                                    </div>

                                                    <div class="col-md-6 col-lg-4">
                                                        <div class="custom-control custom-switch">
                                                            <input type="checkbox" class="custom-control-input" id="pdfReportsFooterTimestamp" wire:model="pdfSettings.reports.show_footer_timestamp">
                                                            <label class="custom-control-label" for="pdfReportsFooterTimestamp">Fecha y Hora de Generación en Pie</label>
                                                        </div>
                                                        <small class="text-muted d-block ps-4">Fecha y hora exacta de emisión en el pie de página.</small>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mt-4 pt-3 border-top d-flex justify-content-end">
                                    <button wire:click.prevent="saveConfig" class="btn btn-primary px-4 py-2 shadow-sm">
                                        <i class="fa fa-save me-2"></i>Guardar Configuración de Facturas PDF
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- History Modal --}}
    <div wire:ignore.self class="modal fade" id="modalRateHistory" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title"><i class="fa fa-history me-2"></i>Historial de Tasas de Cambio</h5>
                    <button class="btn-close btn-close-white" type="button" data-dismiss="modal" aria-label="Close" onclick="$('#modalRateHistory').modal('hide')"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-striped table-hover align-middle text-center">
                            <thead class="table-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tasa BCV</th>
                                    <th>Binance Mañana</th>
                                    <th>Binance Tarde</th>
                                    <th>Tasa con Ajuste</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($historyRates as $h)
                                    <tr>
                                        <td><span class="badge bg-light text-dark fw-bold">{{ $h['date'] }}</span></td>
                                        <td class="fw-bold text-info">
                                            {{ $h['bcv'] ? number_format($h['bcv'], 4) . ' Bs.' : '—' }}
                                        </td>
                                        <td class="text-secondary">
                                            {{ $h['binance_real_am'] ? number_format($h['binance_real_am'], 4) . ' Bs.' : '—' }}
                                        </td>
                                        <td class="text-secondary">
                                            {{ $h['binance_real_pm'] ? number_format($h['binance_real_pm'], 4) . ' Bs.' : '—' }}
                                        </td>
                                        <td class="fw-bold text-success">
                                            @if($h['binance_inflated_pm'])
                                                {{ number_format($h['binance_inflated_pm'], 4) . ' Bs.' }}
                                            @elseif($h['binance_inflated_am'])
                                                {{ number_format($h['binance_inflated_am'], 4) . ' Bs.' }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td class="text-muted"><i class="fa fa-user-circle me-1"></i>{{ $h['user'] }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-3">No hay historial registrado.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#modalRateHistory').modal('hide')">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('show-history-modal', event => {
            $('#modalRateHistory').modal('show');
        });
    </script>
</div>
