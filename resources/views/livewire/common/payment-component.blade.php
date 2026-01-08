<div>
    <div wire:ignore.self class="modal fade" id="modalPayment" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-cash-register me-2"></i>
                        REGISTRAR PAGO
                        @if($customerName)
                            <small class="d-block mt-1" style="font-size: 0.7rem;">Cliente: {{ $customerName }}</small>
                        @endif
                    </h5>
                    <button class="btn-close btn-close-white" type="button" data-dismiss="modal" aria-label="Close" onclick="$('#modalPayment').modal('hide')"></button>
                </div>

                <div class="modal-body">
                    @php
                        $primaryCurrency = collect($currencies)->firstWhere('is_primary', 1);
                        $symbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                    @endphp

                    <div class="row">
                        {{-- Left Column: Summary & Method --}}
                        <div class="col-md-6">
                            {{-- Summary --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-file-invoice-dollar me-2"></i>Resumen</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-flex justify-content-between">
                                        <span class="fs-5 fw-bold text-primary">TOTAL A PAGAR:</span>
                                        <span class="fs-5 fw-bold text-primary">{{ $symbol }}{{ number_format($totalToPay, 2) }}</span>
                                    </div>
                                </div>
                            </div>

                            {{-- Method Selector --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-credit-card me-2"></i>Método de Pago</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2">
                                        <div class="col-6">
                                            <button type="button" wire:click="$set('paymentMethod', 'cash')"
                                                class="btn w-100 {{ $paymentMethod === 'cash' ? 'btn-success' : 'btn-outline-success' }}">
                                                <i class="fa fa-money-bill-wave fa-2x d-block mb-2"></i>
                                                <small>Efectivo</small>
                                            </button>
                                        </div>
                                        <div class="col-6">
                                            <button type="button" wire:click="$set('paymentMethod', 'bank')"
                                                class="btn w-100 {{ $paymentMethod === 'bank' ? 'btn-primary' : 'btn-outline-primary' }}">
                                                <i class="fa fa-university fa-2x d-block mb-2"></i>
                                                <small>Banco / Zelle</small>
                                            </button>
                                        </div>

                                    </div>
                                </div>
                            </div>

                            {{-- Input Form --}}
                            <div class="card">
                                <div class="card-body">
                                    {{-- CASH --}}
                                    @if($paymentMethod === 'cash')
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Monto</label>
                                                <input class="form-control" type="number" wire:model="amount" placeholder="0.00" wire:keydown.enter="addPayment">
                                                @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Moneda</label>
                                                <select class="form-control" wire:model="paymentCurrency">
                                                    @foreach($currencies as $curr)
                                                        <option value="{{ $curr->code }}">{{ $curr->label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-success w-100" wire:click="addPayment">Agregar Pago</button>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- BANK / ZELLE --}}
                                    @if($paymentMethod === 'bank')
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">Banco / Plataforma</label>
                                                <select class="form-control" wire:model.live="bankId">
                                                    <option value="">Seleccione...</option>
                                                    @foreach($banks as $b)
                                                        <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->currency_code }})</option>
                                                    @endforeach
                                                </select>
                                                @error('bankId') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>

                                            @if($isZelleSelected)
                                                {{-- ZELLE FIELDS --}}
                                                
                                                @if($zelleStatusMessage)
                                                    <div class="col-12">
                                                        <div class="alert alert-{{ $zelleStatusType }} mb-0">
                                                            <i class="fa fa-{{ $zelleStatusType == 'danger' ? 'exclamation-circle' : 'info-circle' }} me-1"></i>
                                                            {{ $zelleStatusMessage }}
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="col-12">
                                                    <label class="form-label">Referencia (Opcional)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-barcode"></i>
                                                        </span>
                                                        <input type="text" wire:model="zelleReference" class="form-control" placeholder="Referencia">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Nombre del Emisor <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-user"></i>
                                                        </span>
                                                        <input type="text" wire:model.live="zelleSender" class="form-control" placeholder="Nombre del titular">
                                                    </div>
                                                    @error('zelleSender') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Fecha <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-calendar"></i>
                                                        </span>
                                                        <input type="date" wire:model.live="zelleDate" class="form-control">
                                                    </div>
                                                    @error('zelleDate') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Monto <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-dollar-sign"></i>
                                                        </span>
                                                        <input type="number" wire:model.live="zelleAmount" class="form-control" placeholder="0.00">
                                                    </div>
                                                    @error('zelleAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Comprobante (Foto) <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-camera"></i>
                                                        </span>
                                                        <input type="file" wire:model="zelleImage" class="form-control" accept="image/*">
                                                    </div>
                                                    @error('zelleImage') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    @if ($zelleImage)
                                                        <div class="mt-2">
                                                            <img src="{{ $zelleImage->temporaryUrl() }}" class="img-thumbnail" style="max-height: 100px;">
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                <div class="col-12 mt-3">
                                                    <div class="alert alert-info py-2">
                                                        <small><i class="fa fa-info-circle me-1"></i> El monto a usar en esta venta se calculará automáticamente.</small>
                                                    </div>
                                                    <label class="form-label">Monto a usar en esta venta</label>
                                                    <input class="form-control" type="number" wire:model="amount" placeholder="Monto a aplicar">
                                                    @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>
            
                                                <div class="col-12">
                                                    <button class="btn btn-purple w-100" style="background-color: #6f42c1; color: white;" wire:click="addPayment">Agregar Pago Zelle</button>
                                                </div>

                                            @else
                                                {{-- STANDARD BANK FIELDS --}}
                                                <div class="col-md-6">
                                                    <label class="form-label">Monto</label>
                                                    <input class="form-control" type="number" wire:model="amount" placeholder="0.00">
                                                    @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">N° Cuenta</label>
                                                    <input class="form-control" type="text" wire:model="accountNumber">
                                                    @error('accountNumber') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Referencia</label>
                                                    <input class="form-control" type="text" wire:model="depositNumber">
                                                    @error('depositNumber') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="col-12">
                                                    <button class="btn btn-primary w-100" wire:click="addPayment">Agregar Pago</button>
                                                </div>
                                            @endif
                                        </div>
                                    @endif


                                </div>
                            </div>
                        </div>

                        {{-- Right Column: List & Totals --}}
                        <div class="col-md-6">
                            {{-- Payments List --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-list me-2"></i>Pagos Agregados</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 300px;">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Método</th>
                                                    <th>Monto</th>
                                                    <th>Equiv.</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse($payments as $index => $p)
                                                    <tr>
                                                        <td>
                                                            <span class="badge bg-secondary">{{ strtoupper($p['method']) }}</span>
                                                            @if($p['method'] == 'bank') <br><small>{{ $p['bank_name'] }}</small> @endif
                                                            @if($p['method'] == 'zelle') 
                                                                <br><small>Zelle: {{ $p['zelle_sender'] }}</small>
                                                                @if(isset($p['zelle_file_url']) && $p['zelle_file_url'])
                                                                    <br><a href="{{ $p['zelle_file_url'] }}" target="_blank"><i class="fa fa-image"></i> Ver</a>
                                                                @endif
                                                            @endif
                                                        </td>
                                                        <td>{{ $p['symbol'] }}{{ number_format($p['amount'], 2) }}</td>
                                                        <td>{{ $symbol }}{{ number_format($p['amount_in_primary'], 2) }}</td>
                                                        <td>
                                                            <button class="btn btn-danger btn-sm" wire:click="removePayment({{ $index }})">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="4" class="text-center py-3 text-muted">No hay pagos agregados</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Totals --}}
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total Pagado:</span>
                                        <span class="fw-bold text-success">{{ $symbol }}{{ number_format($totalPaid, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Restante:</span>
                                        <span class="fw-bold {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $symbol }}{{ number_format($remaining, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Change --}}
                            @if($change > 0)
                                <div class="card">
                                    <div class="card-header bg-warning bg-opacity-10">
                                        <h6 class="mb-0 text-dark"><i class="fa fa-exchange-alt me-2"></i>Vuelto: {{ $symbol }}{{ number_format($change, 2) }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-5">
                                                <select class="form-control form-control-sm" wire:model="selectedChangeCurrency">
                                                    <option value="">Moneda...</option>
                                                    @foreach($currencies as $c)
                                                        <option value="{{ $c->code }}">{{ $c->label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-4">
                                                <input type="number" class="form-control form-control-sm" wire:model="selectedChangeAmount" placeholder="Monto">
                                            </div>
                                            <div class="col-3">
                                                <button class="btn btn-success btn-sm w-100" wire:click="addChangeDistribution"><i class="fa fa-plus"></i></button>
                                            </div>
                                        </div>

                                        <ul class="list-group mt-2">
                                            @foreach($changeDistribution as $idx => $cd)
                                                <li class="list-group-item d-flex justify-content-between align-items-center p-1">
                                                    <small>{{ $cd['symbol'] }}{{ number_format($cd['amount'], 2) }} {{ $cd['currency'] }}</small>
                                                    <button class="btn btn-xs btn-danger" wire:click="removeChangeDistribution({{ $idx }})">&times;</button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#modalPayment').modal('hide')">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="submit" {{ ($remaining > 0.01 && !$allowPartialPayment) ? 'disabled' : '' }}>
                        <i class="fa fa-check me-2"></i>Confirmar Pago
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        window.addEventListener('show-payment-modal', event => {
            $('#modalPayment').modal('show');
        });
        
        window.addEventListener('close-payment-modal', event => {
            $('#modalPayment').modal('hide');
        });
    </script>
</div>
