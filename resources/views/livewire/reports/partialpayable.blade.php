<div>
    <div wire:ignore.self class="modal fade" id="modalPartialPayable" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content ">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">Abono a Cuenta</h5>
                    <button class="btn-close py-0" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($purchase_id != null)
                        <section>
                            <div class="card height-equal">
                                <div class="card-header border-l-warning border-r-warning border-3 p-2">
                                    <h4 class="txt-dark text-center text-capitalize"><i
                                            class="icofont icofont-ui-user"></i>
                                        {{ $supplier_name }}</h4>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="light-card balance-card align-items-center mb-1 col-sm-12 col-md-4">
                                            <h6 class="f-w-600 f-18 mb-0 txt-warning">DEBE:</h6>
                                            <div class="ms-auto text-end">
                                                <span class="f-18 f-w-700 ">
                                                    ${{ $debt }}
                                                </span>
                                            </div>
                                        </div>
                                        <div
                                            class="light-card balance-card align-items-center mb-1 col-sm-12 col-md-4 m-l-10">
                                            <h6 class="f-w-600 f-18 mb-0 txt-warning">N° Compra:</h6>
                                            <div class="ms-auto text-end">
                                                <span class="f-18 f-w-700 ">
                                                    {{ $purchase_id }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-12">
                                            <h6 class="f-w-600 f-12 mb-2 txt-primary">MÉTODO DE PAGO:</h6>
                                            <div class="btn-group w-100" role="group">
                                                <button type="button" class="btn {{ $paymentMethod == 'cash' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="$set('paymentMethod', 'cash')">
                                                    <i class="icofont icofont-money"></i> Efectivo
                                                </button>
                                                <button type="button" class="btn {{ $paymentMethod == 'nequi' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="$set('paymentMethod', 'nequi')">
                                                    <i class="icofont icofont-smart-phone"></i> Nequi
                                                </button>
                                                <button type="button" class="btn {{ $paymentMethod == 'deposit' ? 'btn-primary' : 'btn-outline-primary' }}" wire:click="$set('paymentMethod', 'deposit')">
                                                    <i class="icofont icofont-bank-alt"></i> Banco
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row">
                                        {{-- CASH PAYMENT --}}
                                        @if($paymentMethod == 'cash')
                                            <div class="col-sm-12 col-md-6">
                                                <label>
                                                    <h6 class="f-w-600 f-12 mb-0 txt-primary">MONEDA:</h6>
                                                </label>
                                                <select class="form-select" wire:model.live="paymentCurrency">
                                                    @foreach($currencies as $currency)
                                                        <option value="{{ $currency->code }}">{{ $currency->code }} - {{ $currency->symbol }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        @endif

                                        {{-- NEQUI PAYMENT --}}
                                        @if($paymentMethod == 'nequi')
                                            <div class="col-sm-12 col-md-6">
                                                <label for="phoneNumber">
                                                    <h6 class="f-w-600 f-12 mb-0 txt-primary">N°. TELÉFONO:</h6>
                                                </label>
                                                <div class="input-group">
                                                    <span class="input-group-text"><i class="icofont icofont-ui-touch-phone"></i></span>
                                                    <input class="form-control" oninput="validarInputNumber(this)"
                                                        wire:model.live.debounce.750ms="phoneNumber"
                                                        type="number"
                                                        id="phoneNumber" placeholder="Número de celular">
                                                </div>
                                                @error('phoneNumber')
                                                    <span class="txt-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @endif

                                        {{-- DEPOSIT PAYMENT --}}
                                        @if($paymentMethod == 'deposit')
                                            <div class="col-sm-12 col-md-6">
                                                <label for="banco">
                                                    <h6 class="f-w-600 f-12 mb-0 txt-primary">BANCO:</h6>
                                                </label>
                                                <select class="form-select" wire:model="bank">
                                                    <option value="0">Seleccionar Banco</option>
                                                    @forelse($banks as $bank)
                                                        <option value="{{ $bank->id }}">{{ $bank->name }}</option>
                                                    @empty
                                                        <option value="-1" disabled>No hay bancos registrados</option>
                                                    @endforelse
                                                </select>
                                                @error('bank')
                                                    <span class="txt-danger">{{ $message }}</span>
                                                @enderror
                                            </div>

                                            <div class="col-sm-12 col-md-6 mt-3">
                                                <label><h6 class="f-w-600 f-12 mb-0 txt-primary">N°. CUENTA:</h6></label>
                                                <input class="form-control" wire:model.live="acountNumber" type="text" placeholder="Número de cuenta">
                                                @error('nacount')
                                                    <span class="txt-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <div class="col-sm-12 col-md-6 mt-3">
                                                <label><h6 class="f-w-600 f-12 mb-0 txt-primary">N°. DEPÓSITO:</h6></label>
                                                <input class="form-control" wire:model.live="depositNumber" type="text" placeholder="Número de comprobante">
                                                @error('ndeposit')
                                                    <span class="txt-danger">{{ $message }}</span>
                                                @enderror
                                            </div>
                                        @endif
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-sm-12 col-md-6">
                                            <label for="amount">
                                                <h6 class="f-w-600 f-12 mb-0 txt-primary">MONTO A ABONAR:</h6>
                                            </label>
                                            <div class="input-group">
                                                <span class="input-group-text">
                                                    @php
                                                        $curr = $currencies->firstWhere('code', $paymentCurrency);
                                                        echo $curr ? $curr->symbol : '$';
                                                    @endphp
                                                </span>
                                                <input class="form-control form-control-lg" oninput="validarInputNumber(this)"
                                                    wire:model="amount" type="text" id="partialPayableInput" placeholder="0.00">
                                            </div>
                                            @error('amount')
                                                <span class="txt-danger">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="row mt-3">
                                        <div class="col-sm-12 col-md-6">

                                            <button class="btn btn-dark" type="button"
                                                wire:click.prevent="cancelPay">Cancelar</button>

                                            <button class="btn btn-primary" wire:click.prevent='doPayable'
                                                type="button" wire:loading.attr="disabled">

                                                <span wire:loading.remove wire:target="doPayable">
                                                    Registrar Pago
                                                </span>
                                                <span wire:loading wire:target="doPayable">
                                                    Registrando...
                                                </span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </section>

                    @endif
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary " type="button" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
