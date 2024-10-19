<div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header bg-primary p-1">
                    <h5 class="txt-light text-center">Corte de Caja</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-3">
                            <div class="card border-l-primary border-3">

                                <div class="card-body">

                                    <span class="f-14"><b>Usuario</b></span>
                                    <select wire:model="user_id" class="form-select form-control-sm">
                                        <option value="0">Todos los usuarios</option>
                                        @foreach ($users as $user)
                                            <option value="{{ $user->id }}">
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>


                                    <div class="mt-3">
                                        <span class="f-14"><b>Fecha desde</b></span>
                                        <div class="input-group datepicker">
                                            <input class="form-control flatpickr-input active" id="dateFrom"
                                                type="text" autocomplete="off">
                                        </div>
                                    </div>

                                    <div class="mt-2">
                                        <span class="f-14"><b>Hasta</b></span>
                                        <div class="input-group datepicker">
                                            <input class="form-control flatpickr-input active" id="dateTo"
                                                type="text" autocomplete="off">
                                        </div>
                                    </div>



                                    <div class="mt-5">
                                        <button wire:click.prevent="getSalesBetweenDates"
                                            class="btn btn-outline-primary w-100" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="getSalesBetweenDates">
                                                Ventas por Fecha
                                            </span>
                                            <span wire:loading wire:target="getSalesBetweenDates">
                                                Consultando...
                                            </span>
                                        </button>
                                        <button wire:click.prevent="getDailySales"
                                            class="btn btn-outline-primary w-100 mt-3" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="getDailySales">
                                                Ventas del Día
                                            </span>
                                            <span wire:loading wire:target="getDailySales">
                                                Consultando...
                                            </span>

                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-sm-12 col-md-9">
                            <div class="card ">
                                <div class="card-header p-3">
                                    <div class="header-top">
                                        <h5 class="m-0">Información de las ventas<span
                                                class="f-14 f-w-500 ms-1 f-light"></span></h5>

                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row" id="cards">
                                        <div class="row">
                                            <div class="col-sm-12 col-md-4">
                                                <div class="card small-widget">
                                                    <div class="card-body primary">
                                                        <span class="f-light f-18">
                                                            <b>Total Banco</b>
                                                        </span>
                                                        <div class="d-flex align-items-end gap-1 text-info">
                                                            <h4>${{ round($totalDeposit, 2) }}</h4><span
                                                                class="font-primary f-12 f-w-500"></span>
                                                            <h4 class="text-dark">|</h4>
                                                            <h4 class="text-warning">
                                                                ${{ round($totalPaymentsDeposit, 2) }}</h4>
                                                            <span class="font-primary f-12 f-w-500"></span>
                                                        </div>
                                                        <div class="bg-gradient">
                                                            <i class="icofont icofont-coins"
                                                                style="font-size: 35px!important"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-12 col-md-4">
                                                <div class="card small-widget">
                                                    <div class="card-body primary">
                                                        <span class="f-light f-18">
                                                            <b>Total Nequi</b>
                                                        </span>
                                                        <div class="d-flex align-items-end gap-1 text-info">
                                                            <h4>${{ round($totalNequi, 2) }}</h4><span
                                                                class="font-primary f-12 f-w-500"></span>
                                                            <h4 class="text-dark">|</h4>
                                                            <h4 class="text-warning">
                                                                ${{ round($totalPaymentsNequi, 2) }}</h4>
                                                        </div>
                                                        <div class="bg-gradient">
                                                            <img class="b-r-20" src="../logo/nequi.webp" alt="wallet">
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-12 col-md-4">
                                                <div class="card small-widget">
                                                    <div class="card-body primary">
                                                        <span class="f-light f-18">
                                                            <b>Total Contado</b>
                                                        </span>
                                                        <div class="d-flex align-items-end gap-1 text-info">
                                                            <h4>${{ round($totalCash, 2) }}</h4><span
                                                                class="font-primary f-12 f-w-500"></span>
                                                            <h4 class="text-dark">|</h4>
                                                            <h4 class="text-warning">
                                                                ${{ round($totalPaymentsCash, 2) }}</h4>

                                                        </div>
                                                        <div class="bg-gradient">
                                                            <i class="icofont icofont-bill"
                                                                style="font-size: 35px!important"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-12 col-md-4">
                                                <div class="card small-widget">
                                                    <div class="card-body primary">
                                                        <span class="f-light f-18">
                                                            <b>Ventas a Crédito</b>
                                                        </span>
                                                        <div class="d-flex align-items-end gap-1 text-info">
                                                            <h4>${{ round($totalCreditSales, 2) }}</h4><span
                                                                class="font-primary f-12 f-w-500"></span>
                                                        </div>
                                                        <div class="bg-gradient">
                                                            <i class="icofont icofont-credit-card"
                                                                style="font-size: 35px!important"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-sm-12 col-md-4">
                                                <div class="card small-widget">
                                                    <div class="card-body primary">
                                                        <span class="f-light f-18">
                                                            <b class="text-info">Ventas Totales</b>
                                                        </span>
                                                        <div class="d-flex align-items-end gap-1 text-info">
                                                            <h4>${{ round($totalSales, 2) }}</h4><span
                                                                class="font-primary f-12 f-w-500"></span>
                                                        </div>
                                                        <div class="bg-gradient">
                                                            <i class="icofont icofont-money"
                                                                style="font-size: 35px!important"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-sm-12 col-md-4">
                                                <div class="card small-widget">
                                                    <div class="card-body primary">
                                                        <span class="f-light f-18">
                                                            <b class="text-warning">Creditos Pagados</b>
                                                        </span>
                                                        <div class="d-flex align-items-end gap-1 text-warning">
                                                            <h4>${{ round($totalPayments, 2) }}</h4><span
                                                                class="font-primary f-12 f-w-500"></span>
                                                        </div>
                                                        <div class="bg-gradient">
                                                            <i class="icofont icofont-money-bag"
                                                                style="font-size: 35px!important"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button title="Imprimir corte de caja" wire:click.prevent="printCC"
                                        class="btn btn-outline-dark btn-xs border-0 {{ $totalSales > 0 ? '' : 'd-none' }}">
                                        <i class="icofont icofont-printer fa-2x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

        </div>
    </div>
    <style>
        #cards .card {
            margin-bottom: 30px;
            border: solid !important;
            border: 1px !important;
            transition: all 0.3s ease;
            letter-spacing: 0.5px;
            border-radius: 15px;
            box-shadow: 0px 9px 20px rgba(46, 35, 94, 0.07);
        }
    </style>


    <script>
        document.onkeydown = function(e) {

            // f3
            if (e.keyCode == '113') {
                e.preventDefault()
                var input = document.getElementById('inputCustomer');
                var tomselect = input.tomselect
                tomselect.clear()
                tomselect.focus()
            }
        }

        document.addEventListener('livewire:init', () => {
            flatpickr("#dateFrom", {
                dateFormat: "Y/m/d",
                locale: "es",
                theme: "confetti",
                onChange: function(selectedDates, dateStr, instance) {
                    console.log(dateStr);
                    @this.set('dateFrom', dateStr)
                }
            })
            flatpickr("#dateTo", {
                dateFormat: "Y/m/d",
                locale: "es",
                theme: "confetti",
                onChange: function(selectedDates, dateStr, instance) {
                    @this.set('dateTo', dateStr)
                }
            })







            Livewire.on('show-modal-payment', event => {
                $('#modalPartialPay').modal('show')
            })

            Livewire.on('close-modal', event => {
                $('#modalPartialPay').modal('hide')
            })

            Livewire.on('show-payhistory', event => {
                $('#modalPayHistory').modal('show')
            })

        })
    </script>
</div>
