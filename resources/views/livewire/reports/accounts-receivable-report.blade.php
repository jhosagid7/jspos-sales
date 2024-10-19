<div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header bg-dark p-1">
                    <h5 class="txt-light text-center">Reporte de Cuentas por Cobrar</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-3">
                            <div class="card">
                                <div class="card-body">

                                    @if ($customer != null)
                                        <span> {{ $customer['name'] }} <i
                                                class="icofont icofont-verification-check"></i></span>
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

                                    <div class="mt-5">
                                        <button wire:click.prevent="$set('showReport', true)" class="btn btn-dark"
                                            {{ $customer == null && ($dateFrom == null && $dateTo == null) ? 'disabled' : '' }}>
                                            Consultar
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-sm-12 col-md-9">
                            <div class="card {{ $totales == 0 && $dateFrom == null ? 'd-none' : '' }}">
                                <div class="card-header p-3">
                                    <div class="header-top">
                                        <h5 class="m-0">Resultados<span class="f-14 f-w-500 ms-1 f-light"></span>
                                        </h5>
                                        <div class="card-header-right-icon">
                                            <span class="badge badge-light-dark  ms-1 f-14 text-white">Total
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
                                                    <tr class="text-center">
                                                        <td>{{ $sale->id }}</td>
                                                        <td class="text-capitalize">{{ $sale->customer->name }}</td>
                                                        <td style="background-color: rgb(210, 243, 252)">
                                                            ${{ $sale->total }}
                                                        </td>
                                                        <td>${{ $sale->payments->sum('amount') }}</td>
                                                        <td style="background-color: beige">
                                                            ${{ round($sale->total - $sale->payments->sum('amount'), 2) }}
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="badge f-12 {{ $sale->status == 'paid' ? 'badge-light-success' : 'badge-light-danger' }} ">{{ $sale->status }}</span>

                                                        </td>
                                                        <td>{{ $sale->created_at }}</td>


                                                        <td>
                                                            <button
                                                                wire:click.prevent="historyPayments({{ $sale->id }})"
                                                                class="btn btn-outline-dark btn-xs border-0">
                                                                <i class="icofont icofont-list fa-2x"></i>
                                                            </button>
                                                            <button
                                                                wire:click.prevent="initPayment({{ $sale->id }}, '{{ $sale->customer->name }}')"
                                                                class="btn btn-outline-dark btn-xs border-0">
                                                                <i class="icofont icofont-cur-dollar-plus fa-2x"></i>
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
    @include('livewire.reports.partialpay')
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
                        Livewire.dispatch('sale_customer', {
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
