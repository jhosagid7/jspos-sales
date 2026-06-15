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

                        {{-- Payment Status --}}
                        <div class="col-md-3 col-sm-6 mb-3">
                            <label class="font-weight-bold text-dark small">Estado de Pago</label>
                            <select wire:model.live="paymentStatus" class="form-control form-control-sm shadow-none" style="border-radius: 8px;">
                                <option value="all">Todos los pagos</option>
                                <option value="paid">Pagadas Totalmente</option>
                                <option value="pending">Pendientes (Con Deuda)</option>
                            </select>
                        </div>

                        {{-- Search --}}
                        <div class="col-md-3 col-sm-12 mb-3">
                            <label class="font-weight-bold text-dark small">Buscar Factura / Cliente</label>
                            <div class="input-group input-group-sm">
                                <input type="text" wire:model.live.debounce.300ms="searchQuery" class="form-control shadow-none" placeholder="Factura o cliente..." style="border-radius: 8px 0 0 8px;">
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
                                    <tr style="vertical-align: middle;" class="{{ $sale->status === 'paid' ? 'row-paid-glow' : '' }}">
                                        @if(in_array('invoice_number', $selectedColumns))
                                            <td class="font-weight-bold text-dark">
                                                {{ $sale->invoice_number ?: ('#' . $sale->id) }}
                                                @if($sale->status === 'paid')
                                                    <span class="badge badge-success px-2 py-1 ml-2 text-white font-weight-bold" style="font-size: 0.65rem; border-radius: 4px; vertical-align: middle;" title="Factura pagada en su totalidad">
                                                        <i class="fas fa-check-double mr-1" style="font-size: 0.6rem;"></i> PAGADA
                                                    </span>
                                                @endif
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
                                                @if($sale->payment_agreement === 'BCV')
                                                    @php
                                                        $config = \App\Models\Configuration::first();
                                                        $bcvVal = $config ? floatval($config->bcv_rate) : 1;
                                                        $binVal = $config ? floatval($config->binance_rate) : 1;
                                                        $gap = $bcvVal > 0 ? (($binVal - $bcvVal) / $bcvVal) * 100 : 0;
                                                        $diff = floatval($sale->applied_exchange_diff_percent);
                                                        $hasRisk = ($diff < $gap);
                                                    @endphp
                                                    <span class="badge badge-info font-weight-bold px-2 py-1" style="border-radius: 5px; font-size: 0.75rem;">
                                                        {{ $sale->payment_agreement }}
                                                    </span>
                                                    @if($hasRisk)
                                                        <div class="mt-1">
                                                            <span class="badge badge-danger font-weight-bold px-2 py-1" style="border-radius: 5px; font-size: 0.65rem;" title="Diferencial de {{ number_format($diff, 2) }}% no cubre la brecha Binance-BCV ({{ number_format($gap, 2) }}%)">
                                                                <i class="fas fa-exclamation-triangle"></i> Riesgo
                                                            </span>
                                                        </div>
                                                    @endif
                                                @else
                                                    <span class="badge badge-info font-weight-bold px-2 py-1" style="border-radius: 5px; font-size: 0.75rem;">
                                                        {{ $sale->payment_agreement }}
                                                    </span>
                                                @endif
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
                    @php
                        $unifiedPayments = $this->getUnifiedPayments($selectedSale);
                        $activePayment = null;
                        $val = null;
                        
                        if (!empty($unifiedPayments)) {
                            $activePaymentArray = collect($unifiedPayments)->firstWhere('id', $selectedPaymentId) ?? $unifiedPayments[0];
                            $activePayment = $activePaymentArray ? $activePaymentArray['model'] : null;
                            $val = $activePayment ? $this->getPaymentValidation($activePayment) : null;
                        }
                        
                        $color = 'gray';
                        $headerBg = 'bg-dark';
                        $statusText = 'Sin Pagos';
                        $icon = 'fa-file-invoice-dollar';
                        
                        if ($activePayment && $val) {
                            $color = $val['color'];
                            $headerBg = 'bg-success';
                            $statusText = 'Rentable';
                            $icon = 'fa-check-circle';
                            
                            if ($color === 'orange') {
                                $headerBg = 'bg-warning text-dark';
                                $statusText = 'Alerta / Desviación';
                                $icon = 'fa-exclamation-circle';
                            } elseif ($color === 'red') {
                                $headerBg = 'bg-danger';
                                $statusText = 'Pérdida';
                                $icon = 'fa-times-circle';
                            }
                            
                            $ratio = $selectedSale->total_usd > 0 ? (($activePayment->amount / ($activePayment->exchange_rate ?: 1)) / $selectedSale->total_usd) : 0;
                            $paymentBase = floatval($selectedSale->base_amount) * $ratio;
                            $paymentFreight = floatval($selectedSale->freight_amount) * $ratio;
                            $paymentCommission = floatval($selectedSale->commission_amount) * $ratio;
                            $paymentMarkup = floatval($selectedSale->base_markup_amount) * $ratio;
                            $paymentDiff = floatval($selectedSale->exchange_diff_amount) * $ratio;
                            
                            $netDiff = $val['net_usd'] - $paymentBase;
                            $realPaymentUsd = $val['net_usd'] + $paymentFreight + $paymentCommission + $paymentMarkup;
                        }
                    @endphp

                    <div class="modal-header {{ $headerBg }} text-white py-3 border-0">
                        <h5 class="modal-title font-weight-bold text-white d-flex align-items-center" id="modalSaleDetailLabel">
                            <i class="fas {{ $icon }} mr-2" style="font-size: 1.3rem;"></i>
                            Detalle de Auditoría - Factura {{ $selectedSale->invoice_number ?: ('#' . $selectedSale->id) }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close" wire:click="closeSaleDetails" style="font-size: 1.5rem; line-height: 1; outline: none; opacity: 0.9;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <div class="modal-body p-4 text-dark" style="background: #f8f9fa; max-height: 80vh; overflow-y: auto;">
                        
                        {{-- Tab Selector for payments (if multiple exist) --}}
                        @if(count($unifiedPayments) > 1)
                            <div class="mb-4">
                                <label class="font-weight-bold text-dark small mb-2">
                                    <i class="fas fa-list mr-1 text-info"></i> Seleccione Pago a Analizar ({{ count($unifiedPayments) }} registrados):
                                </label>
                                <div class="nav nav-pills flex-wrap">
                                    @foreach($unifiedPayments as $pItem)
                                        <button type="button" 
                                                wire:click="selectPayment('{{ $pItem['id'] }}')" 
                                                class="nav-link btn btn-sm mr-2 mb-2 {{ $selectedPaymentId == $pItem['id'] ? 'active btn-primary text-white' : 'btn-outline-primary bg-white' }} font-weight-bold" 
                                                style="border-radius: 8px;">
                                            <i class="fas fa-money-bill-wave mr-1"></i>
                                            {{ number_format($pItem['amount'], 2) }} {{ $pItem['currency'] }} ({{ $pItem['pay_way'] }})
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        {{-- Status Banner --}}
                        <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px; background: #fff;">
                            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                                @if($activePayment && $val)
                                    <div>
                                        <span class="text-muted small text-uppercase font-weight-bold" style="letter-spacing: 1px; font-size: 0.75rem;">Estado de Rentabilidad</span>
                                        <h4 class="font-weight-bold mb-0 {{ $color === 'red' ? 'text-danger' : ($color === 'orange' ? 'text-warning' : 'text-success') }}">
                                            {{ $statusText }}
                                        </h4>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-muted small d-block">Detalle del Sistema:</span>
                                        <p class="mb-0 font-weight-bold text-dark">{{ $val['message'] }}</p>
                                    </div>
                                @else
                                    <div>
                                        <span class="text-muted small text-uppercase font-weight-bold" style="letter-spacing: 1px; font-size: 0.75rem;">Estado de Rentabilidad</span>
                                        <h4 class="font-weight-bold mb-0 text-muted">
                                            Sin Pagos Registrados
                                        </h4>
                                    </div>
                                    <div class="text-right">
                                        <span class="text-muted small d-block">Detalle del Sistema:</span>
                                        <p class="mb-0 font-weight-bold text-dark">Esta factura aún no ha recibido ningún abono o pago.</p>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="row mb-4">
                            {{-- Col 1: Invoice Configuration --}}
                            <div class="col-md-6 mb-3">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                                    <div class="card-header bg-dark text-white font-weight-bold py-2" style="font-size: 0.9rem;">
                                        <i class="fas fa-file-invoice-dollar mr-2"></i> Configuración de Factura
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">Cliente:</span>
                                            <span class="font-weight-bold text-dark text-truncate" style="max-width: 180px;" title="{{ $selectedSale->customer->name ?? 'Sin Cliente' }}">
                                                {{ $selectedSale->customer->name ?? 'Sin Cliente' }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">Acuerdo de Pago:</span>
                                            <span class="badge badge-info font-weight-bold" style="padding: 4px 8px; border-radius: 5px;">{{ $selectedSale->payment_agreement ?: 'USD' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">Total Facturado (USD):</span>
                                            <span class="font-weight-bold text-dark">${{ number_format($selectedSale->total_usd, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Costo Base:</span>
                                            <span class="text-dark">${{ number_format($selectedSale->base_amount, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Comisión ({{ $selectedSale->resolved_commission_percent }}%):</span>
                                            <span class="text-dark">${{ number_format($selectedSale->commission_amount, 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Flete ({{ $selectedSale->resolved_freight_percent }}%):</span>
                                            <span class="text-dark">${{ number_format($selectedSale->freight_amount, 2) }}</span>
                                        </div>
                                        @if($selectedSale->resolved_base_markup_percent > 0)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Recargo ({{ $selectedSale->resolved_base_markup_percent }}%):</span>
                                                <span class="text-dark">${{ number_format($selectedSale->base_markup_amount, 2) }}</span>
                                            </div>
                                        @endif
                                        @if($selectedSale->resolved_exchange_diff_percent > 0)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Diferencial ({{ $selectedSale->resolved_exchange_diff_percent }}%):</span>
                                                <span class="text-warning font-weight-bold">${{ number_format($selectedSale->exchange_diff_amount, 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Col 2: Received Payment or General Details --}}
                            <div class="col-md-6 mb-3">
                                @if($activePayment)
                                    <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                                        <div class="card-header bg-dark text-white font-weight-bold py-2" style="font-size: 0.9rem;">
                                            <i class="fas fa-hand-holding-usd mr-2"></i> Detalles del Pago
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                                <span class="text-muted">Monto Cobrado:</span>
                                                <span class="font-weight-bold text-primary" style="font-size: 1.1rem;">
                                                    {{ number_format($activePayment->amount, 2) }} {{ $activePayment->currency }}
                                                </span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                                <span class="text-muted">Método de Pago:</span>
                                                <span class="font-weight-bold text-dark text-uppercase">{{ $activePayment->pay_way }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                                <span class="text-muted">Banco / Destino:</span>
                                                <span class="font-weight-bold text-dark text-uppercase">{{ $activePayment->bank ?: 'N/A' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                                <span class="text-muted">Referencia:</span>
                                                <span class="font-weight-bold text-dark">{{ $activePayment->deposit_number ?: ($activePayment->zelleRecord->reference ?? 'N/A') }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Tasa de Cobro:</span>
                                                <span class="font-weight-bold text-dark">{{ number_format($activePayment->exchange_rate, 4) }} Bs</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Equivalente USD:</span>
                                                <span class="font-weight-bold text-dark">${{ number_format($activePayment->amount / ($activePayment->exchange_rate ?: 1), 2) }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @else
                                    <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                                        <div class="card-header bg-dark text-white font-weight-bold py-2" style="font-size: 0.9rem;">
                                            <i class="fas fa-info-circle mr-2"></i> Datos de Emisión
                                        </div>
                                        <div class="card-body p-3">
                                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                                <span class="text-muted">Fecha Emisión:</span>
                                                <span class="font-weight-bold text-dark">{{ $selectedSale->created_at->format('d/m/Y h:i A') }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                                <span class="text-muted">Operador (Cajero):</span>
                                                <span class="font-weight-bold text-dark">{{ $selectedSale->user->name ?? 'N/A' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                                <span class="text-muted">RIF/CI Cliente:</span>
                                                <span class="font-weight-bold text-dark">{{ $selectedSale->customer->taxpayer_id ?? 'N/A' }}</span>
                                            </div>
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Vendedor:</span>
                                                <span class="font-weight-bold text-dark">{{ $selectedSale->customer->seller->name ?? 'Sin Vendedor' }}</span>
                                            </div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        @if($activePayment && $val)
                            {{-- Section: Exchange Rates --}}
                            <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px;">
                                <div class="card-body p-3 bg-white" style="border-radius: 10px;">
                                    <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-history mr-2 text-info"></i> Tasas Referenciales del Día del Pago</h6>
                                    <div class="row text-center">
                                        <div class="col-md-6 border-right mb-2 mb-md-0">
                                            <span class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.75rem;">Tasa BCV Oficial</span>
                                            <h4 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($val['bcv_rate'], 4) }} Bs</h4>
                                        </div>
                                        <div class="col-md-6">
                                            <span class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.75rem;">Tasa Binance Aplicada</span>
                                            <h4 class="font-weight-bold text-primary mb-0 mt-1">{{ number_format($val['binance_rate'], 4) }} Bs</h4>
                                            
                                            @if(isset($val['binance_rates']) && count($val['binance_rates']) > 0)
                                                <div class="mt-2 d-flex flex-wrap justify-content-center align-items-center">
                                                    <span class="text-muted small mr-2 font-weight-bold" style="font-size: 0.7rem;">Tasas del día:</span>
                                                    @foreach($val['binance_rates'] as $rate)
                                                        @if(abs($activePayment->exchange_rate - $rate) < 0.01)
                                                            <span class="badge badge-success text-white px-2 py-1 mr-1 mb-1 font-weight-bold" style="font-size: 0.7rem;" title="Tasa coincidente utilizada para el pago">
                                                                {{ number_format($rate, 2) }} Bs <i class="fas fa-check ml-1"></i>
                                                            </span>
                                                        @else
                                                            <span class="badge badge-light border text-muted px-2 py-1 mr-1 mb-1 font-weight-bold" style="font-size: 0.7rem; background-color: #f8f9fa;">
                                                                {{ number_format($rate, 2) }} Bs
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Section: Mathematical Calculation Table --}}
                            <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px; overflow: hidden;">
                                <div class="card-header bg-dark text-white font-weight-bold py-2" style="font-size: 0.9rem;">
                                    <i class="fas fa-calculator mr-2"></i> Desglose Matemático Proporcional del Pago
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-striped mb-0 text-center" style="font-size: 0.85rem;">
                                        <thead class="bg-light text-dark">
                                            <tr>
                                                <th class="text-left">Concepto</th>
                                                <th>Total Factura</th>
                                                <th>% Prop. Pago</th>
                                                <th>USD Registrado (Tasa Pago)</th>
                                                <th>USD Real Recuperado (Tasa Binance)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td class="text-left font-weight-bold text-dark">Costo Base (Productos)</td>
                                                <td class="text-dark">${{ number_format($selectedSale->base_amount, 2) }}</td>
                                                <td class="text-dark">{{ number_format(($selectedSale->total_usd > 0 ? ($selectedSale->base_amount / $selectedSale->total_usd) * 100 : 0), 1) }}%</td>
                                                <td class="text-dark">${{ number_format($paymentBase, 2) }}</td>
                                                <td class="font-weight-bold {{ $netDiff >= -0.0001 ? 'text-success' : 'text-danger' }}">
                                                    ${{ number_format($val['net_usd'], 2) }}
                                                </td>
                                            </tr>
                                            <tr>
                                                <td class="text-left text-dark">Comisión de Vendedor</td>
                                                <td class="text-dark">${{ number_format($selectedSale->commission_amount, 2) }}</td>
                                                <td class="text-dark">{{ number_format(($selectedSale->total_usd > 0 ? ($selectedSale->commission_amount / $selectedSale->total_usd) * 100 : 0), 1) }}%</td>
                                                <td class="text-dark">${{ number_format($paymentCommission, 2) }}</td>
                                                <td class="text-dark">${{ number_format($paymentCommission, 2) }}</td>
                                            </tr>
                                            <tr>
                                                <td class="text-left text-dark">Flete de Despacho</td>
                                                <td class="text-dark">${{ number_format($selectedSale->freight_amount, 2) }}</td>
                                                <td class="text-dark">{{ number_format(($selectedSale->total_usd > 0 ? ($selectedSale->freight_amount / $selectedSale->total_usd) * 100 : 0), 1) }}%</td>
                                                <td class="text-dark">${{ number_format($paymentFreight, 2) }}</td>
                                                <td class="text-dark">${{ number_format($paymentFreight, 2) }}</td>
                                            </tr>
                                            @if($selectedSale->resolved_base_markup_percent > 0)
                                                <tr>
                                                    <td class="text-left text-dark">Recargo</td>
                                                    <td class="text-dark">${{ number_format($selectedSale->base_markup_amount, 2) }}</td>
                                                    <td class="text-dark">{{ number_format(($selectedSale->total_usd > 0 ? ($selectedSale->base_markup_amount / $selectedSale->total_usd) * 100 : 0), 1) }}%</td>
                                                    <td class="text-dark">${{ number_format($paymentMarkup, 2) }}</td>
                                                    <td class="text-dark">${{ number_format($paymentMarkup, 2) }}</td>
                                                </tr>
                                            @endif
                                            @if($selectedSale->resolved_exchange_diff_percent > 0)
                                                <tr>
                                                    <td class="text-left text-dark">Diferencial Cambiario</td>
                                                    <td class="text-dark">${{ number_format($selectedSale->exchange_diff_amount, 2) }}</td>
                                                    <td class="text-dark">{{ number_format(($selectedSale->total_usd > 0 ? ($selectedSale->exchange_diff_amount / $selectedSale->total_usd) * 100 : 0), 1) }}%</td>
                                                    <td class="text-dark">${{ number_format($paymentDiff, 2) }}</td>
                                                    <td class="text-dark">${{ number_format($paymentDiff, 2) }}</td>
                                                </tr>
                                            @endif
                                            <tr class="table-info font-weight-bold text-dark" style="font-size: 0.9rem;">
                                                <td colspan="3" class="text-right font-weight-bold">Monto Total de este Pago:</td>
                                                <td class="text-muted font-weight-bold">${{ number_format($activePayment->amount / ($activePayment->exchange_rate ?: 1), 2) }}</td>
                                                <td class="text-primary font-weight-bold">${{ number_format($realPaymentUsd, 2) }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Section: Real USD recovered card --}}
                            <div class="card border-0 shadow-sm" style="border-radius: 10px; background: #fff;">
                                <div class="card-body p-3">
                                    <h5 class="font-weight-bold text-dark mb-3">
                                        <i class="fas fa-project-diagram text-info mr-2"></i> 
                                        Análisis del Contravalor y Recuperación Cambiaria
                                    </h5>
                                    
                                    <div class="row align-items-center">
                                        <div class="col-md-6 border-right">
                                            <span class="text-muted small text-uppercase font-weight-bold">Monto Neto Real Recuperado (USD Efectivo)</span>
                                            <h3 class="font-weight-bold text-dark mt-1 mb-1">
                                                ${{ number_format($val['net_usd'], 2) }}
                                            </h3>
                                            <span class="small text-muted">Calculado descontando Flete, Comisión y Recargo sobre el contravalor real en dólares del día.</span>
                                        </div>
                                        <div class="col-md-6 pl-md-4">
                                            <span class="text-muted small text-uppercase font-weight-bold">Margen vs Costo Base Proporcional</span>
                                            <h3 class="font-weight-bold mt-1 mb-1 {{ $netDiff >= -0.0001 ? 'text-success' : 'text-danger' }}">
                                                {{ $netDiff >= -0.0001 ? '+' : '' }}${{ number_format($netDiff, 2) }}
                                            </h3>
                                            <span class="small text-muted">Debe ser mayor o igual a $0.00 para no incurrir en pérdidas frente al costo base de los productos.</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Callout when no payments are found --}}
                            <div class="alert alert-warning border-0 shadow-sm p-4 d-flex align-items-center mb-0" style="border-radius: 10px;">
                                <i class="fas fa-exclamation-triangle mr-3 text-warning" style="font-size: 2.5rem;"></i>
                                <div>
                                    <h5 class="font-weight-bold text-dark">No hay pagos registrados</h5>
                                    <p class="mb-0 text-muted">Esta factura de tipo crédito aún no registra ningún pago o abono en el sistema. Los análisis de rentabilidad y contravalor se activarán una vez que se registren los pagos correspondientes.</p>
                                </div>
                            </div>
                        @endif

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

@push('my-styles')
<style>
    /* Styling for fully paid invoices - premium green border and soft glow */
    tr.row-paid-glow td {
        background-color: rgba(40, 167, 69, 0.02) !important;
        transition: background-color 0.2s ease-in-out;
    }
    tr.row-paid-glow:hover td {
        background-color: rgba(40, 167, 69, 0.05) !important;
    }
    tr.row-paid-glow td:first-child {
        position: relative;
        border-left: 5px solid #28a745 !important;
    }
</style>
@endpush
