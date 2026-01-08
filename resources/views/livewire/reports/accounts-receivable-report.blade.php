<div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light">Reporte de Cuentas por Cobrar</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-3">
                            <div class="card">
                                <div class="card-body">

                                    @if ($customer != null)
                                        <span> {{ $customer['name'] }} <i
                                                class="fas fa-check"></i></span>
                                    @else
                                        <span class="f-14"><b>Cliente</b></span>
                                    @endif
                                    <div class="input-group" wire:ignore>
                                        <input class="form-control" type="text" id="inputCustomer" placeholder="F2">
                                        <span class="input-group-text list-light">
                                            <i class="search-icon" data-feather="user"></i>
                                        </span>
                                    </div>


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

                                    <div class="mt-3">
                                        <span class="f-14"><b>Estatus</b></span>
                                        <select wire:model.live='status' class="form-select">
                                            <option value="0">Todos</option>
                                            <option value="pending">Pendiente</option>
                                            <option value="paid">Pagado</option>
                                        </select>
                                    </div>

                                    <div class="mt-3">
                                        <span class="f-14"><b>Usuario</b></span>
                                        <select wire:model="user_id" class="form-select form-control-sm">
                                            <option value="0">Seleccionar</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}">
                                                    {{ $user->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mt-3">
                                        <span class="f-14"><b>Agrupar por</b></span>
                                        <select wire:model='groupBy' class="form-select">
                                            <option value="none">Ninguno</option>
                                            <option value="customer_id">Cliente</option>
                                            <option value="date">Fecha</option>
                                            <option value="seller_id">Vendedor</option>
                                            <option value="user_id">Usuario</option>
                                        </select>
                                    </div>

                                    <div class="mt-3">
                                        <span class="f-14"><b>Vendedor</b></span>
                                        <select wire:model="seller_id" class="form-select form-control-sm">
                                            <option value="0">Seleccionar</option>
                                            @foreach ($sellers as $seller)
                                                <option value="{{ $seller->id }}">
                                                    {{ $seller->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="mt-5">
                                        <button wire:click.prevent="$set('showReport', true)" class="btn btn-dark">
                                            Consultar
                                        </button>
                                        <button wire:click.prevent="generatePdf" class="btn btn-danger ms-2"
                                            title="Generar Reporte PDF">
                                            <i class="fas fa-file-pdf"></i> PDF
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-sm-12 col-md-9">
                            <div class="card {{ $totales == 0 && $dateFrom == null ? 'd-none' : '' }}">
                                <div class="p-3 card-header">
                                    <div class="header-top">
                                        <h5 class="m-0">Resultados<span class="f-14 f-w-500 ms-1 f-light"></span>
                                        </h5>
                                        <div class="card-header-right-icon">
                                            <span class="text-white badge badge-light-dark ms-1 f-14">Total
                                                por
                                                Cobrar:
                                                ${{ $totales }}</span>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-dashed">
                                            <thead>
                                                <tr class="text-center">
                                                    <th>Folio</th>
                                                    <th>Cliente</th>
                                                    <th>Total</th>
                                                    <th>Abonado</th>
                                                    <th>Saldo</th>
                                                    <th>Estatus</th>
                                                    <th>Fecha</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($sales as $sale)
                                                    @php
                                                        $totalPaidUSD = $sale->payments->sum(function($payment) {
                                                            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                                                            return $payment->amount / $rate;
                                                        });
                                                        $initialPaidUSD = $sale->paymentDetails->sum(function($detail) {
                                                            $rate = $detail->exchange_rate > 0 ? $detail->exchange_rate : 1;
                                                            return $detail->amount / $rate;
                                                        });
                                                        $totalAbonadoUSD = $totalPaidUSD + $initialPaidUSD;
                                                        $saldoUSD = max(0, $sale->total_usd - $totalAbonadoUSD);
                                                    @endphp
                                                    <tr class="text-center">
                                                        <td>{{ $sale->id }}</td>
                                                        <td class="text-capitalize">{{ $sale->customer->name }}</td>
                                                        <td style="background-color: rgb(210, 243, 252)">
                                                            ${{ number_format($sale->total_usd, 2) }}
                                                        </td>
                                                        <td>${{ number_format($totalAbonadoUSD, 2) }}</td>
                                                        <td style="background-color: beige">
                                                            ${{ number_format($saldoUSD, 2) }}
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="badge f-12 {{ ($sale->status == 'paid' ? 'badge-success' : $sale->status == 'return') ? 'badge-warning' : 'badge-danger' }} ">{{ $sale->status }}</span>

                                                        </td>
                                                        <td>{{ $sale->created_at }}</td>


                                                        <td>
                                                            <button
                                                                wire:click.prevent="historyPayments({{ $sale->id }})"
                                                                class="border-0 btn btn-outline-dark btn-xs">
                                                                <i class="fas fa-list"></i>
                                                            </button>
                                                            <button
                                                                wire:click.prevent="initPayment({{ $sale->id }}, '{{ $sale->customer->name }}')"
                                                                class="border-0 btn btn-outline-dark btn-xs">
                                                                <i class="fas fa-hand-holding-usd"></i>
                                                            </button>

                                                        </td>

                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="8" class="text-center">Sin ventas</td>
                                                    </tr>
                                                @endforelse

                                            </tbody>
                                        </table>
                                        <div class="mt-3">
                                            @if (!is_array($sales))
                                                {{ $sales->links() }}
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>


                </div>
            </div>

        </div>
    </div>
    <livewire:common.payment-component />
    @include('livewire.payments.historypays')
    <style>
        .ts-dropdown {
            z-index: 1000000 !important;
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





            if (document.querySelector('#inputCustomer')) {
                new TomSelect('#inputCustomer', {
                    maxItems: 1,
                    valueField: 'id',
                    labelField: 'name',
                    searchField: ['name', 'address'],
                    load: function(query, callback) {
                        var url = "{{ route('data.customers') }}" + '?q=' + encodeURIComponent(
                            query)
                        fetch(url)
                            .then(response => response.json())
                            .then(json => {
                                callback(json);
                                //console.log(json);
                            }).catch(() => {
                                callback();
                            });
                    },
                    onChange: function(value) {
                        var customer = this.options[value]
                        // console.log( value)
                        Livewire.dispatch('account_customer', {
                            customer: customer
                        })

                    },
                    render: {
                        option: function(item, escape) {
                            return `<div class="py-1 d-flex">
            <div>
                <div class="mb-0">
                    <span class="h5 text-info">
                        <b class="text-dark">${ escape(item.id) }
                    </span>
                    <span class="text-warning">|${ escape(item.name.toUpperCase()) }</span>
                </div>
            </div>
        </div>`;
                        },
                    },
                });
            }


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
