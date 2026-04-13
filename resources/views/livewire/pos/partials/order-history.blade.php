<div wire:ignore.self class="modal fade" id="modalOrderHistory" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">Historial y Auditoría de la Orden #{{ $order_selected_id }}</h5>
                <button class="py-0 btn-close" type="button" aria-label="Close" onclick="$('#modalOrderHistory').modal('hide')" wire:click="resetSelection"></button>
            </div>
            <div class="modal-body" style="max-height: 500px; overflow-y: auto;">
                <div class="activity-timeline">
                    @forelse($orderHistory as $log)
                        <div class="media mb-4">
                            <div class="activity-line"></div>
                            <div class="media-body">
                                <div class="d-flex justify-content-between">
                                    <h6 class="m-0 text-bold text-primary">
                                        @switch($log['action'])
                                            @case('created')
                                                <i class="fas fa-plus-circle text-success mr-2"></i> Apertura de Orden
                                                @break
                                            @case('sent_to_office')
                                                <i class="fas fa-paper-plane text-info mr-2"></i> Enviada a Oficina
                                                @break
                                            @case('reverted_to_draft')
                                                <i class="fas fa-undo text-warning mr-2"></i> Devuelta a Borrador
                                                @break
                                            @case('processed')
                                                <i class="fas fa-check-double text-success mr-2"></i> Procesada / Facturada
                                                @break
                                            @case('deleted')
                                                <i class="fas fa-trash text-danger mr-2"></i> Eliminada
                                                @break
                                            @default
                                                <i class="fas fa-info-circle text-muted mr-2"></i> {{ $log['action'] }}
                                        @endswitch
                                    </h6>
                                    <span class="text-muted small">
                                        {{ \Carbon\Carbon::parse($log['created_at'])->format('d/m/Y h:i A') }}
                                    </span>
                                </div>
                                <p class="mt-1 mb-1 text-dark" style="font-weight: 500;">
                                    {{ $log['description'] }}
                                </p>
                                <div class="d-flex align-items-center">
                                    <span class="badge badge-light-secondary mr-2">
                                        <i class="fas fa-user-edit mr-1"></i> {{ $log['user']['name'] ?? 'Sistema' }}
                                    </span>
                                    
                                    @php 
                                        $details = $log['details']; 
                                    @endphp
                                    
                                    @if($log['action'] == 'created' && isset($details['items_count']))
                                        <span class="badge badge-light-success">
                                            {{ $details['items_count'] }} productos cargados
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-5">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay registros de auditoría para esta orden.</p>
                        </div>
                    @endforelse
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="$('#modalOrderHistory').modal('hide')" wire:click="resetSelection">Cerrar</button>
            </div>
        </div>
    </div>
</div>
