<div>
    <div class="row layout-top-spacing">
        <!-- Panel de Filtros -->
        <div class="col-12 layout-spacing">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white p-3">
                    <h5 class="mb-0 text-white"><i class="fas fa-filter mr-2"></i> Opciones de Consulta y Filtros</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Clientes -->
                        <div class="col-sm-12 col-md-3 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Cliente</label>
                            <div wire:ignore>
                                <select id="selectCustomer" class="form-control form-control-sm">
                                    <option value="0">Todos los Clientes</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Vendedores -->
                        <div class="col-sm-12 col-md-3 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Vendedor</label>
                            <div wire:ignore>
                                <select id="selectSeller" class="form-control form-control-sm">
                                    <option value="0">Todos los Vendedores</option>
                                    @foreach($sellers as $seller)
                                        <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Rango de Fechas (Para análisis de cobros) -->
                        <div class="col-sm-12 col-md-3 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Periodo Desde</label>
                            <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-12 col-md-3 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Periodo Hasta</label>
                            <input type="date" wire:model.live="dateTo" class="form-control form-control-sm">
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="row mt-3">
                        <div class="col-12 text-right">
                            <span wire:loading wire:target="openPdfPreview" class="mr-3 text-muted"><i class="fas fa-spinner fa-spin"></i> Procesando...</span>
                            <button wire:click="searchData" class="btn btn-primary btn-sm">
                                <i class="fas fa-chart-line"></i> Analizar Flujo
                            </button>
                            <button wire:click="openPdfPreview" class="btn btn-danger btn-sm ml-2" @if(!$showReport) disabled @endif>
                                <i class="fas fa-file-pdf"></i> Reporte PDF
                            </button>
                            <button wire:click="toggleInterpretationModal" class="btn btn-info btn-sm ml-2" @if(!$showReport) disabled @endif style="background-color: #17a2b8; border-color: #17a2b8;">
                                <i class="fas fa-brain"></i> Analizar Resultados (IA)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        @if($showReport)
        <!-- Tarjetas de KPIs -->
        <div class="col-12 layout-spacing">
            <div class="row">
                <!-- KPI 1: Deuda Total Pendiente (USD) -->
                <div class="col-sm-12 col-md-2 mb-3">
                    <div class="card shadow-sm border-left border-dark h-100" style="cursor: help;" title="Suma total de saldos pendientes de cobrar de todas las facturas a crédito.">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Deuda Total</div>
                                <div class="bg-dark-light p-1 rounded-circle"><i class="fas fa-hand-holding-usd text-dark"></i></div>
                            </div>
                            <div class="f-18 font-weight-bold text-dark mt-2">${{ number_format($metrics['totalDebt'], 2) }}</div>
                            <div class="f-10 text-muted mt-1">Total por cobrar en calle</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 2: Cartera Corriente (USD) -->
                <div class="col-sm-12 col-md-2 mb-3">
                    <div class="card shadow-sm border-left border-primary h-100" style="cursor: help;" title="Deuda pendiente que se encuentra dentro del plazo de crédito otorgado al cliente (todavía no ha vencido).">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Cartera Corriente</div>
                                <div class="bg-primary-light p-1 rounded-circle"><i class="fas fa-check-double text-primary"></i></div>
                            </div>
                            <div class="f-18 font-weight-bold text-primary mt-2">${{ number_format($metrics['currentDebt'], 2) }}</div>
                            <div class="f-10 text-muted mt-1">Créditos al día</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 3: Cartera Vencida (USD) -->
                <div class="col-sm-12 col-md-2 mb-3">
                    <div class="card shadow-sm border-left border-danger h-100" style="cursor: help;" title="Deuda pendiente que ha superado la fecha límite de vencimiento de crédito (atrasada).">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Cartera Vencida</div>
                                <div class="bg-danger-light p-1 rounded-circle"><i class="fas fa-exclamation-circle text-danger"></i></div>
                            </div>
                            <div class="f-18 font-weight-bold text-danger mt-2">${{ number_format($metrics['overdueDebt'], 2) }}</div>
                            <div class="f-10 text-muted mt-1">Monto en mora/atrasado</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 4: Cobros Realizados (USD) -->
                <div class="col-sm-12 col-md-2 mb-3">
                    <div class="card shadow-sm border-left border-success h-100" style="cursor: help;" title="Total de cobros/abonos recibidos en el período de consulta seleccionado.">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Cobros Realizados</div>
                                <div class="bg-success-light p-1 rounded-circle"><i class="fas fa-cash-register text-success"></i></div>
                            </div>
                            <div class="f-18 font-weight-bold text-success mt-2">${{ number_format($metrics['totalCollected'], 2) }}</div>
                            <div class="f-10 text-muted mt-1">Efectivo recuperado</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 5: Eficiencia de Cobranza (CEI %) -->
                <div class="col-sm-12 col-md-2 mb-3">
                    @php
                        $cei = $metrics['cei'];
                        $ceiBadge = 'success';
                        if ($cei < 70) $ceiBadge = 'danger';
                        elseif ($cei < 85) $ceiBadge = 'warning';
                    @endphp
                    <div class="card shadow-sm border-left border-warning h-100" style="cursor: help;" title="Índice de Eficiencia de Cobranza (CEI): Mide la eficacia de recuperar la cartera cobrable (Cobrado / Cobrado + Vencido).">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Eficiencia (CEI)</div>
                                <div class="bg-warning-light p-1 rounded-circle"><i class="fas fa-percentage text-warning"></i></div>
                            </div>
                            <div class="f-18 font-weight-bold text-{{ $ceiBadge }} mt-2">{{ number_format($cei, 1) }}%</div>
                            <div class="f-10 text-muted mt-1">Tasa de cobro exigible</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 6: Atraso Promedio (DSO) -->
                <div class="col-sm-12 col-md-2 mb-3">
                    <div class="card shadow-sm border-left border-info h-100" style="cursor: help;" title="Días promedio de atraso ponderados por el monto de la deuda vencida. Muestra la lentitud promedio en el cobro.">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Atraso Promed.</div>
                                <div class="bg-info-light p-1 rounded-circle"><i class="fas fa-calendar-alt text-info"></i></div>
                            </div>
                            <div class="f-18 font-weight-bold text-info mt-2">{{ number_format($metrics['dso'], 1) }} días</div>
                            <div class="f-10 text-muted mt-1">Días de mora ponderada</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Fichas de Antigüedad de Cartera (Ageing Buckets) -->
        <div class="col-12 layout-spacing">
            <h5 class="txt-primary font-weight-bold mb-3"><i class="fas fa-boxes mr-1"></i> Distribución de Cartera por Antigüedad de Vencimiento</h5>
            <div class="row">
                <!-- Vencido Crítico -->
                <div class="col-sm-12 col-md-2 mb-2" wire:click="selectBucket('vencido_critico')" style="cursor: pointer;">
                    <div class="card p-3 text-center h-100 {{ $selectedBucket === 'vencido_critico' ? 'shadow-sm' : 'shadow-none' }}" 
                         style="background-color: #fdf3f4; border: {{ $selectedBucket === 'vencido_critico' ? '2.5px solid #dc3545' : '1px solid #f5c6cb' }}; transform: {{ $selectedBucket === 'vencido_critico' ? 'scale(1.05)' : 'none' }}; transition: all 0.2s;">
                        <span class="f-11 text-danger font-weight-bold text-uppercase">Vencido Crítico (>15d)</span>
                        <div class="f-18 font-weight-bold text-danger mt-2">${{ number_format($metrics['buckets']['vencido_critico'], 2) }}</div>
                    </div>
                </div>
                <!-- Vencido Medio -->
                <div class="col-sm-12 col-md-2 mb-2" wire:click="selectBucket('vencido_8_15')" style="cursor: pointer;">
                    <div class="card p-3 text-center h-100 {{ $selectedBucket === 'vencido_8_15' ? 'shadow-sm' : 'shadow-none' }}" 
                         style="background-color: #fff9e6; border: {{ $selectedBucket === 'vencido_8_15' ? '2.5px solid #ffc107' : '1px solid #ffeeba' }}; transform: {{ $selectedBucket === 'vencido_8_15' ? 'scale(1.05)' : 'none' }}; transition: all 0.2s;">
                        <span class="f-11 text-warning font-weight-bold text-uppercase" style="color: #856404 !important;">Vencido Medio (8-15d)</span>
                        <div class="f-18 font-weight-bold mt-2" style="color: #856404 !important;">${{ number_format($metrics['buckets']['vencido_8_15'], 2) }}</div>
                    </div>
                </div>
                <!-- Vencido Leve -->
                <div class="col-sm-12 col-md-2 mb-2" wire:click="selectBucket('vencido_1_7')" style="cursor: pointer;">
                    <div class="card p-3 text-center h-100 {{ $selectedBucket === 'vencido_1_7' ? 'shadow-sm' : 'shadow-none' }}" 
                         style="background-color: #fffaf0; border: {{ $selectedBucket === 'vencido_1_7' ? '2.5px solid #fd7e14' : '1px solid #ffe8cc' }}; transform: {{ $selectedBucket === 'vencido_1_7' ? 'scale(1.05)' : 'none' }}; transition: all 0.2s;">
                        <span class="f-11 text-warning font-weight-bold text-uppercase" style="color: #dd7a01 !important;">Vencido Leve (1-7d)</span>
                        <div class="f-18 font-weight-bold mt-2" style="color: #dd7a01 !important;">${{ number_format($metrics['buckets']['vencido_1_7'], 2) }}</div>
                    </div>
                </div>
                <!-- Por Vencer Corto -->
                <div class="col-sm-12 col-md-2 mb-2" wire:click="selectBucket('corriente_1_7')" style="cursor: pointer;">
                    <div class="card p-3 text-center h-100 {{ $selectedBucket === 'corriente_1_7' ? 'shadow-sm' : 'shadow-none' }}" 
                         style="background-color: #f4f8fd; border: {{ $selectedBucket === 'corriente_1_7' ? '2.5px solid #007bff' : '1px solid #b8daff' }}; transform: {{ $selectedBucket === 'corriente_1_7' ? 'scale(1.05)' : 'none' }}; transition: all 0.2s;">
                        <span class="f-11 text-primary font-weight-bold text-uppercase">Por Vencer (1-7d)</span>
                        <div class="f-18 font-weight-bold text-primary mt-2">${{ number_format($metrics['buckets']['corriente_1_7'], 2) }}</div>
                    </div>
                </div>
                <!-- Por Vencer Medio -->
                <div class="col-sm-12 col-md-2 mb-2" wire:click="selectBucket('corriente_8_14')" style="cursor: pointer;">
                    <div class="card p-3 text-center h-100 {{ $selectedBucket === 'corriente_8_14' ? 'shadow-sm' : 'shadow-none' }}" 
                         style="background-color: #f0fbfc; border: {{ $selectedBucket === 'corriente_8_14' ? '2.5px solid #17a2b8' : '1px solid #bee5eb' }}; transform: {{ $selectedBucket === 'corriente_8_14' ? 'scale(1.05)' : 'none' }}; transition: all 0.2s;">
                        <span class="f-11 text-info font-weight-bold text-uppercase" style="color: #0c5460 !important;">Por Vencer (8-14d)</span>
                        <div class="f-18 font-weight-bold mt-2" style="color: #0c5460 !important;">${{ number_format($metrics['buckets']['corriente_8_14'], 2) }}</div>
                    </div>
                </div>
                <!-- Por Vencer Largo -->
                <div class="col-sm-12 col-md-2 mb-2" wire:click="selectBucket('corriente_largo')" style="cursor: pointer;">
                    <div class="card p-3 text-center h-100 {{ $selectedBucket === 'corriente_largo' ? 'shadow-sm' : 'shadow-none' }}" 
                         style="background-color: #f3faf4; border: {{ $selectedBucket === 'corriente_largo' ? '2.5px solid #28a745' : '1px solid #c3e6cb' }}; transform: {{ $selectedBucket === 'corriente_largo' ? 'scale(1.05)' : 'none' }}; transition: all 0.2s;">
                        <span class="f-11 text-success font-weight-bold text-uppercase" style="color: #155724 !important;">Por Vencer (>14d)</span>
                        <div class="f-18 font-weight-bold mt-2" style="color: #155724 !important;">${{ number_format($metrics['buckets']['corriente_largo'], 2) }}</div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Gráfico Highcharts (Real vs Proyectado) -->
        <div class="col-12 layout-spacing {{ $showReport ? '' : 'd-none' }}">
            <div class="card shadow-sm border-0">
                <div class="card-body" wire:ignore>
                    <div id="cashFlowChart" style="height: 320px; width: 100%;"></div>
                </div>
            </div>
        </div>

        @if($showReport)
        <!-- Tabla de Datos -->
        <div class="col-12 layout-spacing">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white p-3 d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-white"><i class="fas fa-table mr-2"></i> Detalle de Cuentas por Cobrar Pendientes</h5>
                    @if($selectedBucket !== 'all')
                        <div>
                            <span class="badge badge-info p-2 mr-2">
                                <i class="fas fa-filter mr-1"></i> Filtrado por: 
                                <strong>
                                    @if($selectedBucket === 'vencido_critico') Vencido Crítico (>15d)
                                    @elseif($selectedBucket === 'vencido_8_15') Vencido Medio (8-15d)
                                    @elseif($selectedBucket === 'vencido_1_7') Vencido Leve (1-7d)
                                    @elseif($selectedBucket === 'corriente_1_7') Por Vencer (1-7d)
                                    @elseif($selectedBucket === 'corriente_8_14') Por Vencer (8-14d)
                                    @elseif($selectedBucket === 'corriente_largo') Por Vencer (>14d)
                                    @endif
                                </strong>
                            </span>
                            <button wire:click="$set('selectedBucket', 'all')" class="btn btn-sm btn-outline-light py-1 px-2">
                                <i class="fas fa-times-circle"></i> Limpiar Filtro
                            </button>
                        </div>
                    @endif
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <thead class="bg-dark text-white text-center">
                                <tr>
                                    <th class="text-white" style="cursor: pointer;" wire:click="sortBy('invoice_number')">
                                        Factura {!! $sortField === 'invoice_number' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' !!}
                                    </th>
                                    <th class="text-white" style="cursor: pointer;" wire:click="sortBy('created_at')">
                                        F. Emisión {!! $sortField === 'created_at' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' !!}
                                    </th>
                                    <th class="text-white" style="cursor: pointer;" wire:click="sortBy('due_date')">
                                        F. Vencimiento {!! $sortField === 'due_date' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' !!}
                                    </th>
                                    <th class="text-white">Cliente</th>
                                    <th class="text-white">Vendedor</th>
                                    <th class="text-white">Plazo (Días)</th>
                                    <th class="text-white text-right">Monto Total (USD)</th>
                                    <th class="text-white text-right">Saldo Deuda (USD)</th>
                                    <th class="text-white" style="cursor: pointer;" wire:click="sortBy('days_diff')">
                                        Días de Mora / Atraso {!! $sortField === 'days_diff' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' !!}
                                    </th>
                                    <th class="text-white">Estatus</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sales as $s)
                                    <tr>
                                        <td class="text-center font-weight-bold">
                                            @if($s['invoice_number'])
                                                F-{{ str_pad($s['invoice_number'], 6, '0', STR_PAD_LEFT) }}
                                            @else
                                                #{{ $s['sale_id'] }}
                                            @endif
                                        </td>
                                        <td class="text-center">{{ \Carbon\Carbon::parse($s['created_at'])->format('d/m/Y') }}</td>
                                        <td class="text-center font-weight-bold text-primary">{{ \Carbon\Carbon::parse($s['due_date'])->format('d/m/Y') }}</td>
                                        <td>{{ $s['customer_name'] }}</td>
                                        <td>{{ $s['seller_name'] }}</td>
                                        <td class="text-center font-weight-bold text-muted">{{ $s['credit_days'] }} d</td>
                                        <td class="text-right font-weight-bold">${{ number_format($s['total_usd'], 2) }}</td>
                                        <td class="text-right font-weight-bold text-danger">${{ number_format($s['debt_usd'], 2) }}</td>
                                        <td class="text-center font-weight-bold">
                                            @if($s['days_diff'] > 0)
                                                <span class="text-danger"><i class="fas fa-caret-up mr-1"></i>+{{ $s['days_diff'] }} días</span>
                                            @elseif($s['days_diff'] < 0)
                                                <span class="text-success"><i class="fas fa-caret-down mr-1"></i>{{ $s['days_diff'] }} días</span>
                                            @else
                                                <span class="text-warning">Vence hoy</span>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($s['bucket'] === 'vencido_critico')
                                                <span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i> Crítico</span>
                                            @elseif($s['status'] === 'vencido')
                                                <span class="badge badge-warning px-2 py-1" style="color: #fff; background-color: #fd7e14;"><i class="fas fa-exclamation-triangle mr-1"></i> Vencida</span>
                                            @else
                                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Corriente</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                                            <p class="mb-0">No se encontraron cuentas por cobrar pendientes para los filtros seleccionados.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div class="text-muted f-12">
                            Mostrando {{ $sales->count() }} de {{ $sales->total() }} registros de deudas
                        </div>
                        <div>
                            {{ $sales->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Modal de Interpretación Analítica -->
    @if($showInterpretationModal)
    <div class="modal show d-block" style="background: rgba(0,0,0,0.6); z-index: 9999;" tabindex="-1" role="dialog" wire:key="cashflow-interpretation-modal">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content shadow-lg border-0">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white font-weight-bold"><i class="fas fa-brain mr-2 text-info"></i> Interpretador de Resultados Analíticos</h5>
                    <button type="button" class="close text-white" wire:click="toggleInterpretationModal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 bg-white" style="max-height: 70vh; overflow-y: auto;">
                    {!! $this->getInterpretation() !!}
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" wire:click="toggleInterpretationModal"><i class="fas fa-times mr-1"></i> Cerrar Análisis</button>
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Modal Visor PDF -->
    @if($showPdfModal)
    <div class="modal show d-block" style="background: rgba(0,0,0,0.6); z-index: 9999;" tabindex="-1" role="dialog" wire:key="cashflow-pdf-modal">
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document" style="max-width: 90%; height: 90vh;">
            <div class="modal-content style-full" style="height: 100%;">
                <div class="modal-header bg-dark text-white p-2">
                    <h5 class="modal-title text-white mb-0 font-weight-bold"><i class="fas fa-file-pdf mr-2"></i> Vista Previa Reporte de Flujo de Caja y Cobranza</h5>
                    <button type="button" class="close text-white" wire:click="closePdfPreview" aria-label="Close">
                        <span aria-hidden="true" style="font-size: 24px;">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0" style="height: calc(100% - 50px); overflow: hidden;">
                    <iframe src="{{ $pdfUrl }}" style="width: 100%; height: 100%; border: none;"></iframe>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>

<!-- Librería Highcharts -->
<script src="https://code.highcharts.com/highcharts.js"></script>

@script
<script>
    let flowChart = null;

    const tsConfig = {
        maxItems: 1,
        create: false,
        allowEmptyOption: true
    };

    new TomSelect('#selectCustomer', {
        ...tsConfig,
        onChange: function(val) {
            $wire.set('customer_id', val);
        }
    });

    new TomSelect('#selectSeller', {
        ...tsConfig,
        onChange: function(val) {
            $wire.set('seller_id', val);
        }
    });

    function renderFlowChart(labels, datasets) {
        if (flowChart) { flowChart.destroy(); }

        let realData = datasets[0].data;
        let projectedData = datasets[1].data;

        flowChart = Highcharts.chart('cashFlowChart', {
            chart: {
                type: 'column',
                backgroundColor: 'transparent'
            },
            title: {
                text: 'Evolución de Caja: Cobros Reales vs. Vencimientos Proyectados',
                style: { fontSize: '14px', fontWeight: 'bold', color: '#1b55e2' }
            },
            xAxis: {
                categories: labels,
                labels: { style: { fontSize: '9px' } }
            },
            yAxis: {
                title: { text: 'Monto en USD' },
                plotLines: [{
                    value: 0,
                    width: 1,
                    color: '#808080'
                }]
            },
            tooltip: {
                shared: true,
                valueSuffix: ' USD'
            },
            credits: { enabled: false },
            series: [
                {
                    name: 'Cobrado Real',
                    data: realData,
                    color: '#2ec4b6'
                },
                {
                    name: 'Vencimiento Proyectado (Entrada Estimada)',
                    data: projectedData,
                    type: 'line',
                    color: '#1b55e2',
                    marker: {
                        enabled: true,
                        radius: 4
                    }
                }
            ]
        });
    }

    $wire.on('updateChart', (event, ...args) => {
        let labels, datasets;
        if (event && event.detail) {
            labels = event.detail.labels;
            datasets = event.detail.datasets;
        } else if (event && event.labels) {
            labels = event.labels;
            datasets = event.datasets;
        } else if (Array.isArray(event) && args.length > 0 && Array.isArray(args[0])) {
            labels = event;
            datasets = args[0];
        }
        
        if (labels && datasets) {
            renderFlowChart(labels, datasets);
        }
    });

    // Initial render if report is already showing
    if ($wire.get('showReport')) {
        $wire.updateChart();
    }
</script>
@endscript
