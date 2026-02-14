<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <div x-data="{
                    selectedIndex: -1,
                    itemCount: 0,
                    
                    getItems() {
                        return this.$refs.resultsList ? this.$refs.resultsList.querySelectorAll('.item-wrapper') : [];
                    },

                    init() {
                         // Listen for Livewire updates to products
                         Livewire.on('refresh', () => {
                             // Wait for DOM update
                             this.$nextTick(() => {
                                 // Recalculate item count from the DOM list
                                 this.itemCount = this.getItems().length;
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
                            const items = this.getItems();
                            const activeItem = items[this.selectedIndex];
                            if (activeItem) {
                                activeItem.scrollIntoView({ block: 'nearest', inline: 'nearest' });
                            }
                        });
                    },

                    navigate(direction) {
                        // Ensure itemCount is current
                        const items = this.getItems();
                        this.itemCount = items.length;

                        if (this.itemCount === 0) return;
                        
                        // Simple linear navigation for now (next/prev)
                        // TODO: Implement true grid navigation (up/down/left/right) if needed
                        if (direction === 'down') {
                            this.selectedIndex = this.selectedIndex < this.itemCount - 1 ? this.selectedIndex + 1 : this.selectedIndex;
                        } else {
                            this.selectedIndex = this.selectedIndex > 0 ? this.selectedIndex - 1 : 0;
                        }
                        this.checkScroll();
                    },

                    selectItem() {
                         const items = this.getItems();
                         if (this.selectedIndex >= 0 && items.length > 0) {
                            // Find the product ID from the DOM element at the selected index
                            const activeItem = items[this.selectedIndex];
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


                    <style>
                        .search-results-container {
                            z-index: 1000;
                            max-height: 65vh; /* Taller on desktop */
                            overflow-y: auto;
                            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
                            border-radius: 0 0 15px 15px;
                            border: 1px solid #e0e0e0;
                        }
                        
                        /* Scrollbar styling */
                        .search-results-container::-webkit-scrollbar {
                            width: 8px;
                        }
                        .search-results-container::-webkit-scrollbar-track {
                            background: #f1f1f1;
                            border-radius: 4px;
                        }
                        .search-results-container::-webkit-scrollbar-thumb {
                            background: #c1c1c1;
                            border-radius: 4px;
                        }
                        .search-results-container::-webkit-scrollbar-thumb:hover {
                            background: #a8a8a8;
                        }

                        .product-card {
                            transition: all 0.2s ease;
                            border: 1px solid #eee;
                            border-radius: 12px;
                            overflow: hidden;
                            height: 100%;
                            background: white;
                            cursor: pointer;
                            position: relative;
                        }
                        
                        .product-card:hover, .product-card.active {
                            transform: translateY(-3px);
                            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
                            border-color: #3b82f6; /* Primary Color */
                        }

                        .product-card.active {
                            background-color: #f0f9ff;
                            border: 2px solid #3b82f6;
                        }

                        .product-image-container {
                            position: relative;
                            width: 100%;
                            padding-top: 75%; /* 4:3 Aspect Ratio */
                            background: #f8f9fa;
                            overflow: hidden;
                        }
                        
                        .product-image {
                            position: absolute;
                            top: 0;
                            left: 0;
                            width: 100%;
                            height: 100%;
                            object-fit: contain; /* Show full product */
                            padding: 10px;
                            transition: transform 0.3s ease;
                        }

                        .product-card:hover .product-image {
                            transform: scale(1.05);
                        }

                        .stock-badge {
                            position: absolute;
                            top: 8px;
                            right: 8px;
                            font-size: 0.75rem;
                            font-weight: 600;
                            padding: 4px 8px;
                            border-radius: 20px;
                            background: rgba(255,255,255,0.9);
                            backdrop-filter: blur(2px);
                            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                        }

                        .price-tag {
                            font-weight: 800;
                            color: #2c3e50;
                            font-size: 1.1rem;
                        }


                        .product-title {
                            font-size: 0.9rem;
                            font-weight: 700;
                            color: #343a40;
                            line-height: 1.2;
                            
                            /* Multi-line truncation (max 3 lines) */
                            display: -webkit-box;
                            -webkit-line-clamp: 3;
                            -webkit-box-orient: vertical;
                            overflow: hidden;
                            text-overflow: ellipsis;
                            
                            /* Fix height for alignment (approx 3 lines) */
                            height: 3.6em; 
                            margin-bottom: 0.5rem;
                        }

                        /* Mobile Optimizations */
                        @media (max-width: 576px) {
                            .product-card {
                                display: flex; /* Horizontal card on very small screens */
                                flex-direction: row;
                                height: auto;
                                min-height: 100px;
                            }
                            .product-image-container {
                                width: 100px; /* Fixed width for image */
                                padding-top: 0;
                                height: auto; /* Let it fill flex height */
                                flex-shrink: 0;
                            }
                            .product-image {
                                position: relative;
                                height: 100%;
                            }
                            .card-body-custom {
                                padding: 10px;
                                display: flex;
                                flex-direction: column;
                                justify-content: center;
                                width: 100%;
                            }
                            .product-title {
                                height: auto; /* Allow auto height on mobile list view */
                                -webkit-line-clamp: 2;
                                margin-bottom: 0.25rem;
                            }
                        }
                    </style>

                    @if (!empty($products))
                        @php
                            $primaryCurrency = $currencies->firstWhere('is_primary', true);
                            $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
                            $primarySymbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                        @endphp
                        {{-- Update item count for Alpine --}}
                        <div x-init="itemCount = {{ count($products) }}"></div>

                        <div x-ref="resultsList" 
                             class="search-results-container position-absolute w-100 bg-white"
                             style="z-index: 1000;">
                            
                            <div class="container-fluid p-2">
                                <div class="row no-gutters">
                                    @foreach ($products as $index => $product)
                                        @php
                                            $priceInPrimary = $product->price * $primaryRate;
                                            $stockStatus = $product->stock_qty <= 0 ? 'bg-danger text-white' : ($product->stock_qty <= $product->low_stock ? 'bg-warning text-dark' : 'bg-success text-white');
                                        @endphp
                                        
                                        <!-- Grid Column settings: 1 col mobile, 2 col small, 3 col md, 4 col lg -->
                                        <div class="col-12 col-sm-6 col-md-4 col-lg-3 p-1 item-wrapper" 
                                             data-id="{{ $product->id }}">
                                            
                                            <div class="product-card h-100"
                                                 :class="{ 'active': selectedIndex === {{ $index }} }"
                                                 @click="@this.AddProduct({{ $product->id }}); reset()"
                                                 @mouseenter="selectedIndex = {{ $index }}">
                                                
                                                <div class="product-image-container">
                                                    @if($product->photo)
                                                        <img src="{{ asset($product->photo) }}" alt="img" class="product-image">
                                                    @else
                                                        <div class="product-image d-flex align-items-center justify-content-center text-muted bg-light">
                                                            <i class="fas fa-image fa-3x opacity-50"></i>
                                                        </div>
                                                    @endif
                                                    
                                                    <span class="stock-badge {{ $stockStatus }}">
                                                        Stock: {{ Auth::user()->can('sales.switch_warehouse') ? $product->productWarehouses->sum('stock_qty') : $product->productWarehouses->where('warehouse_id', $this->warehouse_id)->sum('stock_qty') }}
                                                    </span>
                                                </div>

                                                <div class="p-2 card-body-custom">
                                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                                        <div class="product-title" title="{{ $product->name }}">
                                                            {{ $product->name }}
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="small text-muted mb-2 font-weight-bold">{{ $product->sku }}</div>
                                                    
                                                    <div class="d-flex justify-content-between align-items-end mt-auto">
                                                        @if(Auth::user()->can('sales.manage_adjustments') || $customer)
                                                            <div class="price-tag">
                                                                @if(Auth::user()->can('sales.manage_adjustments'))
                                                                    {{ $primarySymbol }}{{ formatMoney($priceInPrimary) }}
                                                                @else
                                                                    {{-- Vendedor Foráneo: Mostrar precio con comisiones/flete aplicados --}}
                                                                    @php
                                                                        // Calcular precio final simulado usando la configuración del vendedor si existe
                                                                        $finalPrice = $priceInPrimary;
                                                                        if($sellerConfig) {
                                                                            // Aplicar lógica simplificada de comisión (la real está en el carrito, esto es visual)
                                                                            $commission = ($sellerConfig->commission_percent / 100) * $finalPrice;
                                                                            $finalPrice += $commission;
                                                                            
                                                                            // Flete (si aplica)
                                                                            if($sellerConfig->apply_freight && $sellerConfig->freight_percent > 0) {
                                                                                 $freight = ($sellerConfig->freight_percent / 100) * $finalPrice;
                                                                                 $finalPrice += $freight;
                                                                            }
                                                                        }
                                                                    @endphp
                                                                    {{ $primarySymbol }}{{ formatMoney($finalPrice) }}
                                                                    <small class="text-muted d-block" style="font-size: 0.6rem;">Precio Final</small>
                                                                @endif
                                                            </div>
                                                            
                                                            <button class="btn btn-sm btn-primary rounded-circle shadow-sm" style="width: 32px; height: 32px; padding: 0;">
                                                                <i class="fas fa-plus" style="font-size: 0.8rem;"></i>
                                                            </button>
                                                        @else
                                                            <div class="w-100 text-center">
                                                                <span class="badge badge-warning p-2 w-100" style="font-size: 0.75rem;">
                                                                    <i class="fas fa-user-clock mr-1"></i> Seleccione Cliente
                                                                </span>
                                                            </div>
                                                        @endif
                                                    </div>

                                                    @if($product->productWarehouses->count() > 0)
                                                        @can('sales.switch_warehouse')
                                                            <div class="mt-2 border-top pt-1 d-none d-md-block">
                                                                <div class="d-flex flex-wrap" style="gap: 4px;">
                                                                    @foreach($product->productWarehouses as $pw)
                                                                        @if($pw->stock_qty > 0)
                                                                            <span class="badge badge-light border text-muted" 
                                                                                  style="font-size: 0.7rem; cursor: pointer;"
                                                                                  wire:click.stop="AddProduct({{ $product->id }}, 1, {{ $pw->warehouse_id }})">
                                                                                {{ Str::limit($pw->warehouse->name, 8) }}: {{ $pw->stock_qty }}
                                                                            </span>
                                                                        @endif
                                                                    @endforeach
                                                                </div>
                                                            </div>
                                                        @endcan
                                                    @endif

                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
            
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
                                                    value="{{ $item['sale_price'] }}"
                                                    @cannot('sales.manage_adjustments') readonly @endcannot>
                                                
                                                @can('sales.manage_adjustments')
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
                                                @endcan
                                            </div>
                                        </div>
                                    @else
                                        <div class="mb-0">
                                            <div class="input-group input-group-sm">
                                                <input class="form-control form-control-sm" id="inputPrice{{ $item['id'] }}"
                                                    wire:keydown.enter.prevent="setCustomPrice('{{ $item['id'] }}', $event.target.value )"
                                                    oninput="justNumber(this)" type="text"
                                                    placeholder="{{ $item['sale_price'] }}"
                                                    value="{{ $item['sale_price'] }}"
                                                    @cannot('sales.manage_adjustments') readonly @endcannot>
                                                
                                                @can('sales.manage_adjustments')
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
                                                @endcan
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
    
    @include('livewire.pos.partials.items-common-modals')
</div>
