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
                        <span class="f-14"><b>Tipo</b></span>
                        <select wire:model='type' class="form-control">
                            <option value="0">Todas</option>
                            <option value="cash">Contado</option>
                            <option value="credit">Crédito</option>
                        </select>
                    </div>

                    <div class="mt-3">
                        <button wire:click.prevent="$set('showReport', true)" class="btn btn-dark">
                            Consultar
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
                                    <th></th>

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
                                            // Asignar a la moneda correspondiente
                                            if(isset($paidPerCurrency[$payment->currency_code])) {
                                                $paidPerCurrency[$payment->currency_code] += $payment->amount;
                                            }
                                            
                                            // Calcular equivalente en USD para el total pagado
                                            // Si la moneda del pago es la principal, usar primary_exchange_rate
                                            // Si no, usar exchange_rate del pago (asumiendo que exchange_rate es valor vs USD)
                                            // O mejor: convertir todo a USD.
                                            // Si el pago tiene exchange_rate, amount / exchange_rate = USD
                                            // Si el pago es en USD, exchange_rate es 1.
                                            
                                            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                                            $totalPaidUSD += ($payment->amount / $rate);
                                        }
                                        
                                        // Si es venta de contado sin pagos registrados (legacy o simple cash), 
                                        // asumir que se pagó todo en la moneda principal o según 'cash' field?
                                        // El modelo Sale tiene 'cash' que es el monto pagado.
                                        // Si no hay pagos en la tabla payments, usar $sale->cash y $sale->primary_currency_code
                                        
                                        if($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                                            $code = $sale->primary_currency_code ?? 'VED'; // Fallback
                                            if(isset($paidPerCurrency[$code])) {
                                                $paidPerCurrency[$code] += $sale->cash;
                                            }
                                            // Convertir cash a USD usando primary_exchange_rate
                                            $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                                            $totalPaidUSD += ($sale->cash / $rate);
                                        }

                                        // Calcular Crédito Restante en USD
                                        // Si está pagada, es 0. Si no, Total USD - Total Pagado USD
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
                                                class="badge f-12 {{ $sale->status == 'paid' ? 'badge-success' : ($sale->status == 'return' ? 'badge-warning' : ($sale->status == 'pending' ? 'badge-warning' : 'badge-danger')) }} ">{{ $sale->status }}</span>
                                        </td>
                                        <td>{{ $sale->type }}</td>
                                        <td>{{ $sale->created_at }}</td>
                                        <td class="text-primary"></td>

                                        <td data-container="body" data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-html="true" data-bs-title="<b>Ver los detalles de la venta</b>">


                                            <button {{ $sale->status == 'returned' ? 'disabled' : '' }}
                                                class="border-0 btn btn-outline-dark btn-xs"
                                                onclick="Confirm({{ $sale->id }})" title="Eliminar">
                                                <i class="fas fa-trash"></i>
                                            </button>

                                            <button
                                                {{ $sale->status == 'returned' || $sale->status == 'paid' ? 'disabled' : '' }}
                                                wire:click.prevent="getSaleDetailNote({{ $sale->id }})"
                                                class="border-0 btn btn-outline-dark btn-xs" title="Editar Nota">
                                                <i class="fas fa-edit"></i>
                                            </button>



                                            <button wire:click.prevent="getSaleDetail({{ $sale->id }})"
                                                class="border-0 btn btn-outline-dark btn-xs" title="Ver Detalles">
                                                <i class="fas fa-list"></i>
                                            </button>

                                            @if($sale->driver_id)
                                                <a class="border-0 btn btn-outline-dark btn-xs"
                                                    href="{{ route('delivery.tracking', $sale->id) }}"
                                                    target="_blank" title="Rastreo">
                                                    <i class="fas fa-map-marker-alt text-info"></i>
                                                </a>
                                            @endif

                                            <a class="border-0 btn btn-outline-dark btn-xs link-offset-2 link-underline link-underline-opacity-0 {{ $sale->status == 'returned' ? 'disabled' : '' }}"
                                                href="{{ route('pos.sales.generatePdfInvoice', $sale->id) }}"
                                                target="_blank" title="PDF"><i
                                                    class="text-danger fas fa-file-pdf"></i>
                                            </a>

                                        </td>

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
        @include('livewire.reports.sale-detail')
        @include('livewire.reports.sale-detail-note')
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

        })

        document.addEventListener('show-detail', event => {
            $('#modalSaleDetail').modal('show')
        })
        document.addEventListener('show-detail-note', event => {
            $('#modalSaleDetailNote').modal('show')
        })
        document.addEventListener('close-detail-note', event => {
            $('#modalSaleDetailNote').modal('hide')
        })
    </script>
    <script>
        document.addEventListener('livewire:init', () => {

            Livewire.on('init-new', (event) => {
                document.getElementById('inputFocus').focus()
            })



        })


        function Confirm(rowId) {
            // Genera un número aleatorio de 3 cifras
            const randomNum = Math.floor(100 + Math.random() * 900); // Genera un número entre 100 y 999
            const confirmationSum = rowId + randomNum; // Suma el número de factura y el número aleatorio
            const confirCode = {{ session('settings.confirmation_code') }}

            // Muestra el número que el operador debe proporcionar

            swal({
                title: `Número de Confirmación\n\n`,
                text: `El número que debes proporcionar al administrador es:\n\n` + `${confirmationSum}\n\n` +
                    `Por favor, ingresa el código de confirmación para eliminar la venta:`,
                content: {
                    element: "input",
                    attributes: {
                        placeholder: "Código de confirmación",
                        type: "text",
                    },
                },
                icon: "warning",
                buttons: true,
                dangerMode: true,
                buttons: {
                    cancel: "Cancelar",
                    confirm: {
                        text: "Aceptar",
                        closeModal: false // No cerrar el modal automáticamente
                    }
                },
            }).then((value) => {
                if (value === null) {
                    // El usuario canceló
                    return;
                }

                // Calcula el código de confirmación
                const today = new Date();
                const day = today.getDate();
                const month = today.getMonth() + 1; // Los meses son 0-indexed
                const confirmationCode = confirmationSum + day + month + confirCode;

                // Verifica el código de confirmación
                if (parseInt(value) === confirmationCode) {
                    // Si el código es correcto, procede a eliminar la venta
                    Livewire.dispatch('DestroySale', {
                        saleId: rowId
                    });
                    swal("Venta eliminada exitosamente!", {
                        icon: "success",
                    });
                } else {
                    // Si el código es incorrecto, muestra un mensaje de error
                    swal("Código incorrecto. Intenta de nuevo.", {
                        icon: "error",
                    });
                }
            });
        }
    </script>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('update-header', (data) => {
                // Actualizar elementos del breadcrumb
                // data.map -> .rfx (Total Costo)
                // data.child -> .active (Total Venta)
                // data.rest -> .rest (Ganancia)
                
                const rfx = document.querySelector('.breadcrumb-item.rfx');
                // El elemento active puede ser ambiguo, mejor buscar por posición o contexto
                // En breadcrumb.blade.php: icon, rfx, active, rest
                const breadcrumbItems = document.querySelectorAll('.breadcrumb .breadcrumb-item');
                
                if (breadcrumbItems.length >= 4) {
                    // index 1: rfx
                    // index 2: active
                    // index 3: rest
                    if (data.map) breadcrumbItems[1].innerText = data.map;
                    if (data.child) breadcrumbItems[2].innerText = data.child;
                    if (data.rest) breadcrumbItems[3].innerText = data.rest;
                } else {
                    // Fallback a selectores de clase si la estructura cambia
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
