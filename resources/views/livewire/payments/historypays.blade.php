<div>
    <div wire:ignore.self class="modal fade" id="modalPayHistory" tabindex="-1" role="dialog">
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
                        <div class="order-history table-responsive  mt-2">
                            <table class="table table-bordered">
                                <thead class="">
                                    <tr>
                                        <th class='p-2'>Folio</th>
                                        <th class='p-2'>Método</th>
                                        <th class='p-2'>Moneda</th>
                                        <th class='p-2'>Monto</th>
                                        <th class='p-2'>Tasa</th>
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
                                            <td> {{ $pay->id }} </td>
                                            <td>
                                                <span class="badge badge-{{ $badgeColor }}">
                                                    {{ $methodName }}
                                                </span>
                                            </td>
                                            <td>{{ $currencyName }}</td>
                                            <td style="background-color: rgb(228, 243, 253)">
                                                <div> <b>{{ number_format($pay->amount, 2) }}</b></div>
                                            </td>
                                            <td>{{ number_format($pay->exchange_rate, 2) }}</td>
                                            <td>
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
                                                                    <a href="{{ asset('storage/' . $pay->bankRecord->image_path) }}" target="_blank" class="text-primary">
                                                                        <i class="fas fa-image"></i> Ver Comprobante
                                                                    </a>
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
                                                                <a href="{{ asset('storage/' . $pay->zelleRecord->image_path) }}" target="_blank" class="text-primary">
                                                                    <i class="fas fa-image"></i> Ver Comprobante
                                                                </a>
                                                            </div>
                                                        @endif
                                                    </div>
                                                @endif

                                            </td>

                                            <td> {{ app('fun')->dateFormat($pay->created_at) }}</td>
                                            <td>
                                                <button class="btn btn-default btn-sm"
                                                    wire:click="printReceipt({{ $pay->id }})" title="Imprimir Recibo">
                                                    <i class="fas fa-print"></i>
                                                </button>
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
                <button type="button" class="btn btn-dark" wire:click="printHistory">
                    <i class="fas fa-print"></i> Imprimir Historial
                </button>
                <button type="button" class="btn btn-danger" data-dismiss="modal">Cerrar</button>
            </div>
            </div>
        </div>
    </div>
</div>
