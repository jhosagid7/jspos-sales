<div>
    <!-- Content Header (Page header) -->
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2 align-items-center">
                <div class="col-sm-6">
                    <h1 class="m-0 font-weight-bold text-dark"><i class="fas fa-chart-line text-primary mr-2"></i>Reporte Semanal de Ingresos</h1>
                </div>
                <div class="col-sm-6 text-right">
                    <button wire:click="previousWeek" class="btn btn-outline-primary mr-2"><i class="fas fa-chevron-left mr-1"></i> Anterior</button>
                    <input type="date" wire:model.live="selectedDate" class="form-control d-inline-block w-auto mr-2" style="vertical-align: middle;">
                    <button wire:click="nextWeek" class="btn btn-outline-primary mr-2">Siguiente <i class="fas fa-chevron-right ml-1"></i></button>
                    
                    <a href="{{ route('reports.weekly.income.pdf', ['date' => $selectedDate]) }}" target="_blank" class="btn btn-danger font-weight-bold shadow-sm">
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
                                Este reporte corresponde a una semana en progreso o contiene planillas de cobro que aún no han sido cerradas. Los montos mostrados pueden variar en tiempo real.
                            </div>
                            <span class="badge badge-warning ml-auto px-3 py-2 text-uppercase font-weight-bold shadow-sm">Borrador</span>
                        </div>
                    @else
                        <div class="alert alert-success border-0 shadow-sm d-flex align-items-center mb-0" style="border-radius: 8px;">
                            <i class="fas fa-check-circle fa-lg mr-3"></i>
                            <div>
                                <strong>REPORTE CONSOLIDADO / AUDITADO</strong>
                                <br>
                                Todas las planillas de cobro de esta semana han sido conciliadas y cerradas. Los datos presentados son definitivos.
                            </div>
                            <span class="badge badge-success ml-auto px-3 py-2 text-uppercase font-weight-bold shadow-sm">Auditado</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Title Header like Excel -->
            <div class="card shadow-sm border-0 mb-4" style="border-radius: 10px; overflow: hidden;">
                <div class="bg-primary py-3 text-center text-white">
                    <h3 class="m-0 text-uppercase font-weight-bold" style="letter-spacing: 1.5px;">Reporte de Ingresos</h3>
                    <h5 class="m-0 font-weight-light">{{ $weekLabel }}</h5>
                </div>
            </div>

            <!-- Grid of Days -->
            <div class="row">
                @foreach($report as $dayName => $day)
                    <div class="col-xl-6 col-lg-12 mb-4">
                        <div class="card h-100 shadow-sm border-light" style="border-radius: 8px; overflow: hidden;">
                            <div class="py-2 px-3 text-white font-weight-bold text-center" style="background-color: #5B9BD5; border-bottom: 2px solid #2F5597;">
                                {{ $dayName }} ({{ \Carbon\Carbon::parse($day['date'])->format('d/m/Y') }})
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-bordered table-sm m-0 text-center" style="font-size: 13px;">
                                        <thead>
                                            <tr style="background-color: #F2F2F2; color: #333; font-weight: bold;">
                                                <th class="text-left pl-3" style="width: 40%;">MÉTODO / BANCO</th>
                                                <th style="width: 30%;">CONTADO</th>
                                                <th style="width: 30%;">COBRANZA</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($day['data'] as $catName => $amounts)
                                                <tr>
                                                    <td class="text-left pl-3 font-weight-bold" style="color: #555;">{{ $catName }}</td>
                                                    <td class="{{ $amounts['contado'] > 0 ? 'text-dark font-weight-normal' : 'text-muted' }}">
                                                        {{ $amounts['contado'] > 0 ? number_format($amounts['contado'], 2, ',', '.') : '0,00' }}
                                                    </td>
                                                    <td class="{{ $amounts['cobranza'] > 0 ? 'text-dark font-weight-normal' : 'text-muted' }}">
                                                        {{ $amounts['cobranza'] > 0 ? number_format($amounts['cobranza'], 2, ',', '.') : '0,00' }}
                                                    </td>
                                                </tr>
                                            @endforeach
                                            
                                            <!-- Subtotal Recibido -->
                                            <tr class="text-white font-weight-bold" style="background-color: #2F5597;">
                                                <td class="text-left pl-3">SUBTOTAL RECIBIDO</td>
                                                <td>{{ number_format($day['subtotal_contado'], 2, ',', '.') }}</td>
                                                <td>{{ number_format($day['subtotal_cobranza'], 2, ',', '.') }}</td>
                                            </tr>

                                            <!-- Ventas a Crédito -->
                                            <tr class="font-weight-bold" style="background-color: #D9E1F2; color: #1F4E79;">
                                                <td class="text-left pl-3">VENTAS A CRÉDITO</td>
                                                <td>{{ number_format($day['ventas_credito'], 2, ',', '.') }}</td>
                                                <td class="text-muted">0,00</td>
                                            </tr>

                                            <!-- Ventas + Créditos -->
                                            <tr class="text-white font-weight-bold" style="background-color: #1F4E79;">
                                                <td class="text-left pl-3">VENTAS + CRÉDITOS</td>
                                                <td>{{ number_format($day['ventas_mas_credito'], 2, ',', '.') }}</td>
                                                <td>{{ number_format($day['subtotal_cobranza'], 2, ',', '.') }}</td>
                                            </tr>

                                            <!-- Total General -->
                                            <tr class="font-weight-bold" style="background-color: #D9D9D9; color: #333;">
                                                <td class="text-left pl-3">TOTAL GENERAL</td>
                                                <td colspan="2" class="text-center">{{ number_format($day['total_general'], 2, ',', '.') }}</td>
                                            </tr>

                                            <!-- Total Recibido -->
                                            <tr class="text-white font-weight-bold" style="background-color: #4472C4;">
                                                <td class="text-left pl-3">TOTAL RECIBIDO</td>
                                                <td colspan="2" class="text-center">{{ number_format($day['total_recibido'], 2, ',', '.') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach

                <!-- Total Semana Card -->
                <div class="col-xl-6 col-lg-12 mb-4">
                    <div class="card h-100 shadow border-primary" style="border-radius: 8px; overflow: hidden; border-width: 2px;">
                        <div class="py-2 px-3 text-white font-weight-bold text-center bg-primary" style="border-bottom: 2px solid #1F4E79;">
                            TOTAL SEMANA
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm m-0 text-center" style="font-size: 13px;">
                                    <thead>
                                        <tr style="background-color: #D9E1F2; color: #1F4E79; font-weight: bold;">
                                            <th class="text-left pl-3" style="width: 40%;">MÉTODO / BANCO</th>
                                            <th style="width: 30%;">CONTADO</th>
                                            <th style="width: 30%;">COBRANZA</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($weeklyTotals as $catName => $amounts)
                                            <tr style="background-color: #FAFBFD;">
                                                <td class="text-left pl-3 font-weight-bold" style="color: #2F5597;">{{ $catName }}</td>
                                                <td class="{{ $amounts['contado'] > 0 ? 'text-dark font-weight-bold' : 'text-muted' }}">
                                                    {{ $amounts['contado'] > 0 ? number_format($amounts['contado'], 2, ',', '.') : '0,00' }}
                                                </td>
                                                <td class="{{ $amounts['cobranza'] > 0 ? 'text-dark font-weight-bold' : 'text-muted' }}">
                                                    {{ $amounts['cobranza'] > 0 ? number_format($amounts['cobranza'], 2, ',', '.') : '0,00' }}
                                                </td>
                                            </tr>
                                        @endforeach
                                        
                                        <!-- Subtotal Recibido -->
                                        <tr class="text-white font-weight-bold" style="background-color: #2F5597;">
                                            <td class="text-left pl-3">SUBTOTAL RECIBIDO</td>
                                            <td>{{ number_format($weeklySubtotalContado, 2, ',', '.') }}</td>
                                            <td>{{ number_format($weeklySubtotalCobranza, 2, ',', '.') }}</td>
                                        </tr>

                                        <!-- Ventas a Crédito -->
                                        <tr class="font-weight-bold" style="background-color: #D9E1F2; color: #1F4E79;">
                                            <td class="text-left pl-3">VENTAS A CRÉDITO</td>
                                            <td>{{ number_format($weeklyCreditTotal, 2, ',', '.') }}</td>
                                            <td class="text-muted">0,00</td>
                                        </tr>

                                        <!-- Ventas + Créditos -->
                                        <tr class="text-white font-weight-bold" style="background-color: #1F4E79;">
                                            <td class="text-left pl-3">VENTAS + CRÉDITOS</td>
                                            <td>{{ number_format($weeklyVentasMasCredito, 2, ',', '.') }}</td>
                                            <td>{{ number_format($weeklySubtotalCobranza, 2, ',', '.') }}</td>
                                        </tr>

                                        <!-- Total General -->
                                        <tr class="font-weight-bold" style="background-color: #D9D9D9; color: #333; font-size: 14px;">
                                            <td class="text-left pl-3">TOTAL GENERAL</td>
                                            <td colspan="2" class="text-center">{{ number_format($weeklyTotalGeneral, 2, ',', '.') }}</td>
                                        </tr>

                                        <!-- Total Recibido -->
                                        <tr class="text-white font-weight-bold shadow-sm" style="background-color: #4472C4; font-size: 15px;">
                                            <td class="text-left pl-3" style="letter-spacing: 0.5px;">TOTAL RECIBIDO</td>
                                            <td colspan="2" class="text-center font-weight-black">{{ number_format($weeklyTotalRecibido, 2, ',', '.') }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
