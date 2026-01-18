<div>
    <div class="page-header">
        <div class="page-title">
            <h4>Seguimiento de Entrega</h4>
            <h6>Pedido #{{ $sale->id }} - {{ $sale->customer->name }}</h6>
        </div>
        <div class="page-btn">
            <a href="{{ route('sales') }}" class="btn btn-added">
                <i class="fas fa-arrow-left me-2"></i> Volver a Ventas
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Detalles del Cliente</h5>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item">
                            <strong>Nombre:</strong> {{ $sale->customer->name }}
                        </li>
                        <li class="list-group-item">
                            <strong>Teléfono:</strong> {{ $sale->customer->phone }}
                        </li>
                        <li class="list-group-item">
                            <strong>Dirección:</strong> {{ $sale->customer->address }}
                        </li>
                        <li class="list-group-item">
                            <strong>Ciudad:</strong> {{ $sale->customer->city }}
                        </li>
                    </ul>

                    <h5 class="card-title mt-4">Estado Actual</h5>
                    <div class="alert {{ $sale->delivery_status == 'delivered' ? 'alert-success' : ($sale->delivery_status == 'in_transit' ? 'alert-info' : 'alert-warning') }}">
                        <strong class="text-uppercase">{{ $sale->delivery_status }}</strong>
                        @if($sale->driver)
                            <br>
                            <small>Chofer: {{ $sale->driver->name }}</small>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 col-md-12">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title">Historial de Ubicaciones</h5>
                    
                    @if($locations->count() > 0)
                        <div class="timeline mb-4">
                            @foreach($locations as $location)
                                <div class="border-start border-2 ps-3 pb-3 position-relative">
                                    <div class="position-absolute top-0 start-0 translate-middle rounded-circle bg-primary" style="width: 12px; height: 12px;"></div>
                                    <p class="mb-1 fw-bold">{{ $location->created_at->format('d/m/Y H:i A') }}</p>
                                    <p class="mb-1 text-muted">Estado: <span class="badge bg-secondary">{{ $location->status_at_capture }}</span></p>
                                    <a href="https://www.google.com/maps/search/?api=1&query={{ $location->latitude }},{{ $location->longitude }}" target="_blank" class="btn btn-sm btn-outline-primary mt-1">
                                        <i class="fas fa-map-marker-alt"></i> Ver en Mapa
                                    </a>
                                    
                                    <!-- Embedded Map for the latest location -->
                                    @if($loop->first)
                                        <div class="mt-2">
                                            <iframe 
                                                width="100%" 
                                                height="300" 
                                                frameborder="0" 
                                                scrolling="no" 
                                                marginheight="0" 
                                                marginwidth="0" 
                                                src="https://maps.google.com/maps?q={{ $location->latitude }},{{ $location->longitude }}&z=15&output=embed">
                                            </iframe>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="alert alert-light">
                            No hay registros de ubicación para este pedido.
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
