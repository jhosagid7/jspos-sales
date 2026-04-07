<div class="card shadow-sm border-0 mb-4" style="border-radius: 12px; overflow: hidden;">
    <div class="card-header bg-white border-bottom-0 py-4 px-4">
        <div class="row align-items-center">
            <div class="col-sm-12 col-md-4">
                <div class="d-flex align-items-center">
                    <span class="text-muted small text-uppercase font-weight-bold mr-3" style="font-size: 0.65rem; white-space: nowrap;">Búsqueda</span>
                    <div class="flex-grow-1">
                        <livewire:product-search />
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4">
                <div class="d-flex align-items-center" x-data="{
                    selectItem() {
                        const el = document.getElementById('inputClone');
                        const val = el.value.toUpperCase().trim();
                        const match = val.match(/^(PURCHASE|COMPRA|OC)[^0-9]*([0-9]+)$/i);
                        
                        if (match) {
                            const finalCode = match[1].toUpperCase() + ':' + match[2];
                            @this.processCloningCode(finalCode);
                            el.value = '';
                        }
                    }
                }">
                    <span class="text-muted small text-uppercase font-weight-bold mr-3" style="font-size: 0.65rem; white-space: nowrap;">Clonar</span>
                    <div class="input-group input-group-sm">
                        <input type="text" 
                            id="inputClone"
                            class="form-control border-0 bg-light" 
                            placeholder="PURCHASE:ID"
                            style="border-radius: 8px;"
                            @keydown.enter.prevent="selectItem()">
                        <div class="input-group-append">
                            <span class="input-group-text bg-light border-0 text-info"><i class="fas fa-copy"></i></span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-12 col-md-4 text-md-right mt-3 mt-md-0">
                <div class="btn-group shadow-sm" style="border-radius: 10px; overflow: hidden;">
                    <button onclick="processOrder()" type="button" class="btn btn-dark text-white border-0 py-2 px-3" style="font-size: 0.75rem;">
                        <i class="fas fa-list-alt mr-2"></i> ORDENES
                    </button>
                    <button @if ($totalCart > 0) onclick="cancelSale()" @endif type="button" class="btn btn-light text-muted border-0 py-2 px-3" style="font-size: 0.75rem;">
                        <i class="fas fa-trash-alt mr-2"></i> LIMPIAR
                    </button>
                    <button onclick="initPartialPay()" type="button" class="btn btn-outline-primary bg-white border-0 py-2 px-3" style="font-size: 0.75rem;">
                        <i class="fas fa-wallet mr-2"></i> ABONOS
                    </button>
                    <button wire:click.prevent="printLast" type="button" class="btn btn-light text-primary border-0 py-2 px-3" style="font-size: 0.75rem;">
                        <i class="fas fa-repeat mr-2"></i> ÚLTIMA
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="order-history table-responsive wishlist">
                <table class="table table-bordered table-sm table-striped align-middle">
                    <thead class="bg-light text-muted small text-uppercase font-weight-bold">
                        <tr>
                            <th class="py-3 px-3 border-0">Descripción del Producto</th>
                            <th class="py-3 border-0 text-center" width="140">Cantidad</th>
                            <th class="py-3 border-0 text-center" width="130">Costo Unit.</th>
                            <th class="py-3 border-0 text-center" width="130">Precio Venta</th>
                            <th class="py-3 border-0 text-center" width="100">Ut. %</th>
                            <th class="py-3 border-0 text-end pr-4" width="120">Importe</th>
                            @if($flete > 0)
                            <th class="py-3 border-0">Flete</th>
                            <th class="py-3 border-0">Total</th>
                            @endif
                            <th class="py-3 border-0 text-center" width="60"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($cart as $item)
                        <tr class="align-middle transition-all hover-bg-light">
                            <td class="px-3">
                                <div class="product-name text-dark font-weight-bold" style="font-size: 0.85rem; letter-spacing: -0.2px;">{{ strtoupper($item['name']) }}</div>
                                @if(isset($item['is_variable']) && $item['is_variable'])
                                    <div class="mt-2 pl-2 border-left border-primary" style="border-width: 3px !important;">
                                        <div class="d-flex align-items-center mb-1">
                                            <span class="badge badge-info px-2 py-1" style="font-size: 0.6rem;">MODO VARIABLE</span>
                                            <button wire:click="openVariableModal('{{ $item['id'] }}')" class="btn btn-xs btn-outline-primary ml-2 py-0 border-0">
                                                <i class="fas fa-plus"></i> Añadir
                                            </button>
                                        </div>
                                        @if(isset($item['items']) && count($item['items']) > 0)
                                            <div class="d-flex flex-wrap gap-1 mt-1">
                                                @foreach($item['items'] as $idx => $vItem)
                                                    <span class="badge badge-light border text-muted mr-1 mb-1 shadow-none py-1 px-2 d-flex align-items-center" style="font-size: 0.7rem; border-radius: 6px;">
                                                        <b>{{ $vItem['weight'] }} kg</b> 
                                                        @if($vItem['color']) | {{ $vItem['color'] }} @endif
                                                        <a href="javascript:void(0)" wire:click="removeVariableItem('{{ $item['id'] }}', {{ $idx }})" class="text-danger ml-2">
                                                            <i class="fas fa-times"></i>
                                                        </a>
                                                    </span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                @if(isset($item['is_variable']) && $item['is_variable'])
                                    <input type="text" class="form-control form-control-sm text-center border-0 bg-light font-weight-bold" value="{{ $item['qty'] }}" disabled style="border-radius: 8px;">
                                    <small class="text-muted d-block text-center mt-1" style="font-size: 0.6rem;">Cálculo Auto</small>
                                @else
                                <div class="input-group input-group-sm" style="width: 120px; margin: 0 auto;">
                                    <div class="input-group-prepend">
                                        <button class="btn btn-light btn-sm border" type="button"
                                            wire:click="IncDec('{{ $item['id'] }}', 2)">
                                            <i class="fas fa-minus text-muted"></i>
                                        </button>
                                    </div>
                                    <input type="number" 
                                        wire:keydown.enter.prevent="updateQty('{{ $item['id'] }}', $event.target.value )"
                                        class="form-control form-control-sm text-center border font-weight-bold" 
                                        value="{{ $item['qty'] }}"
                                        id="p{{$item['id']}}">
                                    <div class="input-group-append">
                                        <button class="btn btn-light btn-sm border" type="button"
                                            wire:click="IncDec('{{ $item['id'] }}', 1)">
                                            <i class="fas fa-plus text-muted"></i>
                                        </button>
                                    </div>
                                </div>
                                @endif
                            </td>
                            <td>
                                <div class="input-group input-group-sm">
                                    <input wire:change="setCost('{{ $item['id'] }}', $event.target.value )"
                                        class="form-control form-control-sm text-center bg-light border-0 font-weight-bold" type="number" step="0.0001" value="{{ $item['cost'] }}"
                                        id="c{{$item['id']}}" style="border-radius: 8px; color: #495057;">
                                </div>
                            </td>
                            <td>
                                <div class="input-group input-group-sm rounded shadow-none overflow-hidden" style="border-radius: 8px; border: 1px solid #dee2e6;">
                                    <input wire:change="setPrice('{{ $item['id'] }}', $event.target.value )"
                                        class="form-control form-control-sm text-center border-0" type="number" step="0.0001" value="{{ $item['price'] ?? 0 }}"
                                        id="pr{{$item['id']}}">
                                    <div class="input-group-append">
                                        <button class="btn btn-warning btn-sm border-0" type="button" wire:click="openPriceModal('{{ $item['id'] }}')" title="Gestionar Precios">
                                            <i class="fa fa-list"></i>
                                        </button>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center font-weight-bold text-muted small">
                                {{ $item['margin'] ?? 0 }}%
                            </td>
                            <td class="text-right pr-4 font-weight-bold text-dark">
                                ${{ number_format($item['total'], 2) }}
                            </td>
                            @if($flete > 0)
                            <td class="text-success small">${{ $item['flete']['flete_producto'] }}</td>
                            <td class="font-weight-bold small">
                                ${{ number_format(floatval($item['total']) + floatval($item['flete']['total_flete']), 2) }}
                            </td>
                            @endif
                            <td class="text-center">
                                <button wire:click.prevent="removeItem('{{ $item['id'] }}')"
                                    class="btn btn-light btn-xs p-1 text-danger shadow-none hover-bg-danger-light" style="border-radius: 50%;">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-cart-plus fa-3x d-block mb-3 opacity-20"></i>
                                Agrega productos para comenzar la compra
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>