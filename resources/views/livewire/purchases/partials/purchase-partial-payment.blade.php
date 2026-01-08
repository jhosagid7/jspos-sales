<div>
    <div wire:ignore.self class="modal fade" id="modalPartialPayment" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content ">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">Abono a Compra</h5>
                    <button class="btn-close py-0" type="button" wire:click="cancelPay" data-dismiss="modal" aria-label="Close" onclick="$('#modalPartialPayment').modal('hide')"></button>
                </div>
                <div class="modal-body">


                        <div class="faq-form">
                            <input wire:keydown.enter.prevent="$set('search', $event.target.value)"
                                class="form-control form-control-lg" type="text"
                                placeholder="Ingresa el nombre del proveedor" id="inputPartialPaySearch"
                                style="background-color: beige">
                            <i class="search-icon" data-feather="user"></i>
                        </div>

                        {{-- @json($purchases) --}}
                        <div class="order-history table-responsive  mt-2">
                            <table class="table table-bordered">
                                <thead class="">
                                    <tr>
                                        <th class='p-2'> Proveedor</th>
                                        <th class='p-2'>Compra</th>
                                        <th class='p-2'>Total</th>
                                        <th class='p-2'>Abonado</th>
                                        <th class='p-2'>Debe</th>
                                        <th class='p-2'></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($purchases as $purchase)
                                        <tr>
                                            <td>
                                                <div class="txt-info">{{ $purchase->supplier->name }}</div>
                                            </td>
                                            <td>
                                                <div> <b>{{ $purchase->id }}</b></div>
                                                <small><i class="icon-calendar"></i>
                                                    {{ app('fun')->dateFormat($purchase->created_at) }}</small>
                                            </td>
                                            <td>${{ number_format($purchase->total_display, 2) }}</td>
                                            <td>${{ number_format($purchase->total_paid_display, 2) }}</td>
                                            <td>${{ number_format($purchase->debt_display, 2) }}</td>
                                            <td>


                                                @if ($purchase->total_paid_display > 0)
                                                    <button class="btn btn-default btn-sm"
                                                        wire:click="historyPayments({{ $purchase->id }})" title="Ver Historial">
                                                        <i class="fas fa-receipt text-primary"></i>
                                                    </button>
                                                @endif

                                                <button class="btn btn-default btn-sm"
                                                    wire:click="initPay({{ $purchase->id }},'{{ $purchase->supplier->name }}',{{ $purchase->debt_display }})" title="Abonar">
                                                    <i class="fas fa-money-bill-wave text-success"></i>
                                                </button>



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
                        @include('livewire.payments.historypays') {{-- Reusing history view if compatible, or create new one --}}







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
