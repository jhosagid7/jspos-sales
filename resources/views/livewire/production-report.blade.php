<div>
    <div class="card">
        <div class="card-header bg-primary d-flex justify-content-between align-items-center">
            <h4 class="text-white mb-0">Reporte de Producción y Rendimiento</h4>
            <button class="btn btn-info btn-sm fw-bold shadow-sm" wire:click="downloadSopladosReport" wire:loading.attr="disabled">
                <i class="fas fa-file-pdf mr-1"></i> Descargar Reporte Soplados (PDF)
            </button>
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
                            <th>Estadísticas</th>
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
                                            @if($out->product && $out->product->production_target_id)
                                                <br><small class="text-info"><i class="fa fa-arrow-right"></i> Sumado a: {{ $out->product->productionTarget->name ?? 'ID: '.$out->product->production_target_id }}</small>
                                            @endif
                                            <br>
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
                            <td>
                                <div class="text-center">
                                    <h5 class="mb-0 {{ $log->stats['yield'] < 95 ? 'text-danger' : 'text-success' }}">
                                        {{ number_format($log->stats['yield'], 1) }}%
                                    </h5>
                                    <small class="text-muted">
                                        Buenos: <b>{{ $log->stats['good'] }}</b><br>
                                        Merma: <b>{{ $log->stats['damaged'] }}</b>
                                    </small>
                                </div>
                            </td>
                            <td>{{ $log->notes }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">No se encontraron registros de producción.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{ $data->links() }}
        </div>
    </div>
</div>
