<div>
<style>
    @media (max-width: 768px) {
        .table-mobile-payments thead {
            display: none;
        }
        .table-mobile-payments tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        .table-mobile-payments td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            text-align: right;
        }
        .table-mobile-payments td:last-child {
            border-bottom: none;
            justify-content: flex-end;
            margin-top: 5px;
        }
        .table-mobile-payments td::before {
            content: attr(data-label);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: #777;
            text-align: left;
            flex: 0 0 40%;
            margin-right: 10px;
        }
        
        .table-mobile-payments td[data-label="Detalles"] {
            flex-direction: column;
            align-items: flex-start;
            text-align: left;
        }
        .table-mobile-payments td[data-label="Detalles"]::before {
            margin-bottom: 5px;
        }

        /* Dark Mode Support */
        body.dark-mode .table-mobile-payments tbody tr {
            background-color: #343a40;
            border-color: #6c757d;
        }
        body.dark-mode .table-mobile-payments td {
             border-bottom-color: #6c757d;
             color: #fff;
        }
        body.dark-mode .table-mobile-payments td::before {
            color: #ccc;
        }
    }
</style>
    <div wire:ignore.self class="modal fade" id="modalPayHistory" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content ">
                <div class="modal-header bg-dark">
                    <h5 class="modal-title">Historial de Pagos</h5>
                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if (!is_array($pays))
                        <div class="order-history table-responsive mt-2">
                            <table class="table table-bordered table-mobile-payments">
                                <thead>
                                    <tr>
                                        <th class='p-2'>Folio</th>
                                        <th class='p-2'>Método</th>
                                        <th class='p-2'>Moneda</th>
                                        <th class='p-2'>Monto</th>
                                        <th class='p-2'>Tasa</th>
                                        <th class='p-2'>Equiv. $</th>
                                        <th class='p-2'>Detalles</th>
                                        <th class='p-2'>Fecha</th>
                                        <th class='p-2'></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalApprovedInPrimary = 0;
                                        $totalPendingInPrimary = 0;
                                        $primaryCurrency = \App\Models\Currency::where('is_primary', true)->first();
                                    @endphp
                                    @foreach ($pays as $pay)
                                        @php
                                            // Determinar nombre del método
                                            $methodName = 'Efectivo';
                                            $badgeColor = 'success';
                                            
                                            $payWay = $pay->pay_way ?? $pay->payment_method;
                                            
                                            if ($payWay == 'deposit' || $payWay == 'bank') {
                                                $methodName = $pay->bank ?? $pay->bank_name ?? 'Banco';
                                                $badgeColor = 'info';

                                            } elseif ($payWay == 'zelle') {
                                                $methodName = 'Zelle';
                                                $badgeColor = 'dark';
                                            } elseif ($payWay == 'credit_note') {
                                                $methodName = 'Nota de Crédito';
                                                $badgeColor = 'warning';
                                            }
                                            
                                            // Determinar nombre de moneda
                                            $currencyName = match($pay->currency) {
                                                'USD' => 'Dólar (USD)',
                                                'COP' => 'Pesos (COP)',
                                                'VES' => 'Bolívares (VES)',
                                                'VED' => 'Bolívares (VED)',
                                                default => $pay->currency
                                            };
                                            
                                            // Calcular equivalente en moneda principal para el total
                                            // Convertir a USD primero (base)
                                            $rate = $pay->exchange_rate > 0 ? $pay->exchange_rate : 1;
                                            $amountInUSD = $pay->amount / $rate;
                                            $amountInPrimary = $amountInUSD * $primaryCurrency->exchange_rate;
                                            
                                            if (isset($pay->status) && $pay->status == 'pending') {
                                                $totalPendingInPrimary += $amountInPrimary;
                                            } elseif (!isset($pay->status) || (isset($pay->status) && $pay->status != 'rejected')) {
                                                $totalApprovedInPrimary += $amountInPrimary;
                                            }
                                        @endphp
                                        <tr>
                                            <td data-label="Folio"> 
                                                <div class="d-flex align-items-center">
                                                    {{ $pay->id }}
                                                    @if(isset($pay->status) && $pay->status == 'pending')
                                                        <span class="badge badge-warning text-dark ms-2" style="font-size: 0.6rem;">PENDIENTE</span>
                                                    @elseif(isset($pay->status) && $pay->status == 'approved')
                                                        <span class="badge badge-success ms-2" style="font-size: 0.6rem;">APROBADO</span>
                                                    @elseif(isset($pay->status) && $pay->status == 'rejected')
                                                        <span class="badge badge-danger ms-2" style="font-size: 0.6rem;">RECHAZADO</span>
                                                    @elseif(isset($pay->status) && $pay->status == 'voided')
                                                        <span class="badge badge-dark ms-2" style="font-size: 0.6rem; background-color: #6c757d;">ANULADO</span>
                                                    @endif
                                                </div>
                                                @if(isset($pay->rejection_reason) && $pay->status == 'rejected')
                                                    <div class="text-danger small mt-1">
                                                        <b>Motivo Rechazo:</b> {{ $pay->rejection_reason }}
                                                    </div>
                                                @endif
                                                @if(isset($pay->rejection_reason) && $pay->status == 'voided')
                                                    <div class="text-secondary small mt-1">
                                                        <b>Motivo Anulación:</b> {{ $pay->rejection_reason }}
                                                    </div>
                                                @endif
                                                @if(!empty($pay->modification_comment))
                                                    <div class="text-info small mt-1">
                                                        <b>Nota Admin:</b> {{ $pay->modification_comment }}
                                                    </div>
                                                @endif
                                            </td>
                                            <td data-label="Método">
                                                <span class="badge badge-{{ $badgeColor }}">
                                                    {{ $methodName }}
                                                </span>
                                            </td>
                                            <td data-label="Moneda">{{ $currencyName }}</td>
                                            <td data-label="Monto" style="background-color: rgb(228, 243, 253)">
                                                <div> <b>{{ number_format($pay->amount, 2) }}</b></div>
                                            </td>
                                            <td data-label="Tasa">{{ number_format($pay->exchange_rate, 2) }}</td>
                                            <td data-label="Equiv. $">
                                                @php
                                                    $equivUsd = 0;
                                                    // Logic: If VED/COP, divide by rate. If USD/Zelle, use amount.
                                                    // $pay->currency is reliable? 
                                                    // Let's use the same logic we used for $amountInUSD above (line 123)
                                                    $rateSafe = $pay->exchange_rate > 0 ? $pay->exchange_rate : 1;
                                                    
                                                    if (in_array($pay->currency, ['VED', 'VES', 'COP'])) {
                                                        $equivUsd = $pay->amount / $rateSafe;
                                                    } else {
                                                        $equivUsd = $pay->amount;
                                                    }
                                                @endphp
                                                <b>${{ number_format($equivUsd, 2) }}</b>
                                            </td>
                                            <td data-label="Detalles">
                                                @php
                                                    $payWay = $pay->pay_way ?? $pay->payment_method;
                                                @endphp
                                                @if ($payWay == 'deposit' || $payWay == 'bank')
                                                    @if($pay->bankRecord)
                                                        <div class="small text-left">
                                                            <div><b>Banco:</b> {{ $pay->bankRecord->bank->name ?? ($pay->bank ?? ($pay->bank_name ?? 'N/A')) }}</div>
                                                            <div><b>Fecha:</b> {{ \Carbon\Carbon::parse($pay->bankRecord->payment_date)->format('d/m/Y') }}</div>
                                                            <div><b>Monto:</b> {{ number_format($pay->bankRecord->amount, 2) }}</div>
                                                            <div><b>Ref:</b> {{ $pay->bankRecord->reference }}</div>
                                                            @if($pay->bankRecord->image_path)
                                                                <div class="mt-1">
                                                                    @can('payments.view_proof')
                                                                    <a href="{{ asset('storage/' . $pay->bankRecord->image_path) }}" target="_blank" class="text-primary">
                                                                        <i class="fas fa-image"></i> Ver Comprobante
                                                                    </a>
                                                                    @endcan
                                                                </div>
                                                            @endif
                                                        </div>
                                                    @else
                                                        <div>
                                                            <small>
                                                                @if($pay->account_number) Cta:{{ $pay->account_number }} @endif
                                                                @if($pay->account_number && ($pay->deposit_number || $pay->reference_number)) / @endif
                                                                @if($pay->deposit_number || $pay->reference_number) Ref:{{ $pay->reference_number ?? $pay->deposit_number }} @endif
                                                            </small>
                                                        </div>
                                                    @endif
                                                @elseif ($payWay == 'zelle' && $pay->zelleRecord)
                                                    <div class="small text-left">
                                                        <div><b>Emisor:</b> {{ $pay->zelleRecord->sender_name }}</div>
                                                        <div><b>Fecha:</b> {{ \Carbon\Carbon::parse($pay->zelleRecord->zelle_date)->format('d/m/Y') }}</div>
                                                        <div><b>Monto Orig.:</b> ${{ number_format($pay->zelleRecord->amount, 2) }}</div>
                                                        <div><b>Saldo Rest.:</b> ${{ number_format($pay->zelleRecord->remaining_balance, 2) }}</div>
                                                        @if($pay->zelleRecord->reference)
                                                            <div><b>Ref:</b> {{ $pay->zelleRecord->reference }}</div>
                                                        @endif
                                                        <div class="mt-1">
                                                            <span class="badge badge-{{ $pay->zelleRecord->remaining_balance <= 0.01 ? 'secondary' : 'success' }}">
                                                                {{ $pay->zelleRecord->remaining_balance <= 0.01 ? 'Agotado' : 'Disponible' }}
                                                            </span>
                                                        </div>
                                                        @if($pay->zelleRecord->image_path)
                                                            <div class="mt-1">
                                                                @can('payments.view_proof')
                                                                <a href="{{ asset('storage/' . $pay->zelleRecord->image_path) }}" target="_blank" class="text-primary">
                                                                    <i class="fas fa-image"></i> Ver Comprobante
                                                                </a>
                                                                @endcan
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                            </td>

                                            <td data-label="Fecha"> {{ app('fun')->dateFormat($pay->payment_date ?? $pay->created_at) }}</td>
                                            <td data-label="Acciones">
                                                @if($payWay !== 'credit_note')
                                                    <div class="d-flex flex-column gap-1">
                                                        @can('payments.print_receipt')
                                                        <button class="btn btn-default btn-sm"
                                                            wire:click="printReceipt({{ $pay->id }})" title="Imprimir Recibo">
                                                            <i class="fas fa-print"></i>
                                                        </button>
                                                        @endcan
                                                        
                                                        @if(isset($pay->status) && $pay->status == 'pending')
                                                            @can('payments.approve')
                                                                <div class="d-flex gap-1 mt-1">
                                                                    <button class="btn btn-success btn-sm"
                                                                        wire:click="approvePayment({{ $pay->id }})" 
                                                                        wire:confirm="¿Estás seguro de aprobar este pago?"
                                                                        title="Aprobar Pago">
                                                                        <i class="fas fa-check"></i>
                                                                    </button>
                                                                    
                                                                    <!-- EDIT BUTTON -->
                                                                    <button class="btn btn-info btn-sm"
                                                                        wire:click="editPayment({{ $pay->id }})"
                                                                        title="Editar Pago">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    
                                                                    <button class="btn btn-warning btn-sm"
                                                                        type="button"
                                                                        x-on:click="
                                                                            swal({
                                                                                title: 'Rechazar Pago',
                                                                                text: 'Por favor indica el motivo del rechazo:',
                                                                                content: 'input',
                                                                                buttons: {
                                                                                    cancel: { text: 'Cancelar', visible: true, closeModal: true, value: null },
                                                                                    confirm: { text: 'Sí, Rechazar', value: true, visible: true, closeModal: true }
                                                                                },
                                                                                dangerMode: true,
                                                                            }).then((value) => {
                                                                                if (value === null) return;
                                                                                if (value === '') { swal('Error', '¡Debes escribir un motivo!', 'error'); return; }
                                                                                $wire.rejectPayment({{ $pay->id }}, value);
                                                                            })
                                                                        "
                                                                        title="Rechazar Pago">
                                                                        <i class="fas fa-times"></i>
                                                                    </button>
                                                                </div>
                                                            @endcan
                                                        @endif
                                                        
                                                        @if(isset($pay->status) && ($pay->status == 'pending' || $pay->status == 'rejected'))
                                                            @can('payments.delete')
                                                            <button class="btn btn-outline-danger btn-sm mt-1"
                                                                wire:click="deletePayment({{ $pay->id }})"
                                                                wire:confirm="¿Eliminar este pago? Si es Zelle/Banco se restaurará el saldo."
                                                                title="Eliminar Pago">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            @endcan
                                                        @endif

                                                        @if(isset($pay->status) && $pay->status == 'approved')
                                                            @php
                                                                $isToday = \Carbon\Carbon::parse($pay->payment_date ?? $pay->created_at)->isToday();
                                                                $canVoid = false;
                                                                if ($isToday && auth()->user()->can('payments.void_today')) $canVoid = true;
                                                                if (!$isToday && auth()->user()->can('payments.void_anytime')) $canVoid = true;
                                                            @endphp

                                                            @if($canVoid)
                                                            <button class="btn btn-danger btn-sm mt-1"
                                                                type="button"
                                                                x-on:click="
                                                                    swal({
                                                                        title: 'Anular Pago',
                                                                        text: '¿Estás seguro de ANULAR este pago? Se restaurará el saldo en Banco/Zelle y la deuda del cliente aumentará. Indica el motivo:',
                                                                        content: 'input',
                                                                        buttons: {
                                                                            cancel: { text: 'Cancelar', visible: true, closeModal: true, value: null },
                                                                            confirm: { text: 'Sí, Anular', value: true, visible: true, closeModal: true }
                                                                        },
                                                                        dangerMode: true,
                                                                    }).then((value) => {
                                                                        if (value === null) return;
                                                                        if (value === '') { swal('Error', '¡Debes escribir un motivo!', 'error'); return; }
                                                                        $wire.voidPayment({{ $pay->id }}, value);
                                                                    })
                                                                "
                                                                title="Anular Pago">
                                                                <i class="fas fa-ban"></i> Anular
                                                            </button>
                                                            @endif
                                                        @endif
                                                    </div>
                                                @else
                                                    <div class="text-center text-muted">
                                                        <small><i class="fas fa-info-circle"></i> Referencia interna</small>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                    
                                    {{-- CREDIT NOTES / RETURNS --}}
                                    @php
                                        $saleIdForReturns = $history_sale_id ?? null;
                                        $sale_for_returns = $saleIdForReturns ? \App\Models\Sale::find($saleIdForReturns) : null;
                                        $returnsForHistory = $sale_for_returns ? $sale_for_returns->returns->where('refund_method', 'debt_reduction')->where('status', 'approved') : collect([]);
                                    @endphp
                                    @foreach($returnsForHistory as $return)
                                        @php
                                            $rate = $sale_for_returns->primary_exchange_rate > 0 ? $sale_for_returns->primary_exchange_rate : 1;
                                            $equivUsd = $return->total_returned / $rate;
                                            $amountInPrimary = $equivUsd * $primaryCurrency->exchange_rate;
                                            $totalApprovedInPrimary += $amountInPrimary;
                                        @endphp
                                        <tr>
                                            <td data-label="Folio">
                                                <div class="d-flex align-items-center">
                                                    N/C-{{ $return->return_number }}
                                                    <span class="badge badge-success ms-2" style="font-size: 0.6rem;">APROBADO</span>
                                                </div>
                                            </td>
                                            <td data-label="Método">
                                                <span class="badge badge-warning">Nota de Crédito</span>
                                            </td>
                                            <td data-label="Moneda">Dólar (USD)</td>
                                            <td data-label="Monto" style="background-color: rgb(228, 243, 253)">
                                                <div> <b>{{ number_format($return->total_returned, 2) }}</b></div>
                                            </td>
                                            <td data-label="Tasa">{{ number_format($rate, 2) }}</td>
                                            <td data-label="Equiv. $">
                                                <b>${{ number_format($equivUsd, 2) }}</b>
                                            </td>
                                            <td data-label="Detalles">
                                                <div class="small mt-1 text-info"><b>Nota Admin:</b> {{ $return->reason }}</div>
                                            </td>
                                            <td data-label="Fecha"> {{ app('fun')->dateFormat($return->created_at) }}</td>
                                            <td data-label="Acciones">
                                                <div class="text-center text-muted">
                                                    <small><i class="fas fa-info-circle"></i> Referencia interna</small>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach

                                    @if(count($pays) == 0 && count($returnsForHistory) == 0)
                                        <tr>
                                            <td colspan="9" class="text-center">Sin pagos</td>
                                        </tr>
                                    @endif
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><b>TOTAL APROBADO (Estimado en {{ $primaryCurrency->code }}):</b></td>
                                        <td colspan="5"><b>${{ number_format($totalApprovedInPrimary, 2) }}</b></td>
                                    </tr>
                                    @if($totalPendingInPrimary > 0)
                                    <tr>
                                        <td colspan="3" class="text-end text-warning"><b>TOTAL PENDIENTE (Estimado en {{ $primaryCurrency->code }}):</b></td>
                                        <td colspan="5" class="text-warning"><b>${{ number_format($totalPendingInPrimary, 2) }}</b></td>
                                    </tr>
                                    @endif
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                @if(isset($pays) && count($pays) > 0)
                    @can('payments.print_pdf')
                    <button type="button" class="btn btn-danger" wire:click="generatePaymentHistoryPdf({{ $pays[0]->sale_id ?? $saleId }})">
                        <i class="fas fa-file-pdf"></i> Imprimir Reporte PDF
                    </button>
                    @endcan
                @endif
                
                @can('payments.print_history')
                <button type="button" class="btn btn-dark" wire:click="printHistory">
                    <i class="fas fa-print"></i> Imprimir Historial (Ticket)
                </button>
                @endcan

                @if(isset($pays) && count($pays) > 0 && $totalPendingInPrimary > 0)
                    @can('sales.reset_credit_snapshot')
                    <button type="button" class="btn btn-outline-warning" 
                            wire:click="resetCreditSnapshot({{ $pays[0]->sale_id }})"
                            wire:confirm="¿Estás seguro de actualizar las reglas de crédito? Esto aplicará la configuración actual del cliente a esta venta de forma permanente.">
                        <i class="fas fa-sync-alt"></i> Actualizar Reglas de Crédito
                    </button>
                    @endcan
                @endif
                <button type="button" class="btn btn-dark" data-dismiss="modal">Cerrar</button>
            </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Fix for SweetAlert input not throwable when Bootstrap Modal is open
            // Bootstrap enforces focus on the modal, blocking external inputs (like SweetAlert)
            // This overrides that behavior safely for this view.
            if ($.fn.modal && $.fn.modal.Constructor.prototype._enforceFocus) {
                $.fn.modal.Constructor.prototype._enforceFocus = function() {};
            }
            
            Livewire.on('show-edit-payment-modal', () => {
                $('#modalEditPayment').modal('show');
            });
            Livewire.on('hide-edit-payment-modal', () => {
                $('#modalEditPayment').modal('hide');
            });
        });
    </script>
    
    @isset($editSaleTotal)
    <!-- Modal Edit Payment -->
    <div wire:ignore.self class="modal fade" id="modalEditPayment" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">Editar Pago Pendiente</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card bg-light border-secondary shadow-none">
                                <div class="card-body p-2 d-flex justify-content-between align-items-center">
                                    <div class="text-center w-33 border-right">
                                        <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.7rem;">Monto Venta</small>
                                        <span class="font-weight-bold">${{ number_format($editSaleTotal, 2) }}</span>
                                    </div>
                                    <div class="text-center w-33 border-right">
                                        <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.7rem;">Abonado</small>
                                        <span class="font-weight-bold text-success">${{ number_format($editSalePaid, 2) }}</span>
                                    </div>
                                    <div class="text-center w-33">
                                        <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.7rem;">Deuda Actual</small>
                                        <span class="font-weight-bold text-danger">${{ number_format($editSaleDebt, 2) }}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group">
                                <label>Monto</label>
                                <input type="number" step="0.01" class="form-control" wire:model.live="editPaymentAmount">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group">
                                <label>Tasa de Cambio</label>
                                <input type="number" step="0.01" class="form-control" wire:model.live="editPaymentRate">
                            </div>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-12 text-center bg-light p-2 rounded border">
                            <strong>Equivalente en Dólares ($):</strong> 
                            <span class="text-success h5 mb-0">
                                @if(is_numeric($editPaymentAmount) && is_numeric($editPaymentRate) && $editPaymentRate > 0)
                                    ${{ number_format((float)$editPaymentAmount / (float)$editPaymentRate, 2) }}
                                @else
                                    $0.00
                                @endif
                            </span>
                        </div>
                    </div>
                    
                    @if(isset($editEarlyDiscountAmount) && ($editEarlyDiscountAmount > 0 || $editUsdDiscountAmount > 0))
                    <div class="row mb-3">
                        <div class="col-12">
                            <div class="card bg-light border-info shadow-none">
                                <div class="card-body p-2">
                                    <h6 class="text-info font-weight-bold mb-2"><i class="fas fa-tags"></i> Descuentos Aplicables</h6>
                                    @if($editEarlyDiscountAmount > 0)
                                    <div class="custom-control custom-switch mb-1">
                                        <input type="checkbox" class="custom-control-input" id="editApplyEarlyDiscount" wire:model.live="editApplyEarlyDiscount">
                                        <label class="custom-control-label" for="editApplyEarlyDiscount">
                                            {{ $editEarlyDiscountReason ?: 'Pronto Pago' }} ({{ $editEarlyDiscountPercent }}%): <span class="text-success font-weight-bold">${{ number_format($editEarlyDiscountAmount, 2) }}</span>
                                        </label>
                                    </div>
                                    @endif
                                    
                                    @if($editUsdDiscountAmount > 0)
                                    <div class="custom-control custom-switch">
                                        <input type="checkbox" class="custom-control-input" id="editApplyUsdDiscount" wire:model.live="editApplyUsdDiscount">
                                        <label class="custom-control-label" for="editApplyUsdDiscount">
                                            Pago en Divisa ({{ $editUsdDiscountPercent }}%): <span class="text-success font-weight-bold">${{ number_format($editUsdDiscountAmount, 2) }}</span>
                                        </label>
                                    </div>
                                    @endif
                                    <small class="text-muted d-block mt-2">Los descuentos seleccionados se restarán de la deuda total del cliente al aprobar este pago.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @php
                        $predictiveValueUSD = (is_numeric($editPaymentAmount) && is_numeric($editPaymentRate) && $editPaymentRate > 0) ? ((float)$editPaymentAmount / (float)$editPaymentRate) : 0;
                        if (isset($editApplyEarlyDiscount) && $editApplyEarlyDiscount) $predictiveValueUSD += $editEarlyDiscountAmount;
                        if (isset($editApplyUsdDiscount) && $editApplyUsdDiscount) $predictiveValueUSD += $editUsdDiscountAmount;
                        $remainingPredicted = max(0, $editSaleDebt - $predictiveValueUSD);
                    @endphp
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="alert {{ $remainingPredicted <= 0.05 ? 'alert-success' : 'alert-warning' }} m-0 p-2 text-center border">
                                <strong>Saldo Restante Posterior a este Abono: </strong> 
                                <span class="h5 mb-0 font-weight-bold">${{ number_format($remainingPredicted, 2) }}</span>
                                @if($remainingPredicted <= 0.05)
                                    <br><small class="font-weight-bold"><i class="fas fa-check-circle"></i> ¡Este pago liquida la deuda restante!</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <label>Referencia / Envío</label>
                        <input type="text" class="form-control" wire:model="editPaymentRef">
                        <small class="text-muted">Si ingresas un número de cédula aquí, se sobrescribirá con el comprobante bancario real para poder aprobarlo.</small>
                    </div>
                    <div class="form-group">
                        <label>Fecha</label>
                        <input type="date" class="form-control" wire:model="editPaymentDate">
                    </div>
                    <div class="form-group">
                        <label>Comentario de Modificación (Opcional)</label>
                        <textarea class="form-control" wire:model="editPaymentComment" rows="2" placeholder="Ej: Se corrigió la tasa de cambio a la fecha del depósito real..."></textarea>
                        <small class="text-muted">Este comentario será visible para el vendedor en el historial.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" wire:click="updatePayment">Guardar y Actualizar Banco</button>
                </div>
            </div>
        </div>
    </div>
    @endisset
</div>
