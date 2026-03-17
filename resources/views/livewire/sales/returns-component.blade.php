<div>
    <div wire:ignore.self class="modal fade" id="modalReturns" tabindex="-1" role="dialog" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger p-2">
                    <h5 class="modal-title text-white">
                        <b><i class="fa fa-undo"></i> Realizar Devolución</b> 
                        @if($sale) | Venta #{{ $sale->invoice_number }} @endif
                    </h5>
                    <button class="btn-close text-white" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    @if($sale)
                        <div class="alert alert-light border border-danger">
                            <i class="fa fa-info-circle text-danger"></i> 
                            Indique la cantidad exacta de productos que el cliente está devolviendo.
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered">
                                <thead class="table-light">
                                    <tr class="text-center">
                                        <th>Producto</th>
                                        <th>Precio Venta</th>
                                        <th>Cant. Comprada</th>
                                        <th>Cant. a Devolver</th>
                                        <th>Condición / Almacén</th>
                                        <th>Subtotal Devuelto</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($returnItems as $index => $item)
                                        <tr class="text-center align-middle">
                                            <td class="text-start">{{ $item['product_name'] }}</td>
                                            <td>{{ $currencySymbol }}{{ number_format($item['unit_price'], 2) }}</td>
                                            <td>{{ $item['qty_available'] }}</td>
                                            <td style="width: 150px;">
                                                <input type="number" 
                                                       wire:model.live.debounce.500ms="returnItems.{{ $index }}.qty_to_return" 
                                                       class="form-control form-control-sm text-center border-danger" 
                                                       min="0" max="{{ $item['qty_available'] }}" step="any">
                                            </td>
                                            <td style="width: 250px;">
                                                <select wire:model.live="returnItems.{{ $index }}.condition" class="form-select form-select-sm mb-1">
                                                    <option value="good">Buen Estado (Vuelve al origen)</option>
                                                    <option value="bad">Mal Estado (Mover a Merma)</option>
                                                </select>
                                                
                                                @if(isset($item['condition']) && $item['condition'] === 'bad')
                                                    <select wire:model.live="returnItems.{{ $index }}.destination_warehouse_id" class="form-select form-select-sm border-warning">
                                                        <option value="">-- Destino Merma --</option>
                                                        @foreach($warehouses as $wh)
                                                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                                        @endforeach
                                                    </select>
                                                @endif
                                            </td>
                                            <td class="fw-bold text-danger">
                                                {{ $currencySymbol }}{{ number_format(((float)($item['qty_to_return'] ?? 0)) * ((float)($item['unit_price'] ?? 0)), 2) }}
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light-danger">
                                        <td colspan="5" class="text-end fw-bold">TOTAL A DEVOLVER:</td>
                                        <td class="text-center fw-bold h5 text-danger mb-0">
                                            {{ $currencySymbol }}{{ number_format($totalReturnAmount, 2) }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-6 mb-3">
                                <label><b>Método de Reembolso:</b></label>
                                <select wire:model.live="refundMethod" class="form-select border-danger">
                                    @if($sale->debt > 0)
                                        <option value="debt_reduction">Reducir la Deuda (Abono a Factura)</option>
                                    @endif
                                    <option value="cash">Entregar Efectivo (Caja)</option>
                                    <option value="wallet">Saldo a Favor (Billetera Virtual)</option>
                                    <option value="bank">Transferencia / Zelle</option>
                                </select>
                            </div>

                            @if($refundMethod === 'cash')
                                <div class="col-md-6 mb-3">
                                    <label><b>Caja (Afectar Efectivo):</b></label>
                                    <select wire:model="cashRegisterId" class="form-select border-danger">
                                        <option value="">Seleccione una caja abierta...</option>
                                        @foreach($registers as $register)
                                            <option value="{{ $register->id }}">Caja #{{ $register->id }} ({{ $register->user->name ?? 'Usuario' }})</option>
                                        @endforeach
                                    </select>
                                    @error('cashRegisterId') <span class="text-danger small">{{ $message }}</span> @enderror
                                </div>
                            @endif
                            
                            <div class="col-md-12 mb-3">
                                <label><b>Motivo de la Devolución <span class="text-danger">(Obligatorio)</span>:</b></label>
                                <textarea wire:model="reason" class="form-control mb-3" rows="2" placeholder="Ej: Producto dañado, cambio de talla, etc."></textarea>
                            </div>

                        </div>

                    @else
                        <div class="text-center p-3">
                            <div class="spinner-border text-danger" role="status"></div>
                            <p class="mt-2">Cargando...</p>
                        </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
                    @if($sale && $totalReturnAmount > 0)
                        <button class="btn btn-danger" wire:click="processReturn" wire:loading.attr="disabled" wire:target="processReturn">
                            <span wire:loading.remove wire:target="processReturn">Confirmar Devolución</span>
                            <span wire:loading wire:target="processReturn">Procesando...</span>
                        </button>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('show-return-modal', () => {
            $('#modalReturns').modal('show');
        });
        
        Livewire.on('hide-return-modal', () => {
            $('#modalReturns').modal('hide');
        });
    });
</script>
