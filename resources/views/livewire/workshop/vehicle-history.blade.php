<div>
    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-center mb-3">
        <div>
            <h3 class="font-weight-bold text-dark mb-0">
                <i class="fas fa-history text-info mr-2"></i> Historial Clínico y Ficha del Vehículo
            </h3>
            <small class="text-muted">Consulte todas las intervenciones, reparaciones, repuestos instalados y kilometraje por placa</small>
        </div>
    </div>

    {{-- Buscador de Vehículo --}}
    <div class="card shadow-sm mb-3">
        <div class="card-body p-3">
            <label class="font-weight-bold text-xs text-uppercase mb-2">Buscar Vehículo por Placa, Marca o Modelo:</label>
            <div class="input-group">
                <input type="text" wire:model.live.debounce.300ms="searchPlate" wire:keydown.enter="searchVehicle" class="form-control text-uppercase" placeholder="Ej: ABC-123, 24T LAH, Hilux, Fortaleza...">
                <div class="input-group-append">
                    <button type="button" wire:click="searchVehicle" class="btn btn-primary">
                        <i class="fas fa-search mr-1"></i> Buscar
                    </button>
                </div>
            </div>

            @if(!empty($vehicles) && !$selectedVehicleId)
                <div class="list-group mt-2 border shadow-sm">
                    @foreach($vehicles as $v)
                        <a href="javascript:void(0)" wire:click="selectVehicle({{ $v->id }})" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge badge-dark text-sm mr-2">{{ $v->plate }}</span>
                                <strong class="text-dark">{{ $v->brand }} {{ $v->model }} ({{ $v->year }})</strong>
                                <small class="text-muted ml-2">• Dueño: {{ $v->customer->name ?? 'N/A' }}</small>
                            </div>
                            <span class="badge badge-primary badge-pill">Ver Ficha &rarr;</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    @if($history && $history['vehicle'])
        {{-- Ficha del Vehículo --}}
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card shadow-sm border-left border-primary h-100">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase font-weight-bold d-block">Placa Identificadora</small>
                        <h2 class="font-weight-bold text-primary mb-1">{{ $history['vehicle']->plate }}</h2>
                        <div class="font-weight-bold text-sm">{{ $history['vehicle']->brand }} {{ $history['vehicle']->model }} ({{ $history['vehicle']->year }})</div>
                        <small class="text-muted">Color: {{ $history['vehicle']->color ?? 'No registrado' }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-left border-info h-100">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase font-weight-bold d-block">Propietario / Empresa</small>
                        <h5 class="font-weight-bold text-dark mt-1 mb-1">{{ $history['vehicle']->customer->name ?? 'N/A' }}</h5>
                        <div class="text-muted text-xs">{{ $history['vehicle']->customer->taxpayer_id ?? '' }}</div>
                        <small class="text-muted">{{ $history['vehicle']->customer->phone ?? '' }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-left border-warning h-100">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase font-weight-bold d-block">Odómetro Registrado</small>
                        <h3 class="font-weight-bold text-dark mt-1 mb-0">{{ number_format($history['vehicle']->current_mileage ?? 0) }} km</h3>
                        <small class="text-muted">Total Visitas al Taller: {{ $history['total_visits'] }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card shadow-sm border-left border-success h-100">
                    <div class="card-body p-3">
                        <small class="text-muted text-uppercase font-weight-bold d-block">Inversión Total en Taller</small>
                        <h3 class="font-weight-bold text-success mt-1 mb-0">${{ number_format($history['total_spent'], 2) }}</h3>
                        <small class="text-muted">Rep: ${{ number_format($history['total_parts_spent'], 2) }} | MO: ${{ number_format($history['total_labor_spent'], 2) }}</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Lista de Órdenes Históricas --}}
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header p-2">
                <h5 class="card-title font-weight-bold mb-0">Historial Cronológico de Órdenes ({{ $history['orders']->count() }})</h5>
            </div>
            <div class="card-body p-3">
                @forelse($history['orders'] as $ord)
                    <div class="card border mb-3 shadow-none">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center p-2">
                            <div>
                                <span class="badge badge-primary font-weight-bold mr-2">{{ $ord->order_number }}</span>
                                <small class="text-muted mr-2">{{ $ord->created_at->format('d/m/Y H:i') }}</small>
                                @php
                                    $badges = [
                                        'reception' => 'badge-warning',
                                        'in_progress' => 'badge-primary',
                                        'completed' => 'badge-success',
                                        'delivered' => 'badge-info',
                                        'invoiced' => 'badge-dark',
                                    ];
                                @endphp
                                <span class="badge {{ $badges[$ord->status] ?? 'badge-secondary' }}">{{ ucfirst($ord->status) }}</span>
                            </div>
                            <div>
                                <span class="mr-3 font-weight-bold text-success">Total: ${{ number_format($ord->total, 2) }}</span>
                                <a href="{{ route('workshop.work-orders.pdf', $ord->id) }}" target="_blank" class="btn btn-outline-danger btn-xs">
                                    <i class="fas fa-file-pdf"></i> Ficha PDF
                                </a>
                            </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="mb-2 p-2 bg-light rounded text-xs">
                                <strong>Diagnóstico / Motivo de Ingreso:</strong> {{ $ord->initial_diagnosis }}
                                @if($ord->entry_mileage)
                                    <span class="text-muted ml-2">• Odómetro: {{ number_format($ord->entry_mileage) }} km</span>
                                @endif
                            </div>
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0 text-xs">
                                    <thead class="bg-light">
                                        <tr>
                                            <th>Tipo</th>
                                            <th>Descripción</th>
                                            <th>Especialista</th>
                                            <th class="text-center">Cant.</th>
                                            <th class="text-right">Precio</th>
                                            <th class="text-right">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($ord->items as $it)
                                            <tr>
                                                <td><span class="badge {{ $it->type === 'part' ? 'badge-primary' : 'badge-warning' }}">{{ $it->type === 'part' ? 'Repuesto' : 'Mano de Obra' }}</span></td>
                                                <td>{{ $it->description }}</td>
                                                <td>{{ $it->mechanic->name ?? '-' }}</td>
                                                <td class="text-center">{{ $it->quantity }}</td>
                                                <td class="text-right">${{ number_format($it->unit_price, 2) }}</td>
                                                <td class="text-right font-weight-bold">${{ number_format($it->subtotal, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-4 text-muted">
                        No hay reparaciones u órdenes previas para este vehículo.
                    </div>
                @endforelse
            </div>
        </div>
    @elseif(!empty($searchPlate))
        <div class="card shadow-sm p-4 text-center text-muted">
            No se encontró ningún vehículo con la placa ingresada.
        </div>
    @endif
</div>