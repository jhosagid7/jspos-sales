<div>
    <div wire:ignore.self class="modal fade" id="modalProcessOrder" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">Procesar Ordenes</h5>
                    <button class="py-0 btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if ($order_selected_id == null)
                        <div class="faq-form">
                            <input wire:model.defer="search" wire:keydown.enter.prevent="getOrdersWithDetails"
                                class="form-control form-control-lg" type="text"
                                placeholder="Ingresa el nombre del cliente, nombre de operador, monto de compra o número defolio"
                                id="inputprocessOrderSearch" style="background-color: beige">
                            <i class="search-icon" data-feather="user"></i>
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
                                        <th>Fecha</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @forelse ($orders as $order)
                                        <tr class="text-center">
                                            <td>{{ $order->order_number ?? $order->id }}</td>
                                            <td>{{ $order->customer->name }}</td>
                                            <td>${{ $order->total }}</td>
                                            <td>{{ $order->items }}</td>
                                            <td><span
                                                    class="badge f-12 {{ $order->status == 'paid' ? 'badge-light-success' : ($order->status == 'return' ? 'badge-light-warning' : ($order->status == 'pending' ? 'badge-light-warning' : 'badge-light-danger')) }}">{{ $order->status }}</span>
                                            </td>
                                            <td>{{ $order->created_at }}</td>
                                            <td class="text-primary"></td>

                                            <td data-container="body" data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-html="true" data-bs-title="<b>Ver los detalles de la orden</b>">
                                                <button class="btn btn-primary"
                                                    wire:click.prevent="loadOrderToCart({{ $order->id }})">Agregar al
                                                    carrito</button>
                                                @if ($order->status != 'deleted')
                                                    <button class="border-0 btn btn-outline-dark btn-xs"
                                                        onclick="DestroyOrder({{ $order->id }})">
                                                        <i class="icofont icofont-trash fa-2x"></i>
                                                    </button>
                                                @endif
                                                <button wire:click.prevent="getOrderDetailNote({{ $order->id }})"
                                                    class="border-0 btn btn-outline-dark btn-xs">
                                                    <i class="icofont icofont-edit-alt fa-2x"></i>
                                                </button>
                                                <button wire:click.prevent="getOrderDetail({{ $order->id }})"
                                                    class="border-0 btn btn-outline-dark btn-xs">
                                                    <i class="icofont icofont-list fa-2x"></i>
                                                </button>
                                                <a class="border-0 btn btn-outline-dark btn-xs link-offset-2 link-underline link-underline-opacity-0 {{ $order->status == 'returned' ? 'disabled' : '' }}"
                                                    href="{{ route('pos.orders.generatePdfOrderInvoice', $order->id) }}"
                                                    target="_blank"><i
                                                        class="text-danger icofont icofont-file-pdf fa-2x"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">Sin Ordenes</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="mt-2">
                                {{ $orders->links() }}
                                {{-- @if (!is_array($orders))
                                    {{ $orders->links() }}
                                @endif --}}
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
        @include('livewire.pos.partials.order-detail')
        @include('livewire.pos.partials.order-detail-note')
    </div>
    <script>
        document.addEventListener('livewire:init', function() {
            $('#modalProcessOrder').on('shown.bs.modal', function() {
                setTimeout(() => {
                    setFocus()
                }, 700)
            })

            Livewire.on('clear-search', event => {
                setFocus()
            })
        })

        function setFocus() {
            document.getElementById('inputprocessOrderSearch').value = ''
            document.getElementById('inputprocessOrderSearch').focus()
        }
        document.addEventListener('show-detail', event => {
            $('#modalOrderDetail').modal('show')
        })

        // document.addEventListener('close-process-order', event => {
        //     $('#modalProcessOrder').modal('hide')
        // })

        document.addEventListener('show-detail-note', event => {
            $('#modalOrderDetailNote').modal('show')
        })
        document.addEventListener('close-detail-note', event => {
            $('#modalOrderDetailNote').modal('hide')
        })
    </script>
</div>
