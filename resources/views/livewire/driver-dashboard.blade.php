<div>
    <div class="page-header">
        <div class="page-title">
            <h4>Mis Entregas</h4>
            <h6>Gestiona tus pedidos asignados</h6>
        </div>
    </div>

    <!-- Tabs -->
    <ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
        <li class="nav-item" role="presentation">
            <button wire:click="setTab('pending')" class="nav-link {{ $tab == 'pending' ? 'active' : '' }}" type="button">
                <i class="fas fa-truck me-2"></i> Pendientes
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button wire:click="setTab('history')" class="nav-link {{ $tab == 'history' ? 'active' : '' }}" type="button">
                <i class="fas fa-history me-2"></i> Historial
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- Pending Tab -->
        @if($tab == 'pending')
        <div class="row">
            @forelse($sales as $sale)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Pedido #{{ $sale->id }}</h5>
                                <div>
                                    <span class="badge {{ $sale->type != 'cash' ? 'bg-primary' : 'bg-secondary' }} me-1">
                                        {{ $sale->type != 'cash' ? 'Crédito' : 'Contado' }}
                                    </span>
                                    <span class="badge {{ $sale->delivery_status == 'pending' ? 'bg-warning' : 'bg-info' }}">
                                        {{ $sale->delivery_status == 'pending' ? 'Pendiente' : 'En Camino' }}
                                    </span>
                                </div>
                            </div>
                            
                            @if($sale->notes)
                                <div class="alert alert-warning py-1 px-2 mb-2 small">
                                    <i class="fas fa-sticky-note me-1"></i> {{ $sale->notes }}
                                </div>
                            @endif
                            
                            <p class="card-text text-muted mb-1">
                                <i class="fas fa-user me-2"></i> {{ $sale->customer->name }}
                            </p>
                            <p class="card-text text-muted mb-1">
                                <i class="fas fa-map-marker-alt me-2"></i> {{ $sale->customer->address }}
                            </p>
                            <p class="card-text text-muted mb-3">
                                <i class="fas fa-phone me-2"></i> {{ $sale->customer->phone }}
                            </p>

                            <div class="alert alert-light border py-2 mb-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <small class="text-muted">Total a Pagar:</small>
                                    <h5 class="mb-0 text-primary fw-bold">${{ number_format($sale->total, 2) }}</h5>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                @if($sale->delivery_status == 'pending')
                                    <button onclick="updateDeliveryStatus({{ $sale->id }}, 'in_transit')" 
                                            class="btn btn-primary">
                                        <i class="fas fa-truck me-2"></i> Iniciar Ruta
                                    </button>
                                @elseif($sale->delivery_status == 'in_transit')
                                    <button onclick="updateDeliveryStatus({{ $sale->id }}, 'delivered')" 
                                            class="btn btn-success">
                                        <i class="fas fa-check-circle me-2"></i> Confirmar Entrega
                                    </button>
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ urlencode($sale->customer->address . ', ' . $sale->customer->city) }}" 
                                       target="_blank" class="btn btn-outline-secondary">
                                        <i class="fas fa-map me-2"></i> Ver Mapa
                                    </a>
                                @endif
                                
                                <button wire:click="selectSale({{ $sale->id }})" class="btn btn-outline-warning mt-2">
                                    <i class="fas fa-exclamation-circle me-2"></i> Reportar Novedad / Dinero
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3 d-block"></i>
                        No tienes entregas pendientes en este momento.
                    </div>
                </div>
            @endforelse
        </div>
        @endif

        <!-- History Tab -->
        @if($tab == 'history')
        <div class="row">
            @forelse($historySales as $sale)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card shadow-sm border-0 mb-3 bg-light">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h5 class="card-title mb-0">Pedido #{{ $sale->id }}</h5>
                                <span class="badge {{ $sale->delivery_status == 'delivered' ? 'bg-success' : 'bg-danger' }}">
                                    {{ $sale->delivery_status == 'delivered' ? 'Entregado' : 'Cancelado' }}
                                </span>
                            </div>
                            
                            <p class="card-text text-muted mb-1">
                                <i class="fas fa-user me-2"></i> {{ $sale->customer->name }}
                            </p>
                            <p class="card-text text-muted mb-1">
                                <i class="fas fa-calendar-check me-2"></i> {{ $sale->delivered_at ? \Carbon\Carbon::parse($sale->delivered_at)->format('d/m/Y H:i') : 'N/A' }}
                            </p>
                            
                            <div class="alert alert-light border mt-2 mb-0">
                                <small class="d-block text-muted">Total Venta:</small>
                                <strong>${{ number_format($sale->total, 2) }}</strong>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12">
                    <div class="text-center text-muted py-5">
                        <i class="fas fa-history fa-2x mb-3 d-block"></i>
                        No hay historial de entregas recientes.
                    </div>
                </div>
            @endforelse
        </div>
        @endif
    </div>


    <!-- Modal Collection -->
    <div wire:ignore.self class="modal fade" id="modalCollection" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark">Reportar Novedad / Dinero</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info d-flex justify-content-between align-items-center mb-3">
                        <span>Total Factura:</span>
                        <strong class="fs-5">${{ number_format($selectedSaleTotal, 2) }}</strong>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Notas / Observaciones</label>
                        <textarea wire:model="collectionNote" class="form-control" rows="3" placeholder="Ej: Cliente pagó diferencia en efectivo..."></textarea>
                    </div>

                    <hr>
                    <h6 class="mb-3">Registro de Pagos (Opcional)</h6>
                    
                    @foreach($collectionPayments as $index => $payment)
                    <div class="row g-2 mb-2 align-items-end">
                        <div class="col-5">
                            <label class="form-label small">Moneda</label>
                            <select wire:model="collectionPayments.{{ $index }}.currency_id" class="form-select form-select-sm">
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->id }}">{{ $currency->code }} - {{ $currency->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small">Monto</label>
                            <input type="number" step="0.01" wire:model="collectionPayments.{{ $index }}.amount" class="form-control form-select-sm" placeholder="0.00">
                        </div>
                        <div class="col-2">
                            @if($index > 0)
                                <button wire:click="removePaymentRow({{ $index }})" class="btn btn-danger btn-sm w-100">
                                    <i class="fas fa-trash"></i>
                                </button>
                            @endif
                        </div>
                    </div>
                    @endforeach

                    <button wire:click="addPaymentRow" class="btn btn-outline-primary btn-sm mt-2">
                        <i class="fas fa-plus"></i> Agregar otra moneda
                    </button>

                    @if(count($existingCollections) > 0)
                        <hr class="my-4">
                        <h6 class="mb-3 text-muted">Historial de Registros</h6>
                        <div class="list-group list-group-flush">
                            @foreach($existingCollections as $collection)
                                <div class="list-group-item px-0 bg-transparent">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <small class="text-muted d-block mb-1">
                                                {{ $collection->created_at->format('d/m/Y H:i') }}
                                            </small>
                                            @if($collection->note)
                                                <p class="mb-1 small fst-italic">"{{ $collection->note }}"</p>
                                            @endif
                                        </div>
                                    </div>
                                    @if($collection->payments->count() > 0)
                                        <div class="mt-1 ps-2 border-start border-3 border-info">
                                            @foreach($collection->payments as $payment)
                                                <div class="small">
                                                    <span class="fw-bold">{{ $payment->currency->code }}:</span> 
                                                    {{ number_format($payment->amount, 2) }}
                                                </div>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    <button type="button" wire:click="saveCollection" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        function updateDeliveryStatus(saleId, status) {
            if (!navigator.geolocation) {
                alert("Tu navegador no soporta geolocalización.");
                return;
            }

            // Show loading state (optional but good UX)
            // Swal.fire({title: 'Obteniendo ubicación...', allowOutsideClick: false, didOpen: () => { Swal.showLoading() }});

            navigator.geolocation.getCurrentPosition(
                (position) => {
                    // Success
                    const lat = position.coords.latitude;
                    const lng = position.coords.longitude;
                    
                    // Call Livewire method
                    @this.call('updateStatus', saleId, status, lat, lng);
                },
                (error) => {
                    // Error
                    console.error("Error GPS:", error);
                    let msg = "No se pudo obtener la ubicación.";
                    if (error.code == 1) msg = "Permiso de ubicación denegado.";
                    if (error.code == 2) msg = "Ubicación no disponible.";
                    if (error.code == 3) msg = "Tiempo de espera agotado.";
                    
                    alert(msg + " Asegúrate de tener el GPS activado y dar permisos.");
                },
                {
                    enableHighAccuracy: true,
                    timeout: 10000,
                    maximumAge: 0
                }
            );
        }

        document.addEventListener('livewire:initialized', () => {
            @this.on('msg-ok', (msg) => {
                // Swal.close();
                noty(msg, 1); // Assuming 'noty' is your global notification function
            });
            @this.on('msg-error', (msg) => {
                // Swal.close();
                noty(msg, 2);
            });
            @this.on('show-collection-modal', () => {
                var myModal = new bootstrap.Modal(document.getElementById('modalCollection'));
                myModal.show();
            });
            @this.on('hide-collection-modal', () => {
                var el = document.getElementById('modalCollection');
                var modal = bootstrap.Modal.getInstance(el);
                modal.hide();
            });

            // Real-time tracking interval (every 60 seconds)
            setInterval(() => {
                if (navigator.geolocation) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const lat = position.coords.latitude;
                            const lng = position.coords.longitude;
                            @this.call('updateDriverLocation', lat, lng);
                        },
                        (error) => {
                            console.error("Error auto-tracking:", error);
                        },
                        { enableHighAccuracy: true }
                    );
                }
            }, 60000); // 60000 ms = 1 minute
        });
    </script>
</div>
