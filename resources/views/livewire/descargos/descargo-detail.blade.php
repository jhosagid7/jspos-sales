<div>
    <div wire:ignore.self class="modal fade" id="modalDescargoDetail" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="p-1 modal-header bg-dark text-white">
                    <h5 class="modal-title">Detalles del Descargo #{{ $descargo_id }}</h5>
                    <button class="py-0 btn-close text-white" type="button" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    @if ($descargoObt)
                        {{-- Header Information --}}
                        <div class="row mb-4">
                            <div class="col-sm-6">
                                <div><b>Depósito:</b> {{ $descargoObt->warehouse->name ?? 'N/A' }}</div>
                                <div><b>Fecha:</b> {{ $descargoObt->date->format('d/m/Y H:i') }}</div>
                                <div><b>Motivo:</b> {{ $descargoObt->motive }}</div>
                                <div><b>Autorizado Por:</b> {{ $descargoObt->authorized_by }}</div>
                            </div>
                            <div class="col-sm-6 text-right">
                                <div><b>Responsable:</b> {{ $descargoObt->user->name ?? 'N/A' }}</div>
                                <div>
                                    <b>Estado:</b> 
                                    @php
                                        $badgeClass = 'warning';
                                        if($descargoObt->status == 'approved') $badgeClass = 'success';
                                        elseif($descargoObt->status == 'rejected') $badgeClass = 'danger';
                                        elseif($descargoObt->status == 'voided') $badgeClass = 'secondary';
                                    @endphp
                                    <span class="badge badge-{{ $badgeClass }} text-uppercase">{{ $descargoObt->status }}</span>
                                </div>
                                @if($descargoObt->status == 'approved')
                                    <div class="mt-1"><small>Aprobado: {{ $descargoObt->approval_date->format('d/m/Y H:i') }} por {{ $descargoObt->approver->name ?? 'N/A' }}</small></div>
                                @endif
                            </div>
                        </div>

                         @if($descargoObt->status == 'rejected')
                            <div class="alert alert-danger">
                                <b>RECHAZADO:</b> {{ $descargoObt->rejection_reason }}
                                <br><small>Por: {{ $descargoObt->rejecter->name ?? 'N/A' }} el {{ $descargoObt->rejection_date->format('d/m/Y H:i') }}</small>
                            </div>
                        @endif

                        @if($descargoObt->status == 'voided')
                            <div class="alert alert-secondary">
                                <b>ELIMINADO/ANULADO:</b> {{ $descargoObt->deletion_reason }}
                                <br><small>Por: {{ $descargoObt->deleter->name ?? 'N/A' }} el {{ $descargoObt->deletion_date->format('d/m/Y H:i') }}</small>
                            </div>
                        @endif

                        @if($descargoObt->comments)
                            <div class="row mb-3">
                                <div class="col-12">
                                    <div class="alert alert-light border">
                                        <strong>Comentarios:</strong> {{ $descargoObt->comments }}
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
                                                    @php $items = json_decode($detail->items_json, true); @endphp
                                                    <ul class="mb-0 p-0 pl-3">
                                                        @foreach($items as $i)
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
                    @if ($descargo_id)
                        <a class="btn btn-outline-danger"
                            href="{{ route('descargos.pdf', $descargo_id) }}" target="_blank">
                            <i class="fas fa-file-pdf"></i> Imprimir PDF
                        </a>
                    @endif
                    <button class="btn btn-dark" type="button" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
