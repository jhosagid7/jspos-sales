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
                                        <tr class="text-center">
                                            <td data-label="Folio">{{ $pay->id }}</td>
                                            <td data-label="Método">
                                                <span class="badge badge-{{ $badgeColor }}">{{ strtoupper($methodName) }}</span>
                                            </td>
                                            <td data-label="Moneda">{{ $currencyName }}</td>
                                            <td data-label="Monto">
                                                <b>{{ number_format($pay->amount, 2) }}</b>
                                            </td>
                                            <td data-label="Tasa">
                                                {{ number_format($pay->exchange_rate, 2) }}
                                            </td>
                                            <td data-label="Equiv. $">
                                                <span class="text-primary font-weight-bold">
                                                    ${{ number_format($amountInUSD, 2) }}
                                                </span>
                                            </td>
                                            <td data-label="Detalles" class="text-left small" style="min-width: 200px;">
                                                @if(isset($pay->reference) && $pay->reference)
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="fas fa-hashtag text-muted mr-2" style="width: 15px;"></i>
                                                        <span>Ref: <b>{{ $pay->reference }}</b></span>
                                                    </div>
                                                @endif
                                                @if(isset($pay->comment) && $pay->comment)
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="fas fa-comment-dots text-muted mr-2" style="width: 15px;"></i>
                                                        <span class="text-truncate" style="max-width: 150px;">{{ $pay->comment }}</span>
                                                    </div>
                                                @endif
                                                @if(isset($pay->status) && $pay->status == 'pending')
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-clock text-warning mr-2" style="width: 15px;"></i>
                                                        <span class="badge badge-warning">PENDIENTE</span>
                                                    </div>
                                                @elseif(isset($pay->status) && $pay->status == 'rejected')
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-times-circle text-danger mr-2" style="width: 15px;"></i>
                                                        <span class="badge badge-danger">RECHAZADO</span>
                                                    </div>
                                                @endif
                                            </td>
                                            <td data-label="Fecha" class="small">
                                                {{ $pay->created_at }}
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column gap-1">
                                                    @if(isset($pay->status) && $pay->status == 'pending' && isset($pay->currency) && $pay->currency != 'USD')
                                                        <button class="btn btn-info btn-xs mb-1" wire:click="editPayment({{ $pay->id }}, {{ $pay->amount }}, {{ $pay->exchange_rate }}, '{{ $pay->reference }}', '{{ $pay->created_at }}', '{{ $pay->comment }}')" title="Editar Pago">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                    @endif
                                                    
                                                    @if(!isset($pay->status) || $pay->status != 'rejected')
                                                        <button class="btn btn-danger btn-xs"
                                                            onclick="confirmDeletePay({{ $pay->id }})" title="Eliminar Pago">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    @endif
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-light">
                                    <tr class="font-weight-bold">
                                        <td colspan="5" class="text-right"> Totales:</td>
                                        <td colspan="4" class="text-left p-0">
                                            <div class="d-flex flex-column p-2">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <span class="text-success">Aprobado:</span>
                                                    <span class="text-dark">{{ $primaryCurrency->symbol }}{{ number_format($totalApprovedInPrimary, 2) }}</span>
                                                </div>
                                                @if($totalPendingInPrimary > 0)
                                                <div class="d-flex justify-content-between border-top pt-1">
                                                    <span class="text-warning">Pendiente:</span>
                                                    <span class="text-dark">{{ $primaryCurrency->symbol }}{{ number_format($totalPendingInPrimary, 2) }}</span>
                                                </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <div class="alert alert-info py-4 text-center">
                            <i class="fas fa-info-circle fa-2x d-block mb-3"></i>
                            No se han registrado pagos para esta operaciÃ³n.
                        </div>
                    @endif

                    @if(isset($pays) && count($pays) > 0 && isset($history_sale_id) && $history_sale_id)
                    <button class="btn btn-sm btn-outline-primary mt-3 py-1 btn-block shadow-none" wire:click="syncCreditRules({{ $history_sale_id }})">
                        <i class="fas fa-sync-alt"></i> Actualizar Reglas de Crédito
                    </button>
                    @endif

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            // Fix for SweetAlert input not throwable when Bootstrap Modal is open
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
                                    <small class="text-muted d-block mt-2">Los descuentos seleccionados se restarÃ¡n de la deuda total del cliente al aprobar este pago.</small>
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
                                    <br><small class="font-weight-bold"><i class="fas fa-check-circle"></i> Â¡Este pago liquida la deuda restante!</small>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="form-group mt-3">
                        <label>Referencia / EnvÃ­o</label>
                        <input type="text" class="form-control" wire:model="editPaymentRef">
                        <small class="text-muted">Si ingresas un nÃºmero de cÃ©dula aquÃ­, se sobrescribirÃ¡ con el comprobante bancario real para poder aprobarlo.</small>
                    </div>
                    <div class="form-group">
                        <label>Fecha</label>
                        <input type="date" class="form-control" wire:model="editPaymentDate">
                    </div>
                    <div class="form-group">
                        <label>Comentario de ModificaciÃ³n (Opcional)</label>
                        <textarea class="form-control" wire:model="editPaymentComment" rows="2" placeholder="Ej: Se corrigiÃ³ la tasa de cambio a la fecha del depÃ³sito real..."></textarea>
                        <small class="text-muted">Este comentario serÃ¡ visible para el vendedor en el historial.</small>
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
