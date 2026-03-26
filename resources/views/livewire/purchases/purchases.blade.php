<div>
    <div class="row px-2 pt-3">
        {{-- Main Table Section --}}
        <div class="col-sm-12 col-md-8 col-lg-9">
            @include('livewire.purchases.partials.items')
            
            @include('livewire.purchases.partials.payConfirm')
            @include('livewire.purchases.partials.script')
            @include('livewire.purchases.partials.price-modal')
            @include('livewire.purchases.partials.process-order')
            <livewire:purchase-partial-payment />
        </div>

        {{-- Sidebar Summary Section --}}
        <div class="col-sm-12 col-md-4 col-lg-3">
            <div class="card shadow-sm border-0 sticky-top" style="border-radius: 16px; top: 20px; border: 1px solid #e9ecef;">
                <div class="card-header bg-white border-bottom-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                    <h5 class="font-weight-bold text-dark mb-0" style="letter-spacing: -0.5px;">Resumen Compra</h5>
                    <button type="button" class="btn btn-sm btn-light text-primary rounded-circle shadow-none" data-toggle="modal" data-target="#modalSupplier" title="Nuevo Proveedor">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
                
                <div class="card-body px-4 pb-4">
                    <div class="form-group mb-4">
                        <label class="text-muted small text-uppercase font-weight-bold mb-2" style="font-size: 0.65rem;">Destino</label>
                        <div class="input-merge shadow-none d-flex align-items-center bg-light px-3 py-2" style="border-radius: 10px;">
                            <i class="fas fa-warehouse text-muted mr-3"></i>
                            <select wire:model.live="warehouse_id" class="form-control border-0 bg-transparent p-0 font-weight-bold" style="box-shadow: none; height: auto;">
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ strtoupper($w->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="form-group mb-4">
                        <label class="text-muted small text-uppercase font-weight-bold mb-2" style="font-size: 0.65rem;">Proveedor</label>
                        <div class="input-merge shadow-none d-flex align-items-center bg-light px-3 py-2" style="border-radius: 10px;" wire:ignore>
                            <i class="fas fa-user-tie text-muted mr-3"></i>
                            <input class="form-control border-0 bg-transparent p-0 font-weight-bold" type="text" id="inputSupplier" placeholder="Seleccionar (F1)" style="box-shadow: none; height: auto;">
                        </div>
                        @if ($supplier != null)
                        <div class="mt-2 p-2 rounded bg-success-light text-success small border border-success" style="background-color: #f0fdf4;">
                            <i class="fas fa-check-circle"></i> {{ strtoupper($supplier['name'] ?? '') }}
                        </div>
                        @endif
                    </div>

                    {{-- Flete Section --}}
                    <div class="form-group mb-4">
                        <label class="text-muted small text-uppercase font-weight-bold mb-2" style="font-size: 0.65rem;">Gastos de Flete</label>
                        <div class="input-group shadow-none overflow-hidden" style="border-radius: 10px;">
                            <div class="input-group-prepend">
                                <span class="input-group-text bg-white border-right-0" style="color: #adb5bd;">$</span>
                            </div>
                            <input wire:keydown.enter.prevent='setFlete($event.target.value)' 
                                class="form-control border-left-0 {{ $flete > 0 ? 'bg-light font-weight-bold text-success' : '' }}"
                                id="inputFlete" type="number" step="0.01" placeholder="0.00" 
                                style="border-color: #dee2e6;"
                                @if($flete > 0) disabled value="{{$flete}}" @endif>
                            @if($flete != 0)
                                <div class="input-group-append">
                                    <span wire:click='unsetFlete' class="input-group-text bg-white border-left-0 text-danger" style="cursor:pointer">
                                        <i class="fas fa-times-circle"></i>
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>

                    @php
                        $primaryCurrency = \App\Models\Currency::where('is_primary', 1)->first();
                        $symbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                    @endphp

                    <div class="p-3 rounded mb-4" style="background: linear-gradient(135deg, #f8f9fa 0%, #f1f3f5 100%);">
                        <table class="table table-sm table-borderless m-0">
                            <tr>
                                <td class="text-muted py-1 small">Items:</td>
                                <td class="text-right font-weight-bold py-1">{{ $itemsCart }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted py-1 small">Subtotal:</td>
                                <td class="text-right font-weight-bold py-1">{{ $symbol }}{{ number_format($subtotalCart, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted py-1 small">I.V.A.:</td>
                                <td class="text-right font-weight-bold py-1">{{ $symbol }}{{ number_format($ivaCart, 2) }}</td>
                            </tr>
                            <tr class="border-top">
                                <td class="text-muted pt-2 border-0 small">Flete:</td>
                                <td class="text-right font-weight-bold pt-2 border-0 text-success">{{ $symbol }}{{ number_format($flete, 2) }}</td>
                            </tr>
                            <tr>
                                <td class="h5 font-weight-bold pt-3 mb-0">TOTAL:</td>
                                <td class="h4 font-weight-bold text-primary text-right pt-2 mb-0" style="font-family: 'Inter', sans-serif; letter-spacing: -1px;">{{ $symbol }}{{ number_format($totalCart, 2) }}</td>
                            </tr>
                        </table>
                    </div>

                    <button wire:click.prevent="storeOrder" class="btn btn-primary btn-block btn-lg shadow mb-4 py-3 border-0 transition-all hover-scale" 
                        style="border-radius: 12px; background: linear-gradient(135deg, #4361ee, #3f37c9); box-shadow: 0 4px 15px rgba(67, 97, 238, 0.3) !important;">
                        <i class="fas fa-save mr-2"></i> REGISTRAR COMPRA
                    </button>

                    <div class="text-center">
                        <p class="text-muted mb-3 font-weight-bold" style="font-size: 0.6rem; letter-spacing: 1px;">MÉTODOS DE PAGO</p>
                        <div class="d-flex justify-content-between">
                            <button wire:click="initPayment(1)" class="btn flex-fill p-2 border-0 rounded shadow-none transition-all mr-2" 
                                style="background-color: #dcfce7; color: #166534;" title="Contado">
                                <i class="fas fa-money-bill-wave d-block mb-1"></i>
                                <span class="font-weight-bold" style="font-size: 0.65rem;">CONTADO</span>
                            </button>
                            <button wire:click="initPayment(2)" class="btn flex-fill p-2 border-0 rounded shadow-none transition-all ml-2" 
                                style="background-color: #eff6ff; color: #1e40af;" title="Crédito">
                                <i class="fas fa-credit-card d-block mb-1"></i>
                                <span class="font-weight-bold" style="font-size: 0.65rem;">CRÉDITO</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modals Section --}}
    <div wire:ignore.self class="modal fade" id="modalSupplier" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px;">
                <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title font-weight-bold">Nuevo Proveedor</h5>
                    <button class="btn-close text-white border-0 bg-transparent" data-dismiss="modal" type="button" aria-label="Close" onclick="$('#modalSupplier').modal('hide')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-sm-12 col-md-6 mb-3">
                            <label class="font-weight-bold small text-muted">Nombre</label>
                            <input wire:model="sname" class="form-control bg-light border-0" type="text" placeholder="Distribuidora XYZ" style="border-radius: 8px;">
                            @error('sname') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-sm-12 col-md-6 mb-3">
                            <label class="font-weight-bold small text-muted">Teléfono</label>
                            <input wire:model="sphone" class="form-control bg-light border-0" type="text" placeholder="555-1234" style="border-radius: 8px;">
                            @error('sphone') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-sm-12">
                            <label class="font-weight-bold small text-muted">Dirección</label>
                            <input wire:model="saddress" class="form-control bg-light border-0" type="text" placeholder="Calle Principal #123" style="border-radius: 8px;">
                            @error('saddress') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button class="btn btn-light px-4" type="button" data-dismiss="modal" onclick="$('#modalSupplier').modal('hide')" style="border-radius: 8px;">Cancelar</button>
                    <button class="btn btn-primary px-4" type="button" wire:click="storeSupplier" style="border-radius: 8px;">Guardar Proveedor</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="modalVariableItem" tabindex="-1" role="dialog" wire:ignore.self shadow-lg>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0" style="border-radius: 15px;">
                <div class="modal-header bg-dark text-white" style="border-radius: 15px 15px 0 0;">
                    <h5 class="modal-title">Agregar Item / Bobina</h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" onclick="$('#modalVariableItem').modal('hide')">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4">
                    <div class="row">
                        <div class="col-sm-12 mb-3">
                            <label class="font-weight-bold small text-muted">Peso (Kg)</label>
                            <input type="number" id="vw_weight" wire:model="vw_weight" wire:keydown.enter="addVariableItem" class="form-control form-control-lg bg-light border-0 text-primary font-weight-bold" placeholder="0.00" style="border-radius: 10px;">
                            @error('vw_weight') <span class="text-danger small">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-sm-12 mb-3">
                            <label class="font-weight-bold small text-muted">Color (Opcional)</label>
                            <input type="text" wire:model="vw_color" wire:keydown.enter="addVariableItem" class="form-control bg-light border-0" placeholder="Ej: Rojo" style="border-radius: 8px;">
                        </div>
                        <div class="col-sm-12">
                            <label class="font-weight-bold small text-muted">Lote (Opcional)</label>
                            <input type="text" wire:model="vw_batch" wire:keydown.enter="addVariableItem" class="form-control bg-light border-0" placeholder="Lote #" style="border-radius: 8px;">
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-0 p-4">
                    <button type="button" class="btn btn-light px-4" data-dismiss="modal" onclick="$('#modalVariableItem').modal('hide')" style="border-radius: 8px;">Cerrar</button>
                    <button type="button" class="btn btn-primary px-4" wire:click="addVariableItem" style="border-radius: 8px;">Agregar Item</button>
                </div>
            </div>
        </div>
    </div>

    <style>
        .hover-scale:hover { transform: scale(1.02); }
        .transition-all { transition: all 0.3s ease; }
        .bg-success-light { background-color: #f0fdf4; }
        .letter-spacing-1 { letter-spacing: 1px; }
        .card { transition: transform 0.3s ease, box-shadow 0.3s ease; }
        .payment-btn:hover { filter: brightness(0.95); transform: translateY(-2px); }
        .sticky-top { z-index: 10 !important; }
    </style>

    <script>
        document.addEventListener('livewire:init', function() {
            Livewire.on('close-modal-supplier', event => {
                $('#modalSupplier').modal('hide');
            });
            
            Livewire.on('show-variable-modal', () => {
                 $('#modalVariableItem').modal('show');
                 setTimeout(() => {
                     $('#vw_weight').focus();
                 }, 500);
            });

            Livewire.on('focus-weight', () => {
                 setTimeout(() => {
                     $('#vw_weight').val(''); 
                     $('#vw_weight').focus();
                 }, 300);
            });
        })
    </script>
</div>
