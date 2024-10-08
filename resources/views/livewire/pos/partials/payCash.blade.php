<div>
    <div wire:ignore.self class="modal fade" id="modalCash" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header {{ $payType == 1 ? 'bg-dark' : 'bg-info' }}">
                    <h5 class="modal-title">{{ $payTypeName }}</h5>
                    <button class="py-0 btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <div class="mb-1 light-card balance-card align-items-center">
                        <h6 class="mb-0 f-w-400 f-18">Artículos:</h6>
                        <div class="ms-auto text-end">
                            <span class="f-18 f-w-700">
                                {{ $itemsCart }}
                            </span>
                        </div>
                    </div>
                    <div class="mb-1 light-card balance-card align-items-center">
                        <h6 class="mb-0 f-w-400 f-18">Subtotal:</h6>
                        <div class="ms-auto text-end">
                            <span class="f-18 f-w-700">
                                ${{ $subtotalCart }}
                            </span>
                        </div>
                    </div>
                    <div class="light-card balance-card align-items-center border-bottom">
                        <h6 class="mb-0 f-w-400 f-18">I.V.A.:</h6>
                        <div class="ms-auto text-end">
                            <span class="f-18 f-w-700">
                                ${{ $ivaCart }}
                            </span>
                        </div>
                    </div>
                    <div class="light-card balance-card align-items-center">
                        <h6 class="f-w-700 f-18 mb-0 {{ $payType == 1 ? 'txt-dark' : 'txt-info' }}">TOTAL:</h6>
                        <div class="ms-auto text-end">
                            <span class="f-18 f-w-700">
                                ${{ $totalCart }}
                            </span>
                        </div>
                    </div>



                    @if ($payType == 1)
                        <div class="mt-4">
                            <div class="position-relative">
                                <select class="form-control crypto-select info" disabled>
                                    <option>EFECTIVO:</option>
                                </select>
                                <input class="form-control" oninput="validarInputNumber(this)"
                                    wire:model.live.debounce.750ms="cashAmount" wire:keydown.enter.prevent='Store'
                                    type="number" id="inputCash">
                            </div>
                        </div>


                        <div
                            class="light-card balance-card align-items-center {{ $cashAmount > 0 ? 'd:block' : 'd-none' }} mt-2">
                            <h6 class="mb-0 f-w-400 f-16">Cambio:</h6>
                            <div class="ms-auto text-end"><span class="f-16 txt-warning"> ${{ $change }}</span>
                            </div>
                        </div>
                    @endif

                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary " type="button" data-bs-dismiss="modal">Cerrar</button>


                    <button class="btn btn-primary" wire:click.prevent='Store' type="button"
                        wire:loading.attr="disabled" {{ floatval($totalCart) == 0 ? 'disabled' : '' }}>

                        <span wire:loading.remove wire:target="Store">
                            Registrar
                        </span>
                        <span wire:loading wire:target="Store">
                            Registrando...
                        </span>
                    </button>




                    {{-- @if ($payType == 2)
                    <button class="btn btn-primary" wire:click.prevent='Store' type="button"
                        wire:loading.attr="disabled" {{ floatval($totalCart)==0 ? 'disabled' : '' }}>

                        <span wire:loading.remove wire:target="Store">
                            Registrar
                        </span>
                        <span wire:loading wire:target="Store">
                            Registrando...
                        </span>
                    </button>
                    @endif --}}

                </div>
            </div>
        </div>
    </div>
</div>
