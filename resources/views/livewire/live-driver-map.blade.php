<div>
    <div wire:poll.30s="loadDrivers">
        <div class="page-header">
            <div class="page-title">
                <h4>Mapa de Choferes en Vivo</h4>
                <h6>Rastreo de ubicación en tiempo real</h6>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Choferes Activos</h5>
                        <div class="list-group list-group-flush">
                            @forelse($drivers as $driver)
                                <a href="{{ $driver['dashboard_url'] }}" 
                                   class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $driver['name'] }}</h6>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i> {{ $driver['last_update'] }}
                                        </small>
                                    </div>
                                    <span class="badge bg-success rounded-pill">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </span>
                                </a>
                            @empty
                                <div class="text-center py-3 text-muted">
                                    No hay choferes con ubicación reciente.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-md-8">
                <div class="card h-100">
                    <div class="card-body p-0">
                        <div id="map" wire:ignore style="height: 500px; width: 100%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('my-styles')
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    @endpush

    @push('my-scripts')
    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
            var map = L.map('map').setView([0, 0], 2); // Default view
            var markers = {};

            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                maxZoom: 19,
                attribution: '&copy; <a href="http://www.openstreetmap.org/copyright">OpenStreetMap</a>'
            }).addTo(map);

            // Function to update markers
            function updateMarkers(drivers) {
                var bounds = L.latLngBounds();
                var hasDrivers = false;

                drivers.forEach(driver => {
                    if (driver.lat && driver.lng) {
                        hasDrivers = true;
                        var latLng = [driver.lat, driver.lng];
                        
                        var popupContent = `
                            <div style="min-width: 220px;">
                                <h6 class="mb-1 d-flex justify-content-between align-items-center fw-bold">
                                    <span>${driver.name}</span>
                                    <a href="${driver.dashboard_url}" class="btn btn-xs btn-primary text-white py-0 px-1" title="Ver Hoja de Ruta">
                                        <i class="fas fa-external-link-alt"></i>
                                    </a>
                                </h6>
                                <small class="text-muted d-block mb-2">
                                    <i class="fas fa-clock"></i> ${driver.last_update}
                                </small>
                                
                                <div class="border-top pt-2 mt-2">
                                    <strong class="d-block mb-1">Ruta (${driver.active_orders.length} pedidos):</strong>
                                    <ul class="list-group list-group-flush small mb-0 mt-1" style="max-height: 120px; overflow-y: auto;">
                                        ${driver.active_orders.map(order => `
                                            <li class="list-group-item px-0 py-1 bg-transparent d-flex justify-content-between align-items-center">
                                                <div style="flex: 1;">
                                                    <i class="fas fa-box-open text-primary me-1"></i> 
                                                    <span class="fw-bold">${order.customer}</span>
                                                    <div class="text-muted" style="font-size: 0.75rem;">${order.address}</div>
                                                </div>
                                                <a href="${order.tracking_url}" class="text-info ms-2" title="Seguimiento">
                                                    <i class="fas fa-map-marked-alt"></i>
                                                </a>
                                            </li>
                                        `).join('')}
                                    </ul>
                                    ${driver.active_orders.length === 0 ? '<em class="text-muted small">Sin pedidos activos</em>' : ''}
                                </div>
                                <div class="mt-2 text-center">
                                     <a href="https://www.google.com/maps/search/?api=1&query=${driver.lat},${driver.lng}" target="_blank" class="btn btn-xs btn-outline-secondary w-100 py-0" style="font-size: 0.7rem;">
                                         <i class="fas fa-map"></i> Ver en Google Maps
                                     </a>
                                </div>
                            </div>
                        `;

                        if (markers[driver.id]) {
                            markers[driver.id].setLatLng(latLng);
                            markers[driver.id].setPopupContent(popupContent);
                        } else {
                            var marker = L.marker(latLng).addTo(map)
                                .bindPopup(popupContent);
                            markers[driver.id] = marker;
                        }
                        bounds.extend(latLng);
                    }
                });

                if (hasDrivers) {
                    map.fitBounds(bounds, { padding: [50, 50], maxZoom: 15 });
                }
            }

            // Initial load
            var initialDrivers = @json($drivers);
            updateMarkers(initialDrivers);

            // Listen for updates
            @this.on('drivers-updated', (event) => {
                updateMarkers(event.drivers);
            });
        });
    </script>
    @endpush
</div>
