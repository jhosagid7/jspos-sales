<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>Listado de Cargos / Ajustes de Entrada</b>
                </h4>
                <ul class="tabs tab-pills">
                    <li>
                        <a href="{{ route('cargos.create') }}" class="tabmenu bg-dark text-white">Nuevo Cargo</a>
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
                            @forelse($cargos as $cargo)
                                <tr>
                                    <td>{{ $cargo->id }}</td>
                                    <td>{{ $cargo->date->format('d/m/Y H:i') }}</td>
                                    <td>{{ $cargo->warehouse->name }}</td>
                                    <td>{{ $cargo->motive }}</td>
                                    <td>{{ $cargo->authorized_by }}</td>
                                    <td>{{ $cargo->user->name }}</td>
                                    <td class="text-center">
                                        @php
                                            $badgeClass = 'warning';
                                            $statusLabel = 'Pendiente';
                                            if($cargo->status == 'approved') { $badgeClass = 'success'; $statusLabel = 'Aprobado'; }
                                            elseif($cargo->status == 'rejected') { $badgeClass = 'danger'; $statusLabel = 'Rechazado'; }
                                            elseif($cargo->status == 'voided') { $badgeClass = 'secondary'; $statusLabel = 'Eliminado'; }
                                        @endphp
                                        <span class="badge badge-{{ $badgeClass }} text-uppercase">
                                            {{ $statusLabel }}
                                        </span>
                                        
                                        @if($cargo->status == 'pending')
                                            <div class="mt-2 text-center">
                                                @can('adjustments.create')
                                                    <a href="{{ route('cargos.edit', $cargo->id) }}" class="btn btn-primary btn-sm" title="Editar">
                                                        <i class="fas fa-pencil-alt"></i>
                                                    </a>
                                                @endcan
                                                
                                                @can('adjustments.approve_cargo')
                                                    <button wire:click="approve({{ $cargo->id }})" class="btn btn-dark btn-sm" title="Aprobar"
                                                        onclick="confirm('¿Confirmas aprobar este cargo? El stock se actualizará.') || event.stopImmediatePropagation()">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                @endcan
                                                
                                                @can('adjustments.reject_cargo')
                                                    <button wire:click="openActionModal({{ $cargo->id }}, 'reject')" class="btn btn-danger btn-sm" title="Rechazar">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endcan
                                            </div>
                                        @endif

                                        @if($cargo->status == 'approved')
                                            @can('adjustments.delete_cargo')
                                                <button wire:click="openActionModal({{ $cargo->id }}, 'delete')" class="btn btn-outline-danger btn-sm mt-1" title="Eliminar/Anular">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endcan
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <button wire:click="getCargoDetail({{ $cargo->id }})" class="btn btn-dark btn-sm" title="Ver Detalles">
                                            <i class="fas fa-list"></i>
                                        </button>
                                        <a href="{{ route('cargos.create', ['clone_id' => $cargo->id]) }}" class="btn btn-outline-info btn-sm" title="Clonar">
                                            <i class="fas fa-copy"></i>
                                        </a>
                                        <a href="{{ route('cargos.pdf', $cargo->id) }}" class="btn btn-outline-danger btn-sm" target="_blank" title="PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No hay cargos registrados</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $cargos->links() }}
                </div>
            </div>
        </div>
    </div>
    @include('livewire.cargos.cargo-detail')

    <!-- Modal Acción (Rechazo/Eliminación) -->
    <div class="modal fade" id="modalAction" tabindex="-1" role="dialog" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title">{{ $action_type == 'reject' ? 'Rechazar Cargo' : 'Eliminar/Anular Cargo' }}</h5>
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
                    <button type="button" class="btn btn-primary" wire:click="processAction">Procesar Action</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('show-detail', (event) => {
                $('#modalCargoDetail').modal('show');
            });

            Livewire.on('show-action-modal', (event) => {
                $('#modalAction').modal('show');
            });

            Livewire.on('hide-action-modal', (event) => {
                $('#modalAction').modal('hide');
            });

            window.livewire.on('noty', msg => {
                noty(msg)
            });
        });
    </script>
</div>
