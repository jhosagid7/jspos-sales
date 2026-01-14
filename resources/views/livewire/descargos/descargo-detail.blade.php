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
                            </div>
                            <div class="col-sm-6 text-right">
                                <div><b>Responsable:</b> {{ $descargoObt->user->name ?? 'N/A' }}</div>
                                <div><b>Autorizado Por:</b> {{ $descargoObt->authorized_by }}</div>
                                <div><b>Estado:</b> <span class="badge badge-{{ $descargoObt->status == 'approved' ? 'success' : 'warning' }}">{{ strtoupper($descargoObt->status) }}</span></div>
                            </div>
                        </div>

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
                                        <th>Costo</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($details as $detail)
                                        <tr class="text-center">
                                            <td>{{ $detail->product->sku }}</td>
                                            <td>{{ $detail->product->name }}</td>
                                            <td>{{ number_format($detail->quantity, 2) }}</td>
                                            <td>${{ number_format($detail->cost, 2) }}</td>
                                            <td>${{ number_format($detail->quantity * $detail->cost, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center">Sin detalles</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <td colspan="4" class="text-right"><b>Total Costo:</b></td>
                                        <td class="text-center"><b>${{ number_format($details->sum(function($d){ return $d->quantity * $d->cost; }), 2) }}</b></td>
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
