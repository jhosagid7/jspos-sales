<div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Gestión de Comisiones</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <label>Fecha Desde</label>
                            <input type="date" wire:model.live="dateFrom" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label>Fecha Hasta</label>
                            <input type="date" wire:model.live="dateTo" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label>Estado</label>
                            <select wire:model.live="status_filter" class="form-control">
                                <option value="all">Todos</option>
                                <option value="pending">Pendientes</option>
                                <option value="paid">Pagadas</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label>Lote</label>
                            <input type="text" wire:model.live="batch_filter" class="form-control" placeholder="Filtrar por lote...">
                        </div>
                        @if($canManage)
                        <div class="col-md-3">
                            <label>Vendedor</label>
                            <select wire:model.live="seller_id" class="form-control">
                                <option value="0">Todos los Vendedores</option>
                                @foreach($sellers as $seller)
                                    <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-2 d-flex align-items-end">
                            <button wire:click="$refresh" class="btn btn-primary btn-block mr-2">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button wire:click="generatePdf" class="btn btn-danger btn-block">
                                <i class="fas fa-file-pdf"></i> PDF
                            </button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover">
                            <thead class="thead-primary">
                                <tr>
                                    <th style="width: 50px;">
                                        <!-- Checkbox header could be for select all, but keeping simple for now -->
                                    </th>
                                    <th>Fecha Venta</th>
                                    <th>Lote</th>
                                    <th>No. Factura</th>
                                    <th>Vendedor</th>
                                    <th>Cliente</th>
                                    <th>Monto Base</th>
                                    <th>Total Venta</th>
                                    <th>Fecha Pago</th>
                                    <th>Estado Venta</th>
                                    <th>Comisión Aplicada</th>
                                    <th>Comisión Final</th>
                                    <th>Monto Comisión</th>
                                    <th>Estado Comisión</th>
                                    <th>Pago</th>
                                    <th>Tasa</th>
                                    <th>Detalles</th>
                                    @if($canManage)
                                    <th>Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($commissions as $sale)
                                    <tr wire:key="sale-{{ $sale->id }}">
                                        <td class="text-center">
                                            <input type="checkbox" wire:model.live="selected_commissions" value="{{ $sale->id }}">
                                        </td>
                                        <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                                        <td>
                                            @if($sale->batch_name)
                                                <span class="badge badge-info">{{ $sale->batch_name }}-{{ $sale->batch_sequence }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>{{ $sale->invoice_number ?? 'N/A' }}</td>
                                        <td>{{ $sale->customer->seller->name ?? 'N/A' }}</td>
                                        <td>{{ $sale->customer->name }}</td>
                                        <td>
                                            @php
                                                $totalSurchargeFactor = 1 + (($sale->applied_commission_percent + $sale->applied_freight_percent + $sale->applied_exchange_diff_percent) / 100);
                                                if ($totalSurchargeFactor == 0) $totalSurchargeFactor = 1;
                                                $baseAmount = $sale->total / $totalSurchargeFactor;
                                            @endphp
                                            ${{ number_format($baseAmount, 2) }}
                                        </td>
                                        <td>${{ number_format($sale->total, 2) }}</td>
                                        <td>
                                            @php
                                                $lastPayment = $sale->payments->sortByDesc('created_at')->first();
                                            @endphp
                                            {{ $lastPayment ? $lastPayment->created_at->format('d/m/Y') : 'N/A' }}
                                        </td>
                                        <td>
                                            @if($sale->status == 'paid')
                                                <span class="badge badge-success">PAGADA</span>
                                            @elseif($sale->status == 'pending')
                                                <span class="badge badge-warning">PENDIENTE</span>
                                            @else
                                                <span class="badge badge-danger">{{ strtoupper($sale->status) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($sale->applied_commission_percent, 2) }}%</td>
                                        <td>
                                            @php
                                                $finalPercent = ($sale->final_commission_amount / ($sale->total / (1 + ($sale->applied_commission_percent + $sale->applied_freight_percent + $sale->applied_exchange_diff_percent)/100))) * 100;
                                            @endphp
                                            {{ number_format($finalPercent, 2) }}%
                                        </td>
                                        <td class="fw-bold text-success">${{ number_format($sale->final_commission_amount, 2) }}</td>
                                        <td>
                                            @if($sale->commission_status == 'paid')
                                                <span class="badge badge-success">PAGADA</span>
                                                <br>
                                                <small>{{ \Carbon\Carbon::parse($sale->commission_paid_at)->format('d/m/Y') }}</small>
                                            @else
                                                <span class="badge badge-warning">PENDIENTE</span>
                                            @endif
                                        </td>
                                        <td>
                                            @if($sale->commission_status == 'paid')
                                                <h6 class="text-info">{{ number_format($sale->commission_payment_amount, 2) }} {{ $sale->commission_payment_currency }}</h6>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($sale->commission_status == 'paid')
                                                {{ number_format($sale->commission_payment_rate, 4) }}
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td>
                                            @if($sale->commission_status == 'paid')
                                                <small>
                                                    <b>{{ $sale->commission_payment_method }}</b>
                                                    @if($sale->commission_payment_method == 'Bank')
                                                        <br>{{ $sale->commission_payment_bank_name }}
                                                        <br>Ref: {{ $sale->commission_payment_reference }}
                                                    @endif
                                                    @if($sale->commission_payment_notes)
                                                        <br><i>{{ Str::limit($sale->commission_payment_notes, 20) }}</i>
                                                    @endif
                                                </small>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @if($canManage)
                                        <td>
                                            <div class="btn-group">
                                                @if($sale->commission_status == 'pending_payment')
                                                    <button wire:click="initPayment({{ $sale->id }})" 
                                                            class="btn btn-primary btn-sm"
                                                            title="Pagar Comisión">
                                                        <i class="fas fa-money-bill-wave"></i>
                                                    </button>
                                                @endif

                                                @if($sale->commission_status != 'paid')
                                                    <button wire:click="recalculate({{ $sale->id }})" 
                                                            class="btn btn-warning btn-sm"
                                                            title="Recalcular Comisión">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                @endif
                                            </div>
                                        </td>
                                        @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ $canManage ? 18 : 17 }}" class="text-center">No hay comisiones registradas en este periodo</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $commissions->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Pago Comisión -->
    <div wire:ignore.self class="modal fade" id="modalPayment" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">Pagar Comisión</h5>
                    <button type="button" class="close text-white" onclick="$('#modalPayment').modal('hide')" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group">
                                <label>Método de Pago</label>
                                <select wire:model.live="paymentMethod" class="form-control">
                                    <option value="Cash">Efectivo</option>
                                    <option value="Bank">Banco</option>
                                </select>
                                @error('paymentMethod') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group">
                                <label>Moneda</label>
                                <select wire:model.live="selectedCurrencyCode" class="form-control" {{ $paymentMethod == 'Bank' ? 'disabled' : '' }}>
                                    @foreach($currencies as $currency)
                                        <option value="{{ $currency->code }}">{{ $currency->name }} ({{ $currency->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        
                        @if($paymentMethod == 'Bank')
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group">
                                <label>Banco</label>
                                <select wire:model.live="selectedBankId" class="form-control">
                                    <option value="">Seleccione Banco</option>
                                    @foreach($banks as $bank)
                                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                    @endforeach
                                </select>
                                @error('selectedBankId') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group">
                                <label>Referencia / No. Transacción</label>
                                <input type="text" wire:model="referenceNumber" class="form-control" placeholder="Ej: 12345678">
                                @error('referenceNumber') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        @endif

                        <div class="col-sm-12 col-md-6" wire:key="rate-container-{{ $selectedSaleId }}-{{ $selectedCurrencyCode }}">
                            <div class="form-group">
                                <label>Tasa de Cambio</label>
                                <input type="number" step="0.0001" wire:model.live="paymentRate" class="form-control" placeholder="Ej: 1.00">
                                @error('paymentRate') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6" wire:key="amount-container-{{ $selectedSaleId }}-{{ $selectedCurrencyCode }}">
                            <div class="form-group">
                                <label>Monto Pagado</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">{{ $selectedCurrencySymbol }}</span>
                                    </div>
                                    <input type="number" step="0.01" wire:model.live="paymentAmount" class="form-control" placeholder="Ej: 50.00">
                                </div>
                                @error('paymentAmount') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Notas / Detalles</label>
                                <textarea wire:model="paymentNotes" class="form-control" rows="2" placeholder="Detalles adicionales..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark close-btn" onclick="$('#modalPayment').modal('hide')">Cerrar</button>
                    <button type="button" wire:click="savePayment" class="btn btn-primary close-modal">Confirmar Pago</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            window.addEventListener('show-modal', event => {
                $('#modalPayment').modal('show');
            });
            window.addEventListener('hide-modal', event => {
                $('#modalPayment').modal('hide');
            });
        });
    </script>
</div>
