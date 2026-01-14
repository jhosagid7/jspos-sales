<div>
    <div class="row">
        <div class="col-sm-12 col-md-9">
            @include('livewire.pos.partials.items')
        </div>
        <div class="col-sm-12 col-md-3">
            <div class="card card-primary card-outline customer-sticky">
                <div class="card-header">
                    <h3 class="card-title">Resumen</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-toggle="modal" data-target="#modalCustomerCreate">
                            <i class="fas fa-plus"></i> Crear Cliente
                        </button>
                    </div>
                    
                    <!-- Modal-->
                    <div wire:ignore.self class="modal fade" id="modalCustomerCreate" tabindex="-1"
                        aria-labelledby="modalCustomerCreate" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-primary">
                                    <h5 class="modal-title" id="modalCustomerCreate">Registrar Cliente</h5>
                                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form class="row g-3 needs-validation" novalidate="">
                                        <div class="col-sm-12 form-group">
                                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                            <input wire:model='cname' class="form-control" type="text" placeholder="Ingresa el nombre" maxlength="45" id="inputcname">
                                            @error('cname') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label class="form-label">CC/Nit <span class="text-danger">*</span></label>
                                            <input wire:model='ctaxpayerId' class="form-control" type="text" maxlength="65" id="inputctaxpayerId">
                                            @error('ctaxpayerId') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label class="form-label">Email</label>
                                            <input wire:model='cemail' class="form-control" type="text" maxlength="65" id="inputcemail">
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label class="form-label">Teléfono</label>
                                            <input wire:model='cphone' class="form-control" type="number" maxlength="15" id="inputcphone">
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label class="form-label">Dirección <span class="text-danger">*</span></label>
                                            <input wire:model='caddress' class="form-control" type="text" maxlength="255" id="inputcaddress">
                                            @error('caddress') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label class="form-label">Ciudad <span class="text-danger">*</span></label>
                                            <input wire:model='ccity' class="form-control" type="text" maxlength="255" id="inputccity">
                                            @error('ccity') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Tipo <span class="text-danger">*</span></label>
                                            <select wire:model="ctype" class="form-control">
                                                <option value="Consumidor Final">Consumidor Final</option>
                                                <option value="Mayoristas">Mayoristas</option>
                                                <option value="Descuento1">Descuento1</option>
                                                <option value="Descuento2">Descuento2</option>
                                                <option value="Otro">Otro</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12 d-flex justify-content-end">
                                            <button wire:click.prevent='storeCustomer' class="btn btn-primary" type="submit">Registrar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3">
                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="mb-0">
                                @if ($customer != null)
                                    {{ $customer['name'] ?? '' }} <i class="fas fa-check-circle text-success"></i>
                                @else
                                    Cliente
                                @endif
                            </label>
                            
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="customSwitch1" wire:model.live="applyCommissions">
                                <label class="custom-control-label" for="customSwitch1" style="font-size: 0.8rem;">Aplicar Comisiones</label>
                            </div>
                        </div>
                        
                        @if($sellerConfig)
                            <div class="alert alert-info p-2" style="font-size: 0.85rem;">
                                <i class="fas fa-info-circle"></i> Precios Foráneos
                                <br>
                                <small>
                                    (Com: {{ $sellerConfig->commission_percent }}% | Flete: {{ $sellerConfig->freight_percent }}% | Dif: {{ $sellerConfig->exchange_diff_percent }}%)
                                </small>
                            </div>
                        @endif

                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-warehouse"></i></span>
                            </div>
                            <select wire:model.live="warehouse_id" class="form-control" @cannot('sales.switch_warehouse') disabled @endcannot>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="input-group" wire:ignore>
                            <input class="form-control" type="text" id="inputCustomer" placeholder="Buscar Cliente (Shift + C)">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                        </div>
                    </div>

                    @php
                        $primaryCurrency = collect($currencies)->firstWhere('is_primary', 1);
                        $symbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                    @endphp

                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted">Artículos:</td>
                                <td class="text-right font-weight-bold">{{ $itemsCart }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Subtotal:</td>
                                <td class="text-right font-weight-bold">{{ $symbol }}{{ $subtotalCart }}</td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted">I.V.A.:</td>
                                <td class="text-right font-weight-bold">{{ $symbol }}{{ $ivaCart }}</td>
                            </tr>
                            <tr>
                                <td class="h5 font-weight-bold">TOTAL:</td>
                                <td class="h5 font-weight-bold text-primary text-right">{{ $symbol }}{{ $totalCart }}</td>
                            </tr>
                        </table>
                    </div>

                    {{-- Multi-currency display --}}
                    @if($currencies && $currencies->count() > 1)
                        @php
                            $primaryCurrency = $currencies->firstWhere('is_primary', true);
                            $primaryRate = $primaryCurrency ? $primaryCurrency->exchange_rate : 1;
                        @endphp
                        <div class="mt-2 p-2 bg-light rounded">
                            @foreach($currencies as $currency)
                                @if(!$currency->is_primary)
                                    @php
                                        // Convertir: Primaria -> USD -> Moneda Objetivo
                                        $amountInUSD = $totalCart / $primaryRate;
                                        $convertedAmount = $amountInUSD * $currency->exchange_rate;
                                    @endphp
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span>{{ $currency->code }}:</span>
                                        <span>{{ $currency->symbol }}{{ number_format($convertedAmount, 2) }}</span>
                                    </div>
                                @endif
                            @endforeach
                        </div>
                    @endif

                    @can('guardar ordenes de ventas')
                        <button wire:click.prevent="storeOrder" class="btn btn-outline-primary btn-block mt-3">
                            <i class="fas fa-save mr-2"></i> Guardar Orden
                            <small class="d-block text-muted" style="font-size: 0.7rem;">Shift + G</small>
                        </button>
                    @endcan

                    @can('metodos de pago')
                        <hr>
                        <h6 class="text-center font-weight-bold mb-3">Método de Pago</h6>
                        <div class="row">
                            @can('pago con efectivo/nequi')
                                <div class="col-4 text-center mb-2" wire:click="initPayment(1)" style="cursor: pointer;">
                                    <div class="btn btn-outline-success btn-block p-2">
                                        <i class="fas fa-money-bill-wave fa-2x mb-1"></i>
                                        <div style="font-size: 0.7rem;">Efectivo</div>
                                    </div>
                                </div>
                            @endcan
                            @can('pago con credito')
                                <div class="col-4 text-center mb-2" wire:click="initPayment(2)" style="cursor: pointer;">
                                    <div class="btn btn-outline-info btn-block p-2">
                                        <i class="fas fa-credit-card fa-2x mb-1"></i>
                                        <div style="font-size: 0.7rem;">Crédito</div>
                                    </div>
                                </div>
                            @endcan
                            @can('pago con Banco')
                                <div class="col-4 text-center mb-2" wire:click="initPayment(3)" style="cursor: pointer;">
                                    <div class="btn btn-outline-secondary btn-block p-2">
                                        <i class="fas fa-university fa-2x mb-1"></i>
                                        <div style="font-size: 0.7rem;">Banco</div>
                                    </div>
                                </div>
                            @endcan
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @include('livewire.pos.partials.payCash')
    @include('livewire.pos.partials.payDeposit')
    @include('livewire.pos.partials.process-order')
    @include('livewire.pos.partials.script')
    @include('livewire.pos.partials.shortcuts')
    @include('livewire.pos.partials.unit-selection-modal')

    <!-- Modal Stock Warning -->
    <div wire:ignore.self class="modal fade" id="modalStockWarning" tabindex="-1" role="dialog" aria-labelledby="modalStockWarningLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark" id="modalStockWarningLabel">
                        <i class="fas fa-exclamation-triangle"></i> Advertencia de Stock Reservado
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="text-center">{!! $stockWarningMessage !!}</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" wire:click="$set('stockWarningMessage', null)">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    @if($maxAvailableQty > 0)
                        <button type="button" class="btn btn-info" wire:click="adjustToMax">
                            <i class="fas fa-check-circle"></i> Ajustar a {{ $maxAvailableQty }}
                        </button>
                    @endif
                    <button type="button" class="btn btn-warning font-weight-bold" wire:click="forceAddProduct">
                        <i class="fas fa-check"></i> Continuar de todos modos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
             Livewire.on('show-stock-warning', (event) => {
                $('#modalStockWarning').modal('show');
            });
            Livewire.on('close-stock-warning', (event) => {
                $('#modalStockWarning').modal('hide');
            });
        });
    </script>
    <script src="{{ asset('assets/js/keypress.js') }}"></script>
</div>
