<div>
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-md-12 col-12 layout-spacing">
            <div class="widget widget-content-area br-4">
                <div class="widget-one">
                    <!-- Title & Date Selector -->
                    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                        <h3><i class="fas fa-chart-pie text-primary"></i> Análisis Estratégico y Patrimonio</h3>
                        <div class="form-group mb-0 d-flex align-items-center">
                            <label class="mr-2 font-weight-bold text-dark mb-0">Seleccionar Mes:</label>
                            <input type="month" class="form-control form-control-sm" wire:model.live="selectedMonth" style="width: 180px;">
                        </div>
                    </div>

                    <!-- Navigation Tabs -->
                    <ul class="nav nav-pills mb-4" id="pills-tab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab == 'growth' ? 'active' : '' }}" wire:click.prevent="$set('activeTab', 'growth')" href="#"><i class="fas fa-chart-line"></i> Crecimiento Operativo</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab == 'patrimony' ? 'active' : '' }}" wire:click.prevent="$set('activeTab', 'patrimony')" href="#"><i class="fas fa-wallet"></i> Patrimonio y Balance</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab == 'abc' ? 'active' : '' }}" wire:click.prevent="$set('activeTab', 'abc')" href="#"><i class="fas fa-crown"></i> Análisis ABC (80/20)</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $activeTab == 'opex' ? 'active' : '' }}" wire:click.prevent="$set('activeTab', 'opex')" href="#"><i class="fas fa-receipt"></i> Gastos Operativos (OPEX)</a>
                        </li>
                    </ul>

                    <!-- Content Sections -->
                    <div class="tab-content">
                        <!-- TAB 1: OPERATIONAL GROWTH -->
                        @if($activeTab == 'growth')
                        <div>
                            <!-- KPI Comparison Cards -->
                            <div class="row">
                                <!-- Net Sales -->
                                <div class="col-md-3 mb-3">
                                    <div class="card border-0 shadow-sm bg-gradient-info text-white">
                                        <div class="card-body">
                                            <h6 class="text-white-50">Ventas Netas ({{ $monthName }})</h6>
                                            <h3>${{ number_format($current['netSales'], 2) }}</h3>
                                            <div class="mt-2 text-white-50 small">
                                                @php 
                                                    $diffPrevSales = $prev['netSales'] > 0 ? (($current['netSales'] - $prev['netSales']) / $prev['netSales']) * 100 : 0; 
                                                    $diffYearSales = $yearAgo['netSales'] > 0 ? (($current['netSales'] - $yearAgo['netSales']) / $yearAgo['netSales']) * 100 : 0;
                                                @endphp
                                                <div>
                                                    @if($diffPrevSales >= 0)
                                                        <span class="text-white font-weight-bold"><i class="fas fa-arrow-up"></i> +{{ number_format($diffPrevSales, 1) }}%</span> vs mes anterior
                                                    @else
                                                        <span class="text-white font-weight-bold"><i class="fas fa-arrow-down"></i> {{ number_format($diffPrevSales, 1) }}%</span> vs mes anterior
                                                    @endif
                                                </div>
                                                <div class="mt-1">
                                                    @if($diffYearSales >= 0)
                                                        <span class="text-white font-weight-bold"><i class="fas fa-arrow-up"></i> +{{ number_format($diffYearSales, 1) }}%</span> vs año anterior
                                                    @else
                                                        <span class="text-white font-weight-bold"><i class="fas fa-arrow-down"></i> {{ number_format($diffYearSales, 1) }}%</span> vs año anterior
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Cost of Goods Sold (COGS) -->
                                <div class="col-md-3 mb-3">
                                    <div class="card border-0 shadow-sm bg-gradient-secondary text-white">
                                        <div class="card-body">
                                            <h6 class="text-white-50">Costo de Ventas (COGS)</h6>
                                            <h3>${{ number_format($current['cogs'], 2) }}</h3>
                                            <div class="mt-2 text-white-50 small">
                                                Margen Bruto: <strong>{{ number_format($current['grossMarginPercent'], 1) }}%</strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Operational Expenses (OPEX) -->
                                <div class="col-md-3 mb-3">
                                    <div class="card border-0 shadow-sm bg-gradient-danger text-white">
                                        <div class="card-body">
                                            <h6 class="text-white-50">Gastos Fijos (OPEX)</h6>
                                            <h3>${{ number_format($current['opex'], 2) }}</h3>
                                            <div class="mt-2 text-white-50 small">
                                                Deducido de la utilidad bruta
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <!-- Net Profit -->
                                <div class="col-md-3 mb-3">
                                    <div class="card border-0 shadow-sm bg-gradient-success text-white">
                                        <div class="card-body">
                                            <h6 class="text-white-50">Utilidad Neta Real</h6>
                                            <h3>${{ number_format($current['netProfit'], 2) }}</h3>
                                            <div class="mt-2 text-white-50 small">
                                                @php 
                                                    $diffPrevProfit = $prev['netProfit'] > 0 ? (($current['netProfit'] - $prev['netProfit']) / $prev['netProfit']) * 100 : 0; 
                                                    $diffYearProfit = $yearAgo['netProfit'] > 0 ? (($current['netProfit'] - $yearAgo['netProfit']) / $yearAgo['netProfit']) * 100 : 0;
                                                @endphp
                                                <div>
                                                    @if($diffPrevProfit >= 0)
                                                        <span class="text-white font-weight-bold"><i class="fas fa-arrow-up"></i> +{{ number_format($diffPrevProfit, 1) }}%</span> vs mes anterior
                                                    @else
                                                        <span class="text-white font-weight-bold"><i class="fas fa-arrow-down"></i> {{ number_format($diffPrevProfit, 1) }}%</span> vs mes anterior
                                                    @endif
                                                </div>
                                                <div class="mt-1">
                                                    @if($diffYearProfit >= 0)
                                                        <span class="text-white font-weight-bold"><i class="fas fa-arrow-up"></i> +{{ number_format($diffYearProfit, 1) }}%</span> vs año anterior
                                                    @else
                                                        <span class="text-white font-weight-bold"><i class="fas fa-arrow-down"></i> {{ number_format($diffYearProfit, 1) }}%</span> vs año anterior
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Weekly Evolution Chart -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-transparent border-0">
                                            <h5 class="mb-0 text-dark font-weight-bold">Velocidad Semanal de Ventas y Rentabilidad</h5>
                                        </div>
                                         <div class="card-body"
                                              data-labels="{{ json_encode($weeklyBreakdown['labels'] ?? []) }}"
                                              data-sales="{{ json_encode($weeklyBreakdown['sales'] ?? []) }}"
                                              data-profit="{{ json_encode($weeklyBreakdown['profit'] ?? []) }}"
                                              x-data="{
                                                  render() {
                                                      const labels = JSON.parse(this.$el.getAttribute('data-labels') || '[]');
                                                      const sales = JSON.parse(this.$el.getAttribute('data-sales') || '[]').map(Number);
                                                      const profit = JSON.parse(this.$el.getAttribute('data-profit') || '[]').map(Number);
                                                      
                                                      const isDarkMode = document.body.classList.contains('dark-mode');
                                                      const textColor = isDarkMode ? '#e4e4e4' : '#333333';
                                                      const chartBg = isDarkMode ? 'transparent' : '#ffffff';

                                                      Highcharts.chart(this.$refs.chart, {
                                                          chart: { type: 'column', backgroundColor: chartBg },
                                                          title: { text: '' },
                                                          xAxis: {
                                                              categories: labels,
                                                              labels: { style: { color: textColor } }
                                                          },
                                                          yAxis: {
                                                              title: { text: 'Monto ($ USD)', style: { color: textColor } },
                                                              labels: { style: { color: textColor } }
                                                          },
                                                          tooltip: { shared: true },
                                                          legend: { itemStyle: { color: textColor } },
                                                          credits: { enabled: false },
                                                          series: [
                                                              {
                                                                  name: 'Ventas Netas',
                                                                  data: sales,
                                                                  color: '#17a2b8'
                                                              },
                                                              {
                                                                  name: 'Ganancia Bruta',
                                                                  data: profit,
                                                                  color: '#28a745'
                                                              }
                                                          ]
                                                      });
                                                  }
                                              }"
                                              x-init="render()"
                                              @chart-updated.window="$nextTick(() => render())">
                                             <div x-ref="chart" style="height: 350px;" wire:ignore></div>
                                         </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- TAB 2: PATRIMONY & BALANCE -->
                        @if($activeTab == 'patrimony')
                        <div>
                            <!-- Wealth / Balance Cards -->
                            <div class="row">
                                <!-- Inventory Value -->
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-white border border-info shadow-none">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted font-weight-bold">Inventario a Costo</h6>
                                            <h3 class="text-info">${{ number_format($patrimony['inventoryValue'], 2) }}</h3>
                                            <span class="text-muted small">Valor monetario en almacén</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Accounts Receivable (CxC) -->
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-white border border-primary shadow-none">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted font-weight-bold">Cuentas por Cobrar</h6>
                                            <h3 class="text-primary">${{ number_format($patrimony['totalCxC'], 2) }}</h3>
                                            <span class="text-muted small">Cartera activa en la calle</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Cash & Banks -->
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-white border border-success shadow-none">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted font-weight-bold">Efectivo y Bancos</h6>
                                            <h3 class="text-success">${{ number_format($patrimony['totalCash'], 2) }}</h3>
                                            <span class="text-muted small">Saldo líquido acumulado</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Accounts Payable (CxP) -->
                                <div class="col-md-3 mb-3">
                                    <div class="card bg-white border border-danger shadow-none">
                                        <div class="card-body text-center">
                                            <h6 class="text-muted font-weight-bold">Cuentas por Pagar</h6>
                                            <h3 class="text-danger">${{ number_format($patrimony['totalCxP'], 2) }}</h3>
                                            <span class="text-muted small">Deuda pendiente con proveedores</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Net Worth Summary Alert -->
                            <div class="alert bg-light border-primary text-dark mt-3 p-4">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div>
                                        <h4 class="mb-1 text-primary font-weight-bold"><i class="fas fa-hand-holding-usd"></i> Patrimonio Neto Operativo Actual</h4>
                                        <p class="mb-0 text-muted f-12">Fórmula: (Inventario + CxC + Cajas/Bancos) - Cuentas por Pagar</p>
                                    </div>
                                    <div class="text-right">
                                        <h2 class="text-primary font-weight-bold mb-0">${{ number_format($patrimony['netEquity'], 2) }}</h2>
                                        <span class="badge badge-success">Valor neto actual de la empresa</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Historical Equity Trend -->
                            <div class="row mt-4">
                                <div class="col-md-12">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-transparent border-0">
                                            <h5 class="mb-0 text-dark font-weight-bold">Evolución Histórica de Capitalización (Patrimonio Neto)</h5>
                                        </div>
                                        <div class="card-body"
                                              data-labels="{{ json_encode($patrimony['historyLabels'] ?? []) }}"
                                              data-equity="{{ json_encode($patrimony['historyEquity'] ?? []) }}"
                                              x-data="{
                                                  render() {
                                                      const labels = JSON.parse(this.$el.getAttribute('data-labels') || '[]');
                                                      const equity = JSON.parse(this.$el.getAttribute('data-equity') || '[]').map(Number);
                                                      
                                                      const isDarkMode = document.body.classList.contains('dark-mode');
                                                      const textColor = isDarkMode ? '#e4e4e4' : '#333333';
                                                      const chartBg = isDarkMode ? 'transparent' : '#ffffff';

                                                      Highcharts.chart(this.$refs.chart, {
                                                          chart: { type: 'areaspline', backgroundColor: chartBg },
                                                          title: { text: '' },
                                                          xAxis: {
                                                              categories: labels,
                                                              labels: { style: { color: textColor } }
                                                          },
                                                          yAxis: {
                                                              title: { text: 'Patrimonio Neto ($ USD)', style: { color: textColor } },
                                                              labels: { style: { color: textColor } }
                                                          },
                                                          tooltip: { pointFormat: '<b>${point.y:.2f} USD</b>' },
                                                          legend: { enabled: false },
                                                          credits: { enabled: false },
                                                          series: [{
                                                              name: 'Patrimonio Neto',
                                                              data: equity,
                                                              color: '#007bff',
                                                              fillOpacity: 0.1
                                                          }]
                                                      });
                                                  }
                                              }"
                                              x-init="render()"
                                              @chart-updated.window="$nextTick(() => render())">
                                             <div x-ref="chart" style="height: 350px;" wire:ignore></div>
                                         </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <!-- TAB 3: ABC CLIENTS & PRODUCT MARGINS -->
                        @if($activeTab == 'abc')
                        <div>
                            <div class="row">
                                <!-- ABC Clients List -->
                                <div class="col-md-6 mb-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0 text-dark font-weight-bold">Clasificación ABC de Clientes (Regla 80/20)</h5>
                                            <span class="badge badge-primary">Utilidad Acumulada</span>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                <table class="table table-striped table-valign-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Clase</th>
                                                            <th>Cliente</th>
                                                            <th class="text-right">Ventas</th>
                                                            <th class="text-right">Utilidad</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <!-- Class A -->
                                                        @foreach($abc['A'] as $c)
                                                        <tr>
                                                            <td><span class="badge badge-success font-weight-bold">A (80%)</span></td>
                                                            <td>{{ Str::limit($c['name'], 30) }}</td>
                                                            <td class="text-right">${{ number_format($c['sales'], 2) }}</td>
                                                            <td class="text-right text-success font-weight-bold">${{ number_format($c['profit'], 2) }}</td>
                                                        </tr>
                                                        @endforeach

                                                        <!-- Class B -->
                                                        @foreach($abc['B'] as $c)
                                                        <tr>
                                                            <td><span class="badge badge-warning font-weight-bold">B (15%)</span></td>
                                                            <td>{{ Str::limit($c['name'], 30) }}</td>
                                                            <td class="text-right">${{ number_format($c['sales'], 2) }}</td>
                                                            <td class="text-right text-warning font-weight-bold">${{ number_format($c['profit'], 2) }}</td>
                                                        </tr>
                                                        @endforeach

                                                        <!-- Class C -->
                                                        @foreach($abc['C'] as $c)
                                                        <tr>
                                                            <td><span class="badge badge-danger font-weight-bold">C (5%)</span></td>
                                                            <td>{{ Str::limit($c['name'], 30) }}</td>
                                                            <td class="text-right">${{ number_format($c['sales'], 2) }}</td>
                                                            <td class="text-right text-danger font-weight-bold">${{ number_format($c['profit'], 2) }}</td>
                                                        </tr>
                                                        @endforeach

                                                        @if(empty($abc['A']) && empty($abc['B']) && empty($abc['C']))
                                                        <tr>
                                                            <td colspan="4" class="text-center py-4 text-muted">Sin datos para este mes.</td>
                                                        </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Product Margins -->
                                <div class="col-md-6 mb-4">
                                    <div class="card border-0 shadow-sm h-100">
                                        <div class="card-header bg-transparent border-0">
                                            <h5 class="mb-0 text-dark font-weight-bold">Productos con Mayor Aporte de Ganancia</h5>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                                <table class="table table-striped table-valign-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>SKU</th>
                                                            <th>Producto</th>
                                                            <th class="text-right">Qty</th>
                                                            <th class="text-right">Margen %</th>
                                                            <th class="text-right">Utilidad Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($productMargins['top'] as $p)
                                                        <tr>
                                                            <td><span class="text-muted small">{{ $p['sku'] }}</span></td>
                                                            <td>{{ Str::limit($p['name'], 30) }}</td>
                                                            <td class="text-right">{{ $p['qty_sold'] }}</td>
                                                            <td class="text-right text-info">{{ number_format($p['margin_percent'], 1) }}%</td>
                                                            <td class="text-right text-success font-weight-bold">${{ number_format($p['total_profit'], 2) }}</td>
                                                        </tr>
                                                        @endforeach

                                                        @if(empty($productMargins['top']))
                                                        <tr>
                                                            <td colspan="5" class="text-center py-4 text-muted">Sin datos para este mes.</td>
                                                        </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Negative or Low Margin Products Warning -->
                            @if(!empty($productMargins['low']))
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="card border-danger shadow-sm">
                                        <div class="card-header bg-danger text-white">
                                            <h5 class="mb-0 text-white font-weight-bold"><i class="fas fa-exclamation-triangle"></i> Alerta: Productos con Margen Crítico o Nulo (< 5%)</h5>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-hover table-valign-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>SKU</th>
                                                            <th>Producto</th>
                                                            <th class="text-right">Precio Prom.</th>
                                                            <th class="text-right">Costo Unit.</th>
                                                            <th class="text-right">Margen %</th>
                                                            <th class="text-right">Utilidad Total</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($productMargins['low'] as $p)
                                                        <tr class="table-danger">
                                                            <td><span class="text-muted small">{{ $p['sku'] }}</span></td>
                                                            <td class="font-weight-bold">{{ $p['name'] }}</td>
                                                            <td class="text-right">${{ number_format($p['avg_price'], 2) }}</td>
                                                            <td class="text-right">${{ number_format($p['cost'], 2) }}</td>
                                                            <td class="text-right text-danger font-weight-bold">{{ number_format($p['margin_percent'], 1) }}%</td>
                                                            <td class="text-right text-danger">${{ number_format($p['total_profit'], 2) }}</td>
                                                        </tr>
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            @endif
                        </div>
                        @endif

                        <!-- TAB 4: OPEX REGISTRATION -->
                        @if($activeTab == 'opex')
                        <div>
                            <div class="row">
                                <!-- Add OPEX Form -->
                                <div class="col-md-4 mb-4">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-primary text-white">
                                            <h5 class="mb-0 text-white font-weight-bold"><i class="fas fa-plus"></i> Registrar Gasto Fijo</h5>
                                        </div>
                                        <div class="card-body">
                                            <div class="form-group">
                                                <label class="font-weight-bold">Categoría</label>
                                                <select wire:model="opexCategory" class="form-control">
                                                    @foreach($availableCategories as $cat)
                                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="form-group">
                                                <label class="font-weight-bold">Monto ($ USD)</label>
                                                <input type="number" step="0.01" wire:model="opexAmount" class="form-control" placeholder="Ej: 500.00">
                                            </div>
                                            <div class="form-group">
                                                <label class="font-weight-bold">Descripción (Opcional)</label>
                                                <input type="text" wire:model="opexDescription" class="form-control" placeholder="Ej: Alquiler de local comercial">
                                            </div>
                                            <button wire:click="addOpex" class="btn btn-primary btn-block"><i class="fas fa-save"></i> Guardar Gasto</button>
                                        </div>
                                    </div>
                                </div>

                                <!-- OPEX List Table -->
                                <div class="col-md-8 mb-4">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
                                            <h5 class="mb-0 text-dark font-weight-bold">Gastos Operativos de {{ $monthName }}</h5>
                                            <span class="badge badge-danger font-weight-bold">Total: ${{ number_format($opexList->sum('amount'), 2) }}</span>
                                        </div>
                                        <div class="card-body p-0">
                                            <div class="table-responsive">
                                                <table class="table table-striped table-valign-middle mb-0">
                                                    <thead>
                                                        <tr>
                                                            <th>Categoría</th>
                                                            <th>Descripción</th>
                                                            <th class="text-right">Monto</th>
                                                            <th class="text-center" style="width: 100px;">Acciones</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @foreach($opexList as $item)
                                                        <tr>
                                                            <td><span class="badge badge-secondary">{{ $item->category }}</span></td>
                                                            <td>{{ $item->description ?? 'Sin descripción' }}</td>
                                                            <td class="text-right text-danger font-weight-bold">${{ number_format($item->amount, 2) }}</td>
                                                            <td class="text-center">
                                                                <button wire:click="deleteOpex({{ $item->id }})" class="btn btn-danger btn-sm p-1">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        @endforeach

                                                        @if($opexList->isEmpty())
                                                        <tr>
                                                            <td colspan="4" class="text-center py-4 text-muted">No se han registrado gastos operativos para este mes.</td>
                                                        </tr>
                                                        @endif
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>
