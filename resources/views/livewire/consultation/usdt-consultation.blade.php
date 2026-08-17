<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>Consulta y Auditoría de Pagos USDT ($)</b>
                </h4>
            </div>

            <div class="widget-content">
                <div class="row mb-3">
                    <div class="col-sm-12 col-md-3">
                        <label>Buscar (TxID Referencia / Emisor)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" wire:model.live.debounce.500ms="search" class="form-control" placeholder="Buscar...">
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-2">
                        <label>Fecha Desde</label>
                        <input type="date" wire:model.live="dateFrom" class="form-control">
                    </div>
                    <div class="col-sm-12 col-md-2">
                        <label>Fecha Hasta</label>
                        <input type="date" wire:model.live="dateTo" class="form-control">
                    </div>
                    <div class="col-sm-12 col-md-2">
                         <label>Estado</label>
                         <select wire:model.live="status" class="form-control">
                             <option value="">Todos</option>
                             <option value="unused">Sin Usar</option>
                             <option value="partial">Parcial</option>
                             <option value="used">Usado</option>
                         </select>
                    </div>
                    <div class="col-sm-12 col-md-3 d-flex align-items-end">
                        <a href="{{ route('usdt.filtered.pdf', ['search' => $search, 'date_from' => $dateFrom, 'date_to' => $dateTo, 'status' => $status]) }}" target="_blank" class="btn btn-danger btn-block font-weight-bold">
                            <i class="fas fa-file-pdf"></i> PDF de Capturas USDT
                        </a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mt-1">
                        <thead class="text-white" style="background: #3B3F5C">
                            <tr>
                                <th class="table-th text-white">Fecha</th>
                                <th class="table-th text-white">N° Referencia / TxID</th>
                                <th class="table-th text-white">Billetera / Emisor</th>
                                <th class="table-th text-white text-right">Monto (USDT $)</th>
                                <th class="table-th text-white text-right">Saldo Restante</th>
                                <th class="table-th text-white">Estado</th>
                                <th class="table-th text-white text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $rec)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($rec->usdt_date)->format('d/m/Y') }}</td>
                                <td><span class="badge badge-dark font-monospace">{{ $rec->reference }}</span></td>
                                <td class="font-weight-bold text-primary">{{ $rec->sender_name }}</td>
                                <td class="text-right font-weight-bold">${{ number_format($rec->amount, 2) }}</td>
                                <td class="text-right">${{ number_format($rec->remaining_balance, 2) }}</td>
                                <td>
                                    @if($rec->status == 'unused')
                                        <span class="badge badge-success">Sin Usar</span>
                                    @elseif($rec->status == 'partial')
                                        <span class="badge badge-warning">Parcial</span>
                                    @else
                                        <span class="badge badge-secondary">Usado</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <button wire:click="viewDetails({{ $rec->id }})" class="btn btn-dark btn-sm" title="Ver Detalles y Comprobante">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    <a href="{{ route('usdt.pdf', ['id' => $rec->id]) }}" target="_blank" class="btn btn-danger btn-sm ml-1" title="Imprimir PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="7" class="text-center">No hay registros de pagos USDT en este rango de fechas.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $records->links() }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Detalles y Capture -->
    <div wire:ignore.self class="modal fade" id="usdtDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white">
                        <i class="fas fa-receipt"></i> Detalle de Pago USDT: {{ $selectedRecord->reference ?? '' }}
                    </h5>
                    <button type="button" class="close text-white" wire:click="closeDetails" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if($selectedRecord)
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <p><b>Emisor / Billetera:</b> {{ $selectedRecord->sender_name }}</p>
                            <p><b>TxID / Referencia:</b> {{ $selectedRecord->reference }}</p>
                            <p><b>Monto USDT:</b> ${{ number_format($selectedRecord->amount, 2) }}</p>
                            <p><b>Fecha Voucher:</b> {{ \Carbon\Carbon::parse($selectedRecord->usdt_date)->format('d/m/Y') }}</p>
                        </div>
                        <div class="col-md-6 text-center">
                            <b>Comprobante de Pago Subido:</b>
                            @if($selectedRecord->image_path)
                                <div class="mt-2">
                                    <a href="{{ asset('storage/' . $selectedRecord->image_path) }}" target="_blank">
                                        <img src="{{ asset('storage/' . $selectedRecord->image_path) }}" class="img-fluid rounded border" style="max-height: 250px;" alt="Comprobante USDT">
                                    </a>
                                    <small class="d-block text-muted mt-1">(Haz clic en la imagen para abrir en tamaño completo)</small>
                                </div>
                            @else
                                <div class="alert alert-warning mt-2">Sin imagen de comprobante adjunta.</div>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closeDetails" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('show-details-modal', () => {
                $('#usdtDetailsModal').modal('show');
            });
            Livewire.on('hide-details-modal', () => {
                $('#usdtDetailsModal').modal('hide');
            });
        });
    </script>
</div>
