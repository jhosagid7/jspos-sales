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
                                        <span class="badge badge-{{ $cargo->status == 'approved' ? 'success' : 'warning' }} text-uppercase">
                                            {{ $cargo->status == 'approved' ? 'Aprobado' : 'Pendiente' }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <button wire:click="getCargoDetail({{ $cargo->id }})" class="btn btn-dark btn-sm" title="Ver Detalles">
                                            <i class="fas fa-list"></i>
                                        </button>
                                        @if($cargo->status == 'pending' && auth()->user()->can('aprobar_cargos'))
                                            <button wire:click="approve({{ $cargo->id }})" class="btn btn-success btn-sm" title="Aprobar Cargo"
                                                onclick="confirm('¿Confirmas aprobar este cargo?') || event.stopImmediatePropagation()">
                                                <i class="fas fa-check"></i>
                                            </button>
                                        @endif
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
    
    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-detail', (event) => {
                $('#modalCargoDetail').modal('show');
            });
        });
    </script>
</div>
