<div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light">Estado de Cuenta Global</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-3">
                            <div class="card">
                                <div class="card-body">
                                    <span class="f-14"><b>Buscar Cliente</b></span>
                                    <div class="input-group" wire:ignore>
                                        <input class="form-control" type="text" id="inputCustomerStatement" placeholder="Nombre o ID">
                                    </div>
                                    
                                    @if($customerId)
                                    <div class="mt-2">
                                        <button wire:click="clearCustomer" class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-times"></i> Limpiar Selección
                                        </button>
                                    </div>
                                    @endif

                                    <div class="mt-3">
                                        <span class="f-14"><b>Fecha desde</b></span>
                                        <div class="input-group datepicker">
                                            <input wire:model.live="dateFrom" class="form-control flatpickr-input" type="date">
                                        </div>
                                    </div>

                                    <div class="mt-2">
                                        <span class="f-14"><b>Hasta</b></span>
                                        <div class="input-group datepicker">
                                            <input wire:model.live="dateTo" class="form-control flatpickr-input" type="date">
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <span class="f-14"><b>Buscar Referencia / Factura</b></span>
                                        <div class="input-group">
                                            <input wire:model.live="referenceSearch" class="form-control" type="text" placeholder="# Factura o ID">
                                        </div>
                                    </div>

                                    <div class="mt-4">
                                        <h6 class="border-bottom pb-2">Resumen del Periodo</h6>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Ventas:</span>
                                            <span class="font-weight-bold">${{ number_format($totalSales, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Pagos:</span>
                                            <span class="text-success font-weight-bold">-${{ number_format($totalPayments, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span>Devoluciones:</span>
                                            <span class="text-warning font-weight-bold">-${{ number_format($totalReturns, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between pt-2 border-top">
                                            <span class="h6">Saldo:</span>
                                            <span class="h6 {{ $currentBalance > 0 ? 'text-danger' : 'text-success' }} font-weight-bold">${{ number_format($currentBalance, 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-9">
                            @if($customerId)
                            <div class="card">
                                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                    <h5 class="m-0">Historial de Movimientos</h5>
                                    <div>
                                        <button wire:click="openPdfPreview" class="btn btn-info btn-sm mr-2">
                                            <i class="fas fa-eye"></i> Previsualizar
                                        </button>
                                        <button wire:click="exportPdf" class="btn btn-danger btn-sm">
                                            <i class="fas fa-file-pdf"></i> Imprimir PDF
                                        </button>
                                    </div>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover table-striped mb-0">
                                            <thead class="bg-gray-light">
                                                <tr class="text-center">
                                                    <th>Fecha</th>
                                                    <th>Concepto</th>
                                                    <th>Referencia</th>
                                                    <th>Cargo (+)</th>
                                                    <th>Abono (-)</th>
                                                    <th>Saldo Acumulado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($ledger as $row)
                                                <tr class="text-center">
                                                    <td>{{ \Carbon\Carbon::parse($row->t_date)->format('d/m/Y H:i') }}</td>
                                                    <td class="text-left">{{ $row->concept }}</td>
                                                    <td>#{{ $row->reference }}</td>
                                                    <td class="text-danger">
                                                        {{ $row->debit_usd > 0 ? '$' . number_format($row->debit_usd, 2) : '-' }}
                                                    </td>
                                                    <td class="text-success">
                                                        {{ $row->credit_usd > 0 ? '$' . number_format($row->credit_usd, 2) : '-' }}
                                                    </td>
                                                    <td class="font-weight-bold" style="background-color: rgba(0,0,0,0.02)">
                                                        ${{ number_format($row->running_balance, 2) }}
                                                    </td>
                                                </tr>
                                                @empty
                                                <tr>
                                                    <td colspan="6" class="text-center py-4">No hay movimientos en el periodo seleccionado</td>
                                                </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                            @else
                            <div class="alert alert-info text-center py-5">
                                <i class="fas fa-user-search fa-3x mb-3"></i>
                                <h5>Seleccione un cliente para ver su estado de cuenta</h5>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
             var tom = new TomSelect('#inputCustomerStatement', {
                maxItems: 1,
                valueField: 'id',
                labelField: 'name',
                searchField: ['name', 'taxpayer_id'],
                load: function(query, callback) {
                    var url = "{{ route('data.customers') }}" + '?q=' + encodeURIComponent(query);
                    fetch(url)
                        .then(response => response.json())
                        .then(json => {
                            callback(json);
                        }).catch(() => {
                            callback();
                        });
                },
                onChange: function(value) {
                    if (value) {
                        @this.selectCustomer(value);
                    }
                },
                render: {
                    option: function(item, escape) {
                        return `<div class="py-1">
                            <span class="font-weight-bold text-dark">${escape(item.name.toUpperCase())}</span>
                            <br><small class="text-muted">ID: ${escape(item.id)} ${item.taxpayer_id ? '| DOC: ' + escape(item.taxpayer_id) : ''}</small>
                        </div>`;
                    }
                }
            });

            Livewire.on('clear-customer-search', () => {
                tom.clear();
            });
        });
    </script>

    {{-- PDF Preview Modal --}}
    @if($showPdfModal)
    <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.7); z-index: 9999;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document" style="height: 90vh; max-width: 95vw; margin-top: 5vh;">
            <div class="modal-content" style="height: 100%;">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white">Vista Previa: Estado de Cuenta</h5>
                    <button type="button" class="close text-white" wire:click="closePdfPreview" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
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
                </div>
            </div>
        </div>
    </div>
    <script>
        document.body.style.overflow = 'hidden';
    </script>
    @else
    <script>
        document.body.style.overflow = 'auto';
    </script>
    @endif
</div>
