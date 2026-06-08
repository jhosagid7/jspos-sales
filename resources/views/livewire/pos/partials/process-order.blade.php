<div>
    <div wire:ignore.self class="modal fade" id="modalProcessOrder" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title">Procesar Ordenes</h5>
                    <button class="py-0 btn-close" type="button" data-dismiss="modal" aria-label="Close" onclick="$('#modalProcessOrder').modal('hide')"></button>
                </div>
                <div class="modal-body">
                    <div class="faq-form row g-2">
                            <div class="col-md-4">
                                <input wire:model.defer="searchOrder" wire:keydown.enter.prevent="getOrdersWithDetails"
                                    class="form-control form-control-lg" type="text"
                                    placeholder="Buscar por cliente, folio o vendedor..."
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
                            <div class="col-md-4">
                                <select wire:model.live="searchDriver" class="form-control form-control-lg">
                                    <option value="">Filtrar por Chofer/Ruta (Todos)</option>
                                    <option value="none">Sin Chofer/Ruta Asignado</option>
                                    @foreach($drivers as $drv)
                                        <option value="{{ $drv->id }}">{{ $drv->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                         @if($searchDriver && $searchDriver !== 'none')
                             @php
                                 $selectedDriverModel = collect($drivers)->firstWhere('id', $searchDriver);
                                 $routeGoal = $selectedDriverModel ? $selectedDriverModel->route_goal : 0;
                             @endphp
                             @if($routeGoal > 0)
                                 <!-- Tarjeta de progreso de la ruta -->
                                 <div class="row mt-2">
                                     <div class="col-md-12">
                                         <div class="card border-0 shadow-sm" style="border-radius: 12px; background: linear-gradient(135deg, #0f2027 0%, #203a43 50%, #2c5364 100%);">
                                             <div class="card-body py-3 px-4 text-white">
                                                 <div class="d-flex justify-content-between align-items-center mb-2">
                                                     <div class="d-flex align-items-center">
                                                         <div class="p-2 bg-info rounded-circle mr-3 d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                             <i class="fas fa-truck-loading text-white fs-5"></i>
                                                         </div>
                                                         <div>
                                                             <h6 class="mb-0 text-uppercase fw-bold text-info" style="font-size: 0.75rem; letter-spacing: 1px;">Progreso de Ruta: {{ $selectedDriverModel->name }}</h6>
                                                             <h4 class="mb-0 fw-bold">${{ number_format($ordersTotal, 2) }} / ${{ number_format($routeGoal, 2) }}</h4>
                                                         </div>
                                                     </div>
                                                     <div class="text-right">
                                                         @php
                                                             $percent = $routeGoal > 0 ? min(100, ($ordersTotal / $routeGoal) * 100) : 0;
                                                             $missing = max(0, $routeGoal - $ordersTotal);
                                                             $exceeded = max(0, $ordersTotal - $routeGoal);
                                                         @endphp
                                                         @if($ordersTotal >= $routeGoal)
                                                             <span class="badge badge-success px-3 py-2 text-uppercase font-weight-bold" style="border-radius: 15px; font-size: 0.75rem;">
                                                                 <i class="fas fa-check-circle mr-1"></i> ¡Meta Alcanzada! (+${{ number_format($exceeded, 2) }})
                                                             </span>
                                                         @else
                                                             <span class="badge badge-warning text-dark px-3 py-2 text-uppercase font-weight-bold" style="border-radius: 15px; font-size: 0.75rem;">
                                                                 <i class="fas fa-exclamation-circle mr-1"></i> Faltan ${{ number_format($missing, 2) }}
                                                             </span>
                                                         @endif
                                                     </div>
                                                 </div>
                                                 <div class="progress mb-0" style="height: 10px; border-radius: 5px; background-color: rgba(255,255,255,0.15);">
                                                     <div class="progress-bar progress-bar-striped progress-bar-animated {{ $ordersTotal >= $routeGoal ? 'bg-success' : 'bg-info' }}" 
                                                          role="progressbar" 
                                                          style="width: {{ $routeGoal > 0 ? (($ordersTotal / $routeGoal) * 100) : 0 }}%">
                                                     </div>
                                                 </div>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             @endif
                         @endif

                         <div class="row mt-2">
                             <div class="col-md-12">
                                 <div class="card bg-dark text-white border-0 shadow-lg" style="border-radius: 12px; background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%);">
                                     <div class="card-body py-3 px-3">
                                         <div class="row text-center">
                                             <div class="col-6 col-md-2 mb-2 mb-md-0 border-right border-secondary">
                                                 <span class="text-muted d-block font-weight-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Base (Neto)</span>
                                                 <h5 class="mb-0 font-weight-bold text-white">${{ number_format($ordersTotal, 2) }}</h5>
                                             </div>
                                             <div class="col-6 col-md-2 mb-2 mb-md-0 border-right border-secondary">
                                                 <span class="text-success d-block font-weight-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Comisión</span>
                                                 <h5 class="mb-0 font-weight-bold text-success">${{ number_format($ordersCommissionTotal, 2) }}</h5>
                                             </div>
                                             <div class="col-6 col-md-2 mb-2 mb-md-0 border-right border-secondary">
                                                 <span class="text-info d-block font-weight-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Flete</span>
                                                 <h5 class="mb-0 font-weight-bold text-info">${{ number_format($ordersFreightTotal, 2) }}</h5>
                                             </div>
                                             <div class="col-6 col-md-2 mb-2 mb-md-0 border-right border-secondary">
                                                 <span class="text-warning d-block font-weight-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Dif. Cambiario</span>
                                                 <h5 class="mb-0 font-weight-bold text-warning">${{ number_format($ordersDiffTotal, 2) }}</h5>
                                             </div>
                                             <div class="col-12 col-md-4">
                                                 <span class="text-primary d-block font-weight-bold text-uppercase" style="font-size: 0.65rem; letter-spacing: 0.5px;">Total General Ordenes</span>
                                                 <h4 class="mb-0 font-weight-bold text-primary">${{ number_format($ordersGrandTotal, 2) }}</h4>
                                             </div>
                                         </div>
                                     </div>
                                 </div>
                             </div>
                         </div>

                        <div class="mt-3 table-responsive" style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch;">
                            <table class="table table-sm table-hover table-bordered table-mobile-details" id="tblSalesRpt" style="font-size: 0.82rem; white-space: nowrap;">
                                <thead class="thead-primary">
                                    <tr class="text-center">
                                        <th class="p-1">Folio</th>
                                        <th class="p-1">Cliente</th>
                                        <th class="p-1">Vendedor</th>
                                        <th class="p-1">Chofer/Ruta</th>
                                        <th class="p-1">Base</th>
                                        <th class="p-1">%</th>
                                        <th class="p-1">Comisión</th>
                                        <th class="p-1">Flete</th>
                                        <th class="p-1">Dif.</th>
                                        <th class="p-1">Total</th>
                                        <th class="p-1">Art.</th>
                                        <th class="p-1">Estatus</th>
                                        <th class="p-1">Fecha</th>
                                        <th class="p-1">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>

                                    @forelse ($orders as $order)
                                        <tr class="text-center">
                                            <td data-label="Folio">{{ $order->order_number ?? $order->id }}</td>
                                            <td data-label="Cliente" class="text-left" style="max-width: 140px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">{{ $order->customer->name }}</td>
                                            <td data-label="Vendedor">
                                                @php $assignedSeller = $order->customer->seller; @endphp
                                                @if($assignedSeller)
                                                    <span class="badge" 
                                                          style="background-color: {{ $assignedSeller->color ?? '#eee' }}; color: #333; font-weight: 600; border: 1px solid #ccc; font-size: 0.75em;">
                                                        {{ $assignedSeller->name }}
                                                    </span>
                                                @else
                                                    <span class="text-muted" style="font-size: 0.75em;">N/A</span>
                                                @endif
                                            </td>
                                            <td data-label="Chofer/Ruta">
                                                @if($order->driver)
                                                    <span class="badge badge-light-primary" style="font-size: 0.75em;">{{ $order->driver->name }}</span>
                                                @else
                                                    <span class="text-muted" style="font-size: 0.75em;">N/A</span>
                                                @endif
                                            </td>
                                            <td data-label="Base" class="text-right">${{ number_format($order->base_amount, 2) }}</td>
                                            <td data-label="%">{{ number_format($order->surcharge_percentage, 1) }}%</td>
                                            <td data-label="Comisión" class="text-right text-success">${{ number_format($order->commission_amount, 2) }}</td>
                                            <td data-label="Flete" class="text-right text-info">${{ number_format($order->freight_amount, 2) }}</td>
                                            <td data-label="Dif." class="text-right text-warning">${{ number_format($order->exchange_diff_amount, 2) }}</td>
                                            <td data-label="Total" class="text-right font-weight-bold">${{ number_format($order->total, 2) }}</td>
                                            <td data-label="Articulos">{{ $order->items }}</td>
                                            <td data-label="Estatus">
                                                <span class="badge f-10 {{ $order->status == 'paid' ? 'badge-light-success' : ($order->status == 'return' ? 'badge-light-warning' : ($order->status == 'pending' ? 'badge-light-warning' : 'badge-light-danger')) }}">{{ $order->status }}</span>
                                            </td>
                                            <td data-label="Fecha">{{ $order->created_at->format('d/m/Y H:i') }}</td>

                                            <td data-label="Acciones" class="text-center">
                                                <div class="btn-group">
                                                    @can('orders.add_to_cart')
                                                    <button class="btn btn-primary btn-xs py-1 px-2"
                                                        wire:click.prevent="loadOrderToCart({{ $order->id }})"
                                                        style="font-size: 0.75rem;">
                                                        Cargar
                                                    </button>
                                                    @endcan
                                                    <button type="button" class="btn btn-outline-secondary btn-xs dropdown-toggle dropdown-toggle-split py-1 px-2" data-toggle="dropdown" data-boundary="viewport" aria-haspopup="true" aria-expanded="false">
                                                        <span class="sr-only">Opciones</span>
                                                    </button>
                                                    <div class="dropdown-menu dropdown-menu-right shadow-lg" style="min-width: 190px; z-index: 1050; font-size: 0.82rem;">
                                                        @can('orders.details')
                                                        <button wire:click.prevent="getOrderDetail({{ $order->id }})" class="dropdown-item py-2">
                                                            <i class="icofont icofont-list text-primary mr-2"></i> Ver Detalles
                                                        </button>
                                                        @endcan
                                                        
                                                        @can('orders.edit')
                                                        <button wire:click.prevent="getOrderDetailNote({{ $order->id }})" class="dropdown-item py-2">
                                                            <i class="icofont icofont-edit-alt text-warning mr-2"></i> Editar Nota
                                                        </button>
                                                        @endcan
                                                        
                                                        <button wire:click.prevent="getOrderHistory({{ $order->id }})" class="dropdown-item py-2">
                                                            <i class="icofont icofont-clock-time text-info mr-2"></i> Historial/Auditoría
                                                        </button>
                                                        
                                                        <button wire:click.prevent="revertToDraft({{ $order->id }})" class="dropdown-item py-2">
                                                            <i class="icofont icofont-undo text-success mr-2"></i> Desbloquear (Borrador)
                                                        </button>
                                                        
                                                        @can('orders.pdf')
                                                        <a class="dropdown-item py-2 {{ $order->status == 'returned' ? 'disabled' : '' }}"
                                                           href="{{ route('pos.orders.generatePdfOrderInvoice', $order->id) }}"
                                                           target="_blank">
                                                            <i class="icofont icofont-file-pdf text-danger mr-2"></i> Descargar PDF
                                                        </a>
                                                        @endcan
                                                        
                                                        @if ($order->status != 'deleted')
                                                            @can('orders.delete')
                                                            <div class="dropdown-divider my-1"></div>
                                                            <button onclick="DestroyOrder({{ $order->id }})" class="dropdown-item py-2 text-danger">
                                                                <i class="icofont icofont-trash mr-2"></i> Eliminar Orden
                                                            </button>
                                                            @endcan
                                                        @endif
                                                    </div>
                                                </div>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="14" class="text-center">Sin Ordenes</td>
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
                </div>
                <div class="modal-footer">
                    <button class="btn btn-secondary" type="button" data-dismiss="modal" onclick="$('#modalProcessOrder').modal('hide')">Cerrar</button>
                </div>
            </div>
    </div>
    @include('livewire.pos.partials.order-detail')
    @include('livewire.pos.partials.order-detail-note')
    @include('livewire.pos.partials.order-history')
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
        document.addEventListener('show-order-history', event => {
            $('#modalOrderHistory').modal('show')
        })
    </script>
</div>
