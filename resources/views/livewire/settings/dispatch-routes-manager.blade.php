<div>
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-md-12 col-12 layout-spacing">
            <div class="widget-content-area br-4">
                <div class="widget-header">
                    <div class="row align-items-center mb-3">
                        <div class="col-sm-8">
                            <h4 class="mb-0"><i class="fas fa-truck text-primary me-2"></i> Rutas y Ventanas de Despacho Web</h4>
                            <small class="text-muted">Define los días de entrega y las horas límite de corte (cutoff) para los clientes del portal web.</small>
                        </div>
                        <div class="col-sm-4 text-right">
                            <button type="button" class="btn btn-primary" wire:click="openCreateModal">
                                <i class="fas fa-plus me-1"></i> Nueva Ruta
                            </button>
                        </div>
                    </div>
                </div>

                <div class="widget-content widget-content-area">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mt-1">
                            <thead class="text-white" style="background: #3b3f5c">
                                <tr>
                                    <th class="text-white">Nombre de la Ruta</th>
                                    <th class="text-white text-center">Tipo</th>
                                    <th class="text-white text-center">Día de Salida</th>
                                    <th class="text-white text-center">Día de Corte</th>
                                    <th class="text-white text-center">Hora Límite de Corte</th>
                                    <th class="text-white text-center">Clientes Asignados</th>
                                    <th class="text-white text-center">Estado</th>
                                    <th class="text-white text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($routes as $r)
                                    <tr>
                                        <td>
                                            <strong>{{ $r->name }}</strong>
                                            @if($r->description)
                                                <br><small class="text-muted">{{ $r->description }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($r->route_type === 'fixed_route')
                                                <span class="badge badge-primary">Ruta Foránea Fija</span>
                                            @elseif($r->route_type === 'local')
                                                <span class="badge badge-info">Reparto Local</span>
                                            @elseif($r->route_type === 'pickup')
                                                <span class="badge badge-secondary">Retiro en Sede</span>
                                            @endif
                                        </td>
                                        <td class="text-center font-weight-bold">
                                            {{ $r->dispatch_day ? ucfirst($r->dispatch_day) : 'N/A' }}
                                        </td>
                                        <td class="text-center">
                                            {{ $r->cutoff_day ? ucfirst($r->cutoff_day) : 'N/A' }}
                                        </td>
                                        <td class="text-center font-weight-bold text-danger">
                                            {{ substr($r->cutoff_time, 0, 5) }}
                                        </td>
                                        <td class="text-center font-weight-bold">
                                            <span class="badge badge-dark">{{ $r->customers_count }}</span>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" wire:click="toggleActive({{ $r->id }})" class="btn btn-sm {{ $r->is_active ? 'btn-success' : 'btn-outline-secondary' }}">
                                                {{ $r->is_active ? 'Activa' : 'Inactiva' }}
                                            </button>
                                        </td>
                                        <td class="text-center">
                                            <button type="button" wire:click="edit({{ $r->id }})" class="btn btn-warning btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            @if($r->customers_count === 0)
                                                <button type="button" wire:click="delete({{ $r->id }})" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Seguro que deseas eliminar esta ruta?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            No hay rutas de despacho configuradas.
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Formulario Ruta -->
    @if($showModal)
        <div class="modal fade show d-block" tabindex="-1" style="background: rgba(0,0,0,0.6);" role="dialog">
            <div class="modal-dialog modal-dialog-centered" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title text-white">
                            {{ $isEditing ? 'Editar Ruta de Despacho' : 'Nueva Ruta de Despacho' }}
                        </h5>
                        <button type="button" class="close text-white" wire:click="$set('showModal', false)">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Nombre de la Ruta *</label>
                            <input type="text" class="form-control" wire:model="name" placeholder="Ej: Ruta Foránea (Jueves)">
                            @error('name') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Tipo de Despacho *</label>
                            <select class="form-control" wire:model.live="route_type">
                                <option value="fixed_route">Ruta Semanal Fija (Foránea / Zonas Aledañas)</option>
                                <option value="local">Reparto Local Programado</option>
                                <option value="pickup">Retiro en Sede / Planta</option>
                            </select>
                            @error('route_type') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        @if($route_type !== 'pickup')
                            <div class="row">
                                <div class="col-md-6 form-group mb-3">
                                    <label class="font-weight-bold">Día de Salida / Viaje *</label>
                                    <select class="form-control" wire:model="dispatch_day">
                                        <option value="monday">Lunes</option>
                                        <option value="tuesday">Martes</option>
                                        <option value="wednesday">Miércoles</option>
                                        <option value="thursday">Jueves</option>
                                        <option value="friday">Viernes</option>
                                        <option value="saturday">Sábado</option>
                                        <option value="sunday">Domingo</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group mb-3">
                                    <label class="font-weight-bold">Día de Corte de Pedidos *</label>
                                    <select class="form-control" wire:model="cutoff_day">
                                        <option value="monday">Lunes</option>
                                        <option value="tuesday">Martes</option>
                                        <option value="wednesday">Miércoles</option>
                                        <option value="thursday">Jueves</option>
                                        <option value="friday">Viernes</option>
                                        <option value="saturday">Sábado</option>
                                        <option value="sunday">Domingo</option>
                                    </select>
                                </div>
                            </div>
                        @endif

                        <div class="form-group mb-3">
                            <label class="font-weight-bold">Hora Límite de Corte (Cutoff Time) *</label>
                            <input type="time" class="form-control" wire:model="cutoff_time">
                            <small class="text-muted">A partir de esta hora el pedido web se programará para el siguiente viaje.</small>
                            @error('cutoff_time') <br><span class="text-danger small">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group mb-3">
                            <label>Descripción / Observación (Opcional)</label>
                            <textarea class="form-control" wire:model="description" rows="2" placeholder="Ej: Se carga los miércoles por la tarde..."></textarea>
                        </div>

                        <div class="form-group form-check mb-0">
                            <input type="checkbox" class="form-check-input" id="isActiveCheck" wire:model="is_active">
                            <label class="form-check-label font-weight-bold" for="isActiveCheck">Ruta Activa</label>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="$set('showModal', false)">Cancelar</button>
                        <button type="button" class="btn btn-primary" wire:click="save">Guardar Ruta</button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
