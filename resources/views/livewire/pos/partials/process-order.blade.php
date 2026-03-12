<div>
    <div wire:ignore.self class="modal fade" id="modalProcessOrder" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">Procesar Ordenes</h5>
                    <button class="py-0 btn-close" type="button" data-dismiss="modal" aria-label="Close" onclick="$('#modalProcessOrder').modal('hide')"></button>
                </div>
                <div class="modal-body">
                    @if ($order_selected_id == null)
                        <div class="faq-form row g-2">
                            <div class="col-md-8">
                                <input wire:model.defer="search" wire:keydown.enter.prevent="getOrdersWithDetails"
                                    class="form-control form-control-lg" type="text"
                                    placeholder="Buscar por cliente, monto, folio o vendedor..."
                                    id="inputprocessOrderSearch" style="background-color: beige">
                                <i class="search-icon" data-feather="user"></i>
                            </div>
                            <div class="col-md-4">
                                <select wire:model.live="searchSeller" class="form-control form-control-lg">
                                    <option value="">Filtrar por Vendedor (Todos)</option>
                                    @foreach($sellers as $seller)
                                        <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mt-3 table-responsive">
                            <table class="table table-responsive-md table-hover table-mobile-details" id="tblSalesRpt">
                                <thead class="thead-primary">
                                    <tr class="text-center">
                                        <th>Folio</th>
                                        <th>Cliente</th>
                                        <th>Vendedor</th>
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
                                            <td data-label="Folio">{{ $order->order_number ?? $order->id }}</td>
                                            <td data-label="Cliente">{{ $order->customer->name }}</td>
                                            <td data-label="Vendedor">
                                                @if($order->user)
                                                    <span class="badge" 
                                                          style="background-color: {{ $order->user->color ?? '#eee' }}; color: #333; font-weight: 600; border: 1px solid #ccc;">
                                                        {{ $order->user->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                            <td data-label="Total">${{ number_format($order->total, 2) }}</td>
                                            <td data-label="Articulos">{{ $order->items }}</td>
                                            <td data-label="Estatus"><span
                                                    class="badge f-12 {{ $order->status == 'paid' ? 'badge-light-success' : ($order->status == 'return' ? 'badge-light-warning' : ($order->status == 'pending' ? 'badge-light-warning' : 'badge-light-danger')) }}">{{ $order->status }}</span>
                                            </td>
                                            <td data-label="Fecha">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                            <td class="text-primary"></td>

                                            <td data-label="Acciones" data-container="body" data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-html="true" data-bs-title="<b>Ver los detalles de la orden</b>">
                                                @can('orders.add_to_cart')
                                                <button class="btn btn-primary"
                                                    wire:click.prevent="loadOrderToCart({{ $order->id }})">Agregar al
                                                    carrito</button>
                                                @endcan
                                                @if ($order->status != 'deleted')
                                                    @can('orders.delete')
                                                    <button class="border-0 btn btn-outline-dark btn-xs"
                                                        onclick="DestroyOrder({{ $order->id }})">
                                                        <i class="icofont icofont-trash fa-2x"></i>
                                                    </button>
                                                    @endcan
                                                @endif
                                                @can('orders.edit')
                                                <button wire:click.prevent="getOrderDetailNote({{ $order->id }})"
                                                    class="border-0 btn btn-outline-dark btn-xs">
                                                    <i class="icofont icofont-edit-alt fa-2x"></i>
                                                </button>
                                                @endcan
                                                @can('orders.details')
                                                <button wire:click.prevent="getOrderDetail({{ $order->id }})"
                                                    class="border-0 btn btn-outline-dark btn-xs">
                                                    <i class="icofont icofont-list fa-2x"></i>
                                                </button>
                                                @endcan
                                                @can('orders.pdf')
                                                <a class="border-0 btn btn-outline-dark btn-xs link-offset-2 link-underline link-underline-opacity-0 {{ $order->status == 'returned' ? 'disabled' : '' }}"
                                                    href="{{ route('pos.orders.generatePdfOrderInvoice', $order->id) }}"
                                                    target="_blank"><i
                                                        class="text-danger icofont icofont-file-pdf fa-2x"></i>
                                                </a>
                                                @endcan
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
                    <button class="btn btn-secondary" type="button" data-dismiss="modal" onclick="$('#modalProcessOrder').modal('hide')">Cerrar</button>
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
