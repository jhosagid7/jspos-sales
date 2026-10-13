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
                                <button wire:click="openItemModal" class="btn btn-outline-primary btn-sm" title="Agregar Mano de Obra o Repuestos">
                                    <i class="fas fa-plus"></i> Ítem
                                </button>
                                <button wire:click="redirectToPosBilling({{ $selectedOrder->id }})" class="btn btn-success btn-sm ml-1" title="Cobrar / Facturar en Caja POS">
                                    <i class="fas fa-cash-register mr-1"></i> Cobrar en POS
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
                        {{-- CLIENTE CON BUSCADOR INTELIGENTE --}}
                        <div class="col-md-6 form-group">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="font-weight-bold text-xs mb-0">Cliente / Propietario <span class="text-danger">*</span></label>
                                <button type="button" class="btn btn-outline-primary btn-xs" wire:click="$set('showQuickCustomerModal', true)">
                                    <i class="fas fa-user-plus mr-1"></i> + Nuevo Cliente
                                </button>
                            </div>

                            @if($selectedCustomerModel)
                                <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="font-weight-bold text-dark text-sm">
                                            <i class="fas fa-user-check text-success mr-1"></i> {{ $selectedCustomerModel->name }}
                                        </div>
                                        <small class="text-muted d-block">
                                            RIF/CI: <strong>{{ $selectedCustomerModel->taxpayer_id ?: 'S/D' }}</strong> 
                                            @if($selectedCustomerModel->phone) | Tel: {{ $selectedCustomerModel->phone }} @endif
                                        </small>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-xs" wire:click="clearCustomer" title="Cambiar Cliente">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @else
                                <div class="position-relative">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
                                        </div>
                                        <input type="text" 
                                               wire:model.live.debounce.250ms="customerSearch" 
                                               class="form-control" 
                                               placeholder="Escribe nombre, cédula o RIF..."
                                               autocomplete="off">
                                    </div>

                                    {{-- Resultados de búsqueda predictiva: solo mostrar al escribir --}}
                                    @if(strlen(trim($customerSearch)) > 0)
                                        <div class="list-group position-absolute w-100 shadow-lg border" style="z-index: 1060; max-height: 220px; overflow-y: auto;">
                                            @forelse($customers as $c)
                                                <button type="button" 
                                                        class="list-group-item list-group-item-action p-2 text-left text-xs d-flex justify-content-between align-items-center"
                                                        wire:click="selectCustomer({{ $c->id }})">
                                                    <div>
                                                        <span class="font-weight-bold text-dark">{{ $c->name }}</span>
                                                        @if($c->taxpayer_id)
                                                            <span class="text-muted ml-1">({{ $c->taxpayer_id }})</span>
                                                        @endif
                                                    </div>
                                                    <span class="badge badge-primary"><i class="fas fa-check"></i></span>
                                                </button>
                                            @empty
                                                <div class="list-group-item p-2 text-muted text-xs text-center">
                                                    No se encontraron clientes con "{{ $customerSearch }}"
                                                </div>
                                            @endforelse
                                        </div>
                                    @endif
                                </div>
                            @endif
                            @error('newOrder.customer_id') <span class="text-danger text-xs d-block mt-1">{{ $message }}</span> @enderror
                        </div>

                        {{-- VEHÍCULO CON BUSCADOR INTELIGENTE --}}
                        <div class="col-md-6 form-group">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="font-weight-bold text-xs mb-0">Vehículo <span class="text-danger">*</span></label>
                                @if(!empty($newOrder['customer_id']))
                                    <button type="button" class="btn btn-outline-success btn-xs" wire:click="$set('showQuickVehicleModal', true)">
                                        <i class="fas fa-car mr-1"></i> + Nuevo Vehículo
                                    </button>
                                @endif
                            </div>

                            @if(empty($newOrder['customer_id']))
                                <div class="p-2 border rounded bg-light text-muted text-xs">
                                    <i class="fas fa-arrow-left mr-1 text-warning"></i> Primero busca y selecciona un cliente...
                                </div>
                            @elseif($selectedVehicleModel)
                                <div class="p-2 border rounded bg-light d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="font-weight-bold text-dark text-sm">
                                            <span class="badge badge-dark mr-1">{{ $selectedVehicleModel->plate }}</span>
                                            {{ $selectedVehicleModel->brand }} {{ $selectedVehicleModel->model }} ({{ $selectedVehicleModel->year }})
                                        </div>
                                        <small class="text-muted d-block">
                                            Color: {{ $selectedVehicleModel->color ?: 'S/D' }} 
                                            @if($selectedVehicleModel->current_mileage) | Odómetro: {{ number_format($selectedVehicleModel->current_mileage) }} km @endif
                                        </small>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-xs" wire:click="clearVehicle" title="Cambiar Vehículo">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @else
                                <div class="position-relative">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-car text-muted"></i></span>
                                        </div>
                                        <input type="text" 
                                               wire:model.live.debounce.250ms="vehicleSearch" 
                                               class="form-control" 
                                               placeholder="Escribe placa, marca, modelo o color..."
                                               autocomplete="off">
                                    </div>

                                    {{-- Resultados de vehículos del cliente --}}
                                    <div class="list-group position-absolute w-100 shadow-lg border" style="z-index: 1060; max-height: 220px; overflow-y: auto;">
                                        @forelse($vehicles as $v)
                                            <button type="button" 
                                                    class="list-group-item list-group-item-action p-2 text-left text-xs d-flex justify-content-between align-items-center"
                                                    wire:click="selectVehicle({{ $v->id }})">
                                                <div>
                                                    <span class="badge badge-dark mr-1">{{ $v->plate }}</span>
                                                    <span class="font-weight-bold text-dark">{{ $v->brand }} {{ $v->model }}</span>
                                                    <span class="text-muted ml-1">({{ $v->year }} - {{ $v->color }})</span>
                                                </div>
                                                <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                            </button>
                                        @empty
                                            <div class="list-group-item p-2 text-muted text-xs text-center">
                                                @if(count($selectedCustomerModel ? $selectedCustomerModel->vehicles : []) === 0)
                                                    Este cliente no tiene vehículos. ¡Usa <strong>+ Nuevo Vehículo</strong>!
                                                @else
                                                    No hay vehículos coincidentes con "{{ $vehicleSearch }}"
                                                @endif
                                            </div>
                                        @endforelse
                                    </div>
                                </div>
                            @endif
                            @error('newOrder.vehicle_id') <span class="text-danger text-xs d-block mt-1">{{ $message }}</span> @enderror
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
                        {{-- MECÁNICO CON BUSCADOR INTELIGENTE Y FILTRADO POR ROL --}}
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold text-xs">Mecánico Líder Asignado</label>
                            @if($selectedMechanicModel)
                                <div class="p-1 border rounded bg-light d-flex justify-content-between align-items-center">
                                    <div class="text-truncate">
                                        <i class="fas fa-wrench text-warning mr-1"></i>
                                        <span class="font-weight-bold text-dark text-xs">{{ $selectedMechanicModel->name }}</span>
                                    </div>
                                    <button type="button" class="btn btn-outline-danger btn-xs ml-1" wire:click="clearMechanic" title="Remover Mecánico">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            @else
                                <div class="position-relative">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text"><i class="fas fa-user-cog text-muted"></i></span>
                                        </div>
                                        <input type="text" 
                                               wire:model.live.debounce.250ms="mechanicSearch" 
                                               class="form-control" 
                                               placeholder="Buscar mecánico..."
                                               autocomplete="off">
                                    </div>

                                    @if(strlen(trim($mechanicSearch)) > 0)
                                        <div class="list-group position-absolute w-100 shadow-lg border" style="z-index: 1060; max-height: 180px; overflow-y: auto;">
                                            @forelse($mechanics as $m)
                                                <button type="button" 
                                                        class="list-group-item list-group-item-action p-2 text-left text-xs d-flex justify-content-between align-items-center"
                                                        wire:click="selectMechanic({{ $m->id }})">
                                                    <div>
                                                        <span class="font-weight-bold text-dark">{{ $m->name }}</span>
                                                        <span class="badge badge-warning ml-1 text-xs">Mecánico</span>
                                                    </div>
                                                    <span class="badge badge-primary"><i class="fas fa-check"></i></span>
                                                </button>
                                            @empty
                                                <div class="list-group-item p-2 text-muted text-xs text-center">
                                                    No hay mecánicos registrados con ese nombre.
                                                </div>
                                            @endforelse
                                        </div>
                                    @endif
                                </div>
                                <small class="text-muted text-xs mt-0.5 d-block">
                                    <i class="fas fa-info-circle mr-0.5"></i> Solo usuarios con el rol <strong>Mecánico</strong>.
                                </small>
                            @endif
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

    {{-- MODAL 1.1: NUEVO CLIENTE RÁPIDO --}}
    @if($showQuickCustomerModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.7); z-index: 1060;" tabindex="-1">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-user-plus mr-2 text-primary"></i> Registrar Nuevo Cliente</h5>
                    <button type="button" class="close text-white" wire:click="$set('showQuickCustomerModal', false)">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group">
                        <label class="font-weight-bold text-xs">Nombre o Razón Social <span class="text-danger">*</span></label>
                        <input type="text" wire:model.live="quickCustomer.name" class="form-control" placeholder="Ej: Transportes Los Andes C.A.">
                        @error('quickCustomer.name') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="row">
                        <div class="col-6 form-group">
                            <label class="font-weight-bold text-xs">Cédula / RIF <span class="text-danger">*</span></label>
                            <input type="text" wire:model.live="quickCustomer.taxpayer_id" class="form-control" placeholder="Ej: J-12345678-0">
                            @error('quickCustomer.taxpayer_id') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-6 form-group">
                            <label class="font-weight-bold text-xs">Teléfono</label>
                            <input type="text" wire:model.live="quickCustomer.phone" class="form-control" placeholder="Ej: 0414-1234567">
                            @error('quickCustomer.phone') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="font-weight-bold text-xs">Dirección</label>
                        <input type="text" wire:model.live="quickCustomer.address" class="form-control" placeholder="Ej: El Vigía, Sector Las Cumbres">
                        @error('quickCustomer.address') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showQuickCustomerModal', false)">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm font-weight-bold" wire:click="saveQuickCustomer">
                        <i class="fas fa-save mr-1"></i> Guardar Cliente y Asignar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL 1.2: NUEVO VEHÍCULO RÁPIDO --}}
    @if($showQuickVehicleModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.7); z-index: 1060;" tabindex="-1">
        <div class="modal-dialog modal-md modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-car mr-2 text-success"></i> Registrar Nuevo Vehículo</h5>
                    <button type="button" class="close text-white" wire:click="$set('showQuickVehicleModal', false)">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-6 form-group">
                            <label class="font-weight-bold text-xs">Placa / Patente <span class="text-danger">*</span></label>
                            <input type="text" wire:model.live="quickVehicle.plate" class="form-control text-uppercase" placeholder="Ej: AB123CD">
                            @error('quickVehicle.plate') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-6 form-group">
                            <label class="font-weight-bold text-xs">Año</label>
                            <input type="number" wire:model.live="quickVehicle.year" class="form-control" placeholder="Ej: 2018">
                            @error('quickVehicle.year') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 form-group">
                            <label class="font-weight-bold text-xs">Marca <span class="text-danger">*</span></label>
                            <input type="text" wire:model.live="quickVehicle.brand" class="form-control text-uppercase" placeholder="Ej: TOYOTA">
                            @error('quickVehicle.brand') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-6 form-group">
                            <label class="font-weight-bold text-xs">Modelo <span class="text-danger">*</span></label>
                            <input type="text" wire:model.live="quickVehicle.model" class="form-control text-uppercase" placeholder="Ej: COROLLA">
                            @error('quickVehicle.model') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-6 form-group mb-0">
                            <label class="font-weight-bold text-xs">Color</label>
                            <input type="text" wire:model.live="quickVehicle.color" class="form-control text-uppercase" placeholder="Ej: BLANCO">
                            @error('quickVehicle.color') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-6 form-group mb-0">
                            <label class="font-weight-bold text-xs">Kilometraje Actual</label>
                            <input type="number" wire:model.live="quickVehicle.current_mileage" class="form-control" placeholder="Ej: 85000">
                            @error('quickVehicle.current_mileage') <span class="text-danger text-xs">{{ $message }}</span> @enderror
                        </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="$set('showQuickVehicleModal', false)">Cancelar</button>
                    <button type="button" class="btn btn-success btn-sm font-weight-bold" wire:click="saveQuickVehicle">
                        <i class="fas fa-save mr-1"></i> Guardar Vehículo y Asignar
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- MODAL 2: AGREGAR ÍTEM (REPUESTO O MANO DE OBRA) --}}
    @if($showItemModal)
    <div class="modal fade show d-block" style="background: rgba(0,0,0,0.6); z-index: 1050;" tabindex="-1">
        <div class="modal-dialog {{ $newItem['type'] === 'part' ? 'modal-lg' : 'modal-md' }} modal-dialog-centered">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold"><i class="fas fa-wrench mr-2"></i> Agregar Ítem a Orden</h5>
                    <button type="button" class="close text-white" wire:click="$set('showItemModal', false)">&times;</button>
                </div>
                <div class="modal-body p-4">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold text-xs text-uppercase text-secondary">Tipo de Ítem</label>
                        <div class="btn-group btn-group-toggle w-100" data-toggle="buttons">
                            <button type="button" class="btn btn-outline-primary btn-sm {{ $newItem['type'] === 'labor' ? 'active' : '' }}" wire:click="$set('newItem.type', 'labor')">
                                <i class="fas fa-user-cog mr-1"></i> Mano de Obra / Servicio
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm {{ $newItem['type'] === 'part' ? 'active' : '' }}" wire:click="$set('newItem.type', 'part')">
                                <i class="fas fa-cogs mr-1"></i> Repuesto de Inventario
                            </button>
                        </div>
                    </div>

                    @if($newItem['type'] === 'part')
                        {{-- SELECCIÓN INTELIGENTE DE REPUESTO --}}
                        <div class="form-group mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="font-weight-bold text-xs text-uppercase text-secondary mb-0">
                                    <i class="fas fa-search mr-1 text-primary"></i> Buscar Repuesto en Inventario
                                </label>
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" 
                                            class="btn btn-xs {{ $productViewMode === 'list' ? 'btn-primary' : 'btn-outline-secondary' }}" 
                                            wire:click="$set('productViewMode', 'list')" 
                                            title="Vista Lista">
                                        <i class="fas fa-list"></i>
                                    </button>
                                    <button type="button" 
                                            class="btn btn-xs {{ $productViewMode === 'grid' ? 'btn-primary' : 'btn-outline-secondary' }}" 
                                            wire:click="$set('productViewMode', 'grid')" 
                                            title="Vista Cuadrícula">
                                        <i class="fas fa-th-large"></i>
                                    </button>
                                </div>
                            </div>

                            @if($selectedProductModel)
                                {{-- TARJETA DE REPUESTO SELECCIONADO --}}
                                @php
                                    $selPriceUSD = (float)$newItem['unit_price'];
                                    $bsCurr = $currencies->first(fn($c) => in_array($c->code, ['VES', 'VED']) || str_contains($c->symbol, 'Bs'));
                                    $copCurr = $currencies->firstWhere('code', 'COP');
                                    $bcvRate = $sysConfig && $sysConfig->bcv_rate > 0 ? (float)$sysConfig->bcv_rate : 0;
                                    $selPriceBCV = ($bsCurr && $bcvRate > 0) ? (($selPriceUSD * (float)$bsCurr->exchange_rate) / $bcvRate) : 0;
                                @endphp
                                <div class="p-3 border rounded bg-light d-flex justify-content-between align-items-center shadow-sm">
                                    <div class="d-flex align-items-center">
                                        <img src="{{ !empty($selectedProductModel->photo) ? asset($selectedProductModel->photo) : asset('noimage.jpg') }}" 
                                             alt="{{ $selectedProductModel->name }}" 
                                             class="rounded mr-3 border bg-white" 
                                             onerror="this.src='{{ asset('noimage.jpg') }}'; this.onerror=null;" 
                                             style="width: 54px; height: 54px; object-fit: cover;">
                                        <div>
                                            <div class="font-weight-bold text-dark" style="font-size: 0.95rem;">{{ $selectedProductModel->name }}</div>
                                            <div class="text-xs text-muted mt-0.5">
                                                <span class="badge badge-secondary mr-1">SKU: {{ $selectedProductModel->sku }}</span>
                                                <span class="badge {{ $selectedProductModel->stock_qty > 0 ? 'badge-success' : 'badge-danger' }}">
                                                    Stock: {{ $selectedProductModel->stock_qty }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-right d-flex align-items-center">
                                        <div class="mr-3 text-right">
                                            <span class="font-weight-bold text-dark d-block" style="font-size: 1.15rem;">${{ formatMoney($selPriceUSD) }}</span>
                                            @if($selPriceBCV > 0)
                                                <span class="text-danger font-weight-bold d-block" style="font-size: 0.75rem;">
                                                    ${{ formatMoney($selPriceBCV) }} USD/BCV
                                                </span>
                                            @endif
                                            <div class="text-muted" style="font-size: 0.70rem; line-height: 1.15;">
                                                @if($bsCurr)
                                                    <div>{{ $bsCurr->symbol }}{{ formatMoney($selPriceUSD * (float)$bsCurr->exchange_rate) }} {{ $bsCurr->code }}</div>
                                                @endif
                                                @if($copCurr)
                                                    <div>{{ $copCurr->symbol }}{{ formatMoney($selPriceUSD * (float)$copCurr->exchange_rate) }} {{ $copCurr->code }}</div>
                                                @endif
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-outline-danger btn-sm" wire:click="clearSelectedProduct" title="Cambiar Repuesto">
                                            <i class="fas fa-times"></i> Cambiar
                                        </button>
                                    </div>
                                </div>
                            @else
                                {{-- CAMPO DE BÚSQUEDA INTELIGENTE --}}
                                <div class="position-relative">
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                        </div>
                                        <input type="text" 
                                               wire:model.live.debounce.250ms="productSearch" 
                                               class="form-control" 
                                               placeholder="Escribe código SKU, nombre o palabras clave en cualquier orden..."
                                               autocomplete="off">
                                        @if(!empty($productSearch))
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" wire:click="$set('productSearch', '')">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- RESULTADOS DE BÚSQUEDA --}}
                                    @if(strlen(trim($productSearch)) > 0)
                                        @php
                                            $bsCurr = $currencies->first(fn($c) => in_array($c->code, ['VES', 'VED']) || str_contains($c->symbol, 'Bs'));
                                            $copCurr = $currencies->firstWhere('code', 'COP');
                                            $bcvRate = $sysConfig && $sysConfig->bcv_rate > 0 ? (float)$sysConfig->bcv_rate : 0;
                                        @endphp

                                        @if($productViewMode === 'list')
                                            {{-- MODO LISTA (CON MINIATURAS Y MULTIMONEDA) --}}
                                            <div class="list-group position-absolute w-100 shadow-lg border mt-1" style="z-index: 1060; max-height: 280px; overflow-y: auto;">
                                                @forelse($products as $p)
                                                    @php
                                                        $stockClass = $p->stock_qty <= 0 ? 'badge-danger' : ($p->stock_qty <= ($p->low_stock ?: 5) ? 'badge-warning' : 'badge-success');
                                                        $priceUSD = (float)$p->price;
                                                        $priceBCV = ($bsCurr && $bcvRate > 0) ? (($priceUSD * (float)$bsCurr->exchange_rate) / $bcvRate) : 0;
                                                    @endphp
                                                    <button type="button" 
                                                            class="list-group-item list-group-item-action p-2 text-left d-flex justify-content-between align-items-center"
                                                            wire:click="onProductSelected({{ $p->id }})">
                                                        <div class="d-flex align-items-center">
                                                            <img src="{{ !empty($p->photo) ? asset($p->photo) : asset('noimage.jpg') }}" 
                                                                 alt="{{ $p->name }}" 
                                                                 class="rounded mr-2 border bg-white" 
                                                                 onerror="this.src='{{ asset('noimage.jpg') }}'; this.onerror=null;" 
                                                                 style="width: 44px; height: 44px; object-fit: cover;">
                                                            <div>
                                                                <span class="font-weight-bold text-dark text-xs d-block">{{ $p->name }}</span>
                                                                <small class="text-muted text-xs">
                                                                    <span class="badge badge-light border mr-1">SKU: {{ $p->sku }}</span>
                                                                    <span class="badge {{ $stockClass }}">Stock: {{ $p->stock_qty }}</span>
                                                                </small>
                                                            </div>
                                                        </div>
                                                        <div class="text-right">
                                                            <span class="font-weight-bold text-dark text-xs d-block" style="font-size: 0.9rem;">${{ formatMoney($priceUSD) }}</span>
                                                            @if($priceBCV > 0)
                                                                <span class="text-danger font-weight-bold d-block" style="font-size: 0.72rem;">
                                                                    ${{ formatMoney($priceBCV) }} USD/BCV
                                                                </span>
                                                            @endif
                                                            <div class="text-muted" style="font-size: 0.68rem; line-height: 1.1;">
                                                                @if($bsCurr)
                                                                    <span>{{ $bsCurr->symbol }}{{ formatMoney($priceUSD * (float)$bsCurr->exchange_rate) }} {{ $bsCurr->code }}</span>
                                                                @endif
                                                                @if($copCurr)
                                                                    <span class="ml-1">{{ $copCurr->symbol }}{{ formatMoney($priceUSD * (float)$copCurr->exchange_rate) }} {{ $copCurr->code }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </button>
                                                @empty
                                                    <div class="list-group-item p-3 text-muted text-xs text-center">
                                                        <i class="fas fa-box-open mr-1"></i> No se encontraron repuestos con "{{ $productSearch }}"
                                                    </div>
                                                @endforelse
                                            </div>
                                        @else
                                            {{-- MODO CUADRÍCULA (GRID EXACTO AL POS CON FOTOS, STOCK Y MULTIMONEDA) --}}
                                            <div class="position-absolute w-100 shadow-lg border bg-white p-2 mt-1 rounded" style="z-index: 1060; max-height: 340px; overflow-y: auto;">
                                                <div class="row no-gutters">
                                                    @forelse($products as $p)
                                                        @php
                                                            $stockClass = $p->stock_qty <= 0 ? 'badge-danger' : ($p->stock_qty <= ($p->low_stock ?: 5) ? 'badge-warning' : 'badge-success');
                                                            $priceUSD = (float)$p->price;
                                                            $priceBCV = ($bsCurr && $bcvRate > 0) ? (($priceUSD * (float)$bsCurr->exchange_rate) / $bcvRate) : 0;
                                                        @endphp
                                                        <div class="col-6 col-md-4 p-1">
                                                            <div class="card h-100 mb-0 border shadow-sm p-2 text-left" 
                                                                 wire:click="onProductSelected({{ $p->id }})" 
                                                                 style="cursor: pointer; transition: all 0.15s ease;"
                                                                 onmouseover="this.style.borderColor='#007bff'; this.style.boxShadow='0 4px 12px rgba(0,0,0,0.12)';"
                                                                 onmouseout="this.style.borderColor='#dee2e6'; this.style.boxShadow='none';">
                                                                <div class="position-relative text-center mb-1">
                                                                    <img src="{{ !empty($p->photo) ? asset($p->photo) : asset('noimage.jpg') }}" 
                                                                         alt="{{ $p->name }}" 
                                                                         class="img-fluid rounded" 
                                                                         onerror="this.src='{{ asset('noimage.jpg') }}'; this.onerror=null;" 
                                                                         style="height: 85px; width: 100%; object-fit: cover;">
                                                                    <span class="badge {{ $stockClass }} position-absolute font-weight-bold" style="top: 4px; right: 4px; font-size: 10px; border-radius: 10px;">
                                                                        Stock: {{ $p->stock_qty }}
                                                                    </span>
                                                                </div>
                                                                <div class="font-weight-bold text-dark text-xs text-truncate" title="{{ $p->name }}">
                                                                    {{ $p->name }}
                                                                </div>
                                                                <div class="text-muted text-xs small mb-1">{{ $p->sku }}</div>
                                                                <div class="mt-auto pt-1 border-top">
                                                                    <div class="font-weight-bold text-dark" style="font-size: 0.95rem;">
                                                                        ${{ formatMoney($priceUSD) }}
                                                                    </div>
                                                                    @if($priceBCV > 0)
                                                                        <div class="text-danger font-weight-bold" style="font-size: 0.72rem; line-height: 1.1;">
                                                                            ${{ formatMoney($priceBCV) }} USD/BCV
                                                                        </div>
                                                                    @endif
                                                                    <div class="text-muted mt-0.5" style="font-size: 0.67rem; line-height: 1.15;">
                                                                        @if($bsCurr)
                                                                            <div>{{ $bsCurr->symbol }}{{ formatMoney($priceUSD * (float)$bsCurr->exchange_rate) }} {{ $bsCurr->code }}</div>
                                                                        @endif
                                                                        @if($copCurr)
                                                                            <div>{{ $copCurr->symbol }}{{ formatMoney($priceUSD * (float)$copCurr->exchange_rate) }} {{ $copCurr->code }}</div>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    @empty
                                                        <div class="col-12 p-3 text-muted text-xs text-center">
                                                            <i class="fas fa-box-open mr-1"></i> No se encontraron repuestos con "{{ $productSearch }}"
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endif
                                    @endif
                                </div>
                                <small class="text-muted text-xs mt-1 d-block">
                                    <i class="fas fa-info-circle mr-1"></i> Precios calculados con la tasa activa del sistema. Si el cliente tiene margen diferencial, se aplica automáticamente al seleccionarlo.
                                </small>
                            @endif
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
                                    <option value="">{{ count($mechanics) > 0 ? 'Seleccione especialista...' : 'No hay usuarios con rol Mecánico' }}</option>
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