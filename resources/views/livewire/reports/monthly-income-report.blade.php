<div>
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold text-dark"><i class="fas fa-calendar-alt text-primary mr-2"></i>Reporte Mensual de Ingresos</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button wire:click="previousMonth" class="btn btn-outline-primary mr-2"><i class="fas fa-chevron-left mr-1"></i> Anterior</button>
                    <input type="month" wire:model.live="selectedMonth" class="form-control d-inline-block w-auto mr-2" style="vertical-align: middle;">
                    <button wire:click="nextMonth" class="btn btn-outline-primary mr-2">Siguiente <i class="fas fa-chevron-right ml-1"></i></button>
                    
                    <a href="{{ route('reports.monthly.income.pdf', ['month' => $selectedMonth]) }}" target="_blank" class="btn btn-danger font-weight-bold shadow-sm">
                        <i class="fas fa-file-pdf mr-1"></i> Exportar PDF
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main content -->
    <div class="content">
        <div class="container-fluid">
            <!-- Alert for Preliminar / Audit status -->
            <div class="row mb-3">
                <div class="col-12">
                    @if($isPreliminar)
                        <div class="alert alert-warning border-0 shadow-sm d-flex align-items-center mb-0" style="border-radius: 8px;">
                            <i class="fas fa-exclamation-triangle fa-lg mr-3"></i>
                            <div>
                                <strong>REPORTE PRELIMINAR (EN CURSO)</strong>
                                <br>
                                Este reporte corresponde a un mes en progreso o contiene planillas de cobro que aún no han sido cerradas. Los montos mostrados pueden variar en tiempo real.
                            </div>
                            <span class="badge badge-warning ml-auto px-3 py-2 text-uppercase font-weight-bold shadow-sm">Borrador</span>
                        </div>
                    @else
                        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-0" style="border-radius: 8px;">
                            <i class="fas fa-check-circle fa-lg mr-3"></i>
                            <div>
                                <strong>REPORTE CONSOLIDADO / AUDITADO</strong>
                                <br>
                                Todas las planillas de cobro de las semanas de este mes han sido conciliadas y cerradas. Los datos presentados son definitivos.
                            </div>
                            <span class="badge badge-success ml-auto px-3 py-2 text-uppercase font-weight-bold shadow-sm">Auditado</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Title Header like Excel -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 10px; overflow: hidden;">
                <div class="bg-primary py-3 text-center text-white">
                    <h3 class="m-0 text-uppercase font-weight-bold" style="letter-spacing: 1.5px;">Consolidado Mensual de Ingresos</h3>
                    <h5 class="m-0 font-weight-light">{{ $monthLabel }}</h5>
                </div>
            </div>

            <!-- Matrix Spreadsheet Table -->
            <div class="card shadow-sm border-light" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-bordered m-0 text-center" style="font-size: 12px; min-width: 1000px;">
                            <thead>
                                <!-- Top Row Header: Weeks -->
                                <tr style="background-color: #5B9BD5; color: #white;">
                                    <th rowspan="2" class="align-middle text-white text-left pl-3" style="width: 200px; background-color: #2F5597; border-color: #2F5597;">MÉTODO / BANCO</th>
                                    @foreach($weeks as $wKey => $week)
                                        <th colspan="2" class="text-white align-middle" style="border-color: #2F5597;">
                                            {{ $week['label'] }}
                                        </th>
                                    @endforeach
                                    <th colspan="2" class="text-white align-middle" style="background-color: #1F4E79; border-color: #1F4E79;">
                                        TOTAL MES
                                    </th>
                                </tr>
                                <!-- Sub Row Header: Contado / Cobranza -->
                                <tr style="background-color: #F2F2F2; color: #333; font-weight: bold;">
                                    @foreach($weeks as $wKey => $week)
                                        <th style="width: 90px; font-size: 11px;">CONTADO</th>
                                        <th style="width: 90px; font-size: 11px;">COBRANZA</th>
                                    @endforeach
                                    <th style="width: 100px; font-size: 11px; background-color: #E2EFDA; color: #375623;">CONTADO</th>
                                    <th style="width: 100px; font-size: 11px; background-color: #FCE4D6; color: #c65911;">COBRANZA</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Categories Rows -->
                                @foreach($report as $catName => $weekData)
                                    <tr>
                                        <td class="text-left pl-3 font-weight-bold" style="color: #2F5597; background-color: #FAFBFD;">{{ $catName }}</td>
                                        @foreach($weeks as $wKey => $week)
                                            @php $amt = $weekData[$wKey]; @endphp
                                            <td class="{{ $amt['contado'] > 0 ? 'text-dark' : 'text-muted' }}">
                                                {{ $amt['contado'] > 0 ? number_format($amt['contado'], 2, ',', '.') : '0,00' }}
                                            </td>
                                            <td class="{{ $amt['cobranza'] > 0 ? 'text-dark' : 'text-muted' }}">
                                                {{ $amt['cobranza'] > 0 ? number_format($amt['cobranza'], 2, ',', '.') : '0,00' }}
                                            </td>
                                        @endforeach
                                        <!-- Monthly totals for category -->
                                        <td class="font-weight-bold {{ $monthlyTotals[$catName]['contado'] > 0 ? 'text-success' : 'text-muted' }}" style="background-color: #F2F9EE;">
                                            {{ $monthlyTotals[$catName]['contado'] > 0 ? number_format($monthlyTotals[$catName]['contado'], 2, ',', '.') : '0,00' }}
                                        </td>
                                        <td class="font-weight-bold {{ $monthlyTotals[$catName]['cobranza'] > 0 ? 'text-danger' : 'text-muted' }}" style="background-color: #FFF2EB;">
                                            {{ $monthlyTotals[$catName]['cobranza'] > 0 ? number_format($monthlyTotals[$catName]['cobranza'], 2, ',', '.') : '0,00' }}
                                        </td>
                                    </tr>
                                @endforeach

                                <!-- Subtotal Recibido Row -->
                                <tr class="text-white font-weight-bold" style="background-color: #2F5597;">
                                    <td class="text-left pl-3">SUBTOTAL RECIBIDO</td>
                                    @foreach($weeks as $wKey => $week)
                                        <td>{{ number_format($weeklyMetrics[$wKey]['subtotal_contado'], 2, ',', '.') }}</td>
                                        <td>{{ number_format($weeklyMetrics[$wKey]['subtotal_cobranza'], 2, ',', '.') }}</td>
                                    @endforeach
                                    <td style="background-color: #1F4E79;">{{ number_format($monthlySubtotalContado, 2, ',', '.') }}</td>
                                    <td style="background-color: #1F4E79;">{{ number_format($monthlySubtotalCobranza, 2, ',', '.') }}</td>
                                </tr>

                                <!-- Ventas a Crédito Row -->
                                <tr class="font-weight-bold" style="background-color: #D9E1F2; color: #1F4E79;">
                                    <td class="text-left pl-3">VENTAS A CRÉDITO</td>
                                    @foreach($weeks as $wKey => $week)
                                        <td>{{ number_format($weeklyMetrics[$wKey]['ventas_credito'], 2, ',', '.') }}</td>
                                        <td class="text-muted">0,00</td>
                                    @endforeach
                                    <td style="background-color: #B4C6E7; color: #1F4E79;">{{ number_format($monthlyCreditTotal, 2, ',', '.') }}</td>
                                    <td class="text-muted" style="background-color: #B4C6E7;">0,00</td>
                                </tr>

                                <!-- Ventas + Créditos Row -->
                                <tr class="text-white font-weight-bold" style="background-color: #1F4E79;">
                                    <td class="text-left pl-3">VENTAS + CRÉDITOS</td>
                                    @foreach($weeks as $wKey => $week)
                                        <td>{{ number_format($weeklyMetrics[$wKey]['ventas_mas_credito'], 2, ',', '.') }}</td>
                                        <td>{{ number_format($weeklyMetrics[$wKey]['subtotal_cobranza'], 2, ',', '.') }}</td>
                                    @endforeach
                                    <td style="background-color: #16365C;">{{ number_format($monthlyVentasMasCredito, 2, ',', '.') }}</td>
                                    <td style="background-color: #16365C;">{{ number_format($monthlySubtotalCobranza, 2, ',', '.') }}</td>
                                </tr>

                                <!-- Total General Row -->
                                <tr class="font-weight-bold" style="background-color: #D9D9D9; color: #333;">
                                    <td class="text-left pl-3">TOTAL GENERAL</td>
                                    @foreach($weeks as $wKey => $week)
                                        <td colspan="2" class="text-center">{{ number_format($weeklyMetrics[$wKey]['total_general'], 2, ',', '.') }}</td>
                                    @endforeach
                                    <td colspan="2" class="text-center" style="background-color: #AEAAAA; font-size: 13px;">{{ number_format($monthlyTotalGeneral, 2, ',', '.') }}</td>
                                </tr>

                                <!-- Total Recibido Row -->
                                <tr class="text-white font-weight-bold" style="background-color: #4472C4;">
                                    <td class="text-left pl-3">TOTAL RECIBIDO</td>
                                    @foreach($weeks as $wKey => $week)
                                        <td colspan="2" class="text-center">{{ number_format($weeklyMetrics[$wKey]['total_recibido'], 2, ',', '.') }}</td>
                                    @endforeach
                                    <td colspan="2" class="text-center" style="background-color: #2F5597; font-size: 14px; letter-spacing: 0.5px;">{{ number_format($monthlyTotalRecibido, 2, ',', '.') }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
