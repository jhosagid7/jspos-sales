<div>
    <div class="row">
        <!-- Sidebar - Opciones de Consulta -->
        <div class="col-sm-12 col-md-3">
            <div class="card mb-3">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light mb-0">Filtros de Reporte</h5>
                </div>

                <div class="card-body">
                    <!-- Selector de Operadores -->
                    <div class="mt-2">
                        <span class="f-14"><b>Filtrar Operadores / Cajeros</b></span>
                        <div class="border p-2 rounded mt-1" style="max-height: 180px; overflow-y: auto; background-color: #f8f9fa;">
                            @forelse ($operatorsList as $operator)
                                <div class="custom-control custom-checkbox mb-1">
                                    <input type="checkbox" class="custom-control-input" id="operator_{{ $operator->id }}" value="{{ $operator->id }}" wire:model.live="selectedOperators">
                                    <label class="custom-control-label f-12" for="operator_{{ $operator->id }}">{{ $operator->name }}</label>
                                </div>
                            @empty
                                <div class="text-center text-muted f-12 py-2">No se encontraron operadores</div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Rango de Fechas -->
                    <div class="mt-3">
                        <span class="f-14"><b>Desde</b></span>
                        <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm mt-1">
                    </div>
                    <div class="mt-2">
                        <span class="f-14"><b>Hasta</b></span>
                        <input type="date" wire:model.live="dateTo" class="form-control form-control-sm mt-1">
                    </div>

                    <!-- Botón Hoy -->
                    <div class="mt-2">
                        <button wire:click.prevent="setToday" class="btn btn-outline-secondary btn-sm w-100">
                            <i class="fa fa-calendar-day me-1"></i> Hoy
                        </button>
                    </div>

                    <!-- Toggle Local/Gravado -->
                    <div class="mt-3">
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" wire:model.live="splitByDepartment" class="custom-control-input" id="splitDeptSwitch">
                            <label class="custom-control-label f-13" for="splitDeptSwitch"><b>Dividir Local/Gravado</b></label>
                        </div>
                        <div class="custom-control custom-switch mb-2">
                            <input type="checkbox" wire:model.live="condensedSummary" class="custom-control-input" id="condensedSummarySwitch">
                            <label class="custom-control-label f-13" for="condensedSummarySwitch"><b>Resumen Condensado</b></label>
                        </div>
                        <hr>
                        <span class="f-14"><b>Opciones de Visualizaci&oacute;n</b></span>
                        <div class="custom-control custom-checkbox mt-1">
                            <input type="checkbox" wire:model.live="showOriginalAmount" class="custom-control-input" id="opt_original">
                            <label class="custom-control-label f-12" for="opt_original">Monto Original</label>
                        </div>
                        <div class="custom-control custom-checkbox mt-1">
                            <input type="checkbox" wire:model.live="showExchangeRate" class="custom-control-input" id="opt_rate">
                            <label class="custom-control-label f-12" for="opt_rate">Tasa de Cambio</label>
                        </div>
                        <div class="custom-control custom-checkbox mt-1">
                            <input type="checkbox" wire:model.live="showUsdAmount" class="custom-control-input" id="opt_usd">
                            <label class="custom-control-label f-12" for="opt_usd">Equivalente USD</label>
                        </div>
                        <div class="custom-control custom-checkbox mt-1">
                            <input type="checkbox" wire:model.live="showSignatures" class="custom-control-input" id="opt_signatures">
                            <label class="custom-control-label f-12" for="opt_signatures">Incluir Firmas en PDF</label>
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="mt-3">
                        <button wire:key="btn-seller-grouped-search" wire:click.prevent="searchData" class="btn btn-dark w-100">
                            <i class="fa fa-sync"></i> Generar Reporte
                        </button>
                    </div>

                    <!-- Botones PDF e Impresión (solo visible cuando hay datos) -->
                    @if($showReport)
                    <div class="mt-3">
                        <button wire:click.prevent="printTicket" class="btn btn-info btn-sm w-100 mb-2 font-weight-bold" title="Imprimir Ticket Térmico">
                            <i class="fa fa-print me-1"></i> Imprimir Ticket Térmico
                        </button>
                    </div>
                    <div class="mt-1 d-flex gap-1">
                        <button wire:click.prevent="openPdfPreview" class="btn btn-outline-danger btn-sm flex-fill" title="Previsualizar PDF">
                            <i class="fa fa-eye"></i> Vista Previa
                        </button>
                        <button wire:click.prevent="generatePdf" class="btn btn-danger btn-sm flex-fill" title="Descargar PDF">
                            <i class="fa fa-file-pdf"></i> PDF
                        </button>
                    </div>
                    @endif

                    <!-- Leyenda -->
                    <div class="mt-3 p-2 rounded" style="background:#f8f9fa; border-left: 3px solid #6c757d;">
                        <p class="f-11 text-muted mb-1"><b>¿Qué es LOCAL / GRAVADO?</b></p>
                        <p class="f-11 text-muted mb-0">Es la <b>clasificación del departamento</b> al que pertenece el producto, no el tipo de pago ni si cobró IVA.</p>
                        <p class="f-11 text-muted mb-0 mt-1">Se configura en <b>Registros Maestros → Categorías</b>.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Panel de Resultados -->
        <div class="col-sm-12 col-md-9">
            <div class="card card-absolute">
                <div class="card-header bg-dark d-flex justify-content-between align-items-center">
                    <h5 class="txt-light mb-0">Cobranza por Operador / Usuario</h5>
                    @if($showReport && $dateFrom)
                        <span class="badge badge-light f-12">
                            {{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}
                            @if($dateFrom !== $dateTo)
                                — {{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}
                            @endif
                        </span>
                    @endif
                </div>

                <div class="card-body">
                    <!-- Mensaje de instrucción -->
                    <div class="alert alert-info text-center {{ !$showReport ? '' : 'd-none' }}">
                        <i class="fa fa-info-circle me-2"></i>
                        Selecciona los filtros en la barra lateral y haz clic en <strong>Generar Reporte</strong>.<br>
                        Usa el botón <strong>Hoy</strong> para ver rápidamente el reporte del día.
                    </div>

                    <!-- Panel de Resultados -->
                    <div class="{{ !$showReport ? 'd-none' : '' }}">

                        <!-- KPIs de Resumen -->
                        <h5 class="txt-primary mb-3"><i class="fa fa-info-circle"></i> Totales Cobrados en USD</h5>
                        
                        <div class="row">
                            <!-- Metodos individuales -->
                            @foreach($totalsByMethod as $method)
                                <div class="col-md-3 mb-3">
                                    <div class="card shadow-sm border-left border-info h-100">
                                        <div class="card-body p-3">
                                            <div class="f-12 text-muted uppercase font-weight-bold">
                                                {{ strtoupper($method['method']) }} ({{ strtoupper($method['currency']) }})
                                            </div>
                                            <div class="f-11 text-muted">Original: {{ number_format($method['total_amount'], 2) }} {{ strtoupper($method['currency']) }}</div>
                                            <div class="f-18 font-weight-bold text-info mt-2">
                                                USD ${{ number_format($method['total_usd'], 2) }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                            <!-- Total General -->
                            <div class="col-md-3 mb-3">
                                <div class="card shadow-sm border-left border-success h-100 bg-light">
                                    <div class="card-body p-3">
                                        <div class="f-12 text-dark uppercase font-weight-bold">TOTAL GENERAL</div>
                                        <div class="f-11 text-muted">Suma total equivalente</div>
                                        <div class="f-18 font-weight-bold text-success mt-2">
                                            USD ${{ number_format($totalGeneralUsd, 2) }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if($condensedSummary)
                            <!-- Tabla Resumen Condensado General -->
                            <h5 class="txt-primary mt-3 mb-2"><i class="fa fa-list-alt"></i> Resumen Condensado (Totales Local / Gravado)</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered mt-1">
                                    <thead class="text-white" style="background: #1e2a3a">
                                        <tr>
                                            <th class="table-th text-white">Departamento</th>
                                            <th class="table-th text-white">M&eacute;todo</th>
                                            <th class="table-th text-white text-center">Moneda</th>
                                            @if($showOriginalAmount)
                                            <th class="table-th text-white text-right">Monto Original</th>
                                            @endif
                                            @if($showExchangeRate)
                                            <th class="table-th text-white text-right">Tasa Cambio</th>
                                            @endif
                                            @if($showUsdAmount)
                                            <th class="table-th text-white text-right">Equivalente USD</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @if($splitByDepartment)
                                            @php $hasRows = false; @endphp
                                            @foreach(['LOCAL', 'GRAVADO'] as $deptKey)
                                                @if(!empty($condensedData[$deptKey]))
                                                    @php
                                                        $hasRows = true;
                                                        $deptSubtotalUsd = 0;
                                                        $items = $condensedData[$deptKey];
                                                        $rowspan = count($items) + 1;
                                                        $firstItem = true;
                                                    @endphp
                                                    @foreach($items as $item)
                                                        @php $deptSubtotalUsd += $item->total_usd; @endphp
                                                        <tr>
                                                            @if($firstItem)
                                                                <td rowspan="{{ $rowspan }}" class="align-middle font-weight-bold bg-light" style="font-size: 13px;">
                                                                    <span class="badge {{ $deptKey == 'GRAVADO' ? 'badge-warning text-dark' : 'badge-primary' }} p-2">
                                                                        DEP: {{ $deptKey }}
                                                                    </span>
                                                                </td>
                                                                @php $firstItem = false; @endphp
                                                            @endif
                                                            <td class="text-uppercase font-weight-bold" style="font-size: 12px;">{{ $item->method }}</td>
                                                            <td class="text-center font-weight-bold" style="font-size: 12px;">{{ strtoupper($item->currency) }}</td>
                                                            @if($showOriginalAmount)
                                                            <td class="text-right" style="font-size: 12px;">{{ number_format($item->total_amount, 2) }}</td>
                                                            @endif
                                                            @if($showExchangeRate)
                                                            <td class="text-right" style="font-size: 12px;">{{ number_format($item->avg_rate, 2) }}</td>
                                                            @endif
                                                            @if($showUsdAmount)
                                                            <td class="text-right font-weight-bold" style="font-size: 12px;">$ {{ number_format($item->total_usd, 2) }}</td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                    <tr class="table-secondary font-weight-bold">
                                                        <td colspan="{{ 2 + ($showOriginalAmount ? 1 : 0) + ($showExchangeRate ? 1 : 0) }}" class="text-right">
                                                            SUBTOTAL {{ $deptKey }}:
                                                        </td>
                                                        @if($showUsdAmount)
                                                        <td class="text-right text-primary font-weight-bold">$ {{ number_format($deptSubtotalUsd, 2) }}</td>
                                                        @endif
                                                    </tr>
                                                @endif
                                            @endforeach
                                            @if(!$hasRows)
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted p-4">
                                                        No hay registros de cobranza para los filtros seleccionados.
                                                    </td>
                                                </tr>
                                            @endif
                                        @else
                                            @if(!empty($condensedData['GENERAL']))
                                                @foreach($condensedData['GENERAL'] as $item)
                                                    <tr>
                                                        <td class="align-middle font-weight-bold">GENERAL</td>
                                                        <td class="text-uppercase font-weight-bold" style="font-size: 12px;">{{ $item->method }}</td>
                                                        <td class="text-center font-weight-bold" style="font-size: 12px;">{{ strtoupper($item->currency) }}</td>
                                                        @if($showOriginalAmount)
                                                        <td class="text-right" style="font-size: 12px;">{{ number_format($item->total_amount, 2) }}</td>
                                                        @endif
                                                        @if($showExchangeRate)
                                                        <td class="text-right" style="font-size: 12px;">{{ number_format($item->avg_rate, 2) }}</td>
                                                        @endif
                                                        @if($showUsdAmount)
                                                        <td class="text-right font-weight-bold" style="font-size: 12px;">$ {{ number_format($item->total_usd, 2) }}</td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            @else
                                                <tr>
                                                    <td colspan="6" class="text-center text-muted p-4">
                                                        No hay registros de cobranza para los filtros seleccionados.
                                                    </td>
                                                </tr>
                                            @endif
                                        @endif
                                    </tbody>
                                    <tfoot style="background-color: #2c2f4a; font-weight: bold;">
                                        <tr>
                                            <td colspan="{{ 3 + ($showOriginalAmount ? 1 : 0) + ($showExchangeRate ? 1 : 0) }}" class="text-right text-white">TOTAL GENERAL COBRADO USD:</td>
                                            @if($showUsdAmount)
                                            <td class="text-right text-success f-16 text-white">${{ number_format($totalGeneralUsd, 2) }}</td>
                                            @endif
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        @else
                            <!-- Tabla Comparativa Detallada -->
                            <h5 class="txt-primary mt-3 mb-2"><i class="fa fa-table"></i> Detalle por Operador</h5>
                            <div class="table-responsive">
                                <table class="table table-bordered mt-1">
                                    <thead class="text-white" style="background: #3b3f5c">
                                        <tr>
                                            <th class="table-th text-white">Operador</th>
                                            <th class="table-th text-white">M&eacute;todo</th>
                                            <th class="table-th text-white text-center">Moneda</th>
                                            @if($showOriginalAmount)
                                            <th class="table-th text-white text-right">Monto Original</th>
                                            @endif
                                            @if($showExchangeRate)
                                            <th class="table-th text-white text-right">Tasa Cambio</th>
                                            @endif
                                            @if($showUsdAmount)
                                            <th class="table-th text-white text-right">Equivalente USD</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($reportData as $sellerName => $sellerData)
                                            @php
                                                $sellerTotalUsd = 0;
                                            @endphp
                                            
                                            @if($splitByDepartment)
                                                @php
                                                    $rowspan = 1; // Para la fila de subtotal
                                                    foreach($sellerData as $deptType => $payments) {
                                                        $rowspan += 1; // Fila separadora del departamento
                                                        $rowspan += $payments->count();
                                                    }
                                                    $firstDept = true;
                                                @endphp
                                                
                                                @foreach($sellerData as $deptType => $payments)
                                                    @php 
                                                        $deptTotalUsd = 0; 
                                                        $firstPayment = true;
                                                    @endphp
                                                    
                                                    <tr>
                                                        @if($firstDept)
                                                            <td rowspan="{{ $rowspan }}" class="align-middle font-weight-bold">
                                                                {{ $sellerName }}
                                                            </td>
                                                            @php $firstDept = false; @endphp
                                                        @endif
                                                        
                                                        <td colspan="{{ 2 + ($showOriginalAmount ? 1 : 0) + ($showExchangeRate ? 1 : 0) + ($showUsdAmount ? 1 : 0) }}" class="bg-light font-weight-bold" style="font-size: 11px;">
                                                            <i class="fa fa-caret-right me-1"></i> DEP: {{ $deptType }}
                                                        </td>
                                                    </tr>
                                                    
                                                    @foreach($payments as $payment)
                                                        @php 
                                                            $sellerTotalUsd += $payment->total_usd; 
                                                            $deptTotalUsd += $payment->total_usd;
                                                        @endphp
                                                        <tr>
                                                            <td class="text-uppercase" style="font-size: 11px;">{{ $payment->method }}</td>
                                                            <td class="text-center" style="font-size: 11px;">{{ strtoupper($payment->currency) }}</td>
                                                            @if($showOriginalAmount)
                                                            <td class="text-right" style="font-size: 12px;">{{ number_format($payment->total_amount, 2) }}</td>
                                                            @endif
                                                            @if($showExchangeRate)
                                                            <td class="text-right" style="font-size: 12px;">{{ number_format($payment->avg_rate, 2) }}</td>
                                                            @endif
                                                            @if($showUsdAmount)
                                                            <td class="text-right" style="font-size: 12px;">$ {{ number_format($payment->total_usd, 2) }}</td>
                                                            @endif
                                                        </tr>
                                                    @endforeach
                                                @endforeach
                                            @else
                                                @php
                                                    $rowspan = $sellerData->count() + 1; 
                                                    $first = true;
                                                @endphp
                                                
                                                @foreach($sellerData as $payment)
                                                    @php $sellerTotalUsd += $payment->total_usd; @endphp
                                                    <tr>
                                                        @if($first)
                                                            <td rowspan="{{ $rowspan }}" class="align-middle font-weight-bold">
                                                                {{ $sellerName }}
                                                            </td>
                                                            @php $first = false; @endphp
                                                        @endif
                                                        <td class="text-uppercase" style="font-size: 11px;">{{ $payment->method }}</td>
                                                        <td class="text-center" style="font-size: 11px;">{{ strtoupper($payment->currency) }}</td>
                                                        @if($showOriginalAmount)
                                                        <td class="text-right" style="font-size: 12px;">{{ number_format($payment->total_amount, 2) }}</td>
                                                        @endif
                                                        @if($showExchangeRate)
                                                        <td class="text-right" style="font-size: 12px;">{{ number_format($payment->avg_rate, 2) }}</td>
                                                        @endif
                                                        @if($showUsdAmount)
                                                        <td class="text-right" style="font-size: 12px;">$ {{ number_format($payment->total_usd, 2) }}</td>
                                                        @endif
                                                    </tr>
                                                @endforeach
                                            @endif
                                            
                                            <!-- Fila de subtotal por vendedor -->
                                            <tr class="bg-light">
                                                <td colspan="2" class="text-right font-weight-bold" style="font-size: 12px;">SUBTOTAL OPERADOR:</td>
                                                @if($showOriginalAmount)<td></td>@endif
                                                @if($showExchangeRate)<td></td>@endif
                                                @if($showUsdAmount)
                                                <td class="text-right font-weight-bold text-success" style="font-size: 13px;">
                                                    $ {{ number_format($sellerTotalUsd, 2) }}
                                                </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted p-4">
                                                    No hay registros de cobranza para los filtros seleccionados.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if($reportData->isNotEmpty())
                                        <tfoot style="background-color: #2c2f4a; font-weight: bold;">
                                            <tr>
                                                <td colspan="{{ 3 + ($showOriginalAmount ? 1 : 0) + ($showExchangeRate ? 1 : 0) }}" class="text-right text-white">TOTAL GENERAL COBRADO USD:</td>
                                                @if($showUsdAmount)
                                                <td class="text-right text-success">${{ number_format($totalGeneralUsd, 2) }}</td>
                                                @endif
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Visor PDF -->
    @if ($showPdfModal)
        <div class="modal fade show" tabindex="-1" role="dialog" style="display: block; background: rgba(0,0,0,0.5); z-index: 1050;">
            <div class="modal-dialog modal-xl" role="document" style="max-width: 90%; height: 90vh; margin: 30px auto;">
                <div class="modal-content" style="height: 100%;">
                    <div class="modal-header bg-dark p-2 text-white d-flex justify-content-between align-items-center">
                        <h5 class="modal-title text-white mb-0">
                            <i class="fas fa-file-pdf"></i> Vista Previa — Cobranza por Operador
                        </h5>
                        <div class="d-flex align-items-center gap-2">
                            <a href="{{ $pdfUrl }}" target="_blank" class="btn btn-sm btn-outline-light me-2">
                                <i class="fa fa-download"></i> Descargar
                            </a>
                            <button type="button" class="close text-white" wire:click.prevent="closePdfPreview" aria-label="Close" style="outline: none;">
                                <span aria-hidden="true" style="font-size: 24px;">&times;</span>
                            </button>
                        </div>
                    </div>
                    <div class="modal-body p-0" style="height: calc(100% - 55px); overflow: hidden;">
                        <iframe src="{{ $pdfUrl }}" style="width: 100%; height: 100%; border: none;"></iframe>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
