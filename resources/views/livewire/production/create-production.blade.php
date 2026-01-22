<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>{{ $isEdit ? 'Editar Producción' : 'Registrar Producción' }}</b>
                </h4>
                <ul class="tabs tab-pills">
                    <li>
                        <a href="{{ route('production.index') }}" class="tabmenu bg-dark mr-3">
                            Volver
                        </a>
                    </li>
                </ul>
            </div>

            <div class="widget-content">
                <div class="row">
                    <div class="col-sm-12 col-md-4">
                        <div class="form-group">
                            <label>Fecha de Producción</label>
                            <input type="date" wire:model="production_date" class="form-control">
                            @error('production_date') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-8">
                        <div class="form-group">
                            <label>Nota / Observación</label>
                            <input type="text" wire:model="note" class="form-control" placeholder="Opcional...">
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-sm-12 col-md-6">
                        <div class="form-group">
                            <label>Filtrar por Categoría</label>
                            <select wire:model.live="selectedCategory" class="form-control">
                                <option value="">Todas las Categorías</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <div class="form-group">
                            <label>Filtrar por Etiqueta</label>
                            <select wire:model.live="selectedTag" class="form-control">
                                <option value="">Todas las Etiquetas</option>
                                @foreach($tags as $tag)
                                    <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label>Buscar Producto (Nombre o Código)</label>
                            <div class="input-group">
                                <input type="text" 
                                       wire:model.live.debounce.300ms="search" 
                                       wire:keydown="keyDown($event.key)"
                                       class="form-control" 
                                       placeholder="Escanear o buscar..."
                                       id="searchInput"
                                       autofocus>
                                <div class="input-group-append">
                                    <button class="btn btn-primary" data-toggle="modal" data-target="#modalScanner">
                                        <i class="fas fa-camera"></i>
                                    </button>
                                </div>
                            </div>
                            
                            @if(count($searchResults) > 0)
                            <div class="shadow-lg p-3 mb-5 bg-white rounded" style="position: absolute; z-index: 999; width: 95%;">
                                <ul class="list-group">
                                    @foreach($searchResults as $index => $product)
                                    <li class="list-group-item {{ $index === $selectedIndex ? 'active' : '' }}" 
                                        style="cursor: pointer;"
                                        wire:click="selectProduct({{ $index }})">
                                        {{ $product->name }} - {{ $product->sku }}
                                    </li>
                                    @endforeach
                                </ul>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-sm-12">
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="text-white" style="background: #3B3F5C">
                                    <tr>
                                        <th class="table-th text-white">PRODUCTO</th>
                                        <th class="table-th text-white text-center">TM (TIPO)</th>
                                        <th class="table-th text-white text-center">CANTIDAD</th>
                                        <th class="table-th text-white text-center">PESO</th>
                                        <th class="table-th text-white text-center">ACCIONES</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($cart as $productId => $item)
                                    <tr>
                                        <td>
                                            <h6>{{ $item['name'] }}</h6>
                                            <small>{{ $item['sku'] }}</small>
                                        </td>
                                        <td class="text-center">
                                            <input type="text" 
                                                   value="{{ $item['material_type'] }}" 
                                                   onchange="Livewire.dispatch('updateRow', [{{ $productId }}, 'material_type', this.value])"
                                                   class="form-control text-center"
                                                   style="max-width: 100px; margin: 0 auto;">
                                        </td>
                                        <td class="text-center">
                                            <input type="number" 
                                                   value="{{ $item['quantity'] }}" 
                                                   onchange="Livewire.dispatch('updateRow', [{{ $productId }}, 'quantity', this.value])"
                                                   class="form-control text-center"
                                                   step="any"
                                                   style="max-width: 120px; margin: 0 auto;">
                                        </td>
                                        <td class="text-center">
                                            <input type="number" 
                                                   value="{{ $item['weight'] }}" 
                                                   onchange="Livewire.dispatch('updateRow', [{{ $productId }}, 'weight', this.value])"
                                                   class="form-control text-center"
                                                   step="any"
                                                   style="max-width: 120px; margin: 0 auto;">
                                        </td>
                                        <td class="text-center">
                                            <button wire:click="removeFromCart({{ $productId }})" class="btn btn-dark">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="text-center">NO HAY PRODUCTOS SELECCIONADOS</td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="row mt-3">
                    <div class="col-sm-12 col-md-4 d-flex justify-content-end align-items-center">
                        <button wire:click.prevent="save" class="btn btn-dark btn-lg">
                            {{ $isEdit ? 'ACTUALIZAR' : 'GUARDAR' }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
    
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

    <script src="{{ asset('assets/js/keypress.js') }}"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <script>
        document.addEventListener('livewire:initialized', () => {
             Livewire.on('updateRow', (data) => {
                 @this.updateRow(data[0], data[1], data[2]);
             });
        });

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
                let searchInput = document.getElementById('searchInput');
                searchInput.value = decodedText;
                
                // Trigger Livewire update
                searchInput.dispatchEvent(new Event('input'));
                
                // Close modal
                $('#modalScanner').modal('hide');
            }
        });
    </script>
</div>
