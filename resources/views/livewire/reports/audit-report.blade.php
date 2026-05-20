<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0 text-dark">Auditoría de Stock e Inventario</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('welcome') }}">Inicio</a></li>
                        <li class="breadcrumb-item active">Reportes</li>
                        <li class="breadcrumb-item active">Auditoría</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <div class="content">
        <div class="container-fluid">
            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">Filtros de Búsqueda</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Desde</label>
                                <input type="date" wire:model.live="dateFrom" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Hasta</label>
                                <input type="date" wire:model.live="dateTo" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Usuario (Causante)</label>
                                <select wire:model.live="userId" class="form-control">
                                    <option value="">Todos los usuarios</option>
                                    @foreach($users as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Buscar Evento / Descripción</label>
                                <input type="text" wire:model.live="searchTerm" class="form-control" placeholder="Ej. created, updated...">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-body table-responsive p-0">
                    <table class="table table-hover text-nowrap table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Fecha y Hora</th>
                                <th>Usuario</th>
                                <th>Producto / Almacén</th>
                                <th>Evento</th>
                                <th>Descripción</th>
                                <th>Valores Anteriores</th>
                                <th>Nuevos Valores</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($logs as $log)
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($log->created_at)->format('d/m/Y h:i A') }}</td>
                                    <td>
                                        @if($log->causer)
                                            <span class="badge badge-info">{{ $log->causer->name }}</span>
                                        @else
                                            <span class="badge badge-secondary">Sistema</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->custom_subject_name }}</td>
                                    <td>
                                        @if($log->event == 'created')
                                            <span class="badge badge-success">CREADO</span>
                                        @elseif($log->event == 'updated')
                                            <span class="badge badge-warning">ACTUALIZADO</span>
                                        @elseif($log->event == 'deleted')
                                            <span class="badge badge-danger">ELIMINADO</span>
                                        @else
                                            <span class="badge badge-secondary">{{ strtoupper($log->event) }}</span>
                                        @endif
                                        <br>
                                        @if(isset($log->properties['source']) && $log->properties['source'] == 'EDICIÓN MANUAL')
                                            <span class="badge badge-danger mt-1"><i class="fa fa-exclamation-triangle"></i> EDICIÓN MANUAL</span>
                                        @else
                                            <span class="badge badge-success mt-1"><i class="fa fa-check-circle"></i> SISTEMA</span>
                                        @endif
                                    </td>
                                    <td>{{ $log->description }}</td>
                                    <td>
                                        @if(isset($log->properties['old']))
                                            <pre class="bg-light p-1 mb-0" style="font-size: 11px;">@json($log->properties['old'], JSON_PRETTY_PRINT)</pre>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if(isset($log->properties['attributes']))
                                            <pre class="bg-light p-1 mb-0" style="font-size: 11px;">@json($log->properties['attributes'], JSON_PRETTY_PRINT)</pre>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center">No se encontraron registros de auditoría en este rango de fechas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($logs->hasPages())
                    <div class="card-footer pb-0">
                        {{ $logs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
