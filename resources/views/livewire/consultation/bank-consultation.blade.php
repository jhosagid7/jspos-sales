<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>Consulta de Pagos Bancarios</b>
                </h4>
            </div>

            <div class="widget-content">
                <div class="row mb-3">
                    <div class="col-sm-12 col-md-3">
                        <label>Buscar (Referencia / Nota)</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" wire:model.live.debounce.500ms="search" class="form-control" placeholder="Buscar...">
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-2">
                         <label>Banco</label>
                         <select wire:model.live="bank_id" class="form-control">
                             <option value="">Todos los Bancos</option>
                             @foreach($banks as $bank)
                                <option value="{{ $bank->id }}">{{ $bank->name }} ({{ $bank->currency_code }})</option>
                             @endforeach
                         </select>
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
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mt-1">
                        <thead class="text-white" style="background: #3B3F5C">
                            <tr>
                                <th class="table-th text-white">Fecha</th>
                                <th class="table-th text-white">Banco</th>
                                <th class="table-th text-white">Referencia</th>
                                <th class="table-th text-white text-right">Monto</th>
                                <th class="table-th text-white text-right">Saldo Restante</th>
                                <th class="table-th text-white">Estado</th>
                                <th class="table-th text-white">Info. Uso</th>
                                <th class="table-th text-white text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($records as $record)
                                <tr>
                                    <td>{{ $record->payment_date->format('d/m/Y') }}</td>
                                    <td>{{ $record->bank->name }}</td>
                                    <td><strong>{{ $record->reference }}</strong></td>
                                    <td class="text-right">${{ number_format($record->amount, 2) }}</td>
                                    <td class="text-right">${{ number_format($record->remaining_balance, 2) }}</td>
                                    <td>
                                        <span class="badge badge-{{ $record->status == 'unused' ? 'secondary' : ($record->status == 'partial' ? 'warning' : 'success') }} text-uppercase">
                                            {{ $record->status == 'unused' ? 'Sin Usar' : ($record->status == 'partial' ? 'Parcial' : 'Usado') }}
                                        </span>
                                    </td>
                                    <td>
                                        @foreach($record->payments as $payment)
                                            @if($payment->sale)
                                                <small class="d-block text-muted">
                                                    Fact: #{{ $payment->sale->invoice_number ?? $payment->sale->id }} 
                                                    - {{ $payment->sale->customer->name ?? 'Consumidor' }}
                                                </small>
                                            @endif
                                        @endforeach
                                    </td>
                                    <td class="text-center">
                                        @can('bank_view_details')
                                            <button wire:click="viewDetails({{ $record->id }})" class="btn btn-dark btn-sm" title="Ver Detalles">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        @endcan
                                        
                                        @can('bank_print_pdf')
                                            <button wire:click="downloadPdf({{ $record->id }})" class="btn btn-outline-danger btn-sm" title="PDF">
                                                <i class="fas fa-file-pdf"></i>
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">No hay registros bancarios coincidentes</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                    {{ $records->links() }}
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Details -->
    <div class="modal fade" id="modalDetails" tabindex="-1" role="dialog" wire:ignore.self>
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Detalle de Pago Bancario</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close" wire:click="closeDetails">
                         <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if($selectedRecord)
                        <div class="row">
                            <div class="col-md-6 text-center">
                                <h6>Comprobante</h6>
                                @if($selectedRecord->image_path)
                                    <img src="{{ asset('storage/' . $selectedRecord->image_path) }}" alt="Comprobante" class="img-fluid rounded shadow-sm" style="max-height: 400px;">
                                @else
                                    <div class="alert alert-warning">No hay imagen adjunta</div>
                                @endif
                            </div>
                            <div class="col-md-6">
                                <h6>Datos del Depósito</h6>
                                <table class="table table-sm table-bordered">
                                    <tr>
                                        <th>Banco</th>
                                        <td>{{ $selectedRecord->bank->name }} ({{ $selectedRecord->bank->currency_code }})</td>
                                    </tr>
                                    <tr>
                                        <th>Fecha</th>
                                        <td>{{ $selectedRecord->payment_date->format('d/m/Y') }}</td>
                                    </tr>
                                    <tr>
                                        <th>Referencia</th>
                                        <td>{{ $selectedRecord->reference }}</td>
                                    </tr>
                                    <tr>
                                        <th>Monto</th>
                                        <td>${{ number_format($selectedRecord->amount, 2) }}</td>
                                    </tr>
                                     <tr>
                                        <th>Nota</th>
                                        <td>{{ $selectedRecord->note }}</td>
                                    </tr>
                                </table>

                                <h6 class="mt-3">Facturas Pagadas / Usos</h6>
                                <table class="table table-sm table-striped">
                                    <thead>
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Factura #</th>
                                            <th>Cliente</th>
                                            <th class="text-right">Monto Usado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($selectedRecord->payments as $payment)
                                            <tr>
                                                <td>{{ $payment->created_at->format('d/m/Y') }}</td>
                                                <td>
                                                    @if($payment->sale)
                                                        {{ $payment->sale->invoice_number ?? 'ID:'.$payment->sale->id }}
                                                    @else
                                                        <span class="badge badge-info">Abono/Crédito</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($payment->sale)
                                                        {{ $payment->sale->customer->name ?? 'N/A' }}
                                                    @elseif($payment->user)
                                                        {{ $payment->user->name }}
                                                    @endif
                                                    <small class="text-muted d-block">(Abono)</small>
                                                </td>
                                                <td class="text-right">${{ number_format($payment->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                        @foreach($selectedRecord->salePaymentDetails as $paymentDetail)
                                            <tr>
                                                <td>{{ $paymentDetail->created_at->format('d/m/Y') }}</td>
                                                <td>
                                                    @if($paymentDetail->sale)
                                                        {{ $paymentDetail->sale->invoice_number ?? 'ID:'.$paymentDetail->sale->id }}
                                                    @else
                                                        <span class="badge badge-success">Venta Contado</span>
                                                    @endif
                                                </td>
                                                <td>
                                                    @if($paymentDetail->sale)
                                                        {{ $paymentDetail->sale->customer->name ?? 'N/A' }}
                                                    @else
                                                        N/A
                                                    @endif
                                                    <small class="text-success d-block">(Contado)</small>
                                                </td>
                                                <td class="text-right">${{ number_format($paymentDetail->amount, 2) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-dismiss="modal" wire:click="closeDetails">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('show-details-modal', () => {
                $('#modalDetails').modal('show');
            });
            Livewire.on('hide-details-modal', () => {
                $('#modalDetails').modal('hide');
            });
        });
    </script>
</div>
