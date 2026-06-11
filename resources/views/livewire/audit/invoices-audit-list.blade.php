<div class="container-fluid">
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12 layout-spacing">
            
            {{-- Alert messages --}}
            @if (session()->has('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 10px;">
                    <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            @if (session()->has('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert" style="border-radius: 10px;">
                    <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
                    <button type="button" class="close text-white" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
            @endif

            {{-- Main Card --}}
            <div class="card shadow-lg border-0" style="border-radius: 15px; overflow: hidden; background: rgba(255, 255, 255, 0.98); backdrop-filter: blur(10px);">
                
                {{-- Card Header --}}
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-3">
                    <div>
                        <h4 class="mb-1 text-white font-weight-bold">
                            <i class="fas fa-file-invoice-dollar mr-2 text-warning"></i> Auditoría de Facturas
                        </h4>
                        <p class="mb-0 text-white-50 small">Consulte, audite y analice la rentabilidad de las facturas emitidas</p>
                    </div>
                    <div class="d-flex align-items-center">
                        {{-- Columns Customizer Dropdown --}}
                        <div class="dropdown mr-2" x-data="{ open: false }" @click.away="open = false">
                            <button class="btn btn-outline-light dropdown-toggle font-weight-bold" type="button" @click="open = !open">
                                <i class="fas fa-columns mr-1"></i> Columnas
                            </button>
                            <div class="dropdown-menu dropdown-menu-right p-3 shadow-lg border-0" :class="{ 'show': open }" style="border-radius: 10px; min-width: 200px; z-index: 1050;">
                                <h6 class="dropdown-header font-weight-bold px-0 text-dark">Mostrar Columnas</h6>
                                <div class="dropdown-divider"></div>
                                @foreach([
                                    'invoice_number' => 'Factura',
                                    'created_at' => 'Fecha',
                                    'customer' => 'Cliente',
                                    'seller' => 'Vendedor',
                                    'operator' => 'Operador',
                                    'total_usd' => 'Total USD',
                                    'payment_agreement' => 'Acuerdo',
                                    'audit_status' => 'Estado',
                                    'actions' => 'Acciones'
                                ] as $colKey => $colName)
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" 
                                               class="custom-control-input" 
                                               id="col-check-{{ $colKey }}" 
                                               value="{{ $colKey }}" 
                                               wire:model.live="selectedColumns">
                                        <label class="custom-control-label text-dark font-weight-normal" for="col-check-{{ $colKey }}">{{ $colName }}</label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Filter Board --}}
                <div class="card-body bg-light border-bottom p-4">
                    <div class="row">
                        {{-- Date range --}}
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="font-weight-bold text-dark small">Desde</label>
                            <input type="date" wire:model.live="dateFrom" class="form-control form-control-sm shadow-none" style="border-radius: 8px;">
                        </div>
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="font-weight-bold text-dark small">Hasta</label>
                            <input type="date" wire:model.live="dateTo" class="form-control form-control-sm shadow-none" style="border-radius: 8px;">
                        </div>

                        {{-- Audit Status --}}
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="font-weight-bold text-dark small">Estado de Auditoría</label>
                            <select wire:model.live="auditStatus" class="form-control form-control-sm shadow-none" style="border-radius: 8px;">
                                <option value="all">Todos los estados</option>
                                <option value="audited">Auditadas</option>
                                <option value="not_audited">Sin Auditar</option>
                                <option value="deleted">Eliminadas</option>
                            </select>
                        </div>

                        {{-- Agreement --}}
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="font-weight-bold text-dark small">Acuerdo de Pago</label>
                            <select wire:model.live="paymentAgreement" class="form-control form-control-sm shadow-none" style="border-radius: 8px;">
                                <option value="all">Todos los acuerdos</option>
                                <option value="USD">Acuerdo USD</option>
                                <option value="BCV">Acuerdo BCV</option>
                            </select>
                        </div>

                        {{-- Seller --}}
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="font-weight-bold text-dark small">Vendedor</label>
                            <select wire:model.live="sellerId" class="form-control form-control-sm shadow-none" style="border-radius: 8px;">
                                <option value="all">Todos los vendedores</option>
                                @foreach($sellers as $s)
                                    <option value="{{ $s->id }}">{{ $s->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Operator --}}
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="font-weight-bold text-dark small">Operador (Cajero)</label>
                            <select wire:model.live="operatorId" class="form-control form-control-sm shadow-none" style="border-radius: 8px;">
                                <option value="all">Todos los operadores</option>
                                @foreach($operators as $o)
                                    <option value="{{ $o->id }}">{{ $o->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        {{-- Search --}}
                        <div class="col-md-6 col-sm-12 mb-3">
                            <label class="font-weight-bold text-dark small">Buscar Factura / Cliente</label>
                            <div class="input-group input-group-sm">
                                <input type="text" wire:model.live.debounce.300ms="searchQuery" class="form-control shadow-none" placeholder="Número de factura o nombre de cliente..." style="border-radius: 8px 0 0 8px;">
                                <div class="input-group-append">
                                    <span class="input-group-text bg-primary text-white border-0" style="border-radius: 0 8px 8px 0;">
                                        <i class="fas fa-search"></i>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Table Area --}}
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0">
                            <thead class="bg-dark text-white">
                                <tr>
                                    @if(in_array('invoice_number', $selectedColumns))
                                        <th class="text-white cursor-pointer" wire:click="sortBy('invoice_number')">
                                            Factura 
                                            @if($sortField === 'invoice_number')
                                                <i class="fas fa-sort-amount-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                            @endif
                                        </th>
                                    @endif

                                    @if(in_array('created_at', $selectedColumns))
                                        <th class="text-white cursor-pointer" wire:click="sortBy('created_at')">
                                            Fecha
                                            @if($sortField === 'created_at')
                                                <i class="fas fa-sort-amount-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                            @endif
                                        </th>
                                    @endif

                                    @if(in_array('customer', $selectedColumns))
                                        <th class="text-white cursor-pointer" wire:click="sortBy('customer')">
                                            Cliente
                                            @if($sortField === 'customer')
                                                <i class="fas fa-sort-amount-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                            @endif
                                        </th>
                                    @endif

                                    @if(in_array('seller', $selectedColumns))
                                        <th class="text-white cursor-pointer" wire:click="sortBy('seller')">
                                            Vendedor
                                            @if($sortField === 'seller')
                                                <i class="fas fa-sort-amount-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                            @endif
                                        </th>
                                    @endif

                                    @if(in_array('operator', $selectedColumns))
                                        <th class="text-white cursor-pointer" wire:click="sortBy('operator')">
                                            Operador
                                            @if($sortField === 'operator')
                                                <i class="fas fa-sort-amount-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                            @endif
                                        </th>
                                    @endif

                                    @if(in_array('total_usd', $selectedColumns))
                                        <th class="text-white text-right cursor-pointer" wire:click="sortBy('total_usd')">
                                            Total USD
                                            @if($sortField === 'total_usd')
                                                <i class="fas fa-sort-amount-{{ $sortDirection === 'asc' ? 'up' : 'down' }} ml-1"></i>
                                            @endif
                                        </th>
                                    @endif

                                    @if(in_array('payment_agreement', $selectedColumns))
                                        <th class="text-white text-center">Acuerdo</th>
                                    @endif

                                    @if(in_array('audit_status', $selectedColumns))
                                        <th class="text-white text-center">Auditoría</th>
                                    @endif

                                    @if(in_array('actions', $selectedColumns))
                                        <th class="text-white text-center">Acciones</th>
                                    @endif
                                </tr>
                            </thead>
                            <tbody>
                                @if($sales->isEmpty())
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-4">No se encontraron facturas con los filtros seleccionados.</td>
                                    </tr>
                                @endif

                                @foreach($sales as $sale)
                                    @php
                                        $auditStatusText = $sale->audit_status;
                                        $badgeClass = 'badge-warning text-dark';
                                        $badgeIcon = 'fa-clock';

                                        if ($auditStatusText === 'Auditada') {
                                            $badgeClass = 'badge-success text-white';
                                            $badgeIcon = 'fa-check-circle';
                                        } elseif ($auditStatusText === 'Eliminada') {
                                            $badgeClass = 'badge-danger text-white';
                                            $badgeIcon = 'fa-exclamation-circle';
                                        }
                                    @endphp
                                    <tr style="vertical-align: middle;">
                                        @if(in_array('invoice_number', $selectedColumns))
                                            <td class="font-weight-bold text-dark">
                                                {{ $sale->invoice_number ?: ('#' . $sale->id) }}
                                            </td>
                                        @endif

                                        @if(in_array('created_at', $selectedColumns))
                                            <td class="small text-muted">
                                                {{ $sale->created_at->format('d/m/Y h:i A') }}
                                            </td>
                                        @endif

                                        @if(in_array('customer', $selectedColumns))
                                            <td class="font-weight-bold text-dark small">
                                                {{ $sale->customer->name ?? 'Sin Cliente' }}
                                            </td>
                                        @endif

                                        @if(in_array('seller', $selectedColumns))
                                            <td class="small text-dark">
                                                {{ $sale->customer->seller->name ?? 'Sin Vendedor' }}
                                            </td>
                                        @endif

                                        @if(in_array('operator', $selectedColumns))
                                            <td class="small text-dark">
                                                {{ $sale->user->name ?? 'N/A' }}
                                            </td>
                                        @endif

                                        @if(in_array('total_usd', $selectedColumns))
                                            <td class="text-right font-weight-bold text-primary">
                                                ${{ number_format($sale->total_usd, 2) }}
                                            </td>
                                        @endif

                                        @if(in_array('payment_agreement', $selectedColumns))
                                            <td class="text-center">
                                                <span class="badge badge-info font-weight-bold px-2 py-1" style="border-radius: 5px; font-size: 0.75rem;">
                                                    {{ $sale->payment_agreement }}
                                                </span>
                                            </td>
                                        @endif

                                        @if(in_array('audit_status', $selectedColumns))
                                            <td class="text-center">
                                                <span class="badge {{ $badgeClass }} font-weight-bold px-2 py-1" style="border-radius: 8px; font-size: 0.75rem;">
                                                    <i class="fas {{ $badgeIcon }} mr-1"></i> {{ $auditStatusText }}
                                                </span>
                                                @if($auditStatusText === 'Eliminada' && $sale->deletion_reason)
                                                    <div class="text-danger small mt-1 font-italic font-weight-bold" style="max-width: 250px; margin: 0 auto; line-height: 1.2;">
                                                        Motivo: {{ $sale->deletion_reason }}
                                                    </div>
                                                @endif
                                            </td>
                                        @endif

                                        @if(in_array('actions', $selectedColumns))
                                            <td class="text-center">
                                                <div class="btn-group" role="group">
                                                    {{-- Details Trigger --}}
                                                    <button type="button" 
                                                            wire:click="showSaleDetails({{ $sale->id }})" 
                                                            class="btn btn-sm btn-info shadow-none" 
                                                            title="Ver Detalle Matemático y Pagos"
                                                            style="border-radius: 8px 0 0 8px;">
                                                        <i class="fas fa-search-plus"></i> Detalle
                                                    </button>
                                                    
                                                    {{-- Audit Toggle --}}
                                                    <button type="button" 
                                                            wire:click="toggleInvoiceAudit({{ $sale->id }})" 
                                                            class="btn btn-sm {{ $sale->is_audited ? 'btn-success' : 'btn-outline-success' }} shadow-none"
                                                            {{ $auditStatusText === 'Eliminada' ? 'disabled' : '' }}
                                                            title="Cambiar estado de auditoría manualmente"
                                                            style="border-radius: 0 8px 8px 0;">
                                                        <i class="fas {{ $sale->is_audited ? 'fa-check-double' : 'fa-check' }}"></i>
                                                        {{ $sale->is_audited ? 'Auditada' : 'Auditar' }}
                                                    </button>
                                                </div>
                                            </td>
                                        @endif
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- Card Footer --}}
                <div class="card-footer bg-white border-0 py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <span class="text-muted small">Mostrando {{ $sales->firstItem() ?? 0 }} a {{ $sales->lastItem() ?? 0 }} de {{ $sales->total() }} facturas</span>
                        <div>
                            {{ $sales->links() }}
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Modal Sale Detail -->
    <div class="modal fade" id="modalSaleDetail" tabindex="-1" role="dialog" aria-labelledby="modalSaleDetailLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-xl modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                @if($selectedSale)
                    <div class="modal-header bg-dark text-white py-3 border-0">
                        <h5 class="modal-title font-weight-bold text-white d-flex align-items-center" id="modalSaleDetailLabel">
                            <i class="fas fa-file-invoice-dollar mr-2 text-warning" style="font-size: 1.3rem;"></i>
                            Desglose de Auditoría - Factura {{ $selectedSale->invoice_number ?: ('#' . $selectedSale->id) }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close" wire:click="closeSaleDetails" style="font-size: 1.5rem; line-height: 1; outline: none; opacity: 0.9;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4 text-dark" style="background: #f8f9fa; max-height: 80vh; overflow-y: auto;">
                        
                        {{-- Top Sale Summary Header --}}
                        <div class="row mb-4">
                            <div class="col-md-6 mb-3 mb-md-0">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                                    <div class="card-header bg-dark text-white font-weight-bold py-2" style="font-size: 0.9rem;">
                                        <i class="fas fa-info-circle mr-2"></i> Datos de la Factura
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">Cliente:</span>
                                            <span class="font-weight-bold text-dark">{{ $selectedSale->customer->name ?? 'Sin Cliente' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">R.I.F. / C.I.:</span>
                                            <span class="font-weight-bold text-dark">{{ $selectedSale->customer->taxpayer_id ?? 'N/A' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">Fecha de Emisión:</span>
                                            <span class="font-weight-bold text-dark">{{ $selectedSale->created_at->format('d/m/Y h:i A') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Operador (Cajero):</span>
                                            <span class="font-weight-bold text-dark">{{ $selectedSale->user->name ?? 'N/A' }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                                    <div class="card-header bg-dark text-white font-weight-bold py-2" style="font-size: 0.9rem;">
                                        <i class="fas fa-calculator mr-2"></i> Estructura de Montos (USD)
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted font-weight-bold">Total Factura (USD):</span>
                                            <span class="font-weight-bold text-primary" style="font-size: 1.1rem;">${{ number_format($selectedSale->total_usd, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Costo Base:</span>
                                            <span class="text-dark font-weight-bold">${{ number_format($selectedSale->base_amount, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Flete de Despacho ({{ $selectedSale->resolved_freight_percent }}%):</span>
                                            <span class="text-dark">${{ number_format($selectedSale->freight_amount, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Comisión Vendedor ({{ $selectedSale->resolved_commission_percent }}%):</span>
                                            <span class="text-dark">${{ number_format($selectedSale->commission_amount, 2) }}</span>
                                        </div>
                                        @if($selectedSale->resolved_exchange_diff_percent > 0)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Diferencial ({{ $selectedSale->resolved_exchange_diff_percent }}%):</span>
                                                <span class="text-warning font-weight-bold">${{ number_format($selectedSale->exchange_diff_amount, 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Payments List --}}
                        <div class="card border-0 shadow-sm" style="border-radius: 10px; overflow: hidden;">
                            <div class="card-header bg-primary text-white font-weight-bold py-2" style="font-size: 0.95rem;">
                                <i class="fas fa-money-bill-wave mr-2"></i> Pagos Registrados para esta Factura
                            </div>
                            <div class="card-body p-3 bg-white">
                                @if($selectedSale->payments->isEmpty())
                                    <div class="text-center text-muted py-4">No hay pagos registrados para esta factura.</div>
                                @else
                                    @foreach($selectedSale->payments as $payment)
                                        @php
                                            $val = $this->getPaymentValidation($payment);
                                            $color = $val['color'];
                                            $badgeClass = 'badge-success';
                                            $statusText = 'Rentable';
                                            $borderClass = 'border-success';
                                            $textClass = 'text-success';
                                            
                                            if ($color === 'orange') {
                                                $badgeClass = 'badge-warning text-dark';
                                                $statusText = 'Alerta / Desviación';
                                                $borderClass = 'border-warning';
                                                $textClass = 'text-warning';
                                            } elseif ($color === 'red') {
                                                $badgeClass = 'badge-danger';
                                                $statusText = 'Pérdida';
                                                $borderClass = 'border-danger';
                                                $textClass = 'text-danger';
                                            }
                                            
                                            $ratio = $selectedSale->total_usd > 0 ? (($payment->amount / ($payment->exchange_rate ?: 1)) / $selectedSale->total_usd) : 0;
                                            $paymentBase = floatval($selectedSale->base_amount) * $ratio;
                                            $paymentFreight = floatval($selectedSale->freight_amount) * $ratio;
                                            $paymentCommission = floatval($selectedSale->commission_amount) * $ratio;
                                            $paymentDiff = floatval($selectedSale->exchange_diff_amount) * $ratio;
                                            
                                            $netDiff = $val['net_usd'] - $paymentBase;
                                            $realPaymentUsd = $val['net_usd'] + $paymentFreight + $paymentCommission;
                                        @endphp
                                        
                                        <div class="payment-card border-left-lg p-3 mb-4 rounded shadow-sm bg-light" style="border-left: 5px solid; border-color: {{ $color === 'red' ? '#dc3545' : ($color === 'orange' ? '#ffc107' : '#28a745') }};">
                                            <div class="row align-items-center">
                                                <div class="col-md-3 border-right">
                                                    <span class="text-muted small text-uppercase font-weight-bold">Detalle del Pago</span>
                                                    <h4 class="font-weight-bold text-primary mt-1 mb-1">
                                                        {{ number_format($payment->amount, 2) }} {{ $payment->currency }}
                                                    </h4>
                                                    <div class="small text-dark font-weight-bold">Tasa: {{ number_format($payment->exchange_rate, 4) }} Bs</div>
                                                    <div class="small text-muted mt-1">
                                                        Vía: <span class="text-uppercase font-weight-bold text-dark">{{ $payment->pay_way }}</span> 
                                                        @if($payment->bank)
                                                             | {{ strtoupper($payment->bank) }}
                                                        @endif
                                                    </div>
                                                    @if($payment->deposit_number || $payment->zelleRecord)
                                                        <div class="small text-muted">
                                                            Ref: <span class="font-weight-bold text-dark">{{ $payment->deposit_number ?: ($payment->zelleRecord->reference ?? 'N/A') }}</span>
                                                        </div>
                                                    @endif
                                                    <div class="small text-muted mt-1"><i class="far fa-calendar-alt"></i> {{ $payment->created_at->format('d/m/Y h:i A') }}</div>
                                                </div>
                                                
                                                <div class="col-md-4 border-right pl-md-4">
                                                    <span class="text-muted small text-uppercase font-weight-bold">Validación de Rentabilidad</span>
                                                    <div class="mt-2">
                                                        <span class="badge {{ $badgeClass }} font-weight-bold py-1 px-2 mb-2" style="font-size: 0.8rem; border-radius: 8px;">
                                                            {{ $statusText }}
                                                        </span>
                                                        <p class="mb-0 font-weight-bold text-dark small">{{ $val['message'] }}</p>
                                                    </div>
                                                    
                                                    <div class="mt-3">
                                                        <span class="text-muted small d-block">Tasas Referenciales del Día:</span>
                                                        <span class="badge badge-light border text-dark font-weight-bold" style="font-size: 0.75rem;">BCV: {{ number_format($val['bcv_rate'], 2) }} Bs</span>
                                                        <span class="badge badge-light border text-primary font-weight-bold" style="font-size: 0.75rem;">Binance: {{ number_format($val['binance_rate'], 2) }} Bs</span>
                                                    </div>
                                                </div>

                                                <div class="col-md-5 pl-md-4">
                                                    <span class="text-muted small text-uppercase font-weight-bold">Análisis Proporcional de Cobro</span>
                                                    <div class="table-responsive mt-2">
                                                        <table class="table table-bordered table-sm mb-0 text-center" style="font-size: 0.75rem;">
                                                            <thead class="bg-dark text-white">
                                                                <tr>
                                                                    <th class="text-left py-1 text-white">Concepto</th>
                                                                    <th class="py-1 text-white">USD Teórico</th>
                                                                    <th class="py-1 text-white">USD Real</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <tr>
                                                                    <td class="text-left font-weight-bold">Base (Costo)</td>
                                                                    <td>${{ number_format($paymentBase, 2) }}</td>
                                                                    <td class="font-weight-bold {{ $netDiff >= -0.0001 ? 'text-success' : 'text-danger' }}">${{ number_format($val['net_usd'], 2) }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-left">Comisión</td>
                                                                    <td>${{ number_format($paymentCommission, 2) }}</td>
                                                                    <td>${{ number_format($paymentCommission, 2) }}</td>
                                                                </tr>
                                                                <tr>
                                                                    <td class="text-left">Flete</td>
                                                                    <td>${{ number_format($paymentFreight, 2) }}</td>
                                                                    <td>${{ number_format($paymentFreight, 2) }}</td>
                                                                </tr>
                                                                <tr class="font-weight-bold table-active">
                                                                    <td class="text-left text-dark">Total Proporcional</td>
                                                                    <td class="text-dark">${{ number_format($payment->amount / ($payment->exchange_rate ?: 1), 2) }}</td>
                                                                    <td class="text-primary">${{ number_format($realPaymentUsd, 2) }}</td>
                                                                </tr>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                    <div class="mt-2 text-right">
                                                        <span class="small font-weight-bold text-dark">Diferencia neta vs costo base: </span>
                                                        <span class="small font-weight-bold {{ $netDiff >= -0.0001 ? 'text-success' : 'text-danger' }}">
                                                            {{ $netDiff >= -0.0001 ? '+' : '' }}${{ number_format($netDiff, 2) }}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal" wire:click="closeSaleDetails">
                            <i class="fas fa-times mr-1"></i> Cerrar Auditoría
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

</div>

@push('my-scripts')
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('show-sale-details-modal', (event) => {
            $('#modalSaleDetail').modal('show');
        });
        Livewire.on('close-sale-details-modal', (event) => {
            $('#modalSaleDetail').modal('hide');
        });
    });
</script>
@endpush
