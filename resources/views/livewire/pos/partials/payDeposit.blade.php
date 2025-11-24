<div>
    <div wire:ignore.self class="modal fade" id="modalDeposit" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-success">
                    <h5 class="modal-title">{{ $payTypeName }}</h5>
                    <button class="btn-close py-0" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">


                    <div class="light-card balance-card align-items-center mb-1">
                        <h6 class="f-w-400 f-18 mb-0">Artículos:</h6>
                        <div class="ms-auto text-end">
                            <span class="f-18 f-w-700">
                                {{ $itemsCart }}
                            </span>
                        </div>
                    </div>
                    <div class="light-card balance-card align-items-center mb-1">
                        <h6 class="f-w-400 f-18 mb-0">Subtotal:</h6>
                        <div class="ms-auto text-end">
                            <span class="f-18 f-w-700">
                                ${{ $subtotalCart }}
                            </span>
                        </div>
                    </div>
                    <div class="light-card balance-card align-items-center border-bottom">
                        <h6 class="f-w-400 f-18 mb-0">I.V.A.:</h6>
                        <div class="ms-auto text-end">
                            <span class="f-18 f-w-700">
                                ${{ $ivaCart }}
                            </span>
                        </div>
                    </div>
                    <div class="light-card balance-card align-items-center">
                        <h6 class="f-w-700 f-18 mb-0 txt-success">TOTAL:</h6>
                        <div class="ms-auto text-end">
                            <span class="f-18 f-w-700">
                                ${{ $totalCart }}
                            </span>
                        </div>
                    </div>



                    <div class="input-group mt-4">
                        <select class="form-select" id="selBank" wire:model="bank">
                            @forelse($banks as $bank)
                            <option value="{{$bank->id}}">{{$bank->name}}</option>
                            @empty
                            <option value="-1" disabled>No hay bancos registrados</option>
                            @endforelse
                        </select>
                    </div>

                    {{-- <div class="input-group mt-4">
                        <span class="input-group-text">Número de cuenta</span>
                        <input class="form-control number" wire:model.live="acountNumber" type="text">
                    </div> --}}
                    <div class="mt-3">
                        <div class="position-relative">
                            <select class="form-control crypto-select info" disabled>
                                <option>N°. CUENTA:</option>
                            </select>
                            <input class="form-control" oninput="validarInputNumber(this)"
                                wire:model.live.debounce.500ms="acountNumber" type="text">
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="position-relative">
                            <select class="form-control crypto-select info" disabled>
                                <option>N°. DEPÓSITO:</option>
                            </select>
                            <input class="form-control" oninput="validarInputNumber(this)"
                                wire:model.live.debounce.500ms="depositNumber" wire:keydown.enter.prevent='Store'
                                type="text">
                        </div>
                    </div>

                    {{-- <div class="input-group mt-4">
                        <span class="input-group-text">Número de depósito</span>
                        <input class="form-control number" wire:model.live="depositNumber" type="text">
                    </div> --}}






                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary " type="button" data-bs-dismiss="modal">Cerrar</button>

                    <button class="btn btn-primary" wire:click.prevent='Store' type="button"
                        wire:loading.attr="disabled" {{ floatval($totalCart)==0 ? 'disabled' : '' }} {{
                        $acountNumber==null || $depositNumber==null ? 'disabled' : '' }}>

                        <span wire:loading.remove wire:target="Store">
                            Registrar
                        </span>
                        <span wire:loading wire:target="Store">
                            Registrando...
                        </span>
                    </button>

                </div>
            </div>
        </div>
    </div>
</div>