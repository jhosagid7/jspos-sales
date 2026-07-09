<div>
    <div class="row sales layout-top-spacing">
        <div class="col-sm-12">
            <div class="widget widget-chart-one">
                <div class="widget-heading">
                    <h4 class="card-title">
                        <b>Fábrica Soplados | Historial de Turnos</b>
                    </h4>
                </div>

                <div class="row justify-content-between mb-4">
                    <div class="col-lg-4 col-md-4 col-sm-12">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" wire:model.live="search" placeholder="Buscar operario..." class="form-control">
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
                                    <th class="table-th text-white">ID</th>
                                    <th class="table-th text-white text-center">OPERARIO</th>
                                    <th class="table-th text-white text-center">TURNO</th>
                                    <th class="table-th text-white text-center">ALMACÉN</th>
                                    <th class="table-th text-white text-center">INICIO</th>
                                    <th class="table-th text-white text-center">FIN</th>
                                    <th class="table-th text-white text-center">ESTADO</th>
                                    <th class="table-th text-white text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($shifts as $shift)
                                <tr>
                                    <td><h6>{{ $shift->id }}</h6></td>
                                    <td class="text-center"><h6>{{ $shift->user->name }}</h6></td>
                                    <td class="text-center">
                                        <span class="badge {{ $shift->type == 'Diurno' ? 'badge-info' : 'badge-dark' }}">
                                            {{ $shift->type }}
                                        </span>
                                    </td>
                                    <td class="text-center"><h6>{{ $shift->warehouse->name ?? 'N/A' }}</h6></td>
                                    <td class="text-center"><h6>{{ \Carbon\Carbon::parse($shift->start_time)->format('d-m-Y H:i') }}</h6></td>
                                    <td class="text-center"><h6>{{ $shift->end_time ? \Carbon\Carbon::parse($shift->end_time)->format('d-m-Y H:i') : '-' }}</h6></td>
                                    <td class="text-center">
                                        @if($shift->status == 'open')
                                            <span class="badge badge-success">ABIERTO</span>
                                        @else
                                            <span class="badge badge-secondary">CERRADO</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('soplados.shifts.pdf', $shift->id) }}" target="_blank" class="btn btn-dark p-1" title="Ver PDF">
                                            <i class="fas fa-file-pdf text-danger fa-lg"></i>
                                        </a>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $shifts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
