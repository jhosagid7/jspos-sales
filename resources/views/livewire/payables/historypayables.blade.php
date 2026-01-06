<div>
    <div wire:ignore.self class="modal fade" id="modalPayableHistory" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-fullscreen" role="document">
            <div class="modal-content ">
                <div class="modal-header bg-dark">
                    <h5 class="modal-title">Historial de Pagos</h5>
                    <button class="btn-close py-0" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if (!is_array($pays))
                        <div class="order-history table-responsive  mt-2">
                            <table class="table table-bordered">
                                <thead class="">
                                    <tr>
                                        <th class='p-2'>Folio</th>
                                        <th class='p-2'>Monto</th>
                                        <th class='p-2'>Tipo</th>
                                        <th class='p-2'>F.Pago</th>
                                        <th class='p-2'>Banco</th>
                                        <th class='p-2'>Fecha</th>
                                        <th class='p-2'></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($pays as $pay)
                                        <tr>
                                            <td> {{ $pay->id }} </td>
                                            <td style="background-color: rgb(228, 243, 253)">
                                                <div> <b>${{ $pay->amount }}</b></div>
                                            </td>
                                            <td>{{ $pay->type == 'pay' ? 'Abono' : 'Liquidación' }}</td>
                                            <td>
                                                {{ $pay->pay_way == 'cash' ? 'Efectivo' : 'Depósito' }}
                                            </td>
                                            <td>
                                                @if ($pay->pay_way == 'deposit')
                                                    <div>{{ $pay->bank }}</div>
                                                    <div>
                                                        <small>NC:{{ $pay->account_number }} /
                                                            ND:{{ $pay->deposit_number }}</small>
                                                    </div>
                                                @endif

                                            </td>

                                            <td> {{ app('fun')->dateFormat($pay->created_at) }}</td>
                                            <td>
                                                <button class="btn btn-light "
                                                    wire:click="printReceipt({{ $pay->id }})">
                                                    <i class="fa fa-print fa-lg"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6">Sin pagos</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td>TOTALES: </td>
                                        <td>${{ round($pays->sum('amount'), 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary " type="button" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
