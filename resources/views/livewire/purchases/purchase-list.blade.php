<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>{{$componentName}} | {{$pageTitle}}</b>
                </h4>
                <ul class="tabs tab-pills">
                    <li>
                        <a href="{{ url('purchases') }}" class="tabmenu bg-dark" style="margin-right: 5px;">
                            Agregar
                        </a>
                    </li>
                </ul>
            </div>
            
            <div class="widget-content">
                <div class="row mb-4 px-3">
                    <div class="col-sm-12 col-md-4">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                            </div>
                            <input wire:model.live="search" type="text" class="form-control" placeholder="Buscar...">
                        </div>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mt-1">
                        <thead class="text-white" style="background: #3B3F5C">
                            <tr>
                                <th class="table-th text-white">FOLIO</th>
                                <th class="table-th text-white">PROVEEDOR</th>
                                <th class="table-th text-white text-center">TOTAL</th>
                                <th class="table-th text-white text-center">ITEMS</th>
                                <th class="table-th text-white text-center">ESTATUS</th>
                                <th class="table-th text-white text-center">FECHA</th>
                                <th class="table-th text-white text-center">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($purchases as $purchase)
                            <tr>
                                <td><h6>{{$purchase->id}}</h6></td>
                                <td><h6>{{$purchase->supplier_name}}</h6></td>
                                <td class="text-center"><h6>${{number_format($purchase->total,2)}}</h6></td>
                                <td class="text-center"><h6>{{$purchase->items}}</h6></td>
                                <td class="text-center">
                                    <span class="badge {{ $purchase->status == 'paid' ? 'badge-success' : 'badge-warning' }} text-uppercase">
                                        {{$purchase->status == 'paid' ? 'PAGADO' : 'PENDIENTE'}}
                                    </span>
                                </td>
                                <td class="text-center">
                                    <h6>{{ \Carbon\Carbon::parse($purchase->created_at)->format('d-m-Y') }}</h6>
                                </td>
                                <td class="text-center">
                                    <button wire:click.prevent="viewDetails({{$purchase->id}})" class="btn btn-dark btn-sm">
                                        <i class="fas fa-list"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{$purchases->links()}}
                </div>
            </div>
        </div>
    </div>

    <!-- Details Modal -->
    <div wire:ignore.self class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark">
                    <h5 class="modal-title text-white">
                        <b>Detalles de Compra</b>
                    </h5>
                    <button class="close" data-dismiss="modal" type="button" aria-label="Close">
                        <span class="text-white">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mt-1">
                            <thead class="text-white" style="background: #3B3F5C">
                                <tr>
                                    <th class="table-th text-white">PRODUCTO</th>
                                    <th class="table-th text-white text-center">CANTIDAD</th>
                                    <th class="table-th text-white text-center">COSTO</th>
                                    <th class="table-th text-white text-center">IMPORTE</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($details as $detail)
                                <tr>
                                    <td>
                                        {{$detail->product->name}}
                                        @if($detail->product->is_variable_quantity && !empty($detail->metadata))
                                            <div class="mt-2 ml-3">
                                                <small class="text-info font-weight-bold">Detalle de Bobinas:</small>
                                                <ul class="list-unstyled pl-2 border-left border-info">
                                                    @foreach($detail->metadata as $item)
                                                        <li class="mb-1">
                                                            <small>
                                                                Peso: <b>{{ $item['weight'] }} kg</b>
                                                                @if(!empty($item['color'])) | Color: {{ $item['color'] }} @endif
                                                                @if(!empty($item['batch'])) | Lote: {{ $item['batch'] }} @endif
                                                            </small>
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-center">{{number_format($detail->quantity,2)}}</td>
                                    <td class="text-center">${{number_format($detail->cost,2)}}</td>
                                    <td class="text-center">${{number_format($detail->quantity * $detail->cost, 2)}}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-dark" type="button" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', function() {
            Livewire.on('show-modal', msg => {
                $('#detailsModal').modal('show')
            });
            Livewire.on('hide-modal', msg => {
                $('#detailsModal').modal('hide')
            });
        });
    </script>
</div>
