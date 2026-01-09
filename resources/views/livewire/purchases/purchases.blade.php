<div>
    <div class="row">
        <div class="col-sm-12 col-md-9">
            @include('livewire.purchases.partials.items')
            @include('livewire.purchases.partials.payConfirm')
            @include('livewire.purchases.partials.script')
            @include('livewire.purchases.partials.price-modal')
            @include('livewire.purchases.partials.process-order')
    <livewire:purchase-partial-payment />
    
    <div wire:ignore.self class="modal fade" id="modalSupplier" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">
                        <b>Nuevo Proveedor</b>
                    </h5>
                    <button class="btn-close" data-dismiss="modal" type="button" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group">
                                <label>Nombre</label>
                                <input wire:model="sname" class="form-control" type="text" placeholder="ej: Distribuidora XYZ">
                                @error('sname') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group">
                                <label>Teléfono</label>
                                <input wire:model="sphone" class="form-control" type="text" placeholder="ej: 555-1234">
                                @error('sphone') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Dirección</label>
                                <input wire:model="saddress" class="form-control" type="text" placeholder="ej: Calle Principal #123">
                                @error('saddress') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-dark" type="button" data-dismiss="modal">Cancelar</button>
                    <button class="btn btn-primary" type="button" wire:click="storeSupplier">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', function() {
            Livewire.on('close-modal-supplier', event => {
                $('#modalSupplier').modal('hide');
            })
        })
    </script>
</div>
        <div class="col-sm-12 col-md-3">
            <div class="card card-primary card-outline customer-sticky">
                <div class="card-header">
                    <h3 class="card-title">Resumen</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-toggle="modal" data-target="#modalSupplier">
                            <i class="fas fa-plus"></i> Crear Proveedor
                        </button>
                    </div>
                </div>
                <div class="card-body p-3">
                    <div class="form-group">
                        <label>
                            @if ($supplier != null)
                                {{ $supplier['name'] ?? '' }} <i class="fas fa-check-circle text-success"></i>
                            @else
                                Proveedor
                            @endif
                        </label>

                        <div class="input-group" wire:ignore>
                            <input class="form-control" type="text" id="inputSupplier" placeholder="Buscar Proveedor (F1)">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                        </div>
                    </div>

                    {{-- Flete Input --}}
                    <div class="input-group mb-3">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Flete $</span>
                        </div>
                        <input wire:keydown.enter.prevent='setFlete($event.target.value)' class="form-control"
                            id="inputFlete" type="text" placeholder="Costo" 
                            @if($flete > 0) disabled value="{{$flete}}" @endif>
                        @if($flete != 0)
                            <div class="input-group-append">
                                <span wire:click='unsetFlete' class="input-group-text" style="cursor:pointer">
                                    <i class="fas fa-trash"></i>
                                </span>
                            </div>
                        @endif
                    </div>

                    @php
                        $primaryCurrency = \App\Models\Currency::where('is_primary', 1)->first();
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
                            <tr class="border-bottom">
                                <td class="text-muted">Flete:</td>
                                <td class="text-right font-weight-bold text-success">{{ $symbol }}{{ $flete }}</td>
                            </tr>
                            <tr>
                                <td class="h5 font-weight-bold">TOTAL:</td>
                                <td class="h5 font-weight-bold text-primary text-right">{{ $symbol }}{{ $totalCart }}</td>
                            </tr>
                        </table>
                    </div>

                    <button wire:click.prevent="storeOrder" class="btn btn-outline-primary btn-block mt-3">
                        <i class="fas fa-save mr-2"></i> Guardar Orden
                        <small class="d-block text-muted" style="font-size: 0.7rem;">Shift + G</small>
                    </button>

                    <hr>
                    <h6 class="text-center font-weight-bold mb-3">Método de Pago</h6>
                    <div class="row">
                        <div class="col-6 text-center mb-2" wire:click="initPayment(1)" style="cursor: pointer;">
                            <div class="btn btn-outline-success btn-block p-2">
                                <i class="fas fa-money-bill-wave fa-2x mb-1"></i>
                                <div style="font-size: 0.7rem;">Contado</div>
                            </div>
                        </div>
                        <div class="col-6 text-center mb-2" wire:click="initPayment(2)" style="cursor: pointer;">
                            <div class="btn btn-outline-info btn-block p-2">
                                <i class="fas fa-credit-card fa-2x mb-1"></i>
                                <div style="font-size: 0.7rem;">Crédito</div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>


</div>