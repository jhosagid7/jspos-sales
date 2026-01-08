<div class="card">
    <div class="card-header">
        <div class="row">
            <div class="col-sm-12 col-md-6">
                <livewire:product-search>
            </div>
            <div class="col-sm-12 col-md-6 d-flex justify-content-end">
                <div class="btn-group btn-group-pill " role="group" aria-label="Basic example">

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
                <table class="table table-bordered table-sm table-striped align-middle">
                    <thead>
                        <tr>
                            <th class="p-1">Descripción</th>
                            <th class="p-1" width="140">Cantidad</th>
                            <th class="p-1" width="130">Costo</th>
                            <th class="p-1" width="130">Precio Vta</th>
                            <th class="p-1" width="100">Utilidad %</th>
                            <th class="p-1" width="100">Importe</th>
                            @if($flete > 0)
                            <th class="p-1">Flete</th>
                            <th class="p-1">Total</th>
                            @endif
                            <th class="p-1" width="60">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cart as $item)
                        <tr>
                            <td>
                                <div class="product-name txt-info font-weight-bold" style="font-size: 0.9rem;">{{ strtoupper($item['name']) }}</div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm" style="width: 120px;">
                                    <div class="input-group-prepend">
                                        <button class="btn btn-dark btn-sm" type="button"
                                            wire:click="IncDec('{{ $item['id'] }}', 2)">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                    </div>
                                    <input type="number" 
                                        wire:keydown.enter.prevent="updateQty('{{ $item['id'] }}', $event.target.value )"
                                        class="form-control form-control-sm text-center" 
                                        value="{{ $item['qty'] }}"
                                        id="p{{$item['id']}}">
                                    <div class="input-group-append">
                                        <button class="btn btn-dark btn-sm" type="button"
                                            wire:click="IncDec('{{ $item['id'] }}', 1)">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input wire:change="setCost('{{ $item['id'] }}', $event.target.value )"
                                        class="form-control form-control-sm text-center" type="number" value="{{ $item['cost'] }}"
                                        id="c{{$item['id']}}">
                                </div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input wire:change="setPrice('{{ $item['id'] }}', $event.target.value )"
                                        class="form-control form-control-sm text-center" type="number" value="{{ $item['price'] ?? 0 }}"
                                        id="pr{{$item['id']}}">
                                    <div class="input-group-append">
                                        <button class="btn btn-warning btn-sm" type="button" wire:click="openPriceModal('{{ $item['id'] }}')" title="Gestionar Precios">
                                            <i class="fa fa-list"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                {{ $item['margin'] ?? 0 }}%
                            </td>
                            <td>${{ $item['total'] }}</td>
                            @if($flete > 0)
                            <td>${{ $item['flete']['flete_producto'] }}</td>
                            <td>
                                ${{ floatval($item['total']) + floatval($item['flete']['total_flete']) }}
                            </td>
                            @endif
                            <td>
                                <button wire:click.prevent="removeItem('{{ $item['id'] }}')"
                                    class="btn btn-danger btn-sm">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center">Agrega productos al carrito</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>