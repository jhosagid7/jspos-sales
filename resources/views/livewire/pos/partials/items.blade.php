<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <div x-data @click.away="$wire.dispatch('hideResults')" class="relative">
                    <div class="faq-form">
                        <div class="form-control form-control-lg">
                            <input type="text" wire:model.live.debounce.300ms="search3" class="form-control"
                                placeholder="[ F1 ] Ingresa nombre o código del producto"
                                style="text-transform: capitalize" autocomplete="off" id="inputSearch"
                                wire:keydown="keyDown($event.key)">
                            <!-- Captura las teclas presionadas -->
                            <i class="search-icon" data-feather="search"></i>
                        </div>

                        @if (!empty($products))
                            @php
                                $primaryCurrency = $currencies->firstWhere('is_primary', true);
                                $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
                                $primarySymbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                            @endphp
                            <ul class="mt-0 bg-white border-0 list-group position-absolute w-100"
                                style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                @foreach ($products as $index => $product)
                                    @php
                                        $priceInPrimary = $product->price * $primaryRate;
                                    @endphp
                                    <li class="p-1 list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                        style="cursor: pointer; {{ $selectedIndex === $index ? 'background-color: #e9ecef;' : '' }}">
                                        <div class="w-100">
                                            <div class="d-flex justify-content-between align-items-center" wire:click="AddProduct({{ $product->id }})">
                                                <div class="d-flex align-items-center w-100">
                                                    <img src="{{ asset($product->photo) }}" alt="img" class="rounded mr-2" style="width: 30px; height: 30px; object-fit: cover;">
                                                    <div class="d-flex flex-column flex-grow-1">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <h6 class="mb-0 text-{{ $product->stock_qty <= 0 ? 'danger' : ($product->stock_qty < $product->low_stock ? 'info' : 'primary') }}" style="font-size: 0.9rem;">
                                                                <small class="text-muted">{{ $product->sku }}</small> - {{ Str::limit($product->name, 40) }}
                                                            </h6>
                                                            <span class="badge badge-light text-dark" style="font-size: 0.8rem;">
                                                                {{ $primarySymbol }}{{ number_format($priceInPrimary, 2) }} / Total: {{ $product->productWarehouses->sum('stock_qty') }}
                                                            </span>
                                                        </div>
                                                        
                                                        @if($product->productWarehouses->count() > 0)
                                                            @can('sales.switch_warehouse')
                                                                <div class="d-flex flex-wrap mt-1 align-items-center">
                                                                    @foreach($product->productWarehouses as $pw)
                                                                        @if($pw->stock_qty > 0)
                                                                            <button class="btn btn-xs btn-outline-secondary mr-1 p-0 px-1" style="font-size: 0.7rem;"
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

                    @php
                        $uniqueKey = uniqid();
                    @endphp

                    <livewire:partial-payment :key="$uniqueKey" />
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
                <table class="table table-bordered table-sm table-striped align-middle">
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
                                                                                <div>{{ $currency->symbol }}{{ number_format($convertedPrice, 2) }} {{ $currency->code }}</div>
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
                                                                                    <div>{{ $currency->symbol }}{{ number_format($convertedPrice, 2) }} {{ $currency->code }}</div>
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
                                <td>{{ $primarySymbol }}{{ $item['total'] }}</td>
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
    <!-- Container-fluid Ends-->
</div>
