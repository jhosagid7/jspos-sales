<div>
    <div class="row">
        <!-- Sidebar Options -->
        <div class="col-sm-12 col-md-3">
            <div class="card">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light">Opciones de Despacho</h5>
                </div>

                <div class="card-body">
                    <div class="mt-3">
                        <span class="f-14"><b>Chofer / Transportista</b></span>
                        <select wire:model="driver_id" class="form-control form-control-sm">
                            <option value="all">Todos los Choferes</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}">
                                    {{ $driver->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-4">
                        <span class="f-14"><b>Fecha Desde</b></span>
                        <div class="input-group">
                            <input type="date" wire:model="dateFrom" class="form-control">
                        </div>
                    </div>
                    
                    <div class="mt-2">
                        <span class="f-14"><b>Hasta</b></span>
                        <div class="input-group">
                            <input type="date" wire:model="dateTo" class="form-control">
                        </div>
                    </div>

                    <div class="mt-4">
                        <button wire:click="generateReport" class="btn btn-dark w-100 mb-2">
                             <i class="fa fa-search"></i> Consultar
                        </button>
                        <button wire:click="openPdfPreview" class="btn btn-info text-white w-100"
                            {{ !$showReport || count($sales) < 1 ? 'disabled' : '' }}>
                            <i class="fa fa-eye"></i> Previsualizar PDF
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="col-sm-12 col-md-9">
            <div class="card card-absolute">
                <div class="card-header bg-dark">
                    <h5 class="txt-light">Ventas con Despacho Asignado</h5>
                </div>

                <div class="card-body">
                    <div class="mt-3 table-responsive">
                        <table class="table table-hover">
                            <thead class="thead-primary">
                                <tr class="text-center">
                                    <th>Chofer</th>
                                    <th>Factura</th>
                                    <th>Destino (Ciudad)</th>
                                    <th>Cliente</th>
                                    <th>Vendedor</th>
                                    <th>Base ($)</th>
                                    <th>Total ($)</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sales as $sale)
                                    @php
                                        // Cálculo rápido de base para la vista (Total / (1 + sum(incrementos_porcentajes)))
                                        // O simplemente total - flete_monto si lo tenemos.
                                        // Para la vista usaremos algo simple, el PDF tendrá el cálculo exacto.
                                        $increments = ($sale->applied_commission_percent + $sale->applied_freight_percent + $sale->applied_exchange_diff_percent) / 100;
                                        $baseAmount = $sale->total_usd / (1 + $increments);
                                    @endphp
                                    <tr class="text-center">
                                        <td><span class="badge badge-light-primary">{{ $sale->driver->name ?? 'N/A' }}</span></td>
                                        <td>{{ $sale->invoice_number ?? $sale->id }}</td>
                                        <td>{{ $sale->customer->city ?? 'N/A' }}</td>
                                        <td class="text-start">{{ $sale->customer->name }}</td>
                                        <td>{{ $sale->sellerConfig->user->name ?? 'N/A' }}</td>
                                        <td class="text-end font-weight-bold">${{ number_format($baseAmount, 2) }}</td>
                                        <td class="text-end font-weight-bold">${{ number_format($sale->total_usd, 2) }}</td>
                                        <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center">No se encontraron ventas con despacho en este rango.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-2 text-center">
                            @if ($sales && !is_array($sales))
                                {{ $sales->links() }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PDF Viewer Modal (Reuse existing pattern) --}}
    @if($showPdfModal)
    <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.7); z-index: 9999;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document" style="height: 90vh; max-width: 95vw; margin-top: 5vh;">
            <div class="modal-content" style="height: 100%;">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white">Vista Previa: Relación de Despacho</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closePdfPreview" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="height: calc(100% - 60px);">
                    @if($pdfUrl)
                        <iframe src="{{ $pdfUrl }}" style="width: 100%; height: 100%; border: none;"></iframe>
                    @else
                        <div class="d-flex justify-content-center align-items-center" style="height: 100%;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closePdfPreview">Cerrar</button>
                    <a href="{{ $pdfUrl }}&download=1" class="btn btn-danger">Descargar PDF</a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
