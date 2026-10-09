<div>
    {{-- Encabezado estilizado con AdminLTE --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-wrench text-warning mr-2"></i> Taller Automotriz - Fichas y Órdenes de Servicio
            </h3>
            <small class="text-muted">Recepción de vehículos, diagnóstico, control de repuestos, mano de obra y facturación</small>
        </div>
        <div>
            <button type="button" wire:click="$set('showCreateModal', true)" class="btn btn-primary btn-lg shadow-sm">
                <i class="fas fa-plus-circle mr-1"></i> Nueva Ficha / Orden
            </button>
        </div>
    </div>

    {{-- Barra de Búsqueda y Filtros --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body p-3">
            <div class="row align-items-center">
                <div class="col-md-8 mb-2 mb-md-0">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                        </div>
                        <input type="text" wire:model.live.debounce.300ms="search" class="form-control" placeholder="Buscar por placa, número de orden (OT-...), cliente o modelo de vehículo...">
                    </div>
                </div>
                <div class="col-md-4">
                    <select wire:model.live="statusFilter" class="form-control">
                        <option value="">Todos los Estados</option>
                        <option value="reception">Recepción / Diagnóstico</option>
                        <option value="budgeted">Presupuesto</option>
                        <option value="approved">Aprobado</option>
                        <option value="in_progress">En Reparación</option>
                        <option value="ready">Listo para Entrega</option>
                        <option value="invoiced">Facturado / Cobrado</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Grid Principal: Lista de Órdenes (Col-8) y Detalle Activo (Col-4) --}}
    <div class="row">
        {{-- Listado de Órdenes --}}
        <div class="col-lg-8">
            <div class="card card-outline card-primary shadow-sm">
                <div class="card-header p-2">
                    <h5 class="card-title font-weight-bold mb-0">Órdenes de Trabajo Registradas</h5>
                </div>
                <div class="card-body p-0 table-responsive">
                    <table class="table table-hover table-striped mb-0 text-sm">
                        <thead class="bg-light">
                            <tr>
                                <th>Orden</th>
                                <th>Vehículo / Placa</th>
                                <th>Cliente</th>
                                <th class="text-center">Estado</th>
                                <th class="text-right">Total</th>
                                <th class="text-center" style="width: 100px;">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($orders as $order)
                                <tr wire:key="order-{{ $order->id }}" class="{{ $selectedOrderId === $order->id ? 'table-primary font-weight-bold' : '' }}" style="cursor: pointer;" wire:click="selectOrder({{ $order->id }})">
                                    <td>
                                        <span class="badge badge-primary font-weight-bold">{{ $order->order_number }}</span>
                                        <div class="text-muted text-xs mt-1">{{ $order->created_at->format('d/m/Y') }}</div>
                                    </td>
                                    <td>
                                        <span class="badge badge-dark">{{ $order->vehicle?->plate }}</span>
                                        <div class="text-muted text-xs mt-1">{{ $order->vehicle?->brand }} {{ $order->vehicle?->model }}</div>
                                    </td>
                                    <td>
                                        <div>{{ $order->customer?->name }}</div>
                                        <small class="text-muted">{{ $order->customer?->taxpayer_id }}</small>
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $badges = [
                                                'reception' => 'badge-warning',
                                                'budgeted' => 'badge-info',
                                                'approved' => 'badge-secondary',
                                                'in_progress' => 'badge-primary',
                                                'ready' => 'badge-success',
                                                'invoiced' => 'badge-dark',
                                            ];
                                            $labels = [
                                                'reception' => 'Recepción',
                                                'budgeted' => 'Presupuesto',
                                                'approved' => 'Aprobado',
                                                'in_progress' => 'En Taller',
                                                'ready' => 'Listo',
                                                'invoiced' => 'Facturado',
                                            ];
                                        @endphp
                                        <span class="badge {{ $badges[$order->status] ?? 'badge-light' }} p-1 px-2">
                                            {{ $labels[$order->status] ?? $order->status }}
                                        </span>
                                    </td>
                                    <td class="text-right font-weight-bold text-success font-weight-bold">
                                        ${{ number_format($order->total, 2) }}
                                    </td>
                                    <td class="text-center" onclick="event.stopPropagation();">
                                        <a href="{{ route('workshop.work-orders.pdf', $order->id) }}" target="_blank" class="btn btn-outline-danger btn-xs" title="Ficha PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        <button wire:click="selectOrder({{ $order->id }})" class="btn btn-outline-primary btn-xs ml-1" title="Ver Detalle">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-4 text-muted">
                                        <i class="fas fa-car fa-2x mb-2 d-block text-secondary"></i>
                                        No se encontraron órdenes de trabajo registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="card-footer p-2 bg-white">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>

        {{-- Panel Lateral: Detalle de Orden --}}
        <div class="col-lg-4">
            @if($selectedOrder)
                <div class="card card-outline card-info shadow-sm">
                    <div class="card-header bg-white d-flex justify-content-between align-items-center p-3">
                        <div>
                            <small class="text-muted text-uppercase d-block font-weight-bold">Ficha de Trabajo</small>
                            <h4 class="font-weight-bold text-primary mb-0">{{ $selectedOrder->order_number }}</h4>
                        </div>
                        <div>
                            <a href="{{ route('workshop.work-orders.pdf', $selectedOrder->id) }}" target="_blank" class="btn btn-outline-danger btn-sm mr-1" title="Descargar PDF">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                            @if($selectedOrder->status !== 'invoiced')
                                <button wire:click="$set('showItemModal', true)" class="btn btn-outline-primary btn-sm" title="Agregar Mano de Obra o Repuestos">
                                    <i class="fas fa-plus"></i> Ítem
                                </button>
                                <button wire:click="$set('showBillingModal', true)" class="btn btn-success btn-sm ml-1" title="Cobrar / Facturar en Caja">
                                    <i class="fas fa-cash-register"></i> Cobrar
                                </button>
                            @else
                                <span class="badge badge-success p-2">Cobrada</span>
                            @endif
                        </div>
                    </div>

                    <div class="card-body p-3">
                        {{-- Datos del Vehículo y Cliente --}}
                        <div class="bg-light p-3 rounded mb-3 border">
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted text-xs">Cliente:</span>
                                <span class="font-weight-bold text-xs">{{ $selectedOrder->customer?->name }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted text-xs">Vehículo:</span>
                                <span class="font-weight-bold text-xs">{{ $selectedOrder->vehicle?->brand }} {{ $selectedOrder->vehicle?->model }} ({{ $selectedOrder->vehicle?->year }})</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted text-xs">Placa:</span>
                                <span class="badge badge-dark text-xs">{{ $selectedOrder->vehicle?->plate }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span class="text-muted text-xs">Km Entrada:</span>
                                <span class="text-xs">{{ number_format($selectedOrder->entry_mileage) }} km</span>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted text-xs">Tanque:</span>
                                <span class="text-xs font-weight-bold">{{ $selectedOrder->fuel_level }}</span>
                            </div>
                        </div>

                        {{-- Diagnóstico Inicial --}}
                        <div class="mb-3">
                            <small class="text-muted font-weight-bold text-uppercase d-block mb-1">Diagnóstico Inicial:</small>
                            <div class="p-2 bg-white rounded border text-xs text-secondary">
                                {{ $selectedOrder->initial_diagnosis }}
                            </div>
                        </div>

                        {{-- Lista de Ítems / Repuestos --}}
                        <div class="mb-3">
                            <small class="text-muted font-weight-bold text-uppercase d-block mb-2">Servicios y Repuestos:</small>
                            <div class="list-group list-group-flush border rounded" style="max-height: 220px; overflow-y: auto;">
                                @forelse($selectedOrder->items as $item)
                                    <div class="list-group-item p-2 d-flex justify-content-between align-items-center text-xs">
                                        <div>
                                            <div class="font-weight-bold text-dark">
                                                <i class="fas fa-circle mr-1 {{ $item->type === 'part' ? 'text-primary' : 'text-warning' }}" style="font-size: 8px;"></i>
                                                {{ $item->description }}
                                            </div>
                                            <div class="text-muted text-xs mt-0.5">
                                                {{ $item->quantity }} x ${{ number_format($item->unit_price, 2) }}
                                                @if($item->mechanic)
                                                    <span class="badge badge-light ml-1">Mec: {{ $item->mechanic->name }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="font-weight-bold text-dark">
                                            ${{ number_format($item->subtotal, 2) }}
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-3 text-center text-muted text-xs">
                                        No hay ítems cargados en esta orden.
                                    </div>
                                @endforelse
                            </div>
                        </div>

                        {{-- Resumen Financiero --}}
                        <div class="border-top pt-2">
                            <div class="d-flex justify-content-between text-xs mb-1">
                                <span class="text-muted">Subtotal Repuestos:</span>
                                <span class="font-weight-bold">${{ number_format($selectedOrder->subtotal_parts, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between text-xs mb-1">
                                <span class="text-muted">Subtotal Mano de Obra:</span>
                                <span class="font-weight-bold">${{ number_format($selectedOrder->subtotal_labor, 2) }}</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center border-top pt-2 mt-2">
                                <h6 class="font-weight-bold mb-0">TOTAL GENERAL:</h6>
                                <h4 class="font-weight-bold text-success mb-0">${{ number_format($selectedOrder->total, 2) }}</h4>
                            </div>
                        </div>
                    </div>
                </div>
            @else
                <div class="card shadow-sm text-center p-4">
                    <div class="card-body">
                        <i class="fas fa-car-side fa-3x text-muted mb-3 d-block"></i>
                        <h6 class="font-weight-bold text-secondary">Ninguna Orden Seleccionada</h6>
                        <p class="text-muted text-xs mb-0">Selecciona una orden de la lista para ver sus detalles, cargar repuestos o emitir la factura.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL 1: NUEVA FICHA / ORDEN DE TRABAJO --}}
    @if($showCreateModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-file-invoice mr-2"></i> Nueva Ficha de Servicio / Recepción</h5>
                    <button type="button" class="close text-white" wire:click="$set('showCreateModal', false)">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold text-xs">Cliente / Propietario <span class="text-danger">*</span></label>
                            <select wire:model.live="newOrder.customer_id" class="form-control">
                                <option value="">Seleccione Cliente...</option>
                                @foreach($customers as $c)
                                    <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->taxpayer_id }})</option>
                                @endforeach
                            </select>
                            @error('newOrder.customer_id') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold text-xs">Vehículo <span class="text-danger">*</span></label>
                            <select wire:model.live="newOrder.vehicle_id" class="form-control">
                                <option value="">Seleccione Vehículo...</option>
                                @foreach($vehicles as $v)
                                    <option value="{{ $v->id }}">{{ $v->plate }} - {{ $v->brand }} {{ $v->model }} ({{ $v->year }})</option>
                                @endforeach
                            </select>
                            @error('newOrder.vehicle_id') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold text-xs">Kilometraje al Recibir <span class="text-danger">*</span></label>
                            <input type="number" wire:model.live="newOrder.entry_mileage" class="form-control" placeholder="Ej: 95000">
                            @error('newOrder.entry_mileage') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold text-xs">Nivel Tanque Combustible</label>
                            <select wire:model.live="newOrder.fuel_level" class="form-control">
                                <option value="Reserva">Reserva</option>
                                <option value="1/4">1/4 de Tanque</option>
                                <option value="1/2">1/2 de Tanque</option>
                                <option value="3/4">3/4 de Tanque</option>
                                <option value="Lleno">Tanque Lleno</option>
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold text-xs">Mecánico Líder Asignado</label>
                            <select wire:model.live="newOrder.mechanic_id" class="form-control">
                                <option value="">Sin asignar aún</option>
                                @foreach($mechanics as $m)
                                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold text-xs">Diagnóstico Inicial / Motivo de Entrada <span class="text-danger">*</span></label>
                        <textarea wire:model.live="newOrder.initial_diagnosis" rows="3" class="form-control" placeholder="Describa el problema reportado por el cliente, fallas, ruidos o servicios solicitados..."></textarea>
                        @error('newOrder.initial_diagnosis') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showCreateModal', false)">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm font-weight-bold" wire:click="createWorkOrder">
                        <i class="fas fa-save mr-1"></i> Crear Orden de Servicio
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL 2: AGREGAR ÍTEM (REPUESTO O MANO DE OBRA) --}}
    @if($showItemModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;" tabindex="-1">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-wrench mr-2"></i> Agregar Ítem a Orden</h5>
                    <button type="button" class="close text-white" wire:click="$set('showItemModal', false)">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group">
                        <label class="font-weight-bold text-xs">Tipo de Ítem</label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <button type="button" class="btn btn-outline-primary btn-sm {{ $newItem['type'] === 'labor' ? 'active' : '' }}" wire:click="$set('newItem.type', 'labor')">
                                <i class="fas fa-user-cog mr-1"></i> Mano de Obra
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm {{ $newItem['type'] === 'part' ? 'active' : '' }}" wire:click="$set('newItem.type', 'part')">
                                <i class="fas fa-cogs mr-1"></i> Repuesto de Inventario
                            </button>
                        </div>
                    </div>

                    @if($newItem['type'] === 'part')
                        <div class="form-group">
                            <label class="font-weight-bold text-xs">Seleccionar Repuesto del Inventario</label>
                            <select wire:change="onProductSelected($event.target.value)" class="form-control">
                                <option value="">Seleccione producto...</option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}">{{ $p->name }} (Stock: {{ $p->stock_qty }}) - ${{ number_format($p->price, 2) }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="font-weight-bold text-xs">Descripción del Trabajo / Repuesto <span class="text-danger">*</span></label>
                        <input type="text" wire:model.live="newItem.description" class="form-control" placeholder="Ej: Rectificado de discos, Cambio de pastillas...">
                        @error('newItem.description') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="row">
                        <div class="col-6 form-group">
                            <label class="font-weight-bold text-xs">Cantidad <span class="text-danger">*</span></label>
                            <input type="number" step="0.5" wire:model.live="newItem.quantity" class="form-control">
                            @error('newItem.quantity') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-6 form-group">
                            <label class="font-weight-bold text-xs">Precio Unitario ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" wire:model.live="newItem.unit_price" class="form-control">
                            @error('newItem.unit_price') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    @if($newItem['type'] === 'labor')
                        <div class="row bg-light p-2 rounded mb-2 border">
                            <div class="col-7 form-group mb-0">
                                <label class="font-weight-bold text-xs">Mecánico Ejecutante</label>
                                <select wire:model.live="newItem.mechanic_id" class="form-control form-control-sm">
                                    <option value="">Seleccione especialista...</option>
                                    @foreach($mechanics as $m)
                                        <option value="{{ $m->id }}">{{ $m->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-5 form-group mb-0">
                                <label class="font-weight-bold text-xs">% Comisión</label>
                                <input type="number" step="1" wire:model.live="newItem.mechanic_commission_percentage" class="form-control form-control-sm" placeholder="Ej: 30">
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showItemModal', false)">Cancelar</button>
                    <button type="button" class="btn btn-info btn-sm font-weight-bold text-white" wire:click="addItem">
                        <i class="fas fa-check mr-1"></i> Agregar a la Orden
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL 3: COBRAR / FACTURAR ORDEN --}}
    @if($showBillingModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;" tabindex="-1">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-cash-register mr-2"></i> Cobrar y Facturar Orden</h5>
                    <button type="button" class="close text-white" wire:click="$set('showBillingModal', false)">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="alert alert-success text-center">
                        <small class="text-uppercase font-weight-bold">Total a Facturar</small>
                        <h2 class="font-weight-bold mb-0">${{ number_format($selectedOrder->total, 2) }}</h2>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold text-xs">Modalidad de Pago / Facturación</label>
                        <select wire:model.live="billingData.payment_method" class="form-control">
                            <option value="cash_usd">Efectivo Dólares (Caja POS)</option>
                            <option value="credit">Crédito Corporativo (Cuentas por Cobrar)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold text-xs">Kilometraje al Entregar (Odómetro Salida)</label>
                        <input type="number" wire:model.live="billingData.exit_mileage" class="form-control" placeholder="{{ $selectedOrder->entry_mileage }}">
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-xs">Observaciones de Entrega</label>
                        <textarea wire:model.live="billingData.notes" rows="2" class="form-control" placeholder="Notas de liquidación, firma de recibido..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showBillingModal', false)">Cancelar</button>
                    <button type="button" class="btn btn-success btn-sm font-weight-bold" wire:click="billOrder">
                        <i class="fas fa-check-double mr-1"></i> Confirmar Facturación
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>