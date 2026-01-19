<div class="row layout-top-spacing">
    <div class="col-sm-12 col-md-12 col-lg-12 layout-spacing">
        <div class="widget-content-area br-4">
            <div class="widget-header">
                <div class="row">
                    <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                        <h4>Generador de Etiquetas</h4>
                    </div>
                </div>
            </div>
            
            <div class="widget-content widget-content-area">
                <div class="row">
                    <!-- Search Section -->
                    <div class="col-md-6">
                        <div class="input-group mb-4">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                            </div>
                            <input type="text" wire:model.live="search" class="form-control" placeholder="Buscar productos...">
                        </div>

                        @if(count($products) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mt-1">
                                <thead class="text-white" style="background: #3b3f5c">
                                    <tr>
                                        <th class="table-th text-white">Descripción</th>
                                        <th class="table-th text-white text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($products as $product)
                                    <tr>
                                        <td>{{ $product->name }}</td>
                                        <td class="text-center">
                                            <button wire:click="addProduct({{ $product->id }})" class="btn btn-dark mtmobile" title="Agregar">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        @endif
                    </div>

                    <!-- Selected Products Section -->
                    <div class="col-md-6">
                        <h5 class="mb-3">Productos Seleccionados</h5>
                        @if(count($selectedProducts) > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped mt-1">
                                <thead class="text-white" style="background: #3b3f5c">
                                    <tr>
                                        <th class="table-th text-white">Descripción</th>
                                        <th class="table-th text-white text-center">Cant.</th>
                                        <th class="table-th text-white text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($selectedProducts as $id => $item)
                                    <tr>
                                        <td>{{ $item['name'] }}</td>
                                        <td class="text-center">
                                            <input type="number" 
                                                   class="form-control text-center" 
                                                   value="{{ $item['qty'] }}" 
                                                   wire:change="updateQuantity({{ $id }}, $event.target.value)"
                                                   min="1"
                                                   style="width: 80px; margin: 0 auto;">
                                        </td>
                                        <td class="text-center">
                                            <button wire:click="removeProduct({{ $id }})" class="btn btn-dark mtmobile" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-3 text-right">
                            <button wire:click="generatePdf" class="btn btn-primary">
                                <i class="fas fa-print"></i> Generar PDF
                            </button>
                        </div>
                        @else
                            <div class="alert alert-info">
                                No hay productos seleccionados para imprimir.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.on('open-new-tab', (event) => {
                window.open(event[0], '_blank');
            });
            
            @this.on('msg-error', (event) => {
                // Assuming you have a global notification function like noty or sweetalert
                // If not, we can use standard alert for now or check existing layout
                if(typeof noty === 'function') {
                    noty(event[0]);
                } else {
                    alert(event[0]);
                }
            });
        });
    </script>
</div>
