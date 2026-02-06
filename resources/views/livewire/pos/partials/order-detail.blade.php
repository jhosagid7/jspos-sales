<div>
    <div wire:ignore.self class="modal fade" id="modalOrderDetail" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="p-1 modal-header bg-primary">
                    <h5 class="modal-title">Detalles de la orden #{{ $order_id }}</h5>
                    <button class="py-0 btn-close" type="button" data-dismiss="modal" aria-label="Close" onclick="$('#modalOrderDetail').modal('hide')"></button>
                </div>

                <div class="modal-body">
                    @if (count($details) > 0)
                        <div class="table-responsive">
                            <table class="table table-responsive-md table-hover table-mobile-details" id="tblPermissions">
                                <thead class="thead-primary">
                                    <tr class="text-center">
                                        <th>Folio</th>
                                        <th>Descripción</th>
                                        <th>Cantidad</th>
                                        <th>Precio</th>
                                        <th>Importe</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($details as $detail)
                                        <tr class="text-center">
                                            <td data-label="Folio">{{ $detail->id }}</td>
                                            <td data-label="Descripción">{{ $detail->product->name }}</td>
                                            <td data-label="Cantidad">{{ $detail->quantity }}</td>
                                            <td data-label="Precio">${{ $detail->sale_price }}</td>
                                            <td data-label="Importe">${{ round($detail->sale_price * $detail->quantity, 2) }}</td>

                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Sin detalles</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="2"><b>Totales</b></td>
                                        <td class="text-center">{{ $details->sum('quantity') }}</td>
                                        <td></td>
                                        <td class="text-center">
                                            @php
                                                $sumTotalDetail = $details->sum(function ($item) {
                                                    return $item->quantity * $item->sale_price;
                                                });
                                            @endphp
                                            ${{ round($sumTotalDetail, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif

                </div>

                <div class="modal-footer">

                    <button class="btn btn-dark " type="button" data-dismiss="modal" onclick="$('#modalOrderDetail').modal('hide')">Cerrar</button>


                </div>

            </div>
        </div>
    </div>
</div>
