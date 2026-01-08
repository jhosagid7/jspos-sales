<div>
    <div class="row">
        <div class="col-sm-12 col-md-3 ">
            <div class="card">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light">Opciones</h5>
                </div>

                <div class="card-body">
                    <div class="mt-3">
                        @if ($customer != null)
                            <span> {{ $customer['name'] }} <i class="icofont icofont-verification-check"></i></span>
                        @else
                            <span class="f-14"><b>Cliente</b></span>
                        @endif
                        <div class="input-group" wire:ignore>
                            <input class="form-control" type="text" id="inputCustomer" placeholder="F2">
                            <span class="input-group-text list-light">
                                <i class="search-icon" data-feather="user"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <span class="f-14"><b>Folio / Factura</b></span>
                        <div class="input-group">
                            <input wire:model="searchFolio" class="form-control" type="text" placeholder="Buscar por Folio">
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
                        <span class="f-14"><b>Fecha desde</b></span>
                        <div class="input-group datepicker">
                            <input class="form-control flatpickr-input active" id="dateFrom" type="text"
                                autocomplete="off">
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="f-14"><b>Hasta</b></span>
                        <div class="input-group datepicker">
                            <input class="form-control flatpickr-input active" id="dateTo" type="text"
                                autocomplete="off">
                        </div>
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
                        <span class="f-14"><b>Tipo</b></span>
                        <select wire:model='type' class="form-control">
                            <option value="0">Todas</option>
                            <option value="cash">Contado</option>
                            <option value="credit">Crédito</option>
                        </select>
                    </div>

                    <div class="mt-4">
                        <h6 class="txt-light">Configuración del Reporte</h6>
                        <hr class="mt-1 mb-2">
                        
                        <span class="f-14"><b>Formato</b></span>
                        <div class="mt-2">
                            <div class="custom-control custom-radio">
                                <input class="custom-control-input" type="radio" id="formatDetailed" name="reportFormat" value="detailed" wire:model="reportFormat">
                                <label for="formatDetailed" class="custom-control-label">Detallado (Bancos)</label>
                            </div>
                            <div class="custom-control custom-radio">
                                <input class="custom-control-input" type="radio" id="formatSummarized" name="reportFormat" value="summarized" wire:model="reportFormat">
                                <label for="formatSummarized" class="custom-control-label">Resumido (Moneda)</label>
                            </div>
                        </div>

                        <div class="mt-3">
                            <div class="custom-control custom-checkbox">
                                <input class="custom-control-input" type="checkbox" id="includeDetails" wire:model="includeDetails">
                                <label for="includeDetails" class="custom-control-label">Incluir Detalles (Referencias)</label>
                            </div>
                        </div>
                    </div>

                    <div class="mt-3">
                        <button wire:click.prevent="$set('showReport', true)" class="btn btn-dark">
                            Consultar
                        </button>
                        <button wire:click.prevent="generatePdf" class="btn btn-danger"
                            {{ count($sales) < 1 ? 'disabled' : '' }}>
                            Generar PDF
                        </button>
                    </div>


                </div>
            </div>

        </div>



        <div class="col-sm-12 col-md-9">
            <div class="card card-absolute">
                <div class="card-header bg-dark">
                    <h5 class="txt-light">Resultados de la consulta</h5>
                </div>

                <div class="card-body">
                    <div class="row note-labels">
                        <div class="col-sm-12 col-md-5"></div>
                        <div class="col-sm-12 col-md-4"></div>
                        <div class="col-sm-12 col-md-3 text-end">
                            <span class="badge badge-light-success f-18" {{ $totales == 0 ? 'hidden' : '' }}>Total
                                Ventas:
                                ${{ round($totales, 2) }}</span>
                        </div>
                    </div>
                    <div class="mt-3 table-responsive">
                        <table class="table table-responsive-md table-hover" id="tblSalesRpt">
                            <thead class="thead-primary">
                                <tr class="text-center">
                                    <th>Folio</th>
                                    <th>Cliente</th>
                                    <th>Total Neto (USD)</th>
                                    @foreach($currencies as $currency)
                                        <th>Pagado {{ $currency->code }}</th>
                                    @endforeach
                                    <th>Crédito (USD)</th>
                                    <th>Articulos</th>
                                    <th>Estatus</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sales as $sale)
                                    @php
                                        // Calcular montos pagados por moneda
                                        $paidPerCurrency = [];
                                        $totalPaidUSD = 0;
                                        
                                        foreach($currencies as $currency) {
                                            $paidPerCurrency[$currency->code] = 0;
                                        }

                                        // Sumar pagos
                                        foreach($sale->paymentDetails as $payment) {
                                            if(isset($paidPerCurrency[$payment->currency_code])) {
                                                $paidPerCurrency[$payment->currency_code] += $payment->amount;
                                            }
                                            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                                            $totalPaidUSD += ($payment->amount / $rate);
                                        }
                                        
                                        if($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                                            $code = $sale->primary_currency_code ?? 'VED'; // Fallback
                                            if(isset($paidPerCurrency[$code])) {
                                                $paidPerCurrency[$code] += $sale->cash;
                                            }
                                            $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                                            $totalPaidUSD += ($sale->cash / $rate);
                                        }

                                        $creditUSD = 0;
                                        if($sale->status != 'paid' && $sale->status != 'returned') {
                                            $creditUSD = max(0, $sale->total_usd - $totalPaidUSD);
                                        }
                                    @endphp
                                    <tr class="text-center">
                                        <td>{{ $sale->invoice_number ?? $sale->id }}</td>
                                        <td>{{ $sale->customer->name }}</td>
                                        <td>${{ number_format($sale->total_usd, 2) }}</td>
                                        
                                        @foreach($currencies as $currency)
                                            <td>
                                                @if($paidPerCurrency[$currency->code] > 0)
                                                    {{ number_format($paidPerCurrency[$currency->code], 2) }}
                                                @else
                                                    -
                                                @endif
                                            </td>
                                        @endforeach
                                        
                                        <td>
                                            @if($creditUSD > 0.01)
                                                <span class="text-danger">${{ number_format($creditUSD, 2) }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        
                                        <td>{{ $sale->items }}</td>
                                        <td><span
                                                class="badge f-12 {{ $sale->status == 'paid' ? 'badge-light-success' : ($sale->status == 'return' ? 'badge-light-warning' : ($sale->status == 'pending' ? 'badge-light-warning' : 'badge-light-danger')) }} ">{{ $sale->status }}</span>
                                        </td>
                                        <td>{{ $sale->type }}</td>
                                        <td>{{ $sale->created_at }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center">Sin ventas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-2">
                            @if (!is_array($sales))
                                {{ $sales->links() }}
                            @endif
                        </div>
                    </div>



                </div>
                <div class="p-1 card-footer d-flex justify-content-between">

                </div>
            </div>
        </div>
    </div>
    
    <style>
        .swal-text {
            background-color: #FEFAE3;
            padding: 17px;
            border: 1px solid #F0E1A1;
            display: block;
            margin: 22px;
            text-align: center;
            color: #61534e;
        }
        .rest {
            display: block !important;
        }
    </style>

    <script>
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
                            }).catch(() => {
                                callback();
                            });
                    },
                    onChange: function(value) {
                        var customer = this.options[value]
                        Livewire.dispatch('daily_sale_customer', {
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

            Livewire.on('update-header', (data) => {
                const rfx = document.querySelector('.breadcrumb-item.rfx');
                const breadcrumbItems = document.querySelectorAll('.breadcrumb .breadcrumb-item');
                
                if (breadcrumbItems.length >= 4) {
                    if (data.map) breadcrumbItems[1].innerText = data.map;
                    if (data.child) breadcrumbItems[2].innerText = data.child;
                    if (data.rest) breadcrumbItems[3].innerText = data.rest;
                } else {
                    const active = document.querySelector('.breadcrumb-item.active');
                    const rest = document.querySelector('.breadcrumb-item.rest');
                    if (rfx && data.map) rfx.innerText = data.map;
                    if (active && data.child) active.innerText = data.child;
                    if (rest && data.rest) rest.innerText = data.rest;
                }
            })

        })
    </script>
</div>
