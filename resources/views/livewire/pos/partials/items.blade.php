<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <div x-data @click.away="$wire.dispatch('hideResults')" class="relative">
                    <div class="faq-form">
                        <div class="form-control form-control-lg">
                            <input type="text" wire:model.live.debounce.650ms="search3" class="form-control"
                                placeholder="Ingresa nombre o código del producto" style="text-transform: capitalize"
                                autocomplete="off" id="inputSearch" wire:keydown="keyDown($event.key)">
                            <!-- Captura las teclas presionadas -->
                            <i class="search-icon" data-feather="search"></i>
                        </div>

                        @if (!empty($products))
                            <ul class="list-group mt-0 position-absolute w-100 bg-white border-0"
                                style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                @foreach ($products as $index => $product)
                                    <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center p-2"
                                        wire:click="selectProduct({{ $index }})"
                                        style="cursor: pointer; {{ $selectedIndex === $index ? 'background-color: #e9ecef;' : '' }}">
                                        <div>
                                            <h6
                                                class="mb-0 text-{{ $product->stock_qty <= 0 ? 'danger' : ($product->stock_qty < $product->low_stock ? 'info' : 'primary') }}">
                                                <small class="mb-0" style="text-muted">
                                                    {{ $product->sku }} - {{ Str::limit($product->name, 50) }}
                                                </small> - {{ $product->price }} / <small>stock:
                                                    @if ($product->stock_qty <= 0)
                                                        <span class="text-danger">Agotado</span>
                                                    @else
                                                        <span>{{ $product->stock_qty }}</span>
                                                    @endif


                                                </small>
                                            </h6>
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
                <table class="table table-bordered">
                    <thead>
                        <tr>
                            <th class="p-2" width="100"></th>
                            <th class="p-2">Descripción</th>
                            <th class="p-2" width="200">Precio Vta</th>
                            <th class="p-2" width="300">Cantidad</th>
                            <th class="p-2">Importe</th>
                            <th class="p-2">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cart as $item)
                            <tr>
                                <td>
                                    <img class="img-fluid img-30" src="{{ asset($item['image']) }} ">
                                </td>
                                <td>
                                    <div class="product-name txt-info">{{ strtoupper($item['name']) }}</div>
                                    <small
                                        class="{{ $item['sku'] == null ? 'd-none' : '' }}">sku:{{ $item['sku'] }}</small>
                                </td>
                                <td>
                                    @if (count($item['pricelist']) == 0)
                                        <input
                                            wire:keydown.enter.prevent="setCustomPrice('{{ $item['id'] }}', $event.target.value )"
                                            type="text" oninput="justNumber(this)" class="text-center form-control"
                                            value="{{ $item['sale_price'] }}">
                                    @else
                                        <div class="mb-3">
                                            <div class="position-relative">
                                                <input class="form-control" id="inputPrice{{ $item['id'] }}"
                                                    wire:keydown.enter.prevent="setCustomPrice('{{ $item['id'] }}', $event.target.value )"
                                                    oninput="justNumber(this)" type="text"
                                                    placeholder="{{ $item['sale_price'] }}">
                                                <select class="form-select crypto-select warning"
                                                    wire:change="setCustomPrice('{{ $item['id'] }}', $event.target.value )">
                                                    @foreach ($item['pricelist'] as $price)
                                                        <option>${{ $price['price'] }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <div class="right-details">
                                        <div class="touchspin-wrapper">

                                            <button
                                                onclick="updateQty({{ $item['pid'] }},'{{ $item['id'] }}','decrement')"
                                                class="decrement-touchspin btn-touchspin"><i
                                                    class="fa fa-minus text-gray"></i>
                                            </button>
                                            <input
                                                wire:keydown.enter.prevent="updateQty('{{ $item['id'] }}', $event.target.value )"
                                                class=" input-touchspin" type="number" value="{{ $item['qty'] }}"
                                                id="p{{ $item['pid'] }}">

                                            <button
                                                onclick="updateQty({{ $item['pid'] }},'{{ $item['id'] }}', 'increment')"
                                                class="increment-touchspin btn-touchspin"><i
                                                    class="fa fa-plus text-gray"></i>
                                            </button>
                                        </div>
                                    </div>


                                </td>
                                <td>${{ $item['total'] }}</td>
                                <td>

                                    <button wire:click.prevent="removeItem({{ $item['pid'] }})"
                                        class="btn btn-light btn-sm">
                                        <i class="fa fa-trash fa-2x"></i>
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
