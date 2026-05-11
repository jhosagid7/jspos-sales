<div>
    <div class="card">
        <div class="card-header bg-primary">
            <h4 class="text-white">Reporte de Producción y Rendimiento</h4>
        </div>
        <div class="card-body">
            <div class="row mb-4">
                <div class="col-md-3">
                    <label class="form-label fw-bold">Desde</label>
                    <input type="date" wire:model.live="dateFrom" class="form-control border-primary">
                </div>
                <div class="col-md-3">
                    <label class="form-label fw-bold">Hasta</label>
                    <input type="date" wire:model.live="dateTo" class="form-control border-primary">
                </div>
                <div class="col-md-6 d-flex align-items-end">
                    <div class="btn-group w-100 shadow-sm">
                        <div class="btn btn-outline-info text-dark bg-light flex-grow-1">
                            <h6 class="mb-0">Yield (Rendimiento)</h6>
                            <h4 class="mb-0 {{ $stats['yield'] < 95 ? 'text-danger' : 'text-success' }}">{{ number_format($stats['yield'], 2) }}%</h4>
                        </div>
                        <div class="btn btn-outline-success text-dark bg-light flex-grow-1">
                            <h6 class="mb-0">Prod. Buena</h6>
                            <h4 class="mb-0">{{ number_format($stats['totalGood'], 0) }}</h4>
                        </div>
                        <div class="btn btn-outline-danger text-dark bg-light flex-grow-1">
                            <h6 class="mb-0">Dañado (Merma)</h6>
                            <h4 class="mb-0">{{ number_format($stats['totalDamaged'], 0) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="bg-dark text-white">
                        <tr>
                            <th>ID / Fecha</th>
                            <th>Turno / Usuario</th>
                            <th>Insumos Consumidos</th>
                            <th>Producción (Salidas)</th>
                            <th>Nota</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($data as $log)
                        <tr>
                            <td>
                                <b>#{{ $log->id }}</b><br>
                                {{ $log->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td>
                                Turno: {{ $log->shift ? ucfirst($log->shift->type) : 'N/A' }}<br>
                                Por: {{ $log->user ? $log->user->name : 'N/A' }}
                            </td>
                            <td>
                                <ul class="mb-0 pl-3">
                                    @foreach($log->materials as $mat)
                                        <li>{{ $mat->product ? $mat->product->name : 'Prod. Eliminado' }}: <b>{{ $mat->quantity }}</b></li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>
                                <ul class="mb-0 pl-3">
                                    @foreach($log->outputs as $out)
                                        <li>
                                            {{ $out->product ? $out->product->name : 'Merma Gral' }}: <b>{{ $out->quantity }}</b>
                                            @if($out->quality == '1st')
                                                <span class="badge badge-success">1ra</span>
                                            @elseif($out->quality == '2nd')
                                                <span class="badge badge-warning">2da</span>
                                            @else
                                                <span class="badge badge-danger">Dañado</span>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            </td>
                            <td>{{ $log->notes }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="text-center">No se encontraron registros de producción.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{ $data->links() }}
        </div>
    </div>
</div>
