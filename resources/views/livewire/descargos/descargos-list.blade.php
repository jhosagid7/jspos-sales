<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>Listado de Descargos / Ajustes de Salida</b>
                </h4>
                <ul class="tabs tab-pills">
                    <li>
                        <a href="{{ route('descargos.create') }}" class="tabmenu bg-dark text-white">Nuevo Descargo</a>
                    </li>
                </ul>
            </div>

            <div class="widget-content widget-content-area">
                <div class="row mb-4">
                    <div class="col-sm-12 col-md-3">
                        <div class="form-group">
                            <label>Buscar</label>
                            <input type="text" wire:model.live="search" class="form-control" placeholder="Motivo, Autorizado, ID...">
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <div class="form-group">
                            <label>Depósito</label>
                            <select wire:model.live="warehouse_id" class="form-control">
                                <option value="">Todos</option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <div class="form-group">
                            <label>Desde</label>
                            <input type="date" wire:model.live="dateFrom" class="form-control">
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <div class="form-group">
                            <label>Hasta</label>
                            <input type="date" wire:model.live="dateTo" class="form-control">
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mt-1">
                        <thead class="text-white" style="background: #3b3f5c">
                            <tr>
                                <th class="table-th text-white">ID</th>
                                <th class="table-th text-white">FECHA</th>
                                <th class="table-th text-white">DEPÓSITO</th>
                                <th class="table-th text-white">MOTIVO</th>
                                <th class="table-th text-white">AUTORIZADO POR</th>
                                <th class="table-th text-white">RESPONSABLE</th>
                                <th class="table-th text-white">ESTADO</th>
                                <th class="table-th text-white text-center">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($descargos as $descargo)
                                <tr>
                                    <td>{{ $descargo->id }}</td>
                                    <td>{{ $descargo->date->format('d/m/Y H:i') }}</td>
                                    <td>{{ $descargo->warehouse->name }}</td>
                                    <td>{{ $descargo->motive }}</td>
                                    <td>{{ $descargo->authorized_by }}</td>
                                    <td>{{ $descargo->user->name }}</td>
                                    <td class="text-center">
                                         @php
                                            $badgeClass = 'warning';
                                            $statusLabel = 'Pendiente';
                                            if($descargo->status == 'approved') { $badgeClass = 'success'; $statusLabel = 'Aprobado'; }
                                            elseif($descargo->status == 'rejected') { $badgeClass = 'danger'; $statusLabel = 'Rechazado'; }
                                            elseif($descargo->status == 'voided') { $badgeClass = 'secondary'; $statusLabel = 'Eliminado'; }
                                        @endphp
                                        <span class="badge badge-{{ $badgeClass }} text-uppercase">
                                            {{ $statusLabel }}
                                        </span>
                                        
                                        @if($descargo->status == 'pending')
                                            <div class="mt-2 text-center">
                                                @can('adjustments.approve_descargo')
                                                    <button wire:click="approve({{ $descargo->id }})" class="btn btn-dark btn-sm" title="Aprobar"
                                                        onclick="confirm('¿Confirmas aprobar este descargo? El stock se actualizará.') || event.stopImmediatePropagation()">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                @endcan
                                                
                                                @can('adjustments.reject_descargo')
                                                    <button wire:click="openActionModal({{ $descargo->id }}, 'reject')" class="btn btn-danger btn-sm" title="Rechazar">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        @endif

                                        @if($descargo->status == 'approved')
                                            @can('adjustments.delete_descargo')
                                                <button wire:click="openActionModal({{ $descargo->id }}, 'delete')" class="btn btn-outline-danger btn-sm mt-1" title="Eliminar/Anular">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endcan
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button wire:click="getDescargoDetail({{ $descargo->id }})" class="btn btn-dark btn-sm" title="Ver Detalles">
                                            <i class="fas fa-list"></i>
                                        </button>
                                        @if($descargo->status == 'pending')
                                            <a href="{{ route('descargos.edit', $descargo->id) }}" class="btn btn-info btn-sm" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        @endif
                                        <a href="{{ route('descargos.create', ['clone_id' => $descargo->id]) }}" class="btn btn-outline-info btn-sm" title="Clonar">
                                            <i class="fas fa-copy"></i>
                                        </a>
                                        <a href="{{ route('descargos.pdf', $descargo->id) }}" class="btn btn-outline-danger btn-sm" target="_blank" title="PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No hay descargos registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $descargos->links() }}
                </div>
            </div>
        </div>
    </div>
    
    @include('livewire.descargos.descargo-detail')

    <!-- Modal Acción (Rechazo/Eliminación) -->
    <div class="modal fade" id="modalAction" tabindex="-1" role="dialog" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white">{{ $action_type == 'reject' ? 'Rechazar Descargo' : 'Eliminar/Anular Descargo' }}</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12">
                            <label>Indique el motivo de esta acción (Obligatorio)</label>
                            <textarea wire:model="reason" class="form-control" rows="3" placeholder="Escriba aquí..."></textarea>
                            @error('reason') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" wire:click="processAction">Procesar Acción</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
             Livewire.on('show-detail', (event) => {
                $('#modalDescargoDetail').modal('show');
            });

            Livewire.on('show-action-modal', (event) => {
                $('#modalAction').modal('show');
            });

            Livewire.on('hide-action-modal', (event) => {
                $('#modalAction').modal('hide');
            });

            window.livewire.on('noty', (msg, type = 'success') => {
                 noty(msg, type);
            });
        });
    </script>
</div>
