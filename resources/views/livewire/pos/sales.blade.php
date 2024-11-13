<div>
    <div class="row">
        <div class="col-sm-12 col-md-9">
            @include('livewire.pos.partials.items')
        </div>
        <div class="col-sm-12 col-md-3">
            <div class="card customer-sticky">
                <div class="pb-3 card-header card-no-border">
                    <div class="pb-3 header-top border-bottom">
                        <h5 class="m-0">Resumen </h5>
                        <div class="card-header-right-icon create-right-btn"><a class="btn btn-light-primary f-w-500 f-12"
                                href="javascript:void(0)" data-bs-toggle="modal"
                                data-bs-target="#modalCustomerCreate">Crear +</a></div>

                        <!-- Modal-->
                        <div wire:ignore.self class="modal fade" id="modalCustomerCreate" tabindex="-1"
                            aria-labelledby="modalCustomerCreate" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="modalCustomerCreate">Registrar Cliente</h5>
                                        <button class="btn-close" type="button" data-bs-dismiss="modal"
                                            aria-label="Close"></button>
                                    </div>
                                    <div class="p-0 modal-body">
                                        <div class="text-start dark-sign-up">
                                            <div class="modal-body">
                                                <form class="row g-3 needs-validation" novalidate="">
                                                    <div class="col-sm-12">
                                                        <label class="form-label">Nombre
                                                            <span class="txt-danger">*</span></label>
                                                        <input wire:model='cname' class="form-control" type="text"
                                                            placeholder="ingresa el nombre" maxlength="45"
                                                            id="inputcname">
                                                        @error('cname')
                                                            <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                    <div class="col-sm-12">
                                                        <label class="form-label">CC/Nit <span
                                                                class="txt-danger">*</span></label>
                                                        <input wire:model='ctaxpayerId' class="form-control"
                                                            type="text" placeholder="" maxlength="65"
                                                            id="inputctaxpayerId">
                                                        @error('ctaxpayerId')
                                                            <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                    <div class="col-sm-12">
                                                        <label class="form-label">Email</label>
                                                        <input wire:model='cemail' class="form-control" type="text"
                                                            placeholder="" maxlength="65" id="inputcemail">

                                                    </div>
                                                    <div class="col-sm-12">
                                                        <label class="form-label">Teléfono</label>
                                                        <input wire:model='cphone' class="form-control" type="number"
                                                            placeholder="" maxlength="15" id="inputcphone">

                                                    </div>
                                                    <div class="col-sm-12">
                                                        <div class="">
                                                            <label class="form-label">Dirección <span
                                                                    class="txt-danger">*</span></label>
                                                            <input wire:model='caddress' class="form-control"
                                                                type="text" placeholder="" maxlength="255"
                                                                id="inputcaddress">
                                                            @error('caddress')
                                                                <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-12">
                                                        <div class="">
                                                            <label class="form-label">Ciudad <span
                                                                    class="txt-danger">*</span></label>
                                                            <input wire:model='ccity' class="form-control"
                                                                type="text" placeholder="" maxlength="255"
                                                                id="inputccity">
                                                            @error('ccity')
                                                                <span class="text-danger">{{ $message }}</span>
                                                            @enderror
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-12">
                                                        Tipo <span class="txt-danger">*</span>
                                                        <select wire:model="ctype" class="form-control">
                                                            <option value="Consumidor Final">Consumidor Final</option>
                                                            <option value="Mayoristas">Mayoristas</option>
                                                            <option value="Descuento1">Descuento1</option>
                                                            <option value="Descuento2">Descuento2</option>
                                                            <option value="Otro">Otro</option>
                                                        </select>
                                                    </div>
                                                    <div class="col-md-12 d-flex justify-content-end">
                                                        <button wire:click.prevent='storeCustomer'
                                                            class="btn btn-primary" type="submit">Registrar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
                <div class="pt-0 card-body order-details">

                    @if ($customer != null)
                        <span> {{ $customer['name'] ?? '' }} <i class="icofont icofont-verification-check"></i></span>
                    @else
                        Cliente
                    @endif

                    {{-- <div class="faq-form" wire:ignore>
                        <input class="form-control" type="text" placeholder="Nombre del Cliente" id="inputCustomer2">
                        <i class="search-icon" data-feather="user"></i>
                    </div> --}}

                    <div class="input-group" wire:ignore>
                        <input class="form-control" type="text" id="inputCustomer" placeholder="Shift + C">
                        <span class="input-group-text list-light">
                            <i class="search-icon" data-feather="user"></i>
                        </span>
                    </div>

                    <div class="total-item">
                        <div class="item-number"><span class="text-gray">Artículos</span><span
                                class="f-w-500">{{ $itemsCart }}
                                (Items)</span></div>
                        <div class="item-number"><span class="text-gray">Subtotal</span><span
                                class="f-w-500">${{ $subtotalCart }}</span></div>
                        <div class="item-number border-bottom"><span class="text-gray">I.V.A.</span><span
                                class="f-w-500">${{ $ivaCart }}</span></div>
                        <div class="pt-3 pb-0 item-number"><span class="f-w-700">TOTAL</span>
                            <h6 class="txt-primary">${{ $totalCart }}</h6>
                        </div>
                    </div>
                    @can('guardar ordenes de ventas')
                        {{-- <div class="mt-2 card-header-right-icon create-right-btn btn-block w-100"><button
                                wire:click.prevent="storeOrder" class="btn btn-light-primary f-w-500 f-18 w-100"><i
                                    class="m-2 icofont icofont-save fa-1x"></i><span>Gurardar </span>
                                orden <small class="f-0 small sm">Shift + G</small></button>
                        </div> --}}

                        <div class="mt-2 card-header-right-icon create-right-btn btn-block w-100">
                            <button wire:click.prevent="storeOrder" class="btn btn-light-primary f-w-500 f-18 w-100">
                                <i class="m-2 icofont icofont-save fa-1x"></i>
                                <span>Gurardar orden</span>

                                <small class="f-0 small sm text-muted font-weight-bold">Shift + G</small>
                            </button>
                        </div>
                    @endcan
                    @can('metodos de pago')
                        <h5 class="m-0 p-t-40">Método de Pago</h5>
                        <div class="payment-methods">
                            @can('pago con efectivo/nequi')
                                <div wire:click="initPayment(1)">
                                    <div class="bg-payment widget-hover"> <img
                                            src="../assets/images/dashboard-8/payment-option/cash.svg" alt="cash"></div>
                                    <span class="f-w-500 text-gray">Efectivo</span>
                                </div>
                            @endcan
                            @can('pago con credito')
                                <div wire:click="initPayment(2)">
                                    <div class="bg-payment widget-hover"> <img
                                            src="../assets/images/dashboard-8/payment-option/card.svg" alt="card"></div>
                                    <span class="f-w-500 text-gray">Crédito</span>
                                </div>
                            @endcan
                            @can('pago con Banco')
                                <div wire:click="initPayment(3)">
                                    <div class="bg-payment widget-hover"> <img
                                            src="../assets/images/dashboard-8/payment-option/wallet.svg" alt="wallet"></div>
                                    <span class="f-w-500 text-gray">Banco</span>
                                </div>
                            @endcan
                            @can('pago con Nequi')
                                <div wire:click="initPayment(4)">
                                    <div class="bg-payment widget-hover"> <img src="../logo/nequi.webp" alt="wallet">
                                    </div>
                                    <span class="f-w-500 text-gray">Nequi</span>
                                </div>
                            @endcan
                        </div>
                        {{-- <div class="place-order">
                            <button class="btn btn-primary btn-hover-effect w-100 f-w-500" type="button"><i
                                    class="m-2 icofont icofont-save fa-1x"></i>Guradar
                                orden de venta</button>
                        </div> --}}
                    @endcan
                </div>
            </div>
        </div>
    </div>
    <script src="{{ asset('assets/js/keypress.js') }}"></script>


    @include('livewire.pos.partials.payCash')
    @include('livewire.pos.partials.payNequi')
    @include('livewire.pos.partials.payDeposit')
    @include('livewire.pos.partials.process-order')
    @include('livewire.pos.partials.order-detail')
    @include('livewire.pos.partials.script')
    @include('livewire.pos.partials.shortcuts')

</div>
