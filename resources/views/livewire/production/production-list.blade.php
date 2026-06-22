<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>{{$componentName}} | {{$pageTitle}}</b>
                </h4>
                <ul class="tabs tab-pills">
                    <li>
                        <a href="{{ route('production.create') }}" class="tabmenu bg-dark mr-3">
                            Agregar
                        </a>
                    </li>
                </ul>
            </div>

            <div class="widget-content">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped mt-1">
                        <thead class="text-white" style="background: #3B3F5C">
                            <tr>
                                <th class="table-th text-white">ID</th>
                                <th class="table-th text-white">FECHA</th>
                                <th class="table-th text-white">USUARIO</th>
                                <th class="table-th text-white">ESTADO</th>
                                <th class="table-th text-white">NOTA</th>
                                <th class="table-th text-white">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($productions as $row)
                            <tr>
                                <td><h6>{{$row->id}}</h6></td>
                                <td><h6>{{ \Carbon\Carbon::parse($row->production_date)->locale('es')->isoFormat('dddd, D [de] MMMM [de] YYYY') }}</h6></td>
                                <td><h6>{{$row->user_name}}</h6></td>
                                <td>
                                    @if($row->status == 'sent')
                                        <span class="badge badge-success">PROCESADO / ENVIADO</span>
                                    @elseif($row->status == 'approved')
                                        <span class="badge badge-info">APROBADO</span>
                                    @else
                                        <span class="badge badge-warning">PENDIENTE</span>
                                    @endif
                                </td>
                                <td><h6>{{$row->note}}</h6></td>
                                <td class="text-center">
                                    @if($row->status == 'pending')
                                    <button wire:click="delete({{$row->id}})" 
                                            onclick="confirm('¿CONFIRMAS ELIMINAR EL REGISTRO?') || event.stopImmediatePropagation()"
                                            class="btn btn-dark mtmobile" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                    @endif
                                    
                                    @if($row->status == 'pending' && $row->cargos->isEmpty())
                                    <button wire:click="sendToCargo({{$row->id}})" 
                                            class="btn btn-info mtmobile" title="Aprobar Planilla">
                                        <i class="fas fa-check-circle"></i>
                                    </button>
                                    @endif
                                    
                                    @if($row->status == 'pending')
                                    <a href="{{ route('production.create', ['production' => $row->id]) }}" 
                                       class="btn btn-warning mtmobile" title="Editar">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    @endif
                                    
                                    <a href="{{ route('production.pdf', $row->id) }}" target="_blank" class="btn btn-dark mtmobile" title="PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </a>

                                    <button wire:click="sendEmail({{$row->id}})" class="btn btn-dark mtmobile" title="Enviar por Correo">
                                        <i class="fas fa-envelope"></i>
                                    </button>

                                    <button wire:click="viewDetails({{$row->id}})" class="btn btn-dark mtmobile" title="Ver Detalles">
                                        <i class="fas fa-list"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                    {{$productions->links()}}
                </div>
            </div>
        </div>
    </div>

    <div wire:ignore.self class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark">
                    <h5 class="modal-title text-white"><b>Detalle de Producción</b></h5>
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
                                    <th class="table-th text-white">DEPÓSITO</th>
                                    <th class="table-th text-white text-center">OPERARIO</th>
                                    <th class="table-th text-white text-center">TIPO (TM)</th>
                                    <th class="table-th text-white text-center">CANTIDAD</th>
                                    <th class="table-th text-white text-center">PESO</th>
                                    <th class="table-th text-white text-center">COSTO</th>
                                    <th class="table-th text-white text-center">TOTAL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php
                                    $groupedDetails = collect($details)->groupBy(function($detail) {
                                        $date = $detail->production_date ?? null;
                                        return $date ? \Carbon\Carbon::parse($date)->format('Y-m-d') : 'Sin Fecha';
                                    });
                                @endphp
                                @foreach($groupedDetails as $dateStr => $group)
                                    @if($dateStr !== 'Sin Fecha')
                                        <tr class="bg-light">
                                            <td colspan="8" class="font-weight-bold text-primary text-uppercase">
                                                <i class="fas fa-calendar-alt"></i> {{ \Carbon\Carbon::parse($dateStr)->locale('es')->isoFormat('dddd DD-MM-YYYY') }}
                                            </td>
                                        </tr>
                                    @else
                                        <tr class="bg-light">
                                            <td colspan="8" class="font-weight-bold text-secondary">
                                                Sin Fecha de Producción
                                            </td>
                                        </tr>
                                    @endif
                                    @foreach($group as $detail)
                                    @php
                                        $rowCost = $detail->cost ?? ($detail->product->cost ?? 0);
                                        $effQty = ($detail->product->is_variable_quantity) ? $detail->weight : $detail->quantity;
                                        $rowTotal = $effQty * $rowCost;
                                    @endphp
                                    <tr>
                                        <td>
                                            {{ $detail->product->name }}
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
                                        <td>
                                            {{ $detail->warehouse->name ?? 'N/A' }} 
                                        </td>
                                        <td class="text-center">{{ $detail->operator_name ?? 'N/A' }}</td>
                                        <td class="text-center">{{ $detail->material_type }}</td>
                                        <td class="text-center">{{ number_format($detail->quantity, 2) }}</td>
                                        <td class="text-center">{{ number_format($detail->weight, 2) }}</td>
                                        <td class="text-center">{{ number_format($rowCost, 2) }}</td>
                                        <td class="text-center">{{ number_format($rowTotal, 2) }}</td>
                                    </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" class="text-right font-weight-bold">TOTALES</td>
                                    <td class="text-center font-weight-bold">{{ number_format(collect($details)->sum('quantity'), 2) }}</td>
                                    <td class="text-center font-weight-bold">{{ number_format(collect($details)->sum('weight'), 2) }}</td>
                                    <td></td>
                                    <td class="text-center font-weight-bold">
                                        @php
                                            $grandTotalCost = collect($details)->sum(function($d) {
                                                $effQty = ($d->product->is_variable_quantity) ? $d->weight : $d->quantity;
                                                return $effQty * ($d->cost ?? ($d->product->cost ?? 0));
                                            });
                                        @endphp
                                        {{ number_format($grandTotalCost, 2) }}
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark close-btn text-info" data-dismiss="modal">CERRAR</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('show-modal', (modalId) => {
                $('#' + modalId).modal('show');
            });
            Livewire.on('hide-modal', (modalId) => {
                $('#' + modalId).modal('hide');
            });
        });
    </script>
</div>
