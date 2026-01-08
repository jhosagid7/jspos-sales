<div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light">Relación de Pagos</h5>
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
                                        <span class="f-14"><b>Usuario</b></span>
                                        <select wire:model="user_id" class="form-control form-control-sm">
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
                                        <select wire:model='groupBy' class="form-control">
                                            <option value="none">Ninguno</option>
                                            <option value="customer_id">Cliente</option>
                                            <option value="date">Fecha</option>
                                            <option value="seller_id">Vendedor</option>
                                            <option value="user_id">Usuario</option>
                                        </select>
                                    </div>

                                    <div class="mt-3">
                                        <span class="f-14"><b>Vendedor</b></span>
                                        <select wire:model="seller_id" class="form-control form-control-sm">
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
                                            <i class="icofont icofont-file-pdf"></i> PDF
                                        </button>
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="col-sm-12 col-md-9">
                            <div class="card {{ !$showReport ? 'd-none' : '' }}">
                                <div class="p-3 card-header">
                                    <div class="header-top">
                                        <h5 class="m-0">Resultados</h5>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table table-dashed">
                                            <thead>
                                                <tr class="text-center">
                                                    <th>Folio</th>
                                                    <th>Cliente</th>
                                                    <th>Total (USD)</th>
                                                    <th>Pagado (USD)</th>
                                                    <th>Saldo (USD)</th>
                                                    <th>Estatus</th>
                                                    <th>Fecha</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($sales as $sale)
                                                    <tr class="text-center">
                                                        <td>{{ $sale->id }}</td>
                                                        <td class="text-capitalize">{{ $sale->customer->name }}</td>
                                                        <td>
                                                            @php
                                                                $totalUSD = $sale->total_usd;
                                                                if (!$totalUSD || $totalUSD == 0) {
                                                                    $exchangeRate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                                                                    $totalUSD = $sale->total / $exchangeRate;
                                                                }
                                                            @endphp
                                                            ${{ number_format($totalUSD, 2) }}
                                                        </td>
                                                        <td>
                                                            @php
                                                                $paidUSD = $sale->payments->sum(function($p) {
                                                                    $r = $p->exchange_rate > 0 ? $p->exchange_rate : 1;
                                                                    return $p->amount / $r;
                                                                });
                                                            @endphp
                                                            ${{ number_format($paidUSD, 2) }}
                                                        </td>
                                                        <td>
                                                            ${{ number_format($totalUSD - $paidUSD, 2) }}
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="badge f-12 {{ ($sale->status == 'paid' ? 'badge-light-success' : $sale->status == 'return') ? 'badge-light-warning' : 'badge-light-danger' }} ">{{ $sale->status }}</span>

                                                        </td>
                                                        <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="7" class="text-center">Sin resultados</td>
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
    
    <style>
        .ts-dropdown {
            z-index: 1000000 !important;
        }
        .rest {
            display: block !important;
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
            
            window.addEventListener('update-header', event => {
                // console.log(event.detail) 
                $('.rfx').text(event.detail.map)
                $('.breadcrumb-item.active').text(event.detail.child)
                $('.rest').text(event.detail.rest)
            })

            flatpickr("#dateFrom", {
                dateFormat: "Y/m/d",
                locale: "es",
                theme: "confetti",
                onChange: function(selectedDates, dateStr, instance) {
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
                            }).catch(() => {
                                callback();
                            });
                    },
                    onChange: function(value) {
                        var customer = this.options[value]
                        Livewire.dispatch('relationship_customer', {
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

        })
    </script>
</div>
