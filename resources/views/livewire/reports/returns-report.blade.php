<div>
    <div class="row sales layout-top-spacing">
        <div class="col-sm-12">
            <div class="widget widget-chart-one">
                <div class="widget-heading">
                    <h4 class="card-title">
                        <b>Gestor de Notas de Crédito / Devoluciones</b>
                    </h4>
                    <ul class="tabs tab-pills">
                        <li>
                            <a href="javascript:void(0)" class="tabmenu bg-dark" style="color:white; padding: 10px; border-radius: 5px;">
                                Total Retornado: ${{ number_format($totales, 2) }}
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="widget-content">
                    <div class="row">
                        <div class="col-sm-12 col-md-4">
                            <div class="form-group">
                                <label>Buscador General</label>
                                <input type="text" wire:model.live="searchFolio" class="form-control" placeholder="🔍 Folio, Cliente o Factura...">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-2">
                            <div class="form-group">
                                <label>Estado</label>
                                <select wire:model.live="status" class="form-control">
                                    <option value="all">Todos</option>
                                    <option value="pending">Pendientes</option>
                                    <option value="approved">Aprobados</option>
                                    <option value="rejected">Rechazados</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <div class="form-group">
                                <label>Desde</label>
                                <input type="date" wire:model.live="dateFrom" class="form-control">
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            <div class="form-group">
                                <label>Hasta</label>
                                <input type="date" wire:model.live="dateTo" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-striped mt-1">
                            <thead class="text-white" style="background: #3B3F5C">
                                <tr>
                                    <th class="table-th text-white">NRO NOTA</th>
                                    <th class="table-th text-white text-center">CLIENTE</th>
                                    <th class="table-th text-white text-center">FACTURA REF</th>
                                    <th class="table-th text-white text-center">TOTAL</th>
                                    <th class="table-th text-white text-center">MOTIVO</th>
                                    <th class="table-th text-white text-center">ESTADO</th>
                                    <th class="table-th text-white text-center">FECHA</th>
                                    <th class="table-th text-white text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($returns as $r)
                                <tr>
                                    <td><h6>N/C-{{ $r->return_number }}</h6></td>
                                    <td class="text-center">
                                        <span class="text-uppercase" style="font-size: 0.8rem;">{{ $r->customer->name ?? 'N/A' }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-outline-primary">
                                            #{{ $r->sale->invoice_number ?? $r->sale_id }}
                                        </span>
                                    </td>
                                    <td class="text-center"><h6>${{ number_format($r->total_returned, 2) }}</h6></td>
                                    <td class="text-center">
                                        <small class="text-muted">{{ $r->reason ?: 'Sin motivo' }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if($r->status == 'pending')
                                            <span class="badge badge-warning text-dark">PENDIENTE</span>
                                        @elseif($r->status == 'approved')
                                            <span class="badge badge-success">APROBADO</span>
                                        @else
                                            <span class="badge badge-danger">{{ strtoupper($r->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($r->created_at)->format('d/m/Y H:i') }}</td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center gap-1">
                                            @if($r->status == 'pending')
                                                @can('sales.approve_return')
                                                    <button wire:click="approveReturn({{$r->id}})" 
                                                        wire:confirm="¿Aprobar esta devolución? Se ajustará stock y deuda."
                                                        class="btn btn-success btn-sm" title="Aprobar">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button wire:click="rejectReturn({{$r->id}})" 
                                                        wire:confirm="¿Rechazar esta devolución?"
                                                        class="btn btn-danger btn-sm" title="Rechazar">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @endcan
                                            @endif
                                            <a href="{{ route('pos.sales.generatePdfInvoice', $r->sale_id) }}" target="_blank" class="btn btn-dark btn-sm" title="Ver Factura Ref.">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('pos.returns.generateCreditNotePdf', $r->id) }}" target="_blank" class="btn btn-warning btn-sm" title="Ver Nota de Crédito">
                                                <i class="fas fa-file-pdf"></i>
                                            </a>
                                        </div>
                                        <div class="mt-1">
                                            @if($r->requester)
                                                <small class="text-primary d-block">Solicitado por: {{ $r->requester->name }}</small>
                                            @endif
                                            @if($r->status == 'approved' && $r->approver)
                                                <small class="text-success d-block">Aprobado por: {{ $r->approver->name }}</small>
                                            @elseif($r->status == 'rejected' && $r->approver)
                                                <small class="text-danger d-block">Rechazado por: {{ $r->approver->name }}</small>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-right"><b>TOTAL:</b></td>
                                    <td class="text-center"><b>${{ number_format($totales, 2) }}</b></td>
                                    <td colspan="4"></td>
                                </tr>
                            </tfoot>
                        </table>
                        {{ $returns->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .widget-chart-one {
            background: #fff;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 4px 6px 0 rgba(85, 85, 85, 0.08), 0 1px 20px 0 rgba(0, 0, 0, 0.07), 0px 7px 11px -3px rgba(0, 0, 0, 0.06);
        }
        .badge-outline-primary {
            color: #1b55e2;
            border: 1px solid #1b55e2;
            background-color: transparent;
        }
        .gap-1 { gap: 0.25rem; }
    </style>
</div>
