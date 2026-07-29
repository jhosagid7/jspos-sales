<div>
    <div wire:ignore.self class="modal fade" id="modalPartialPayment" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content ">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">Abono a Cuenta</h5>
                    <button class="btn-close py-0" type="button" wire:click="cancelPay" data-dismiss="modal" aria-label="Close" onclick="$('#modalPartialPayment').modal('hide')"></button>
                </div>
                <div class="modal-body">


                        <div class="faq-form">
                            <input wire:keydown.enter.prevent="$set('search', $event.target.value)"
                                class="form-control form-control-lg" type="text"
                                placeholder="Ingresa el nombre del cliente o Nro Factura" id="inputPartialPaySearch"
                                style="background-color: beige">
                            <i class="search-icon" data-feather="user"></i>
                        </div>

                        {{-- @json($sales) --}}
                        <div class="order-history table-responsive  mt-2">
                            <table class="table table-bordered table-mobile-details">
                                <thead class="">
                                    <tr>
                                        <th class='p-2'> Cliente</th>
                                        <th class='p-2'>Vendedor</th>
                                        <th class='p-2'>Venta</th>
                                        <th class='p-2'>Total</th>
                                        <th class='p-2'>Abonado</th>
                                        <th class='p-2 text-warning' title="Abonos notificados pendientes por aprobar">Por Bajar</th>
                                        <th class='p-2'>N/C</th>
                                        <th class='p-2'>Debe</th>
                                        <th class='p-2'></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($sales as $sale)
                                        <tr>
                                            <td data-label="Cliente">
                                                <div class="txt-info">{{ $sale->customer->name }}</div>
                                            </td>
                                            <td data-label="Vendedor">
                                                @php $assignedSeller = $sale->customer->seller; @endphp
                                                @if($assignedSeller)
                                                    <span class="badge" 
                                                          style="background-color: {{ $assignedSeller->color ?? '#eee' }}; color: #333; font-weight: 600; border: 1px solid #ccc;">
                                                        {{ $assignedSeller->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td data-label="Venta">
                                                <div> 
                                                    <b>{{ $sale->id }}</b>
                                                    @foreach ($sale->returns as $return)
                                                        @php 
                                                            $isManual = $return->details->count() === 0;
                                                        @endphp
                                                        <a href="{{ route('pos.returns.generateCreditNotePdf', $return->id) }}" 
                                                           target="_blank" 
                                                           class="ms-1" 
                                                           title="{{ $isManual ? 'Nota de Crédito (Ajuste)' : 'Nota de Crédito (Devolución)' }} #{{ $return->id }}">
                                                            <i class="fas fa-file-invoice" style="font-size: 1.1em; color: {{ $isManual ? '#fd7e14' : '#ffc107' }};"></i>
                                                        </a>
                                                    @endforeach
                                                </div>
                                                <small><i class="icon-calendar"></i>
                                                    {{ app('fun')->dateFormat($sale->created_at) }}</small>
                                            </td>
                                            <td data-label="Total">${{ number_format($sale->total_display, 2) }}</td>
                                            <td data-label="Abonado">${{ number_format($sale->total_paid_display, 2) }}</td>
                                            <td data-label="Por Bajar">
                                                @if(($sale->pending_paid_display ?? 0) > 0)
                                                    <span class="badge badge-warning text-dark font-weight-bold" style="font-size: 0.85rem;" title="Monto de abonos notificados pendientes por bajar/aprobar">
                                                        <i class="fas fa-clock"></i> ${{ number_format($sale->pending_paid_display, 2) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">$0.00</span>
                                                @endif
                                            </td>
                                            <td data-label="N/C" class="text-warning font-weight-bold">
                                                @if($sale->total_returns_display > 0)
                                                    ${{ number_format($sale->total_returns_display, 2) }}
                                                @else
                                                    $0.00
                                                @endif
                                            </td>
                                            <td data-label="Debe">
                                                <div class="font-weight-bold">${{ number_format($sale->debt_display, 2) }}</div>
                                                @if(($sale->pending_paid_display ?? 0) > 0)
                                                    <small class="text-info d-block font-weight-bold" style="font-size: 0.73rem;" title="Saldo restante tras aprobar el abono pendiente">
                                                        (Restaría: ${{ number_format(max(0, $sale->debt_display - $sale->pending_paid_display), 2) }})
                                                    </small>
                                                @endif
                                            </td>
                                            <td data-label="Acciones">


                                                @if ($sale->payments->count() > 0)
                                                    @can('payments.history')
                                                    <button class="btn btn-default btn-sm"
                                                        wire:click="historyPayments({{ $sale->id }})" title="Ver Historial">
                                                        <i class="fas fa-receipt text-primary"></i>
                                                    </button>
                                                    @endcan
                                                @endif

                                                @can('payments.pay')
                                                <button class="btn btn-default btn-sm"
                                                    wire:click="initPay({{ $sale->id }}, '', {{ $sale->debt_display }})" title="Abonar">
                                                    <i class="fas fa-money-bill-wave text-success"></i>
                                                </button>
                                                @endcan



                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6">Sin resultado</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3">
                            {{ $sales->links() }}
                        </div>
                        @include('livewire.payments.historypays')






                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary " type="button" wire:click="cancelPay" data-dismiss="modal" onclick="$('#modalPartialPayment').modal('hide')">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    
    <livewire:common.payment-component />

    <script>
        document.addEventListener('livewire:init', function() {


            $('#modalPartialPayment').on('shown.bs.modal', function() {
                setTimeout(() => {
                    setFocus()
                }, 700)
            })


            Livewire.on('clear-search', event => {
                setFocus()
            })

            Livewire.on('show-payhistory', event => {
                $('#modalPayHistory').modal('show')
            })
            
            // Close modal listener
            Livewire.on('close-modal', event => {
                $('#modalPartialPayment').modal('hide');
            })

        })

        function setFocus() {
            document.getElementById('inputPartialPaySearch').value = ''
            document.getElementById('inputPartialPaySearch').focus()
        }
    </script>
</div>
