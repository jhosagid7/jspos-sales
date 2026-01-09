<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>{{ $componentName ?? 'Relación de Cobro General' }}</b>
                </h4>
            </div>

            <div class="widget-content">
                <div class="row mb-3">
                    <div class="col-sm-12 col-md-3">
                        <label>Fecha Desde</label>
                        <input type="date" wire:model.live="dateFrom" class="form-control">
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <label>Fecha Hasta</label>
                        <input type="date" wire:model.live="dateTo" class="form-control">
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <label>Operador</label>
                        <select wire:model.live="operator_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach($operators as $op)
                                <option value="{{ $op->id }}">{{ $op->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <label>Vendedor</label>
                        <select wire:model.live="seller_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-12 col-md-3">
                        <label>Lote</label>
                        <input type="text" wire:model.live="batch_name" class="form-control" placeholder="Buscar por Lote...">
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <label>Zona</label>
                        <input type="text" wire:model.live="zone" class="form-control" placeholder="Buscar por Zona...">
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <label>Factura Desde (ID)</label>
                        <input type="number" wire:model.live="invoice_from" class="form-control">
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <label>Factura Hasta (ID)</label>
                        <input type="number" wire:model.live="invoice_to" class="form-control">
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-12 col-md-8">
                        <button wire:click="$set('showReport', true)" class="btn btn-primary btn-block">
                            Consultar
                        </button>
                    </div>
                    <div class="col-sm-12 col-md-4">
                        <button wire:click="generatePdf" class="btn btn-danger btn-block" {{ !$showReport ? 'disabled' : '' }}>
                            <i class="fas fa-file-pdf"></i> Exportar PDF
                        </button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mt-1">
                        <thead class="text-white" style="background: #3B3F5C">
                            <tr>
                                <th class="table-th text-white">FOLIO</th>
                                <th class="table-th text-white">FECHA APERTURA</th>
                                <th class="table-th text-white">ESTADO</th>
                                <!-- Dynamic Cash Columns -->
                                @foreach($currencies as $currency)
                                    <th class="table-th text-white">EFECTIVO {{ $currency->code }}</th>
                                @endforeach
                                <!-- Dynamic Bank Columns -->
                                @foreach($banks as $bank)
                                    <th class="table-th text-white">{{ strtoupper($bank->name) }}</th>
                                @endforeach
                                <th class="table-th text-white">OTROS BANCOS</th>
                                <th class="table-th text-white">TOTAL RECAUDADO (USD)</th>
                                <th class="table-th text-white">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($sheets) && count($sheets) > 0)
                                @foreach($sheets as $sheet)
                                    <tr>
                                        <td><h6>{{ $sheet->sheet_number }}</h6></td>
                                        <td><h6>{{ $sheet->opened_at->format('d/m/Y H:i') }}</h6></td>
                                        <td>
                                            <span class="badge {{ $sheet->status == 'open' ? 'badge-success' : 'badge-dark' }}">
                                                {{ strtoupper($sheet->status) }}
                                            </span>
                                        </td>
                                        
                                        <!-- Cash Totals per Currency -->
                                        @foreach($currencies as $currency)
                                            @php
                                                $cashTotal = $sheet->payments->where('pay_way', 'cash')->where('currency', $currency->code)->sum('amount');
                                            @endphp
                                            <td><h6>{{ number_format($cashTotal, 2) }}</h6></td>
                                        @endforeach

                                        <!-- Bank Totals per Bank -->
                                        @foreach($banks as $bank)
                                            @php
                                                // Base: Payments via bank/deposit for this specific bank
                                                $bankTotal = $sheet->payments->whereIn('pay_way', ['bank', 'deposit'])->where('bank', $bank->name)->sum('amount');
                                                
                                                // Special Case: If this bank is "Zelle", also include payments with pay_way = 'zelle'
                                                if (stripos($bank->name, 'zelle') !== false) {
                                                    $zellePayments = $sheet->payments->where('pay_way', 'zelle')->sum('amount');
                                                    $bankTotal += $zellePayments;
                                                }
                                            @endphp
                                            <td><h6>{{ number_format($bankTotal, 2) }}</h6></td>
                                        @endforeach

                                        <!-- Other Banks (not in list) -->
                                        @php
                                            $knownBanks = $banks->pluck('name')->toArray();
                                            // Exclude 'zelle' pay_way from here since it's handled in the Zelle bank column
                                            $otherBanksTotal = $sheet->payments
                                                ->whereIn('pay_way', ['bank', 'deposit'])
                                                ->whereNotIn('bank', $knownBanks)
                                                ->sum('amount');
                                        @endphp
                                        <td><h6>{{ number_format($otherBanksTotal, 2) }}</h6></td>

                                        <td><h6>${{ number_format($sheet->total_amount, 2) }}</h6></td>
                                        <td class="text-center">
                                            <button wire:click="viewDetails({{ $sheet->id }})" class="btn btn-dark btn-sm">
                                                <i class="fas fa-list"></i> Ver Detalle
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            @else
                                <tr>
                                    <td colspan="5" class="text-center">NO HAY PLANILLAS REGISTRADAS</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                    @if(isset($sheets) && count($sheets) > 0)
                        {{ $sheets->links() }}
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
