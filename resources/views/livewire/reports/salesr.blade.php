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
                        <span class="f-14"><b>Factura</b></span>
                        <div class="input-group">
                            <input wire:model="searchFactura" wire:keydown.enter.prevent="searchData" class="form-control" type="text" placeholder="Ej: 105 o F000105">
                            <span class="input-group-text list-light">
                                <i class="search-icon" data-feather="file-text"></i>
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
                        <button wire:click.prevent="searchData" class="btn btn-dark">
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

                                        // Sumar pagos posteriores (Abonos)
                                        foreach($sale->payments as $payment) {
                                            // Asignar a la moneda correspondiente
                                            $curr = $payment->currency; // Payment model uses 'currency' column
                                            
                                            // Fallback for legacy data if needed, or if currency code matches
                                            if(isset($paidPerCurrency[$curr])) {
                                                $paidPerCurrency[$curr] += $payment->amount;
                                            }
                                            
                                            // Add Discount to USD bucket (Value Settled)
                                            // Only if NOT surcharge (overdue), because surcharge is already included in payment amount (extra money paid).
                                            // Discount means we paid LESS cash, but settled MORE debt.
                                            if(isset($payment->discount_applied) && $payment->discount_applied > 0 && $payment->rule_type !== 'overdue') {
                                                if(isset($paidPerCurrency['USD'])) {
                                                    $paidPerCurrency['USD'] += $payment->discount_applied;
                                                }
                                            }

                                            // Sum totals for calculation
                                            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                                            $amountUSD = $payment->amount / $rate;
                                            
                                            // For Total Paid USD calculation (used for Credit calculation below)
                                            // If Surcharge: Paid $110. Principal $100.
                                            // Effective Principal Paid = $110 - $10 = $100.
                                            // If Discount: Paid $90. Principal $100.
                                            // Effective Principal Paid = $90 + $10 = $100.
                                            
                                            $adjustment = $payment->discount_applied ?? 0;
                                            if($payment->rule_type === 'overdue') {
                                                $totalPaidUSD += ($amountUSD - $adjustment);
                                            } else {
                                                $totalPaidUSD += ($amountUSD + $adjustment);
                                            }
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
                                    <tr class="text-center {{ $sale->deletion_requested_at ? 'table-warning' : '' }}">
                                        <td>
                                            {{ $sale->invoice_number ?? $sale->id }}
                                            @foreach ($sale->returns as $return)
                                                @php $isManual = $return->details->count() === 0; @endphp
                                                <a href="{{ route('pos.returns.generateCreditNotePdf', $return->id) }}" 
                                                   target="_blank" 
                                                   class="ms-1" 
                                                   title="{{ $isManual ? 'Nota de Crédito (Ajuste)' : 'Nota de Crédito (Devolución)' }} #{{ $return->id }}">
                                                    <i class="fas fa-file-invoice" style="color: {{ $isManual ? '#fd7e14' : '#ffc107' }};"></i>
                                                </a>
                                            @endforeach
                                        </td>
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
                                        <td>
                                            @if($sale->deletion_requested_at || $sale->status == 'returned')
                                                @if($sale->deletion_requested_at && $sale->status != 'returned')
                                                    <span class="badge badge-warning">Solicitud Borrado</span>
                                                @else
                                                    <span class="badge badge-danger">returned</span>
                                                @endif

                                                @if($sale->deletion_reason)
                                                    <div class="mt-1">
                                                        <small class="text-dark"><b>Motivo:</b> {{ $sale->deletion_reason }}</small>
                                                    </div>
                                                @endif
                                            @else
                                                <span
                                                    class="badge f-12 {{ $sale->status == 'paid' ? 'badge-success' : ($sale->status == 'return' ? 'badge-warning' : ($sale->status == 'pending' ? 'badge-warning' : 'badge-danger')) }} ">{{ $sale->status }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $sale->type }}</td>
                                        <td>{{ $sale->created_at }}</td>
                                        <td class="text-primary"></td>

                                        <td data-container="body" data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-html="true" data-bs-title="<b>Ver los detalles de la venta</b>">

                                            @if($sale->deletion_requested_at)
                                                {{-- PENDING APPROVAL STATE --}}
                                                @can('sales.approve_deletion')
                                                    <button onclick="ConfirmDelete({{ $sale->id }}, '{{ addslashes($sale->deletion_reason ?? '') }}')"
                                                        class="border-0 btn btn-outline-success btn-xs" title="Aprobar Eliminación">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button wire:click="RejectDeletion({{ $sale->id }})"
                                                        class="border-0 btn btn-outline-danger btn-xs" title="Rechazar Eliminación">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @else
                                                    <span class="badge badge-warning">Pendiente de Aprobación</span>
                                                @endcan
                                            @else
                                                {{-- NORMAL STATE --}}
                                                <button {{ $sale->status == 'returned' ? 'disabled' : '' }}
                                                    class="border-0 btn btn-outline-dark btn-xs"
                                                    onclick="ConfirmDelete({{ $sale->id }})" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif

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
        @livewire('sales.returns-component')
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
        .swal-content__input {
            border: 1px solid #dbdbdb;
            color: #333;
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
                    searchField: ['name', 'address', 'taxpayer_id'],
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
                            var doc = item.taxpayer_id ? ' - ' + escape(item.taxpayer_id) : '';
                            return `<div class="py-1 d-flex">
            <div>
                <div class="mb-0">
                    <span class="h5 text-info">
                        <b class="text-dark">${ escape(item.id) }
                    </span>
                    <span class="text-warning">| ${ escape(item.name.toUpperCase()) }${doc}</span>
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


        function ConfirmDelete(saleId, currentReason = '') {
            swal({
                title: currentReason ? 'Aprobar Eliminación' : 'Solicitar/Eliminar Venta',
                text: 'Ingresa el motivo de la eliminación para continuar:',
                content: {
                    element: "input",
                    attributes: {
                        placeholder: "Escribe la razón aquí...",
                        type: "text",
                        value: currentReason, // Pre-fill with existing reason if approving
                    },
                },
                icon: 'warning',
                buttons: {
                    cancel: "Cancelar",
                    confirm: {
                        text: currentReason ? "Confirmar y Eliminar" : "Enviar",
                        closeModal: true,
                    }
                },
                dangerMode: true,
            }).then((reason) => {
                if (reason === null) return; // Cancelled
                
                if (reason.trim() === "") {
                    swal("¡Error!", "Debes ingresar un motivo para proceder", "error");
                    return;
                }
                
                Livewire.dispatch('DestroySale', { saleId: saleId, reason: reason });
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
