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
<div>
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
                                        $totalInPrimary = 0;
                                        $primaryCurrency = \App\Models\Currency::where('is_primary', true)->first();
                                    @endphp
                                    @forelse ($pays as $pay)
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
                                            $totalInPrimary += $amountInPrimary;
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
                                                    @endif
                                                </div>
                                                @if(isset($pay->rejection_reason) && $pay->status == 'rejected')
                                                    <div class="text-danger small mt-1">
                                                        <b>Motivo:</b> {{ $pay->rejection_reason }}
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

                                            <td data-label="Fecha"> {{ app('fun')->dateFormat($pay->created_at) }}</td>
                                            <td data-label="Acciones">
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
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="8">Sin pagos</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="3" class="text-end"><b>TOTAL PAGADO (Estimado en {{ $primaryCurrency->code }}):</b></td>
                                        <td colspan="5"><b>${{ number_format($totalInPrimary, 2) }}</b></td>
                                    </tr>
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
        });
    </script>
</div>
