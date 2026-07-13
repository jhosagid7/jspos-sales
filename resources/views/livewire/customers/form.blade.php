<div class="card">
    <div class="card-header">
        <div>
            @if ($editing && $customer->id)
                <h5>Editar Cliente | <small class="text-info">{{ $customer->name }}</small></h5>
            @else
                <h5>Crear Nuevo Cliente</h5>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="row g-xl-5 g-3">
            {{-- Left Sidebar --}}
            <div class="col-xxl-3 col-xl-4 box-col-4e sidebar-left-wrapper">
                <ul class="nav flex-column nav-pills me-3" id="customer-pills-tab" role="tablist">
                    {{-- Tab 1: Información General --}}
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 1 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',1)" href="#">
                            <i class="fa fa-id-card fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Información General</h6>
                                <small class="{{ $tab == 1 ? 'text-white' : 'text-muted' }}">Datos básicos</small>
                            </div>
                        </a>
                    </li>
                    {{-- Tab 2: Configuración Comercial --}}
                    @module('module_commissions')
                    @can('customers.edit_commercial_config')
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 2 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',2)" href="#">
                            <i class="fa fa-briefcase fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Configuración Comercial</h6>
                                <small class="{{ $tab == 2 ? 'text-white' : 'text-muted' }}">Vendedor y comisiones</small>
                            </div>
                        </a>
                    </li>
                    @endcan
                    @endmodule
                    {{-- Tab 3: Historial de Ventas (Solo en edición) --}}
                    @if($editing && $customer->id > 0)
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 3 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',3)" href="#">
                            <i class="fa fa-chart-line fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Historial de Ventas</h6>
                                <small class="{{ $tab == 3 ? 'text-white' : 'text-muted' }}">Últimas transacciones</small>
                            </div>
                        </a>
                    </li>
                    @endif
                    {{-- Tab 4: Configuración de Crédito --}}
                    @module('module_credits')
                    @can('customers.edit_credit_config')
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 4 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',4)" href="#">
                            <i class="fa fa-credit-card fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Configuración de Crédito</h6>
                                <small class="{{ $tab == 4 ? 'text-white' : 'text-muted' }}">Crédito y descuentos</small>
                            </div>
                        </a>
                    </li>
                    @endcan
                    @endmodule
                    {{-- Tab 5: Notificaciones (WhatsApp) --}}
                    @module('module_whatsapp')
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 5 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',5)" href="#">
                            <i class="fas fa-bell fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Notificaciones</h6>
                                <small class="{{ $tab == 5 ? 'text-white' : 'text-muted' }}">WhatsApp y Correo</small>
                            </div>
                        </a>
                    </li>
                    @endmodule
                    
                    {{-- Tab 6: Estudio de Crédito & Score IA --}}
                    @module('module_credits')
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 6 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',6)" href="#">
                            <i class="fas fa-brain fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Score & Crédito IA</h6>
                                <small class="{{ $tab == 6 ? 'text-white' : 'text-muted' }}">Estudio de capacidad</small>
                            </div>
                        </a>
                    </li>
                    @endmodule
                </ul>
            </div>

            {{-- Right Content --}}
            <div class="col-xxl-9 col-xl-8 box-col-8 position-relative">
                <div class="tab-content" id="customer-pills-tabContent">
                    
                    {{-- Tab 1: Generales --}}
                    <div class="tab-pane fade {{ $tab == 1 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="row g-2">
                                <div class="col-sm-12">
                                    <label class="form-label">Nombre <span class="txt-danger">*</span></label>
                                    <input wire:model="customer.name" id='inputFocus' class="form-control" type="text" placeholder="nombre">
                                    @error('customer.name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">CC/Nit</label>
                                    <input wire:model="customer.taxpayer_id" class="form-control" type="text" placeholder="cc/nit">
                                    @error('customer.taxpayer_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Teléfono</label>
                                    <input wire:model="customer.phone" class="form-control" type="text" placeholder="teléfono" maxlength="15">
                                    @error('customer.phone') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-12 mt-3">
                                    <label class="form-label">Dirección <span class="txt-danger">*</span></label>
                                    <input wire:model="customer.address" class="form-control" type="text" placeholder="dirección">
                                    @error('customer.address') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Ciudad <span class="txt-danger">*</span></label>
                                    <input wire:model="customer.city" class="form-control" type="text" placeholder="ciudad" maxlength="100">
                                    @error('customer.city') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Email</label>
                                    <input wire:model="customer.email" class="form-control" type="email" placeholder="correo@ejemplo.com">
                                    @error('customer.email') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Contraseña (App VIP)</label>
                                    <input wire:model="password" class="form-control" type="password" placeholder="Solo si tiene acceso a la App VIP">
                                    <small class="text-muted">Dejar vacío si no se desea cambiar. Mínimo 6 caracteres.</small>
                                    @error('password') <br><span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- Tab 2: Configuración Comercial --}}
                    @module('module_commissions')
                    @can('customers.edit_commercial_config')
                    <div class="tab-pane fade {{ $tab == 2 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="row g-2">
                                <div class="col-sm-12">
                                    <label class="form-label">Vendedor Asignado</label>
                                    <select class="form-control" wire:model="customer.seller_id">
                                        <option value="0">Seleccionar (Por defecto: OFICINA)</option>
                                        @foreach($sellers as $seller)
                                            <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('customer.seller_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-sm-12 mt-3 d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="text-info mb-0">Configuración Comercial Opcional</h6>
                                        <small class="text-muted">Dejar en 0 o vacío para heredar del Vendedor o Global.</small>
                                    </div>
                                    @if($customer->id)
                                    <button class="btn btn-info btn-sm" type="button" wire:click="viewHistory({{ $customer->id }})">
                                        <i class="fas fa-history"></i> Ver Historial
                                    </button>
                                    @endif
                                </div>
                                <div class="col-sm-3 form-group mt-3">
                                    <span class="form-label">Comisión (%)</span>
                                    <input wire:model="commission_percent" class="form-control" type="number" step="0.01" min="0" max="100" placeholder="Heredado">
                                </div>
                                <div class="col-sm-3 form-group mt-3">
                                    <span class="form-label">Flete (%)</span>
                                    <input wire:model="freight_percent" class="form-control" type="number" step="0.01" min="0" max="100" placeholder="Heredado">
                                </div>
                                <div class="col-sm-3 form-group mt-3">
                                    <span class="form-label">Recargo (%)</span>
                                    <input wire:model="base_markup_percent" class="form-control" type="number" step="0.01" min="0" max="100" placeholder="Heredado">
                                </div>
                                <div class="col-sm-3 form-group mt-3">
                                    <span class="form-label">Dif. Cambiario (%)</span>
                                    <input wire:model="exchange_diff_percent" class="form-control" type="number" step="0.01" min="0" max="1000" placeholder="Heredado">
                                </div>
                                <div class="col-sm-12 form-group mt-3">
                                    <span class="form-label">Lote Actual</span>
                                    <input wire:model="current_batch" class="form-control" type="text" placeholder="Heredado">
                                </div>

                                <div class="col-sm-12 mt-3">
                                    <label class="form-label text-info"><strong>Acuerdo Comercial</strong></label>
                                    <textarea wire:model="agreement" class="form-control" rows="4" placeholder="Escriba aquí los términos acordados con este cliente..."></textarea>
                                    <small class="text-muted">Este acuerdo se mostrará al operador en el POS durante la venta.</small>
                                </div>

                                <div class="col-sm-12 mt-4">
                                    <h6 class="text-info">Sobrescribir Comisiones por Días (Opcional)</h6>
                                    <small class="text-muted">Dejar en blanco para usar la configuración del vendedor o global.</small>
                                </div>
                                
                                <div class="col-sm-6 form-group mt-3">
                                    <span class="form-label">Nivel 1: Días (<=)</span>
                                    <input wire:model="customerCommission1Threshold" class="form-control" type="number" placeholder="Heredado">
                                    @error('customerCommission1Threshold') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 form-group mt-3">
                                    <span class="form-label">Nivel 1: Porcentaje (%)</span>
                                    <input wire:model="customerCommission1Percentage" class="form-control" type="number" step="0.01" placeholder="Heredado">
                                    @error('customerCommission1Percentage') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-sm-6 form-group mt-3">
                                    <span class="form-label">Nivel 2: Días (<=)</span>
                                    <input wire:model="customerCommission2Threshold" class="form-control" type="number" placeholder="Heredado">
                                    @error('customerCommission2Threshold') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 form-group mt-3">
                                    <span class="form-label">Nivel 2: Porcentaje (%)</span>
                                    <input wire:model="customerCommission2Percentage" class="form-control" type="number" step="0.01" placeholder="Heredado">
                                    @error('customerCommission2Percentage') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>
                    @endcan
                    @endmodule

                    {{-- Tab 3: Historial de Ventas --}}
                    @if($editing && $customer->id > 0)
                    <div class="tab-pane fade {{ $tab == 3 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="row">
                                <div class="col-sm-12">
                                    <h6 class="text-info mb-3">Resumen</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="card bg-light-success">
                                                <div class="card-body text-center">
                                                    <h6 class="text-muted">Total Ventas</h6>
                                                    <h4 class="text-success">{{ $customer->sales->count() }}</h4>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light-info">
                                                <div class="card-body text-center">
                                                    <h6 class="text-muted">Última Venta</h6>
                                                    <h6 class="text-info">
                                                        {{ $customer->sales->sortByDesc('created_at')->first()?->created_at?->format('d/m/Y') ?? 'N/A' }}
                                                    </h6>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="card bg-light-primary">
                                                <div class="card-body text-center">
                                                    <h6 class="text-muted">Monto Total</h6>
                                                    <h5 class="text-primary">
                                                        ${{ number_format($customer->sales->sum('total'), 2) }}
                                                    </h5>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-sm-12 mt-4">
                                    <h6 class="text-info mb-3">Últimas 10 Ventas</h6>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-hover">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Fecha</th>
                                                    <th>Factura #</th>
                                                    <th>Total</th>
                                                    <th>Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($customer->sales()->orderBy('created_at', 'desc')->limit(10)->get() as $sale)
                                                    <tr>
                                                        <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                                                        <td>{{ $sale->invoice_number }}</td>
                                                        <td>${{ number_format($sale->total, 2) }}</td>
                                                        <td>
                                                            <span class="badge {{ $sale->status == 'PAID' ? 'badge-light-success' : 'badge-light-warning' }}">
                                                                {{ $sale->status }}
                                                            </span>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center text-muted">Sin ventas registradas</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- Tab 4: Configuración de Crédito --}}
                    @module('module_credits')
                    @can('customers.edit_credit_config')
                    <div class="tab-pane fade {{ $tab == 4 ? 'active show' : '' }}" role="tabpanel">
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
                                        <input wire:model="customer.allow_credit" class="form-check-input" type="checkbox" id="allowCreditSwitch">
                                        <label class="form-check-label" for="allowCreditSwitch">
                                            <strong>Permitir Crédito</strong>
                                            <small class="d-block text-muted">Habilitar compras a crédito para este cliente</small>
                                        </label>
                                    </div>
                                    @error('customer.allow_credit') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Días de Crédito</label>
                                    <input wire:model="customer.credit_days" type="number" class="form-control" 
                                           placeholder="Ej: 15, 30, 60">
                                    <small class="text-muted">Plazo máximo para pagar (en días)</small>
                                    @error('customer.credit_days') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Límite de Crédito ($)</label>
                                    <input wire:model="customer.credit_limit" type="number" step="0.01" class="form-control" 
                                           placeholder="Ej: 10000.00">
                                    <small class="text-muted">Monto máximo total en crédito pendiente</small>
                                    @error('customer.credit_limit') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label text-success"><strong>Saldo en Billetera ($)</strong></label>
                                    <input wire:model="customer.wallet_balance" type="number" step="0.01" class="form-control border-success" 
                                           placeholder="Ej: 50.00">
                                    <small class="text-muted">Saldo a favor del cliente para usar en compras</small>
                                    @error('customer.wallet_balance') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                {{-- Sección 2: Reglas de Descuento/Recargo --}}
                                <div class="col-sm-12 mt-4">
                                    <h6 class="text-info mb-3">
                                        <i class="fa fa-percentage"></i> Reglas de Descuento/Recargo
                                    </h6>
                                    <p class="text-muted small">Configure descuentos por pronto pago o recargos por mora según días transcurridos.</p>
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
                                                    <th>% Desc/Recargo</th>
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
                                        <i class="fa fa-info-circle"></i> No hay reglas configuradas. Haga clic en "Agregar Regla" para crear una.
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
                                        <input wire:model="customer.usd_payment_discount" type="number" step="0.01" 
                                               class="form-control text-center" placeholder="Ej: 5.00">
                                        <small class="text-muted">Descuento aplicado si paga con Zelle o Dólar en efectivo</small>
                                        @error('customer.usd_payment_discount') <br><span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-sm-4 text-center">
                                        <label class="form-label">Código (Tag)</label>
                                        <input wire:model="customer.usd_payment_discount_tag" type="text" 
                                               class="form-control text-center" placeholder="Ej: PD">
                                        <small class="text-muted">Ej: PD</small>
                                        @error('customer.usd_payment_discount_tag') <br><span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                </div>

                                {{-- Nota sobre jerarquía --}}
                                <div class="col-sm-12 mt-4">
                                    <div class="alert alert-warning">
                                        <i class="fa fa-info-circle"></i> <strong>Nota:</strong> Si no configura estos valores, se usará la configuración del vendedor asignado. Si el vendedor tampoco tiene configuración, se usará la configuración global del sistema.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endcan
                    @endmodule

                    {{-- Tab 5: Notificaciones WhatsApp --}}
                    @module('module_whatsapp')
                    <div class="tab-pane fade {{ $tab == 5 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="row g-2">
                                <div class="col-sm-12">
                                    <h6 class="text-success mb-3">
                                        <i class="fab fa-whatsapp"></i> Ajustes de WhatsApp
                                    </h6>
                                    <p class="text-muted small">Seleccione qué notificaciones desea enviar a este cliente vía WhatsApp.</p>
                                </div>

                                <div class="col-sm-12 mt-3">
                                    <div class="form-check form-switch form-switch-lg">
                                        <input wire:model="customer.whatsapp_notify_sales" class="form-check-input form-check-input-success" type="checkbox" id="waNotifySales">
                                        <label class="form-check-label" for="waNotifySales">
                                            <strong>Notificar Ventas</strong>
                                            <small class="d-block text-muted">Enviar recibo de venta cuando se crea un nuevo comprobante o pedido.</small>
                                        </label>
                                    </div>
                                    @error('customer.whatsapp_notify_sales') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-sm-12 mt-4">
                                    <div class="form-check form-switch form-switch-lg">
                                        <input wire:model="customer.whatsapp_notify_payments" class="form-check-input form-check-input-success" type="checkbox" id="waNotifyPayments">
                                        <label class="form-check-label" for="waNotifyPayments">
                                            <strong>Notificar Abonos / Pagos</strong>
                                            <small class="d-block text-muted">Enviar estado de cuenta cuando se registra o aprueba un nuevo pago.</small>
                                        </label>
                                    </div>
                                    @error('customer.whatsapp_notify_payments') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                
                                
                                <div class="col-sm-12 mt-3">
                                    <label class="form-label text-success"><strong>Modo de Envío WhatsApp</strong></label>
                                    <select wire:model="customer.wa_dispatch_mode" class="form-select border-success">
                                        <option value="auto">Automático (Enviar al instante)</option>
                                        <option value="manual">Manual (Enviar a Bandeja de Revisión)</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Si está en "Manual", el mensaje irá a la Bandeja de Salida para que lo revises antes de enviarlo.</small>
                                </div>

                                <div class="col-sm-12 mt-4 pt-3 border-top">
                                    <h6 class="text-primary mb-3">
                                        <i class="fas fa-envelope"></i> Ajustes de Correo Electrónico
                                    </h6>
                                    <p class="text-muted small">Seleccione qué correos electrónicos desea enviar a este cliente con los adjuntos en PDF.</p>
                                </div>

                                <div class="col-sm-12 mt-2">
                                    <div class="form-check form-switch form-switch-lg">
                                        <input wire:model="customer.email_notify_sales" class="form-check-input" type="checkbox" id="emailNotifySales">
                                        <label class="form-check-label" for="emailNotifySales">
                                            <strong>Notificar Ventas</strong>
                                            <small class="d-block text-muted">Enviar factura/pedido en PDF por correo al crear venta.</small>
                                        </label>
                                    </div>
                                    @error('customer.email_notify_sales') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-sm-12 mt-4">
                                    <div class="form-check form-switch form-switch-lg">
                                        <input wire:model="customer.email_notify_payments" class="form-check-input" type="checkbox" id="emailNotifyPayments">
                                        <label class="form-check-label" for="emailNotifyPayments">
                                            <strong>Notificar Abonos / Pagos</strong>
                                            <small class="d-block text-muted">Enviar estado de cuenta y recibo en PDF por correo.</small>
                                        </label>
                                    </div>
                                    @error('customer.email_notify_payments') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-sm-12 mt-3">
                                    <label class="form-label text-primary"><strong>Modo de Envío por Correo</strong></label>
                                    <select wire:model="customer.email_dispatch_mode" class="form-select border-primary">
                                        <option value="auto">Automático (Enviar al instante)</option>
                                        <option value="manual">Manual (Enviar a Bandeja de Revisión)</option>
                                    </select>
                                    <small class="text-muted d-block mt-1">Si está en "Manual", el correo irá a la Bandeja de Salida para que lo revises antes de enviarlo.</small>
                                </div>

                                <div class="col-sm-12 mt-4">
                                    <div class="alert alert-info">
                                        <i class="fa fa-info-circle"></i> <strong>Importante:</strong> Si el cliente no tiene un teléfono/correo válido, el mensaje saltará a los datos del vendedor asignado. También los ajustes globales del sistema deben estar encendidos.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endmodule

                    {{-- Tab 6: Score & Crédito IA --}}
                    @module('module_credits')
                    <div class="tab-pane fade {{ $tab == 6 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <h5 class="text-primary mb-4"><i class="fas fa-brain me-2"></i> Estudio de Crédito & Score Inteligente</h5>

                            {{-- KPI Cards --}}
                            <div class="row g-3 mb-4">
                                {{-- Card 1: Score --}}
                                <div class="col-md-4">
                                    <div class="card bg-light border-0 shadow-sm h-100">
                                        <div class="card-body text-center p-3">
                                            <span class="text-muted d-block mb-1">Score de Puntualidad</span>
                                            @php
                                                $score = $creditScoringResult['credit_score'] ?? 100;
                                                $badgeColor = $score >= 85 ? 'bg-success' : ($score >= 60 ? 'bg-warning text-dark' : 'bg-danger text-white');
                                            @endphp
                                            <div class="d-inline-flex rounded-circle {{ $badgeColor }} p-3 mb-2 font-weight-bold" style="font-size: 1.8rem; width: 80px; height: 80px; line-height: 48px; align-items: center; justify-content: center;">
                                                {{ $score }}
                                            </div>
                                            <small class="d-block font-weight-bold">
                                                {{ $score >= 85 ? 'Excelente Pagador' : ($score >= 60 ? 'Pago Regular' : 'Alto Riesgo (Moroso)') }}
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                {{-- Card 2: Capacidad de Pago --}}
                                <div class="col-md-4">
                                    <div class="card bg-light border-0 shadow-sm h-100">
                                        <div class="card-body p-3">
                                            <span class="text-muted d-block mb-2 text-center">Capacidad de Compra</span>
                                            <div class="mb-1" style="font-size: 0.9rem;">
                                                <i class="fas fa-shopping-cart text-primary me-1"></i> Compras Contado: 
                                                <strong>{{ $creditScoringResult['cash_purchase_count'] ?? 0 }}</strong>
                                            </div>
                                            <div class="mb-1" style="font-size: 0.9rem;">
                                                <i class="fas fa-dollar-sign text-success me-1"></i> Ticket Promedio: 
                                                <strong>${{ number_format($creditScoringResult['average_cash_purchase'] ?? 0.00, 2) }}</strong>
                                            </div>
                                            <div style="font-size: 0.9rem;">
                                                <i class="fas fa-calendar-day text-info me-1"></i> Antigüedad: 
                                                <strong>{{ $creditScoringResult['days_since_registration'] ?? 0 }} días</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Card 3: Cupo Recomendado --}}
                                <div class="col-md-4">
                                    <div class="card bg-light border-0 shadow-sm h-100">
                                        <div class="card-body text-center p-3">
                                            <span class="text-muted d-block mb-2">Cupo Crediticio Sugerido</span>
                                            <h3 class="text-success mb-2">${{ number_format($creditScoringResult['credit_limit_recommended'] ?? 0.00, 2) }}</h3>
                                            @if(isset($creditScoringResult['credit_limit_recommended']) && $creditScoringResult['credit_limit_recommended'] > 0)
                                                <button type="button" class="btn btn-xs btn-outline-success" wire:click="applyRecommendedCreditLimit">
                                                    <i class="fas fa-check-double me-1"></i> Aplicar Sugerido
                                                </button>
                                            @else
                                                <span class="badge bg-secondary p-1">Sin sugerencia disponible</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- AI analysis card --}}
                            <div class="card border-0 shadow-sm mb-4" style="background: rgba(27, 94, 32, 0.05);">
                                <div class="card-body p-3">
                                    <h6 class="text-success mb-2"><i class="fas fa-magic me-1"></i> Diagnóstico y Análisis IA</h6>
                                    <p class="text-dark mb-0 font-italic" style="font-size: 0.9rem;">
                                        {!! preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', e($creditScoringResult['ai_analysis'] ?? '')) !!}
                                    </p>
                                </div>
                            </div>

                            {{-- Legend Card for Operator --}}
                            <div class="card border-info">
                                <div class="card-header bg-info text-white p-2">
                                    <h6 class="mb-0 text-white"><i class="fas fa-info-circle me-1"></i> Guía de Interpretación (Leyenda para Operadores)</h6>
                                </div>
                                <div class="card-body p-3 bg-light" style="font-size: 0.85rem;">
                                    <p class="mb-2">El motor de crédito evalúa automáticamente a los clientes bajo las siguientes reglas:</p>
                                    <ul class="list-unstyled ps-0 mb-0">
                                        <li class="mb-2">
                                            <span class="badge bg-secondary me-1 text-white">NUEVO (Bootstrapping)</span>
                                            Clientes con menos de <strong>30 días de registro</strong> o menos de <strong>3 compras de contado</strong> finalizadas. Su cupo recomendado siempre será <strong>$0.00</strong> (Crédito Bloqueado) para evitar fraudes iniciales.
                                        </li>
                                        <li class="mb-2">
                                            <span class="badge bg-success me-1">Riesgo Bajo (Score 85 - 100)</span>
                                            Clientes puntuales que pagan sin demoras. Califican para un cupo recomendado del <strong>30% de su ticket promedio de compra</strong>.
                                        </li>
                                        <li class="mb-2">
                                            <span class="badge bg-warning text-dark me-1">Riesgo Medio (Score 60 - 84)</span>
                                            Clientes que registran retrasos ocasionales menores a 5 días. Califican para un cupo reducido al <strong>20% de su compra promedio</strong>.
                                        </li>
                                        <li>
                                            <span class="badge bg-danger me-1 text-white">Riesgo Alto / Moroso (Score < 60)</span>
                                            Clientes con más de 5 días de mora promedio o facturas vencidas sin saldar. El sistema <strong>bloqueará automáticamente la opción de crédito</strong>.
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endmodule
                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between p-3">
        <button class="btn btn-secondary" wire:click="cancelEdit">
            <i class="fas fa-times mr-1"></i> Cancelar
        </button>
        
        @if($customer->id)
            @can('customers.edit')
            <button class="btn btn-primary" wire:click="Store">
                <i class="fas fa-check mr-1"></i> Actualizar
            </button>
            @endcan
        @else
            @can('customers.create')
            <button class="btn btn-primary" wire:click="Store">
                <i class="fas fa-check mr-1"></i> Crear Cliente
            </button>
            @endcan
        @endif
    </div>
</div>
