<div>
    <div class="row layout-top-spacing">
        <!-- Panel de Filtros -->
        <div class="col-12 layout-spacing">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white p-3">
                    <h5 class="mb-0 text-white"><i class="fas fa-filter mr-2"></i> Opciones de Análisis y Filtros</h5>
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

                        <!-- Acuerdo de Pago -->
                        <div class="col-sm-12 col-md-2 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Acuerdo de Pago</label>
                            <select wire:model.live="payment_agreement" class="form-control form-control-sm">
                                <option value="ALL">Todos los Acuerdos</option>
                                <option value="BCV">Acuerdo BCV</option>
                                <option value="USD">Acuerdo USD</option>
                            </select>
                        </div>

                        <!-- Rango de Fechas (Fecha de Pago) -->
                        <div class="col-sm-12 col-md-2 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Pago Desde</label>
                            <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm">
                        </div>
                        <div class="col-sm-12 col-md-2 mb-2">
                            <label class="font-weight-bold text-muted f-12 mb-1">Pago Hasta</label>
                            <input type="date" wire:model.live="dateTo" class="form-control form-control-sm">
                        </div>
                    </div>

                    <!-- Botones de Acción -->
                    <div class="row mt-3">
                        <div class="col-12 text-right">
                            <span wire:loading wire:target="generatePdf" class="mr-3 text-muted"><i class="fas fa-spinner fa-spin"></i> Procesando...</span>
                            <button wire:click="searchData" class="btn btn-primary btn-sm">
                                <i class="fas fa-search-plus"></i> Analizar Diferencial
                            </button>
                            <button wire:click="generatePdf" class="btn btn-danger btn-sm ml-2" @if(!$showReport) disabled @endif>
                                <i class="fas fa-file-pdf"></i> Exportar PDF (Landscape)
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
                <!-- KPI 1: Ventas Facturadas (USD) -->
                <div class="col-sm-12 col-md-2 mb-3">
                    <div class="card shadow-sm border-left border-dark h-100" style="cursor: help;" title="Total acumulado en USD de las facturas que recibieron abonos en Bolívares en este período.">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Facturado (USD)</div>
                                <div class="bg-dark-light p-1 rounded-circle"><i class="fas fa-file-invoice-dollar text-dark"></i></div>
                            </div>
                            <div class="f-18 font-weight-bold text-dark mt-2">${{ number_format($kpis['totalInvoicedUSD'], 2) }}</div>
                            <div class="f-10 text-muted mt-1">Facturas con abonos VED</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 2: Monto Cobrado (Tasa Pago) -->
                <div class="col-sm-12 col-md-2 mb-3">
                    <div class="card shadow-sm border-left border-primary h-100" style="cursor: help;" title="Suma de los abonos aplicados a las facturas convertidos a USD al tipo de cambio registrado (lo que se descontó del saldo del cliente).">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Cobrado Teórico</div>
                                <div class="bg-primary-light p-1 rounded-circle"><i class="fas fa-cash-register text-primary"></i></div>
                            </div>
                            <div class="f-18 font-weight-bold text-primary mt-2">${{ number_format($kpis['totalCreditedUSD'], 2) }}</div>
                            <div class="f-10 text-muted mt-1">USD descontados al cliente</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 3: Monto Cobrado Real (Tasa Binance) -->
                <div class="col-sm-12 col-md-2 mb-3">
                    <div class="card shadow-sm border-left border-info h-100" style="cursor: help;" title="Suma de los abonos en Bolívares convertidos a USD usando la tasa de mercado real (Binance) del día del pago. Representa el poder de compra real recibido.">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Cobrado Real</div>
                                <div class="bg-info-light p-1 rounded-circle"><i class="fas fa-dollar-sign text-info"></i></div>
                            </div>
                            <div class="f-18 font-weight-bold text-info mt-2">${{ number_format($kpis['totalRealUSD'], 2) }}</div>
                            <div class="f-10 text-muted mt-1">USD equivalentes reales</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 4: Diferencial Cambiario Neto -->
                <div class="col-sm-12 col-md-2 mb-3">
                    <div class="card shadow-sm border-left border-danger h-100" style="cursor: help;" title="Brecha financiera entre el USD real recibido y el USD cobrado teóricamente (Cobrado Real - Cobrado Teórico). Generalmente negativo debido a tasas de pago inferiores a la del mercado.">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Diferencial Neto</div>
                                <div class="bg-danger-light p-1 rounded-circle"><i class="fas fa-compress-arrows-alt text-danger"></i></div>
                            </div>
                            <div class="f-18 font-weight-bold @if($kpis['netExchangeDifferenceUSD'] < 0) text-danger @else text-success @endif mt-2">${{ number_format($kpis['netExchangeDifferenceUSD'], 2) }}</div>
                            <div class="f-10 text-muted mt-1">Fuga por desvío de tasa</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 5: Cojín Billed (Recargos) -->
                <div class="col-sm-12 col-md-2 mb-3">
                    <div class="card shadow-sm border-left border-warning h-100" style="cursor: help;" title="Suma de los recargos por concepto de diferencial cambiario (exchange_diff_amount) aplicados en las facturas del período para mitigar la devaluación.">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Cojín Facturado</div>
                                <div class="bg-warning-light p-1 rounded-circle"><i class="fas fa-shield-alt text-warning"></i></div>
                            </div>
                            <div class="f-18 font-weight-bold text-warning mt-2">${{ number_format($kpis['totalSurchargesBilledUSD'], 2) }}</div>
                            <div class="f-10 text-muted mt-1">Recargo por amortización</div>
                        </div>
                    </div>
                </div>

                <!-- KPI 6: Resultado Neto Cambiario -->
                <div class="col-sm-12 col-md-2 mb-3">
                    <div class="card shadow-sm border-left border-success h-100" style="cursor: help;" title="Resultado neto final para la empresa: Diferencial Cambiario Neto + Cojín Facturado. Si es positivo, los recargos lograron cubrir la pérdida cambiaria; si es negativo, persiste una fuga de capital.">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div class="f-11 text-muted uppercase font-weight-bold">Resultado Cambiario</div>
                                <div class="bg-success-light p-1 rounded-circle"><i class="fas fa-balance-scale text-success"></i></div>
                            </div>
                            <div class="f-18 font-weight-bold @if($kpis['netCambiaryResultUSD'] < 0) text-danger @else text-success @endif mt-2">${{ number_format($kpis['netCambiaryResultUSD'], 2) }}</div>
                            <div class="f-10 text-muted mt-1">Resultado final neto</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif

        <!-- Gráfico Highcharts -->
        <div class="col-12 layout-spacing {{ $showReport ? '' : 'd-none' }}">
            <div class="card shadow-sm border-0">
                <div class="card-body" wire:ignore>
                    <div id="exchangeDiffChart" style="height: 320px; width: 100%;"></div>
                </div>
            </div>
        </div>

        @if($showReport)
        <!-- Tabla de Datos -->
        <div class="col-12 layout-spacing">
            <div class="card shadow-sm border-0">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover mb-0">
                            <thead class="bg-dark text-white text-center">
                                <tr>
                                    <th class="text-white" style="cursor: pointer;" wire:click="sortBy('invoice_number')">
                                        Factura {!! $sortField === 'invoice_number' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' !!}
                                    </th>
                                    <th class="text-white" style="cursor: pointer;" wire:click="sortBy('payment_date')">
                                        Fecha Pago {!! $sortField === 'payment_date' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' !!}
                                    </th>
                                    <th class="text-white">Cliente</th>
                                    <th class="text-white">Vendedor</th>
                                    <th class="text-white">Acuerdo</th>
                                    <th class="text-white">Monto VED</th>
                                    <th class="text-white">Tasa Pago</th>
                                    <th class="text-white">Tasa Binance</th>
                                    <th class="text-white">USD Abonado</th>
                                    <th class="text-white">USD Real</th>
                                    <th class="text-white" style="cursor: pointer;" wire:click="sortBy('diff')">
                                        Diferencial (USD) {!! $sortField === 'diff' ? ($sortDirection === 'asc' ? '↑' : '↓') : '' !!}
                                    </th>
                                    <th class="text-white">Estado Auditoría</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($payments as $p)
                                    <tr>
                                        <td class="text-center font-weight-bold">
                                            @if($p['invoice_number'])
                                                F-{{ str_pad($p['invoice_number'], 6, '0', STR_PAD_LEFT) }}
                                            @else
                                                #{{ $p['sale_id'] }}
                                            @endif
                                        </td>
                                        <td class="text-center">{{ \Carbon\Carbon::parse($p['payment_date'])->format('d/m/Y') }}</td>
                                        <td>{{ $p['customer_name'] }}</td>
                                        <td>{{ $p['seller_name'] ?: 'OFICINA' }}</td>
                                        <td class="text-center font-weight-bold">{{ $p['agreement'] }}</td>
                                        <td class="text-right font-weight-bold text-muted">{{ number_format($p['amount'], 2) }} Bs.</td>
                                        <td class="text-center text-primary font-weight-bold">{{ number_format($p['pay_rate'], 2) }}</td>
                                        <td class="text-center text-info font-weight-bold">{{ number_format($p['binance_rate'], 2) }}</td>
                                        <td class="text-right font-weight-bold">${{ number_format($p['usd_credited'], 2) }}</td>
                                        <td class="text-right font-weight-bold">${{ number_format($p['usd_real'], 2) }}</td>
                                        <td class="text-right font-weight-bold @if(($p['surcharge_portion'] > 0 ? $p['net_diff'] : $p['diff']) < 0) text-danger @else text-success @endif">
                                            @if($p['surcharge_portion'] > 0)
                                                ${{ number_format($p['net_diff'], 2) }}
                                                <small class="d-block text-muted f-11" title="Diferencial Directo: ${{ number_format($p['diff'], 2) }} | Cojín Facturado: +${{ number_format($p['surcharge_portion'], 2) }}">
                                                    Directo: ${{ number_format($p['diff'], 2) }} <span class="text-warning font-weight-bold">(+Cojín ${{ number_format($p['surcharge_portion'], 2) }})</span>
                                                </small>
                                            @else
                                                ${{ number_format($p['diff'], 2) }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if($p['status'] === 'green')
                                                <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> {{ $p['msg'] ?? 'Cumple' }}</span>
                                            @elseif($p['status'] === 'orange')
                                                <span class="badge badge-warning px-2 py-1" style="color: #fff; background-color: #fd7e14;"><i class="fas fa-exclamation-triangle mr-1"></i> {{ $p['msg'] ?? 'Desviación' }}</span>
                                            @else
                                                <span class="badge badge-danger px-2 py-1"><i class="fas fa-times-circle mr-1"></i> {{ $p['msg'] ?? 'Fuga' }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center text-muted py-4">
                                            <i class="fas fa-info-circle fa-2x mb-2"></i>
                                            <p class="mb-0">No se encontraron cobros en Bolívares que requieran auditoría de diferencial cambiario para los filtros seleccionados.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginación -->
                    <div class="mt-3 d-flex justify-content-between align-items-center">
                        <div class="text-muted f-12">
                            Mostrando {{ $payments->count() }} de {{ $payments->total() }} registros de cobro
                        </div>
                        <div>
                            {{ $payments->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Modal de Interpretación Analítica -->
    @if($showInterpretationModal)
    <div class="modal show d-block" style="background: rgba(0,0,0,0.6); z-index: 9999;" tabindex="-1" role="dialog" wire:key="exchange-interpretation-modal">
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
</div>

<!-- Librería Highcharts -->
<script src="https://code.highcharts.com/highcharts.js"></script>

@script
<script>
    let diffChart = null;

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

    function renderDiffChart(labels, datasets) {
        if (diffChart) { diffChart.destroy(); }

        let diffData = datasets[0].data;
        let surchargeData = datasets[1].data;

        diffChart = Highcharts.chart('exchangeDiffChart', {
            chart: {
                type: 'areaspline',
                backgroundColor: 'transparent'
            },
            title: {
                text: 'Evolución Cambiaria Diaria: Pérdida vs. Cojín de Amortización',
                style: { fontSize: '14px', fontWeight: 'bold', color: '#1b55e2' }
            },
            xAxis: {
                categories: labels,
                labels: { style: { fontSize: '9px' } }
            },
            yAxis: {
                title: { text: 'Valor en USD' },
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
                    name: 'Diferencial Neto (Fuga)',
                    data: diffData,
                    color: '#e7515a',
                    fillColor: {
                        linearGradient: [0, 0, 0, 300],
                        stops: [
                            [0, 'rgba(231, 81, 90, 0.4)'],
                            [1, 'rgba(231, 81, 90, 0)']
                        ]
                    }
                },
                {
                    name: 'Cojín de Diferencial Facturado',
                    data: surchargeData,
                    color: '#e2a03f',
                    fillColor: {
                        linearGradient: [0, 0, 0, 300],
                        stops: [
                            [0, 'rgba(226, 160, 63, 0.4)'],
                            [1, 'rgba(226, 160, 63, 0)']
                        ]
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
            renderDiffChart(labels, datasets);
        }
    });

    // Initial render if report is already showing
    if ($wire.get('showReport')) {
        $wire.updateChart();
    }
</script>
@endscript
