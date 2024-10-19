<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <div class="faq-form">
                    <input wire:keydown.enter='ScanningCode($event.target.value)' class="form-control form-control-lg"
                        type="text" placeholder="Escanea el SKU o Código de Barras [F1]" id="inputSearch">
                    <i class="search-icon" data-feather="search"></i>
                </div>
            </div>
            <div class="col-sm-12 col-md-6 d-flex justify-content-end">
                <div class="btn-group btn-group-pill " role="group" aria-label="Basic example">

                    @php
                        $uniqueKey = uniqid();
                    @endphp

                    <livewire:partial-payment :key="$uniqueKey" />

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
                                <td colspan="6" class="text-center">Agrega productos al carrito</td>
                            </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Container-fluid Ends-->
</div>
