<div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light">Reporte de Cuentas por Pagar</h5>
                </div>

                <div class="card-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-3">
                            <div class="card">
                                <div class="card-body">

                                    @if ($supplier != null)
                                        <span> {{ $supplier['name'] }} <i
                                                class="fas fa-check"></i></span>
                                    @else
                                        <span class="f-14"><b>Proveedor</b></span>
                                    @endif
                                    <div class="input-group" wire:ignore>
                                        <input class="form-control" type="text" id="inputSupplier" placeholder="F2">
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
                                        <select wire:model.live='status' class="form-control">
                                            <option value="0">Todos</option>
                                            <option value="pending">Pendiente</option>
                                            <option value="paid">Pagado</option>
                                        </select>
                                    </div>

                                    <div class="mt-5">
                                        <button wire:click.prevent="$set('showReport', true)" class="btn btn-dark"
                                            {{ $supplier == null && ($dateFrom == null && $dateTo == null) ? 'disabled' : '' }}>
                                            Consultar
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
                                                Pagar:
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
                                                    <th>Proveedor</th>
                                                    <th>Total</th>
                                                    <th>Abonado</th>
                                                    <th>Saldo</th>
                                                    <th>Estatus</th>
                                                    <th>Fecha</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($purchases as $purchase)
                                                    <tr class="text-center">
                                                        <td>{{ $purchase->id }}</td>
                                                        <td class="text-capitalize">{{ $purchase->supplier->name }}</td>
                                                        <td style="background-color: rgb(210, 243, 252)">
                                                            ${{ $purchase->total }}
                                                        </td>
                                                        <td>${{ $purchase->payables->sum('amount') }}</td>
                                                        <td style="background-color: beige">
                                                            ${{ round($purchase->total - $purchase->payables->sum('amount'), 2) }}
                                                        </td>
                                                        <td>
                                                            <span
                                                                class="badge f-12 {{ ($purchase->status == 'paid' ? 'badge-success' : $purchase->status == 'return') ? 'badge-warning' : 'badge-danger' }} ">{{ $purchase->status }}</span>

                                                        </td>
                                                        <td>{{ $purchase->created_at }}</td>


                                                        <td>
                                                            <button
                                                                wire:click.prevent="historyPayables({{ $purchase->id }})"
                                                                class="border-0 btn btn-outline-dark btn-xs">
                                                                <i class="fas fa-list"></i>
                                                            </button>
                                                            <button
                                                                wire:click.prevent="initPayable({{ $purchase->id }}, '{{ $purchase->supplier->name }}')"
                                                                class="border-0 btn btn-outline-dark btn-xs">
                                                                <i class="fas fa-hand-holding-usd"></i>
                                                            </button>

                                                        </td>

                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="8" class="text-center">Sin compras</td>
                                                    </tr>
                                                @endforelse

                                            </tbody>
                                        </table>
                                        <div class="mt-3">
                                            @if (!is_array($purchases))
                                                {{ $purchases->links() }}
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
    @include('livewire.payables.historypayables')
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
                var input = document.getElementById('inputSupplier');
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





            if (document.querySelector('#inputSupplier')) {
                new TomSelect('#inputSupplier', {
                    maxItems: 1,
                    valueField: 'id',
                    labelField: 'name',
                    searchField: ['name', 'address'],
                    load: function(query, callback) {
                        var url = "{{ route('data.suppliers') }}" + '?q=' + encodeURIComponent(
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
                        var supplier = this.options[value]
                        // console.log( value)
                        Livewire.dispatch('account_supplier', {
                            supplier: supplier
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


            Livewire.on('show-modal-payable', event => {
                $('#modalPartialPayable').modal('show')
            })

            Livewire.on('close-modal', event => {
                $('#modalPartialPayable').modal('hide')
            })

            Livewire.on('show-payablehistory', event => {
                $('#modalPayableHistory').modal('show')
            })

        })
    </script>
</div>
