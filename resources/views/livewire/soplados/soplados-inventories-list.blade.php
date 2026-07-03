<div>
    <div class="row sales layout-top-spacing">
        <div class="col-sm-12">
            <div class="widget widget-chart-one">
                <div class="widget-heading">
                    <h4 class="card-title">
                        <b>Fábrica Soplados | Historial de Inventarios Físicos</b>
                    </h4>
                </div>

                <div class="row justify-content-between mb-4">
                    <div class="col-lg-3 col-md-3 col-sm-12">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" wire:model.live="search" placeholder="Buscar supervisor/operario..." class="form-control">
                        </div>
                    </div>

                    <div class="col-lg-3 col-md-3 col-sm-12">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Estado</span>
                            </div>
                            <select wire:model.live="status" class="form-control">
                                <option value="all">Todos los Estados</option>
                                <option value="pending_acceptance">Pendientes de Aceptación</option>
                                <option value="completed">Completados</option>
                            </select>
                        </div>
                    </div>

                    <div class="col-lg-6 col-md-6 col-sm-12 d-flex">
                        <div class="input-group mr-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Desde</span>
                            </div>
                            <input type="date" wire:model.live="dateFrom" class="form-control">
                        </div>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Hasta</span>
                            </div>
                            <input type="date" wire:model.live="dateTo" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="widget-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mt-1">
                            <thead class="text-white" style="background: #3B3F5C">
                                <tr>
                                    <th class="table-th text-white text-center">ID</th>
                                    <th class="table-th text-white text-center">SUPERVISOR (CONTO)</th>
                                    <th class="table-th text-white text-center">OPERARIO (CONFIRMO)</th>
                                    <th class="table-th text-white text-center">ALMACÉN</th>
                                    <th class="table-th text-white text-center">FECHA CONTEO</th>
                                    <th class="table-th text-white text-center">FECHA CONFIRMACIÓN</th>
                                    <th class="table-th text-white text-center">ESTADO</th>
                                    <th class="table-th text-white text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($inventories as $inventory)
                                <tr>
                                    <td class="text-center"><h6>#{{ $inventory->id }}</h6></td>
                                    <td class="text-center"><h6>{{ $inventory->supervisor->name }}</h6></td>
                                    <td class="text-center">
                                        <h6>
                                            @if($inventory->operator)
                                                {{ $inventory->operator->name }}
                                            @else
                                                <span class="text-warning font-weight-bold">Pendiente</span>
                                            @endif
                                        </h6>
                                    </td>
                                    <td class="text-center"><h6>{{ $inventory->warehouse->name ?? 'N/A' }}</h6></td>
                                    <td class="text-center"><h6>{{ $inventory->created_at->format('d-m-Y H:i') }}</h6></td>
                                    <td class="text-center">
                                        <h6>
                                            {{ $inventory->accepted_at ? $inventory->accepted_at->format('d-m-Y H:i') : '-' }}
                                        </h6>
                                    </td>
                                    <td class="text-center font-weight-bold">
                                        @if($inventory->status == 'completed')
                                            <span class="badge badge-success px-2 py-1">COMPLETADO</span>
                                        @elseif($inventory->status == 'pending_acceptance')
                                            <span class="badge badge-warning px-2 py-1">PENDIENTE DE FIRMA</span>
                                        @else
                                            <span class="badge badge-danger px-2 py-1">{{ strtoupper($inventory->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button wire:click="viewDetails({{ $inventory->id }})" class="btn btn-dark btn-sm">
                                            <i class="fas fa-eye mr-1"></i> Detalles
                                        </button>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center">No se encontraron inventarios en este rango de fechas.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        {{ $inventories->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal for details -->
    @if($showModal && $selectedInventory)
    <div class="modal fade show" style="display: block; background-color: rgba(0,0,0,0.5); z-index: 1050; overflow-y: auto;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title font-weight-bold">
                        <i class="fas fa-info-circle mr-2 text-warning"></i>
                        Detalle de Inventario #{{ $selectedInventory->id }}
                    </h5>
                    <button type="button" class="close text-white" wire:click="closeModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <!-- Cabecera de datos -->
                    <div class="row mb-4 p-3 bg-light rounded shadow-sm mx-1">
                        <div class="col-md-3">
                            <strong>Supervisor (Contó):</strong><br>
                            {{ $selectedInventory->supervisor->name }}
                        </div>
                        <div class="col-md-3">
                            <strong>Operario (Aceptó):</strong><br>
                            @if($selectedInventory->operator)
                                {{ $selectedInventory->operator->name }}
                            @else
                                <span class="badge badge-warning">Esperando confirmación...</span>
                            @endif
                        </div>
                        <div class="col-md-3">
                            <strong>Depósito / Almacén:</strong><br>
                            {{ $selectedInventory->warehouse->name ?? 'N/A' }}
                        </div>
                        <div class="col-md-3">
                            <strong>Turno Asociado:</strong><br>
                            {{ $selectedInventory->shift ? $selectedInventory->shift->type . ' (#' . $selectedInventory->shift->id . ')' : 'Ninguno' }}
                        </div>
                    </div>

                    <!-- Notas -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card card-outline card-info h-100 shadow-none">
                                <div class="card-header font-weight-bold">Observaciones del Supervisor:</div>
                                <div class="card-body py-2">
                                    <p class="card-text text-muted font-italic">
                                        {{ $selectedInventory->notes ?: 'Sin observaciones de conteo.' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card card-outline card-success h-100 shadow-none">
                                <div class="card-header font-weight-bold">Conformidad del Operador:</div>
                                <div class="card-body py-2">
                                    @if($selectedInventory->accepted_at)
                                        <div class="text-success font-weight-bold mb-1">
                                            <i class="fas fa-check-circle mr-1"></i> Aceptado de conformidad el {{ $selectedInventory->accepted_at->format('d-m-Y H:i') }}
                                        </div>
                                        <p class="card-text text-muted font-italic">
                                            {{ $selectedInventory->operator_notes ?: 'Aceptado sin observaciones adicionales.' }}
                                        </p>
                                    @else
                                        <p class="text-warning font-weight-bold font-italic">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> El operario a cargo aún no ha firmado de conformidad.
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Detalles -->
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th rowspan="2" class="align-middle">Producto / Insumo</th>
                                    <th rowspan="2" class="align-middle text-center">Tipo</th>
                                    <th colspan="3" class="text-center bg-primary text-white">Primera Calidad / Insumos</th>
                                    <th colspan="3" class="text-center bg-info text-white">Segunda Calidad (Si aplica)</th>
                                    <th colspan="3" class="text-center bg-danger text-white">Merma (Dañados)</th>
                                </tr>
                                <tr>
                                    <th class="text-center">Sist.</th>
                                    <th class="text-center">Fís.</th>
                                    <th class="text-center">Dif.</th>
                                    <th class="text-center">Sist.</th>
                                    <th class="text-center">Fís.</th>
                                    <th class="text-center">Dif.</th>
                                    <th class="text-center">Sist.</th>
                                    <th class="text-center">Fís.</th>
                                    <th class="text-center">Dif.</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedInventory->details as $d)
                                    <tr>
                                        <td>
                                            <strong>{{ $d->product->name }}</strong><br>
                                            <small class="text-muted">SKU: {{ $d->product->sku ?: 'N/A' }}</small>
                                        </td>
                                        <td class="text-center align-middle">
                                            <span class="badge {{ $d->type == 'finished_product' ? 'badge-primary' : 'badge-secondary' }} px-2">
                                                {{ $d->type == 'finished_product' ? 'Botellón' : 'Insumo' }}
                                            </span>
                                        </td>
                                        
                                        <!-- Primera / Insumos -->
                                        <td class="text-center align-middle">{{ $d->system_stock_primera }}</td>
                                        <td class="text-center align-middle font-weight-bold text-primary">{{ $d->counted_primera }}</td>
                                        <td class="text-center align-middle font-weight-bold">
                                            @if($d->difference_primera == 0)
                                                <span class="text-muted">0</span>
                                            @elseif($d->difference_primera > 0)
                                                <span class="text-success">+{{ $d->difference_primera }}</span>
                                            @else
                                                <span class="text-danger">{{ $d->difference_primera }}</span>
                                            @endif
                                        </td>
 
                                        <!-- Segunda -->
                                        @if($d->type == 'finished_product' && $d->system_stock_segunda !== null)
                                            <td class="text-center align-middle">{{ $d->system_stock_segunda }}</td>
                                            <td class="text-center align-middle font-weight-bold text-info">{{ $d->counted_segunda }}</td>
                                            <td class="text-center align-middle font-weight-bold">
                                                @if($d->difference_segunda == 0)
                                                    <span class="text-muted">0</span>
                                                @elseif($d->difference_segunda > 0)
                                                    <span class="text-success">+{{ $d->difference_segunda }}</span>
                                                @else
                                                    <span class="text-danger">{{ $d->difference_segunda }}</span>
                                                @endif
                                            </td>
                                        @else
                                            <td colspan="3" class="text-center text-muted align-middle bg-light">-</td>
                                        @endif
 
                                        <!-- Merma -->
                                        @if($d->type == 'finished_product')
                                            <td class="text-center align-middle">{{ $d->system_stock_merma !== null ? $d->system_stock_merma : 0 }}</td>
                                            <td class="text-center align-middle font-weight-bold text-danger">{{ $d->counted_merma !== null ? $d->counted_merma : 0 }}</td>
                                            <td class="text-center align-middle font-weight-bold">
                                                @php
                                                    $diffMerma = $d->difference_merma !== null ? $d->difference_merma : (($d->counted_merma ?? 0) - ($d->system_stock_merma ?? 0));
                                                @endphp
                                                @if($diffMerma == 0)
                                                    <span class="text-muted">0</span>
                                                @elseif($diffMerma > 0)
                                                    <span class="text-success">+{{ $diffMerma }}</span>
                                                @else
                                                    <span class="text-danger">{{ $diffMerma }}</span>
                                                @endif
                                            </td>
                                        @else
                                            <td colspan="3" class="text-center text-muted align-middle bg-light">-</td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary px-4" wire:click="closeModal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
