    <!-- Modal Scanner -->
    <div class="modal fade" id="modalScanner" tabindex="-1" role="dialog" aria-labelledby="modalScannerLabel" aria-hidden="true" wire:ignore>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="modalScannerLabel">Escanear Código de Barras</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div id="reader" style="width: 100%;"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Variable Item Modal -->
    <div class="modal fade" id="variableItemModal" tabindex="-1" role="dialog" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title font-weight-bold">
                        Seleccionar Item
                        @if(isset($variableItemStats) && isset($variableItemStats['warehouse']))
                             - <span class="text-warning">{{ $variableItemStats['warehouse'] }}</span>
                        @endif
                        
                        <br>
                        @if(isset($variableItemStats))
                            <div class="d-flex justify-content-between mt-2 p-1 bg-light rounded" style="font-size: 0.95rem;">
                                <span class="badge badge-success px-2 py-1 mr-1">Disp: {{ $variableItemStats['available'] }} Kg</span>
                                <span class="badge badge-warning px-2 py-1 mr-1">Reserv: {{ $variableItemStats['reserved'] }} Kg</span>
                                <span class="badge badge-info px-2 py-1">Total: {{ $variableItemStats['total'] }} Kg</span>
                            </div>
                        @endif
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover mb-0">
                            <thead class="thead-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Color</th>
                                    <th>Peso (Kg)</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($availableVariableItems) && count($availableVariableItems) > 0)
                                    @foreach($availableVariableItems as $item)
                                        <tr>
                                            <td>#{{ $item->id }}</td>
                                            <td>{{ $item->color ?? 'N/A' }}</td>
                                            <td class="font-weight-bold">{{ floatval($item->quantity) }}</td>
                                            <td>
                                                <button wire:click="addVariableItem({{ $item->id }})" class="btn btn-sm btn-success">
                                                    <i class="fas fa-plus"></i> Seleccionar
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                @else
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No hay items disponibles</td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let html5QrcodeScanner = null;

            $('#modalScanner').on('shown.bs.modal', function () {
                if (html5QrcodeScanner === null) {
                    html5QrcodeScanner = new Html5Qrcode("reader");
                }
                
                const config = { fps: 10, qrbox: { width: 250, height: 250 } };
                
                html5QrcodeScanner.start({ facingMode: "environment" }, config, onScanSuccess)
                .catch(err => {
                    console.error("Error starting scanner", err);
                    alert("No se pudo iniciar la cámara. Verifique los permisos.");
                });
            });

            $('#modalScanner').on('hidden.bs.modal', function () {
                if (html5QrcodeScanner) {
                    html5QrcodeScanner.stop().then(() => {
                        console.log("Scanner stopped");
                    }).catch(err => {
                        console.error("Failed to stop scanner", err);
                    });
                }
            });

            function onScanSuccess(decodedText, decodedResult) {
                // Handle the scanned code
                console.log(`Code matched = ${decodedText}`, decodedResult);
                
                // Set value to search input
                let searchInput = document.getElementById('inputSearch');
                if(searchInput) {
                    searchInput.value = decodedText;
                    
                    // Trigger Livewire update
                    searchInput.dispatchEvent(new Event('input'));
                }
                
                // Close modal
                $('#modalScanner').modal('hide');
            }
        });
    </script>
