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
                                    <label class="form-label">CC/Nit <span class="txt-danger">*</span></label>
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
                                    <label class="form-label">Tipo <span class="txt-danger">*</span></label>
                                    <select class="form-control" wire:model="customer.type">
                                        <option value="0" selected disabled>Seleccionar</option>
                                        <option value="Mayoristas">Mayoristas</option>
                                        <option value="Consumidor Final">Consumidor Final</option>
                                        <option value="Descuento1">Descuento1</option>
                                        <option value="Descuento2">Descuento2</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                    @error('customer.type') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 2: Configuración Comercial --}}
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

                                <div class="col-sm-12 mt-4">
                                    <h6 class="text-info">Sobrescribir Comisiones (Opcional)</h6>
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

                                <div class="col-sm-12">
                                    <label class="form-label">% Descuento por Pago en USD (Zelle/Efectivo)</label>
                                    <input wire:model="customer.usd_payment_discount" type="number" step="0.01" 
                                           class="form-control" placeholder="Ej: 5.00">
                                    <small class="text-muted">Descuento aplicado si el cliente paga con Zelle o Dólar en efectivo</small>
                                    @error('customer.usd_payment_discount') <span class="text-danger">{{ $message }}</span> @enderror
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

                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between p-3">
        <button class="btn btn-secondary" wire:click="cancelEdit">
            <i class="fas fa-times mr-1"></i> Cancelar
        </button>
        
        <button class="btn btn-primary" wire:click="Store">
            <i class="fas fa-check mr-1"></i> {{ $customer->id ? 'Actualizar' : 'Crear Cliente' }}
        </button>
    </div>
</div>
