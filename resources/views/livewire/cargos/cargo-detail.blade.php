<div>
    <div wire:ignore.self class="modal fade" id="modalCargoDetail" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="p-1 modal-header bg-dark text-white">
                    <h5 class="modal-title">Detalles del Cargo #{{ $cargo_id }}</h5>
                    <button class="py-0 btn-close text-white" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    @if ($cargoObt)
                        {{-- Header Information --}}
                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <div><b>Depósito:</b> {{ $cargoObt->warehouse->name ?? 'N/A' }}</div>
                                <div><b>Fecha:</b> {{ $cargoObt->date->format('d/m/Y H:i') }}</div>
                                <div><b>Motivo:</b> {{ $cargoObt->motive }}</div>
                                <div><b>Autorizado Por:</b> {{ $cargoObt->authorized_by }}</div>
                            </div>
                            <div class="col-sm-6 text-right">
                                <div><b>Responsable:</b> {{ $cargoObt->user->name ?? 'N/A' }}</div>
                                <div>
                                    <b>Estado:</b> 
                                    @php
                                        $badgeClass = 'warning';
                                        if($cargoObt->status == 'approved') $badgeClass = 'success';
                                        elseif($cargoObt->status == 'rejected') $badgeClass = 'danger';
                                        elseif($cargoObt->status == 'voided') $badgeClass = 'secondary';
                                    @endphp
                                    <span class="badge badge-{{ $badgeClass }} text-uppercase">{{ $cargoObt->status }}</span>
                                </div>
                                @if($cargoObt->status == 'approved')
                                    <div class="mt-1"><small>Aprobado: {{ $cargoObt->approval_date->format('d/m/Y H:i') }} por {{ $cargoObt->approver->name ?? 'N/A' }}</small></div>
                                @endif
                            </div>
                        </div>

                         @if($cargoObt->status == 'rejected')
                            <div class="alert alert-danger">
                                <b>RECHAZADO:</b> {{ $cargoObt->rejection_reason }}
                                <br><small>Por: {{ $cargoObt->rejecter->name ?? 'N/A' }} el {{ $cargoObt->rejection_date->format('d/m/Y H:i') }}</small>
                            </div>
                        @endif

                        @if($cargoObt->status == 'voided')
                            <div class="alert alert-secondary">
                                <b>ELIMINADO/ANULADO:</b> {{ $cargoObt->deletion_reason }}
                                <br><small>Por: {{ $cargoObt->deleter->name ?? 'N/A' }} el {{ $cargoObt->deletion_date->format('d/m/Y H:i') }}</small>
                            </div>
                        @endif

                        @if($cargoObt->comments)
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="alert alert-light border">
                                        <strong>Comentarios:</strong> {{ $cargoObt->comments }}
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="table-responsive">
                            <table class="table table-striped table-bordered table-sm">
                                <thead class="thead-dark">
                                    <tr class="text-center">
                                        <th>SKU</th>
                                        <th>Producto</th>
                                        <th>Cantidad</th>
                                        <th>Detalles (Items)</th>
                                        <th>Costo</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($details as $detail)
                                        <tr>
                                            <td class="text-center">{{ $detail->product->sku }}</td>
                                            <td>{{ $detail->product->name }}</td>
                                            <td class="text-center">{{ number_format($detail->quantity, 2) }}</td>
                                            <td>
                                                @if($detail->items_json)
                                                    @php $itemsArr = json_decode($detail->items_json, true); @endphp
                                                    <ul class="mb-0 p-0 pl-3">
                                                        @foreach($itemsArr as $i)
                                                            <li><small>{{ $i['weight'] }} kg {{ $i['color'] ? '| '.$i['color'] : '' }} {{ $i['batch'] ? '| Lote: '.$i['batch'] : '' }}</small></li>
                                                        @endforeach
                                                    </ul>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td class="text-right">${{ number_format($detail->cost, 2) }}</td>
                                            <td class="text-right">${{ number_format($detail->quantity * $detail->cost, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="6" class="text-center">Sin detalles</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="5" class="text-right"><b>Total General:</b></td>
                                        <td class="text-right"><b>${{ number_format($details->sum(function($d){ return $d->quantity * $d->cost; }), 2) }}</b></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    @if ($cargo_id)
                        <a class="btn btn-outline-danger"
                            href="{{ route('cargos.pdf', $cargo_id) }}" target="_blank">
                            <i class="fas fa-file-pdf"></i> Imprimir PDF
                        </a>
                    @endif
                    <button class="btn btn-dark" type="button" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
