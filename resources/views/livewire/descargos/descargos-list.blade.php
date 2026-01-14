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
                                        <span class="badge badge-{{ $descargo->status == 'approved' ? 'success' : 'warning' }} text-uppercase">
                                            {{ $descargo->status == 'approved' ? 'Aprobado' : 'Pendiente' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button wire:click="getDescargoDetail({{ $descargo->id }})" class="btn btn-dark btn-sm" title="Ver Detalles">
                                            <i class="fas fa-list"></i>
                                        </button>
                                        @if($descargo->status == 'pending' && auth()->user()->can('aprobar_descargos'))
                                            <button wire:click="approve({{ $descargo->id }})" class="btn btn-success btn-sm" title="Aprobar Descargo"
                                                onclick="confirm('¿Confirmas aprobar este descargo?') || event.stopImmediatePropagation()">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        @endif
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
    
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-detail', (event) => {
                $('#modalDescargoDetail').modal('show');
            });
        });
    </script>
</div>
