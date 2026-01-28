<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <div x-data="{
                    selectedIndex: -1,
                    itemCount: 0,
                    
                    init() {
                         // Listen for Livewire updates to products
                         Livewire.on('refresh', () => {
                             // Wait for DOM update
                             this.$nextTick(() => {
                                 // Recalculate item count from the DOM list
                                 this.itemCount = this.$refs.resultsList ? this.$refs.resultsList.children.length : 0;
                                 this.selectedIndex = -1;
                             });
                         });

                         // Also watch for manual property updates if needed
                         $watch('itemCount', value => {
                             if(value === 0) this.selectedIndex = -1;
                         });
                    },

                    checkScroll() {
                        this.$nextTick(() => {
                            if (!this.$refs.resultsList) return;
                            const activeItem = this.$refs.resultsList.children[this.selectedIndex];
                            if (activeItem) {
                                activeItem.scrollIntoView({ block: 'nearest' });
                            }
                        });
                    },

                    navigate(direction) {
                        // Ensure itemCount is current
                        if (this.$refs.resultsList) {
                            this.itemCount = this.$refs.resultsList.children.length;
                        } else {
                            this.itemCount = 0;
                        }

                        if (this.itemCount === 0) return;
                        
                        if (direction === 'down') {
                            this.selectedIndex = this.selectedIndex < this.itemCount - 1 ? this.selectedIndex + 1 : this.selectedIndex;
                        } else {
                            this.selectedIndex = this.selectedIndex > 0 ? this.selectedIndex - 1 : 0;
                        }
                        this.checkScroll();
                    },

                    selectItem() {
                         if (this.selectedIndex >= 0 && this.$refs.resultsList) {
                            // Find the product ID from the DOM element at the selected index
                            const activeItem = this.$refs.resultsList.children[this.selectedIndex];
                            if (activeItem && activeItem.dataset.id) {
                                @this.AddProduct(activeItem.dataset.id);
                                this.reset();
                            }
                         }
                    },

                    reset() {
                        this.selectedIndex = -1;
                        $wire.dispatch('hideResults');
                    }
                }" 
                @click.away="reset()" 
                class="relative">
                    <div class="d-flex align-items-center gap-2">
                        <div class="faq-form w-100">
                            <div class="form-control form-control-lg">
                                <input type="text" 
                                    wire:model.live.debounce.300ms="search3" 
                                    class="form-control"
                                    placeholder="[ F1 ] Ingresa nombre o código del producto"
                                    style="text-transform: capitalize" 
                                    autocomplete="off" 
                                    id="inputSearch"
                                    @keydown.escape="reset()"
                                    @keydown.arrow-down.prevent="navigate('down')"
                                    @keydown.arrow-up.prevent="navigate('up')"
                                    @keydown.enter.prevent="selectItem()">
                                <!-- Captura las teclas presionadas -->
                                <i class="search-icon" data-feather="search"></i>
                            </div>
                        </div>
                        <button type="button" class="btn btn-primary d-md-none ml-2" data-toggle="modal" data-target="#modalScanner">
                            <i class="fas fa-camera"></i>
                        </button>
                    </div>

                    @if (!empty($products))
                        @php
                            $primaryCurrency = $currencies->firstWhere('is_primary', true);
                            $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
                            $primarySymbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                        @endphp
                        {{-- Update item count for Alpine --}}
                        <div x-init="itemCount = {{ count($products) }}"></div>

                        <ul x-ref="resultsList" 
                            class="mt-0 bg-white border-0 list-group position-absolute w-100 shadow-sm"
                            style="z-index: 1000; max-height: 300px; overflow-y: auto;">
                            @foreach ($products as $index => $product)
                                @php
                                    $priceInPrimary = $product->price * $primaryRate;
                                @endphp
                                <li class="p-1 list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                    data-id="{{ $product->id }}"
                                    :class="{ 'bg-light': selectedIndex === {{ $index }} }"
                                    @click="@this.AddProduct({{ $product->id }}); reset()"
                                    @mouseenter="selectedIndex = {{ $index }}"
                                    style="cursor: pointer; border-bottom: 1px solid #f0f0f0;">
                                    <div class="w-100">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div class="d-flex align-items-center w-100">
                                                <img src="{{ asset($product->photo) }}" alt="img" class="rounded mr-2" style="width: 40px; height: 40px; object-fit: cover;">
                                                <div class="d-flex flex-column flex-grow-1">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <h6 class="mb-0 font-weight-bold text-{{ $product->stock_qty <= 0 ? 'danger' : ($product->stock_qty <= $product->low_stock ? 'warning' : 'success') }}" style="font-size: 1rem;">
                                                            <small class="text-muted">{{ $product->sku }}</small> - {{ Str::limit($product->name, 40) }}
                                                        </h6>
                                                        <span class="badge badge-light text-dark border" style="font-size: 0.85rem;">
                                                            {{ $primarySymbol }}{{ formatMoney($priceInPrimary) }} 
                                                            <span class="text-muted ml-1">| Stock: {{ $product->productWarehouses->sum('stock_qty') }}</span>
                                                        </span>
                                                    </div>
                                                    
                                                    @if($product->productWarehouses->count() > 0)
                                                        @can('sales.switch_warehouse')
                                                            <div class="d-flex flex-wrap mt-1 align-items-center">
                                                                @foreach($product->productWarehouses as $pw)
                                                                    @if($pw->stock_qty > 0)
                                                                        <button type="button" class="btn btn-xs btn-outline-secondary mr-1 p-0 px-1" style="font-size: 0.75rem;"
                                                                            wire:click.stop="AddProduct({{ $product->id }}, 1, {{ $pw->warehouse_id }})">
                                                                            {{ $pw->warehouse->name }}: {{ $pw->stock_qty }}
                                                                        </button>
                                                                    @endif
                                                                @endforeach
                                                            </div>
                                                        @endcan
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
            {{-- <div class="col-sm-12 col-md-6">
                <div class="faq-form">
                    <input wire:keydown.enter='ScanningCode($event.target.value)' class="form-control form-control-lg"
                        type="text" placeholder="Escanea el SKU o Código de Barras [F1]" id="inputSearch">
                    <i class="search-icon" data-feather="search"></i>
                </div>
            </div> --}}
            <div class="col-sm-12 col-md-6 d-flex justify-content-end">
                <div class="btn-group btn-group-pill " role="group" aria-label="Basic example">

                    <livewire:partial-payment key="partial-payment-component" />
                    <button onclick="processOrder()" type="button" class="btn btn-outline-light-2x txt-dark"><i
                            class="icon-money"></i>
                        Ordenes</button>
                    <button @if ($totalCart > 0) onclick="cancelSale()" @endif type="button"
                        class="btn btn-outline-light-2x txt-dark"><i class="icon-trash"></i>
                        Cancelar</button>
                    <button onclick="initPartialPay()" type="button" class="btn btn-outline-light-2x txt-dark"><i
                            class="icon-money"></i>
                        Abonos</button>
                    <button wire:click.prevent="printLast" type="button" class="btn btn-outline-light-2x txt-dark"><i
                            class="icon-printer"></i>
                        Última</button>
                </div>

            </div>
        </div>
    </div>
    <div class="card-body">
        {{-- @json($cart) --}}
        <div class="row">
            <div class="order-history table-responsive wishlist">
                <table class="table table-bordered table-sm table-striped align-middle table-mobile-cards">
                    <thead>
                        <tr>
                            <th class="p-1" width="100">Código</th>
                            <th class="p-1">Descripción</th>
                            <th class="p-1" width="130">Precio Vta</th>
                            <th class="p-1" width="140">Cantidad</th>
                            <th class="p-1" width="100">Importe</th>
                            <th class="p-1" width="60">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cart as $item)
                            @php
                                $primaryCurrency = $currencies->firstWhere('is_primary', true);
                                $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
                                $primarySymbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                            @endphp
                            <tr wire:key="cart-item-{{ $item['id'] }}">
                                <td>
                                    <span class="badge badge-light text-dark">{{ $item['sku'] }}</span>
                                </td>
                                <td>
                                    <div class="product-name txt-info font-weight-bold" style="font-size: 0.9rem;">{{ strtoupper($item['name']) }}</div>
                                    @php
                                        $product = \App\Models\Product::find($item['pid']);
                                    @endphp

                                </td>
                                <td>
                                    @if (count($item['pricelist']) <= 1)
                                        <div class="mb-0">
                                            <div class="input-group input-group-sm">
                                                <input class="form-control form-control-sm"
                                                    wire:keydown.enter.prevent="setCustomPrice('{{ $item['id'] }}', $event.target.value )"
                                                    type="text" oninput="justNumber(this)" 
                                                    value="{{ $item['sale_price'] }}">
                                                
                                                <div class="input-group-append">
                                                    <button class="btn btn-warning btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-info"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right" style="min-width: 250px; z-index: 10000;">
                                                        <a class="dropdown-item" href="javascript:void(0)">
                                                            <div class="d-flex flex-column">
                                                                <span class="font-weight-bold">{{ $primarySymbol }}{{ $item['sale_price'] }}</span>
                                                                @if(isset($currencies) && $currencies->count() > 1)
                                                                    <div class="text-muted small">
                                                                        @foreach($currencies as $currency)
                                                                            @if(!$currency->is_primary)
                                                                                @php
                                                                                    $convertedPrice = ($item['sale_price'] / $primaryRate) * $currency->exchange_rate;
                                                                                @endphp
                                                                                <div>{{ $currency->symbol }}{{ formatMoney($convertedPrice) }} {{ $currency->code }}</div>
                                                                            @endif
                                                                        @endforeach
                                                                    </div>
                                                                @endif
                                                            </div>
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @else
                                        <div class="mb-0">
                                            <div class="input-group input-group-sm">
                                                <input class="form-control form-control-sm" id="inputPrice{{ $item['id'] }}"
                                                    wire:keydown.enter.prevent="setCustomPrice('{{ $item['id'] }}', $event.target.value )"
                                                    oninput="justNumber(this)" type="text"
                                                    placeholder="{{ $item['sale_price'] }}"
                                                    value="{{ $item['sale_price'] }}">
                                                
                                                <div class="input-group-append">
                                                    <button class="btn btn-warning btn-sm dropdown-toggle" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                                        <i class="fa fa-list"></i>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right" style="min-width: 250px; max-height: 300px; overflow-y: auto; z-index: 10000;">
                                                        @php
                                                            $primaryCurrency = $currencies->firstWhere('is_primary', true);
                                                            $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
                                                            $primarySymbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                                                        @endphp
                                                        @foreach ($item['pricelist'] as $price)
                                                            <a class="dropdown-item" href="javascript:void(0)" 
                                                               wire:click.prevent="setCustomPrice('{{ $item['id'] }}', '{{ $price['price'] }}')">
                                                                <div class="d-flex flex-column">
                                                                    <span class="font-weight-bold">{{ $primarySymbol }}{{ $price['price'] }}</span>
                                                                    @if(isset($currencies) && $currencies->count() > 1)
                                                                        <div class="text-muted small">
                                                                            @foreach($currencies as $currency)
                                                                                @if(!$currency->is_primary)
                                                                                    @php
                                                                                        $convertedPrice = ($price['price'] / $primaryRate) * $currency->exchange_rate;
                                                                                    @endphp
                                                                                    <div>{{ $currency->symbol }}{{ formatMoney($convertedPrice) }} {{ $currency->code }}</div>
                                                                                @endif
                                                                            @endforeach
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            </a>
                                                            @if(!$loop->last)
                                                                <div class="dropdown-divider"></div>
                                                            @endif
                                                        @endforeach
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="input-group input-group-sm" style="width: 120px;">
                                        <div class="input-group-prepend">
                                            <button class="btn btn-dark btn-sm" type="button"
                                                onclick="updateQty('{{ $item['id'] }}','decrement')">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                        </div>
                                        <input type="number" 
                                            wire:blur="updateQty('{{ $item['id'] }}', $event.target.value )"
                                            wire:keydown.enter.prevent="updateQty('{{ $item['id'] }}', $event.target.value )"
                                            class="form-control form-control-sm text-center" 
                                            value="{{ $item['qty'] }}"
                                            id="qty-{{ $item['id'] }}">
                                        <div class="input-group-append">
                                            <button class="btn btn-dark btn-sm" type="button"
                                                onclick="updateQty('{{ $item['id'] }}','increment')">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>


                                </td>
                                <td>{{ $primarySymbol }}{{ formatMoney($item['total']) }}</td>
                                <td>

                                    <button wire:click.prevent="removeItem({{ $item['pid'] }})"
                                        class="btn btn-danger btn-sm" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center">Agrega productos al carrito
                                    {{ Auth::user()->roles[0]->name }}
                                </td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
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
                searchInput.value = decodedText;
                
                // Trigger Livewire update
                searchInput.dispatchEvent(new Event('input'));
                
                // Close modal
                $('#modalScanner').modal('hide');
                
                // Optional: Play a beep sound
                // let audio = new Audio('path/to/beep.mp3');
                // audio.play();
            }
        });
    </script>
    <!-- Container-fluid Ends-->
</div>
