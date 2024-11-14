<div>
    <div class="row">
        <div class="col-sm-12 col-md-3 ">
            <div class="card">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light">Opciones</h5>
                </div>

                <div class="card-body">
                    <span class="f-14"><b>Usuario</b></span>
                    <select wire:model="user_id" class="form-select form-control-sm">
                        <option value="0">Seleccionar</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>


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
                        <select wire:model='type' class="form-select">
                            <option value="0">Todas</option>
                            <option value="cash">Contado</option>
                            <option value="credit">Crédito</option>
                        </select>
                    </div>

                    <div class="mt-3">
                        <button wire:click.prevent="$set('showReport', true)" class="btn btn-dark"
                            {{ $user_id == null && ($dateFrom == null && $dateTo == null) ? 'disabled' : '' }}>
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
                                    <th>Total</th>
                                    <th>Articulos</th>
                                    <th>Estatus</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th></th>

                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sales as $sale)
                                    <tr class="text-center">
                                        <td>{{ $sale->id }}</td>
                                        <td>{{ $sale->customer->name }}</td>
                                        <td>${{ $sale->total }}</td>
                                        <td>{{ $sale->items }}</td>
                                        <td><span
                                                class="badge f-12 {{ $sale->status == 'paid' ? 'badge-light-success' : ($sale->status == 'return' ? 'badge-light-warning' : ($sale->status == 'pending' ? 'badge-light-warning' : 'badge-light-danger')) }} ">{{ $sale->status }}</span>
                                        </td>
                                        <td>{{ $sale->type }}</td>
                                        <td>{{ $sale->created_at }}</td>
                                        <td class="text-primary"></td>

                                        <td data-container="body" data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-html="true" data-bs-title="<b>Ver los detalles de la venta</b>">


                                            <button {{ $sale->status == 'returned' ? 'disabled' : '' }}
                                                class="border-0 btn btn-outline-dark btn-xs"
                                                onclick="Confirm({{ $sale->id }})">
                                                <i class="icofont icofont-trash fa-2x"></i>
                                            </button>

                                            <button
                                                {{ $sale->status == 'returned' || $sale->status == 'paid' ? 'disabled' : '' }}
                                                wire:click.prevent="getSaleDetailNote({{ $sale->id }})"
                                                class="border-0 btn btn-outline-dark btn-xs">
                                                <i class="icofont icofont-edit-alt fa-2x"></i>
                                            </button>



                                            <button wire:click.prevent="getSaleDetail({{ $sale->id }})"
                                                class="border-0 btn btn-outline-dark btn-xs">
                                                <i class="icofont icofont-list fa-2x"></i>
                                            </button>

                                            <a class="border-0 btn btn-outline-dark btn-xs link-offset-2 link-underline link-underline-opacity-0 {{ $sale->status == 'returned' ? 'disabled' : '' }}"
                                                href="{{ route('pos.sales.generatePdfInvoice', $sale->id) }}"
                                                target="_blank"><i
                                                    class="text-danger icofont icofont-file-pdf fa-2x"></i>
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
    @push('my-styles')
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
        </style>
    @endpush


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
</div>
