<div>
    <div wire:ignore.self class="modal fade" id="modalProcessOrder" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">Procesar Ordenes de Compra</h5>
                    <button class="py-0 btn-close" type="button" data-dismiss="modal" aria-label="Close" onclick="$('#modalProcessOrder').modal('hide')"></button>
                </div>
                <div class="modal-body">
                    @if ($order_selected_id == null)
                        <div class="faq-form">
                            <input wire:model.live.debounce.300ms="searchOrder"
                                class="form-control form-control-lg" type="text"
                                placeholder="Ingresa el nombre del proveedor, monto de compra o número de folio"
                                id="inputprocessOrderSearch" style="background-color: beige">
                            <i class="search-icon" data-feather="search"></i>
                        </div>

                        <div class="mt-3 table-responsive">
                            <table class="table table-responsive-md table-hover" id="tblSalesRpt">
                                <thead class="thead-primary">
                                    <tr class="text-center">
                                        <th>Folio</th>
                                        <th>Proveedor</th>
                                        <th>Total</th>
                                        <th>Articulos</th>
                                        <th>Estatus</th>
                                        <th>Fecha</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @forelse ($orders as $order)
                                        <tr class="text-center">
                                            <td>{{ $order->id }}</td>
                                            <td>{{ $order->supplier->name ?? 'N/A' }}</td>
                                            <td>${{ number_format($order->total, 2) }}</td>
                                            <td>{{ $order->items }}</td>
                                            <td><span
                                                    class="badge f-12 {{ $order->status == 'paid' ? 'badge-light-success' : ($order->status == 'pending' ? 'badge-light-warning' : 'badge-light-danger') }}">{{ $order->status }}</span>
                                            </td>
                                            <td>{{ $order->created_at }}</td>
                                            
                                            <td data-container="body" data-bs-toggle="tooltip" data-bs-placement="top"
                                                data-bs-html="true" data-bs-title="<b>Ver los detalles de la orden</b>">
                                                <button class="btn btn-primary btn-sm"
                                                    wire:click.prevent="loadOrderToCart({{ $order->id }})">
                                                    <i class="fas fa-cart-plus"></i> Cargar
                                                </button>
                                                @if ($order->status != 'deleted')
                                                    <button class="btn btn-danger btn-sm"
                                                        onclick="DestroyOrder({{ $order->id }})">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="7" class="text-center">Sin Ordenes Pendientes</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            <div class="mt-2">
                                {{ $orders->links() }}
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal" onclick="$('#modalProcessOrder').modal('hide')">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        document.addEventListener('livewire:init', function() {
            $('#modalProcessOrder').on('shown.bs.modal', function() {
                setTimeout(() => {
                    document.getElementById('inputprocessOrderSearch').focus()
                }, 500)
            })
            
            Livewire.on('close-process-order', event => {
                $('#modalProcessOrder').modal('hide')
            })
        })
    </script>
</div>
