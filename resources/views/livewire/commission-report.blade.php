<div>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">Reporte de Comisiones (Vendedores Foráneos)</h4>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-sm-3">
                            <label>Vendedor</label>
                            <select wire:model.live="seller_id" class="form-control">
                                <option value="0">Seleccionar Vendedor</option>
                                @foreach($sellers as $seller)
                                    <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-3">
                            <label>Fecha Desde</label>
                            <input type="date" wire:model.live="dateFrom" class="form-control">
                        </div>
                        <div class="col-sm-3">
                            <label>Fecha Hasta</label>
                            <input type="date" wire:model.live="dateTo" class="form-control">
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Venta #</th>
                                    <th>Fecha Venta</th>
                                    <th>Fecha Pago</th>
                                    <th>Días</th>
                                    <th>Cliente</th>
                                    <th>Total Venta</th>
                                    <th>Base Calc.</th>
                                    <th>% Pactado</th>
                                    <th>Penalidad</th>
                                    <th>% Final</th>
                                    <th>Comisión</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $sale)
                                    <tr>
                                        <td>{{ $sale['id'] }}</td>
                                        <td>{{ $sale['date'] }}</td>
                                        <td>{{ $sale['paid_date'] }}</td>
                                        <td>{{ $sale['days'] }}</td>
                                        <td>{{ $sale['customer'] }}</td>
                                        <td>${{ number_format($sale['total'], 2) }}</td>
                                        <td>${{ number_format($sale['base_amount'], 2) }}</td>
                                        <td>{{ $sale['applied_percent'] }}%</td>
                                        <td>
                                            @if($sale['penalty'] > 0)
                                                <span class="badge badge-danger">-{{ $sale['penalty'] }}%</span>
                                            @else
                                                <span class="badge badge-success">0%</span>
                                            @endif
                                        </td>
                                        <td>{{ $sale['final_percent'] }}%</td>
                                        <td><strong>${{ number_format($sale['commission_amount'], 2) }}</strong></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center">No hay ventas registradas para este período/vendedor.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                @if(count($sales) > 0)
                                    <tr>
                                        <td colspan="10" class="text-right"><strong>TOTAL COMISIONES:</strong></td>
                                        <td><strong>${{ number_format(collect($sales)->sum('commission_amount'), 2) }}</strong></td>
                                    </tr>
                                @endif
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
