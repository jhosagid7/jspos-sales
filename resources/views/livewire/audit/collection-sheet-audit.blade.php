<div class="container-fluid">
    @if(!$sheet)
        {{-- ENTRANCE PORTAL --}}
        <div class="row layout-top-spacing justify-content-center">
            <div class="col-sm-12 col-md-8 col-lg-6 mt-4">
                <div class="card shadow-lg border-0" style="border-radius: 15px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px);">
                    <div class="card-header bg-dark text-white text-center py-4" style="border-top-left-radius: 15px; border-top-right-radius: 15px;">
                        <h3 class="mb-1 text-white font-weight-bold">
                            <i class="fas fa-shield-alt mr-2 text-warning"></i> Portal de Auditoría
                        </h3>
                        <p class="mb-0 text-white-50">Auditoría Rápida y Verificación de Planillas de Cobranza</p>
                    </div>
                    <div class="card-body p-4">
                        
                        @if (session()->has('error'))
                            <div class="alert alert-danger shadow-sm mb-4">
                                <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('error') }}
                            </div>
                        @endif

                        @if (session()->has('success'))
                            <div class="alert alert-success shadow-sm mb-4">
                                <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                            </div>
                        @endif

                        {{-- 1. Hand Scanner (Auto-focused) --}}
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark" style="font-size: 1rem;">
                                <i class="fas fa-barcode mr-2 text-primary"></i> Lector Manual (Escáner de Mano)
                            </label>
                            <div x-data x-init="setTimeout(() => $refs.handScanner.focus(), 300)">
                                <input type="text" 
                                       x-ref="handScanner" 
                                       wire:model="scannerInput" 
                                       wire:keydown.enter.prevent="handleScanner" 
                                       class="form-control text-center shadow-sm font-weight-bold" 
                                       placeholder="Pase el escáner sobre el código de barra..."
                                       style="font-size: 1.1rem; height: 50px; border: 2px solid #3b3f5c; border-radius: 10px;">
                            </div>
                            <small class="form-text text-muted text-center mt-1">El lector manual enfocará este campo al cargar la página automáticamente.</small>
                        </div>

                        <div class="text-center my-3 text-uppercase font-weight-bold text-muted" style="font-size: 0.75rem; letter-spacing: 2px;">
                            — O TAMBIÉN —
                        </div>

                        {{-- 2. Camera QR Scanner --}}
                        <div class="form-group mb-4">
                            <label class="font-weight-bold text-dark" style="font-size: 1rem;">
                                <i class="fas fa-camera mr-2 text-success"></i> Escáner de Cámara (Webcam / Celular)
                            </label>
                            <div x-data="{
                                active: false,
                                scanner: null,
                                start() {
                                    this.active = true;
                                    this.$nextTick(() => {
                                        this.scanner = new Html5Qrcode('webcam-reader');
                                        this.scanner.start(
                                            { facingMode: 'environment' },
                                            { fps: 10, qrbox: 250 },
                                            (decodedText) => {
                                                this.stop();
                                                $wire.set('scannerInput', decodedText);
                                                $wire.handleScanner();
                                            },
                                            (error) => {}
                                        ).catch(err => {
                                            alert('No se pudo iniciar la cámara: ' + err);
                                            this.active = false;
                                        });
                                    });
                                },
                                stop() {
                                    if (this.scanner) {
                                        this.scanner.stop().then(() => {
                                            this.active = false;
                                            this.scanner = null;
                                        }).catch(err => {
                                            this.active = false;
                                            this.scanner = null;
                                        });
                                    } else {
                                        this.active = false;
                                    }
                                }
                            }">
                                <button type="button" x-show="!active" @click="start()" class="btn btn-success btn-block shadow-sm font-weight-bold" style="height: 45px; border-radius: 10px;">
                                    <i class="fas fa-video mr-2"></i> Activar Cámara para Escanear QR
                                </button>
                                <button type="button" x-show="active" @click="stop()" class="btn btn-danger btn-block shadow-sm font-weight-bold" style="height: 45px; border-radius: 10px;">
                                    <i class="fas fa-stop mr-2"></i> Detener Cámara
                                </button>

                                <div x-show="active" id="webcam-reader" style="width: 100%; max-width: 400px; margin: 15px auto; border: 2px dashed #28a745; border-radius: 10px; overflow: hidden; background: #000;"></div>
                            </div>
                        </div>

                        <div class="text-center my-3 text-uppercase font-weight-bold text-muted" style="font-size: 0.75rem; letter-spacing: 2px;">
                            — O TAMBIÉN —
                        </div>

                        {{-- 3. Manual Entry --}}
                        <div class="form-group mb-2">
                            <label class="font-weight-bold text-dark" style="font-size: 1rem;">
                                <i class="fas fa-keyboard mr-2 text-info"></i> Entrada Manual
                            </label>
                            <div class="input-group">
                                <input type="text" 
                                       wire:model="searchQuery" 
                                       wire:keydown.enter.prevent="search" 
                                       class="form-control" 
                                       placeholder="Ingrese número de planilla o ID..."
                                       style="height: 45px; border-radius: 10px 0 0 10px;">
                                <div class="input-group-append">
                                    <button wire:click="search" class="btn btn-primary font-weight-bold" type="button" style="border-radius: 0 10px 10px 0;">
                                        <i class="fas fa-search mr-1"></i> Buscar
                                    </button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- WORKSPACE SPLIT-SCREEN --}}
        <div class="row layout-top-spacing" style="margin-left: -20px; margin-right: -20px; height: calc(100vh - 120px);">
            {{-- Left Column: PDF Viewer --}}
            <div class="col-lg-6 col-md-12 pr-md-0 h-100">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 10px; overflow: hidden; display: flex; flex-direction: column;">
                    <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center py-2" style="flex-shrink: 0;">
                        <span class="font-weight-bold text-white"><i class="far fa-file-pdf mr-2"></i> Relación de Cobros PDF</span>
                        <a href="{{ route('reports.collection.relationship.pdf', ['sheet' => $sheet->id]) }}" target="_blank" class="btn btn-sm btn-outline-light text-white">
                            <i class="fas fa-external-link-alt"></i> Ver pantalla completa
                        </a>
                    </div>
                    <div class="card-body p-0" style="flex-grow: 1; position: relative;">
                        <iframe src="{{ route('reports.collection.relationship.pdf', ['sheet' => $sheet->id]) }}" width="100%" height="100%" style="border: none; position: absolute; top:0; left:0;"></iframe>
                    </div>
                </div>
            </div>

            {{-- Right Column: Reconciled checklist & Semaphores --}}
            <div class="col-lg-6 col-md-12 pl-md-2 mt-md-3 mt-lg-0 h-100">
                <div class="card shadow-sm border-0 h-100" style="border-radius: 10px; overflow: hidden; display: flex; flex-direction: column;">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2" style="flex-shrink: 0;">
                        <span class="font-weight-bold text-white"><i class="fas fa-tasks mr-2 text-warning"></i> Panel de Conciliación y Auditoría</span>
                        <a href="{{ route('audit.sheet') }}" class="btn btn-sm btn-light font-weight-bold" style="color: #212529 !important;">
                            <i class="fas fa-arrow-left"></i> Volver al Portal
                        </a>
                    </div>
                    <div class="card-body p-3" style="flex-grow: 1; overflow-y: auto; display: flex; flex-direction: column;">
                        
                        @if (session()->has('error'))
                            <div class="alert alert-danger py-2 shadow-sm mb-3">
                                {{ session('error') }}
                            </div>
                        @endif
                        @if (session()->has('success'))
                            <div class="alert alert-success py-2 shadow-sm mb-3">
                                {{ session('success') }}
                            </div>
                        @endif

                        {{-- Sheet Info Card --}}
                        <div class="p-3 mb-3 border bg-light rounded shadow-sm d-flex justify-content-between align-items-center" style="flex-shrink: 0;">
                            <div>
                                <h4 class="mb-1 font-weight-bold text-dark" style="font-size: 1.15rem;">Planilla: {{ $sheet->sheet_number }}</h4>
                                <span class="text-muted small"><i class="far fa-calendar-alt mr-1"></i> Abierta: {{ $sheet->opened_at->format('d/m/Y h:i A') }}</span>
                            </div>
                            {{-- Quick Barcode Input for continuous scanning --}}
                            <div class="d-flex align-items-center" style="max-width: 250px;">
                                <span class="text-muted mr-2 font-weight-bold" style="font-size: 0.8rem;"><i class="fas fa-barcode"></i> Lector:</span>
                                <input type="text" 
                                       wire:model="scannerInput" 
                                       wire:keydown.enter.prevent="handleScanner" 
                                       class="form-control form-control-sm text-center font-weight-bold" 
                                       placeholder="Escanee otra..."
                                       style="width: 130px; border-color: #3b3f5c;">
                            </div>
                        </div>

                        {{-- Checklist Area --}}
                        <div class="flex-grow-1" style="overflow-y: auto;">
                            <h5 class="font-weight-bold text-dark mb-2" style="font-size: 1rem;">Transacciones en la Planilla</h5>
                            
                            <table class="table table-bordered table-hover table-sm">
                                <thead class="bg-dark text-white">
                                    <tr>
                                        <th class="text-center text-white" style="width: 70px;">Rent.</th>
                                        <th class="text-white">Documento</th>
                                        <th class="text-white">Cliente / Detalles</th>
                                        <th class="text-right text-white">Monto Original</th>
                                        <th class="text-right text-white">Tasa</th>
                                        <th class="text-center text-white" style="width: 90px;">Conciliado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $totalReconciledCount = $payments->where('is_bank_reconciled', true)->count();
                                        $totalPaymentsCount = $payments->count();
                                    @endphp
                                    
                                    @if($payments->isEmpty() && $returns->isEmpty())
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">No hay transacciones registradas en esta planilla.</td>
                                        </tr>
                                    @endif

                                    @foreach($payments as $p)
                                        @php
                                            $val = $this->getPaymentValidation($p);
                                            $sale = $p->sale;
                                        @endphp
                                        <tr class="{{ $p->status === 'voided' ? 'table-secondary text-muted' : '' }}">
                                            {{-- Profitability Semaphore --}}
                                            <td class="text-center">
                                                @if($p->status === 'voided')
                                                    <span class="badge badge-dark" style="font-size: 0.65rem;">Anulada</span>
                                                @else
                                                    @php
                                                        $badgeClass = 'badge-success';
                                                        $badgeText = 'Rentable';
                                                        if ($val['color'] === 'orange') {
                                                            $badgeClass = 'badge-warning text-dark';
                                                            $badgeText = 'Alerta';
                                                        } elseif ($val['color'] === 'red') {
                                                            $badgeClass = 'badge-danger';
                                                            $badgeText = 'Pérdida';
                                                        }
                                                        
                                                        // Prepare Tooltip Math
                                                        $commissionPercent = $sale ? $sale->resolved_commission_percent : 0;
                                                        $freightPercent = $sale ? $sale->resolved_freight_percent : 0;
                                                        
                                                        $tooltipText = "Acuerdo: " . ($val['agreement'] ?? 'USD') . "\n"
                                                                    . "Costo Base: $" . number_format($val['base_amount'], 2) . "\n"
                                                                    . "Neto Recibido: $" . number_format($val['net_usd'], 2) . "\n"
                                                                    . "Tasa Pago: " . number_format($p->exchange_rate, 2) . " Bs.\n"
                                                                    . "Tasa BCV del día: " . number_format($val['bcv_rate'], 2) . " Bs.\n"
                                                                    . "Tasa Binance del día: " . number_format($val['binance_rate'], 2) . " Bs.\n"
                                                                    . "Comisión: {$commissionPercent}%, Flete: {$freightPercent}%\n"
                                                                    . "Detalle: {$val['message']}";
                                                    @endphp
                                                    <span class="badge {{ $badgeClass }} font-weight-bold" 
                                                          style="cursor: pointer; padding: 4px 6px; border-radius: 8px; font-size: 0.65rem;" 
                                                          wire:click="showAuditDetails({{ $p->id }})">
                                                        {{ $badgeText }} <i class="fas fa-search-plus ml-1" style="font-size: 0.60rem;"></i>
                                                    </span>
                                                @endif
                                            </td>

                                            {{-- Document Number --}}
                                            <td>
                                                <div class="font-weight-bold text-dark small">
                                                    @if($sale)
                                                        Factura: {{ $sale->invoice_number ?: ('#' . $sale->id) }}
                                                    @else
                                                        Pago Suelto
                                                    @endif
                                                </div>
                                                <div class="text-muted" style="font-size: 0.7rem;">{{ strtoupper($p->pay_way) }}</div>
                                            </td>

                                            {{-- Client & Surcharges --}}
                                            <td>
                                                <div class="font-weight-bold text-truncate text-dark small" style="max-width: 180px;">
                                                    @if($sale && $sale->customer)
                                                        {{ $sale->customer->name }}
                                                    @else
                                                        Sin cliente
                                                    @endif
                                                </div>
                                                <div class="text-muted" style="font-size: 0.7rem;">
                                                    Vía: {{ $p->bank ? strtoupper($p->bank) : 'EFECTIVO' }}
                                                    @if($p->deposit_number)
                                                        | Ref: {{ $p->deposit_number }}
                                                    @elseif($p->zelleRecord)
                                                        | Ref: {{ $p->zelleRecord->reference }}
                                                    @endif
                                                </div>
                                            </td>

                                            {{-- Original Amount --}}
                                            <td class="text-right font-weight-bold text-dark small">
                                                {{ number_format($p->amount, 2) }} {{ $p->currency }}
                                            </td>

                                            {{-- Applied Rate --}}
                                            <td class="text-right text-muted small">
                                                {{ number_format($p->exchange_rate, 4) }}
                                            </td>

                                            {{-- Reconciliation Toggle --}}
                                            <td class="text-center">
                                                @if($p->status === 'voided')
                                                    -
                                                @else
                                                    <div class="custom-control custom-switch">
                                                        <input type="checkbox" 
                                                               class="custom-control-input" 
                                                               id="reconcile-switch-{{ $p->id }}" 
                                                               wire:click="toggleReconciliation({{ $p->id }})"
                                                               {{ $p->is_bank_reconciled ? 'checked' : '' }}>
                                                        <label class="custom-control-label" for="reconcile-switch-{{ $p->id }}"></label>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach

                                    {{-- Returns List (Read-only) --}}
                                    @if(count($returns) > 0)
                                        <tr class="table-dark">
                                            <td colspan="6" class="font-weight-bold py-1 text-white" style="font-size: 0.7rem; background-color: #3b3f5c;">NOTAS DE CRÉDITO (DEVOLUCIONES)</td>
                                        </tr>
                                        @foreach($returns as $r)
                                            <tr class="table-warning">
                                                <td class="text-center">
                                                    <span class="badge badge-secondary" style="font-size: 0.65rem;">Devolución</span>
                                                </td>
                                                <td>
                                                    <div class="font-weight-bold small text-dark">Nota: {{ $r->return_number }}</div>
                                                    <div class="text-muted" style="font-size: 0.7rem;">F. {{ $r->sale->invoice_number ?? $r->sale_id }}</div>
                                                </td>
                                                <td>
                                                    <div class="font-weight-bold text-truncate text-dark small" style="max-width: 180px;">
                                                        {{ $r->customer->name }}
                                                    </div>
                                                    <div class="text-muted" style="font-size: 0.7rem;">{{ $r->reason ?: 'Devolución de productos' }}</div>
                                                </td>
                                                <td class="text-right font-weight-bold text-dark small">
                                                    -{{ number_format($r->total_returned, 2) }} {{ $r->sale->primary_currency_code ?? 'USD' }}
                                                </td>
                                                <td class="text-right text-muted small">
                                                    {{ number_format($r->sale->primary_exchange_rate, 4) }}
                                                </td>
                                                <td class="text-center text-muted font-weight-bold">-</td>
                                            </tr>
                                        @endforeach
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        {{-- Footer Audit Statistics --}}
                        <div class="mt-3 p-3 border rounded bg-dark text-white d-flex justify-content-between align-items-center shadow-sm" style="flex-shrink: 0;">
                            <div>
                                <h6 class="mb-0 text-white-50 small">Resumen de Conciliación Bancaria:</h6>
                                <h4 class="mb-0 text-white font-weight-bold" style="font-size: 1.2rem;">
                                    {{ $totalReconciledCount }} / {{ $totalPaymentsCount }} Conciliados 
                                    @if($totalPaymentsCount > 0)
                                        <span style="font-size: 0.85rem;" class="font-weight-normal text-warning">({{ round(($totalReconciledCount / $totalPaymentsCount) * 100) }}%)</span>
                                    @endif
                                </h4>
                            </div>
                            <div class="d-flex align-items-center">
                                @if($totalReconciledCount === $totalPaymentsCount && $totalPaymentsCount > 0)
                                    <span class="badge badge-success py-2 px-3 font-weight-bold text-white mr-2" style="border-radius: 12px; font-size: 0.75rem;">
                                        <i class="fas fa-check-double mr-1"></i> Completado
                                    </span>
                                @else
                                    <span class="badge badge-warning text-dark py-2 px-3 font-weight-bold mr-2" style="border-radius: 12px; font-size: 0.75rem;">
                                        <i class="fas fa-spinner fa-spin mr-1"></i> Pendiente
                                    </span>
                                @endif

                                @if($sheet->status === 'closed')
                                    <button class="btn btn-secondary font-weight-bold py-2 px-3 btn-sm" disabled style="border-radius: 12px;">
                                        <i class="fas fa-lock mr-1"></i> Auditoría Finalizada
                                    </button>
                                @else
                                    <button wire:click="finalizeAudit" wire:loading.attr="disabled" class="btn btn-warning text-dark font-weight-bold py-2 px-3 btn-sm" style="border-radius: 12px; background-color: #ffc107; border-color: #ffc107;">
                                        <i class="fas fa-flag-checkered mr-1"></i> Finalizar Auditoría
                                    </button>
                                @endif
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Modal Audit Detail -->
    <div class="modal fade" id="modalAuditDetail" tabindex="-1" role="dialog" aria-labelledby="modalAuditDetailLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                @if($selectedPaymentDetails)
                    @php
                        $color = $selectedPaymentDetails['color'];
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
                        
                        $netDiff = $selectedPaymentDetails['net_real_usd'] - $selectedPaymentDetails['payment_base_prop'];
                    @endphp
                    <div class="modal-header {{ $headerBg }} text-white py-3 border-0">
                        <h5 class="modal-title font-weight-bold text-white d-flex align-items-center" id="modalAuditDetailLabel">
                            <i class="fas {{ $icon }} mr-2" style="font-size: 1.3rem;"></i>
                            Detalle de Auditoría - Factura {{ $selectedPaymentDetails['invoice_number'] }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white close text-white border-0 bg-transparent" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close" wire:click="closeAuditDetails" style="font-size: 1.5rem; line-height: 1; outline: none; opacity: 0.9;">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4 text-dark" style="background: #f8f9fa; max-height: 75vh; overflow-y: auto;">
                        
                        {{-- Status Banner --}}
                        <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px; background: #fff;">
                            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                                <div>
                                    <span class="text-muted small text-uppercase font-weight-bold" style="letter-spacing: 1px; font-size: 0.75rem;">Estado de Rentabilidad</span>
                                    <h4 class="font-weight-bold mb-0 {{ $color === 'red' ? 'text-danger' : ($color === 'orange' ? 'text-warning' : 'text-success') }}">
                                        {{ $statusText }}
                                    </h4>
                                </div>
                                <div class="text-right">
                                    <span class="text-muted small d-block">Detalle del Sistema:</span>
                                    <p class="mb-0 font-weight-bold text-dark">{{ $selectedPaymentDetails['message'] }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            {{-- Col 1: Invoice Configuration --}}
                            <div class="col-md-6 mb-3">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                                    <div class="card-header bg-dark text-white font-weight-bold py-2" style="font-size: 0.9rem;">
                                        <i class="fas fa-file-invoice-dollar mr-2"></i> Configuración de Factura
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">Cliente:</span>
                                            <span class="font-weight-bold text-dark text-truncate" style="max-width: 180px;" title="{{ $selectedPaymentDetails['client_name'] }}">
                                                {{ $selectedPaymentDetails['client_name'] }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">Acuerdo de Pago:</span>
                                            <span class="badge badge-info font-weight-bold" style="padding: 4px 8px; border-radius: 5px;">{{ $selectedPaymentDetails['agreement'] }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">Total Facturado (USD):</span>
                                            <span class="font-weight-bold text-dark">${{ number_format($selectedPaymentDetails['invoice_total'], 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Costo Base:</span>
                                            <span class="text-dark">${{ number_format($selectedPaymentDetails['base_amount'], 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Comisión ({{ $selectedPaymentDetails['commission_percent'] }}%):</span>
                                            <span class="text-dark">${{ number_format($selectedPaymentDetails['commission_amount'], 2) }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Flete ({{ $selectedPaymentDetails['freight_percent'] }}%):</span>
                                            <span class="text-dark">${{ number_format($selectedPaymentDetails['freight_amount'], 2) }}</span>
                                        </div>
                                        @if($selectedPaymentDetails['diff_percent'] > 0)
                                            <div class="d-flex justify-content-between mb-1">
                                                <span class="text-muted">Diferencial ({{ $selectedPaymentDetails['diff_percent'] }}%):</span>
                                                <span class="text-warning font-weight-bold">${{ number_format($selectedPaymentDetails['diff_amount'], 2) }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            {{-- Col 2: Received Payment --}}
                            <div class="col-md-6 mb-3">
                                <div class="card border-0 shadow-sm h-100" style="border-radius: 10px;">
                                    <div class="card-header bg-dark text-white font-weight-bold py-2" style="font-size: 0.9rem;">
                                        <i class="fas fa-hand-holding-usd mr-2"></i> Detalles del Pago
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">Monto Cobrado:</span>
                                            <span class="font-weight-bold text-primary" style="font-size: 1.1rem;">
                                                {{ number_format($selectedPaymentDetails['payment_amount'], 2) }} {{ $selectedPaymentDetails['payment_currency'] }}
                                            </span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">Método de Pago:</span>
                                            <span class="font-weight-bold text-dark text-uppercase">{{ $selectedPaymentDetails['pay_way'] }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">Banco / Destino:</span>
                                            <span class="font-weight-bold text-dark text-uppercase">{{ $selectedPaymentDetails['bank_name'] ?: 'N/A' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-2 pb-1 border-bottom">
                                            <span class="text-muted">Referencia:</span>
                                            <span class="font-weight-bold text-dark">{{ $selectedPaymentDetails['reference'] ?: 'N/A' }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Tasa de Cobro:</span>
                                            <span class="font-weight-bold text-dark">{{ number_format($selectedPaymentDetails['payment_rate'], 4) }} Bs</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted">Equivalente USD:</span>
                                            <span class="font-weight-bold text-dark">${{ number_format($selectedPaymentDetails['payment_usd'], 2) }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Section 3: Exchange Rates --}}
                        <div class="card border-0 shadow-sm mb-4" style="border-radius: 10px;">
                            <div class="card-body p-3 bg-white" style="border-radius: 10px;">
                                <h6 class="font-weight-bold text-dark mb-3"><i class="fas fa-history mr-2 text-info"></i> Tasas Referenciales del Día del Pago</h6>
                                <div class="row text-center">
                                    <div class="col-md-6 border-right mb-2 mb-md-0">
                                        <span class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.75rem;">Tasa BCV Oficial</span>
                                        <h4 class="font-weight-bold text-dark mb-0 mt-1">{{ number_format($selectedPaymentDetails['bcv_rate'], 4) }} Bs</h4>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="text-muted small text-uppercase font-weight-bold" style="font-size: 0.75rem;">Tasa Binance Aplicada</span>
                                        <h4 class="font-weight-bold text-primary mb-0 mt-1">{{ number_format($selectedPaymentDetails['binance_rate'], 4) }} Bs</h4>
                                        
                                        @if(isset($selectedPaymentDetails['binance_rates']) && count($selectedPaymentDetails['binance_rates']) > 0)
                                            <div class="mt-2 d-flex flex-wrap justify-content-center align-items-center">
                                                <span class="text-muted small mr-2 font-weight-bold" style="font-size: 0.7rem;">Tasas del día:</span>
                                                @foreach($selectedPaymentDetails['binance_rates'] as $rate)
                                                    @if(abs($selectedPaymentDetails['binance_rate'] - $rate) < 0.01)
                                                        <span class="badge badge-success text-white px-2 py-1 mr-1 mb-1 font-weight-bold" style="font-size: 0.7rem;" title="Tasa coincidente utilizada para auditar el pago">
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

                        {{-- Section 4: Mathematical Calculation Table --}}
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
                                        @php
                                            $realPaymentUsd = $selectedPaymentDetails['net_real_usd'] + $selectedPaymentDetails['payment_freight_prop'] + $selectedPaymentDetails['payment_commission_prop'];
                                        @endphp
                                        <tr>
                                            <td class="text-left font-weight-bold text-dark">Costo Base (Productos)</td>
                                            <td class="text-dark">${{ number_format($selectedPaymentDetails['base_amount'], 2) }}</td>
                                            <td class="text-dark">{{ number_format(($selectedPaymentDetails['invoice_total'] > 0 ? ($selectedPaymentDetails['base_amount'] / $selectedPaymentDetails['invoice_total']) * 100 : 0), 1) }}%</td>
                                            <td class="text-dark">${{ number_format($selectedPaymentDetails['payment_base_prop'], 2) }}</td>
                                            <td class="font-weight-bold {{ $netDiff >= -0.0001 ? 'text-success' : 'text-danger' }}">
                                                ${{ number_format($selectedPaymentDetails['net_real_usd'], 2) }}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-left text-dark">Comisión de Vendedor</td>
                                            <td class="text-dark">${{ number_format($selectedPaymentDetails['commission_amount'], 2) }}</td>
                                            <td class="text-dark">{{ number_format(($selectedPaymentDetails['invoice_total'] > 0 ? ($selectedPaymentDetails['commission_amount'] / $selectedPaymentDetails['invoice_total']) * 100 : 0), 1) }}%</td>
                                            <td class="text-dark">${{ number_format($selectedPaymentDetails['payment_commission_prop'], 2) }}</td>
                                            <td class="text-dark">${{ number_format($selectedPaymentDetails['payment_commission_prop'], 2) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="text-left text-dark">Flete de Despacho</td>
                                            <td class="text-dark">${{ number_format($selectedPaymentDetails['freight_amount'], 2) }}</td>
                                            <td class="text-dark">{{ number_format(($selectedPaymentDetails['invoice_total'] > 0 ? ($selectedPaymentDetails['freight_amount'] / $selectedPaymentDetails['invoice_total']) * 100 : 0), 1) }}%</td>
                                            <td class="text-dark">${{ number_format($selectedPaymentDetails['payment_freight_prop'], 2) }}</td>
                                            <td class="text-dark">${{ number_format($selectedPaymentDetails['payment_freight_prop'], 2) }}</td>
                                        </tr>
                                        @if($selectedPaymentDetails['diff_percent'] > 0)
                                            <tr>
                                                <td class="text-left text-dark">Diferencial Cambiario</td>
                                                <td class="text-dark">${{ number_format($selectedPaymentDetails['diff_amount'], 2) }}</td>
                                                <td class="text-dark">{{ number_format(($selectedPaymentDetails['invoice_total'] > 0 ? ($selectedPaymentDetails['diff_amount'] / $selectedPaymentDetails['invoice_total']) * 100 : 0), 1) }}%</td>
                                                <td class="text-dark">${{ number_format($selectedPaymentDetails['payment_diff_prop'], 2) }}</td>
                                                <td class="text-dark">${{ number_format($selectedPaymentDetails['payment_diff_prop'], 2) }}</td>
                                            </tr>
                                        @endif
                                        <tr class="table-info font-weight-bold text-dark" style="font-size: 0.9rem;">
                                            <td colspan="3" class="text-right font-weight-bold">Monto Total de este Pago:</td>
                                            <td class="text-muted font-weight-bold">${{ number_format($selectedPaymentDetails['payment_usd'], 2) }}</td>
                                            <td class="text-primary font-weight-bold">${{ number_format($realPaymentUsd, 2) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- Section 5: Real USD recovered and mathematical audit explanation --}}
                        <div class="card border-0 shadow-sm" style="border-radius: 10px; background: #fff;">
                            <div class="card-body p-3">
                                <h5 class="font-weight-bold text-dark mb-3">
                                    <i class="fas fa-project-diagram text-info mr-2"></i> 
                                    Análisis del Contravalor y Recuperación Cambiaria
                                </h5>
                                
                                <div class="row mb-3 align-items-center">
                                    <div class="col-md-6 border-right">
                                        <p class="text-muted small mb-1">Monto Neto Real Recuperado (USD Efectivo):</p>
                                        <h3 class="font-weight-bold text-dark mb-0">${{ number_format($selectedPaymentDetails['net_real_usd'], 2) }}</h3>
                                        <small class="text-muted">Calculado descontando Flete y Comisión sobre el contravalor real en dólares del día.</small>
                                    </div>
                                    <div class="col-md-6 pl-md-4">
                                        <p class="text-muted small mb-1">Margen vs Costo Base Proporcional:</p>
                                        <h3 class="font-weight-bold mb-0 {{ $netDiff >= -0.0001 ? 'text-success' : 'text-danger' }}">
                                            {{ $netDiff >= -0.0001 ? '+' : '' }}${{ number_format($netDiff, 2) }}
                                        </h3>
                                        <small class="text-muted">Debe ser mayor o igual a $0.00 para no incurrir en pérdidas frente al costo base de los productos.</small>
                                    </div>
                                </div>

                                <hr>

                                <div class="alert border-0 p-3 mt-3" style="border-radius: 10px; background-color: {{ $color === 'red' ? '#fde8e8' : ($color === 'orange' ? '#fef3c7' : '#def7ec') }}; color: {{ $color === 'red' ? '#9b1c1c' : ($color === 'orange' ? '#92400e' : '#03543f') }};">
                                    <h6 class="font-weight-bold mb-2">
                                        <i class="fas fa-info-circle mr-1"></i> Razón Matemática de la Clasificación:
                                    </h6>
                                    <p class="mb-0 small shadow-none" style="line-height: 1.5;">
                                        @if($selectedPaymentDetails['payment_currency'] === 'USD')
                                            <strong>Pago directo en USD:</strong> El pago se recibe en la moneda base (USD). El contravalor real coincide con el cobrado.
                                            <br>
                                            Fórmula: <code>Neto USD = Pago USD - Flete Prop. - Comisión Prop.</code>
                                            <br>
                                            Matemática: <code>${{ number_format($selectedPaymentDetails['payment_usd'], 2) }} - ${{ number_format($selectedPaymentDetails['payment_freight_prop'], 2) }} - ${{ number_format($selectedPaymentDetails['payment_commission_prop'], 2) }} = ${{ number_format($selectedPaymentDetails['net_real_usd'], 2) }}</code>.
                                            @if($netDiff >= -0.0001)
                                                El neto real de ${{ number_format($selectedPaymentDetails['net_real_usd'], 2) }} cubre el costo base de ${{ number_format($selectedPaymentDetails['payment_base_prop'], 2) }} satisfactoriamente.
                                            @else
                                                El neto real de ${{ number_format($selectedPaymentDetails['net_real_usd'], 2) }} es menor que el costo base de ${{ number_format($selectedPaymentDetails['payment_base_prop'], 2) }}, resultando en pérdida.
                                            @endif
                                        @elseif($selectedPaymentDetails['agreement'] === 'USD')
                                            <strong>Factura con Acuerdo USD cobrada en Bolívares (VES):</strong> El cliente acordó pagar en dólares pero pagó en Bolívares.
                                            @if($color === 'red' && $selectedPaymentDetails['payment_rate'] <= $selectedPaymentDetails['bcv_rate'] + 0.05)
                                                <br><strong class="text-danger">INCUMPLIMIENTO DE ACUERDO:</strong> El cliente pagó a tasa BCV ({{ number_format($selectedPaymentDetails['payment_rate'], 2) }} Bs) en lugar de tasa Binance/Paralelo ({{ number_format($selectedPaymentDetails['binance_rate'], 2) }} Bs).
                                                Al comprar dólares con los Bolívares recibidos en el mercado paralelo, sufrimos una pérdida cambiaria directa de 
                                                <span class="font-weight-bold">${{ number_format($selectedPaymentDetails['payment_usd'] - ($selectedPaymentDetails['payment_amount'] / $selectedPaymentDetails['binance_rate']), 2) }}</span>.
                                                <br>
                                                Fórmula de Conversión: <code>USD Real = Pago VES / Tasa Binance</code>
                                                <br>
                                                Matemática: <code>{{ number_format($selectedPaymentDetails['payment_amount'], 2) }} Bs / {{ number_format($selectedPaymentDetails['binance_rate'], 2) }} = ${{ number_format($selectedPaymentDetails['payment_amount'] / $selectedPaymentDetails['binance_rate'], 2) }}</code> (en lugar de los ${{ number_format($selectedPaymentDetails['payment_usd'], 2) }} teóricos).
                                                <br>
                                                Neto Real: <code>Neto Real = USD Real - Flete Prop. - Comisión Prop.</code>
                                                <br>
                                                Cálculo: <code>${{ number_format($selectedPaymentDetails['payment_amount'] / $selectedPaymentDetails['binance_rate'], 2) }} - ${{ number_format($selectedPaymentDetails['payment_freight_prop'], 2) }} - ${{ number_format($selectedPaymentDetails['payment_commission_prop'], 2) }} = ${{ number_format($selectedPaymentDetails['net_real_usd'], 2) }}</code>.
                                            @else
                                                El pago se cobró a tasa Binance ({{ number_format($selectedPaymentDetails['payment_rate'], 2) }} Bs), mitigando la pérdida por tasa. El contravalor neto real es de ${{ number_format($selectedPaymentDetails['net_real_usd'], 2) }}.
                                                @if($netDiff < -0.0001)
                                                    Sin embargo, el monto neto real sigue siendo inferior al costo base proporcional de ${{ number_format($selectedPaymentDetails['payment_base_prop'], 2) }}.
                                                @endif
                                            @endif
                                        @else
                                            <strong>Factura con Acuerdo BCV cobrada en Bolívares (VES):</strong> El cliente acordó pagar en Bolívares indexados a la tasa BCV.
                                            @if($color === 'orange')
                                                <br><strong class="text-warning">ALERTA DE TASA:</strong> La tasa de cobro aplicada ({{ number_format($selectedPaymentDetails['payment_rate'], 2) }} Bs) no coincide con la tasa BCV oficial del día del pago ({{ number_format($selectedPaymentDetails['bcv_rate'], 2) }} Bs).
                                            @endif
                                            <br>
                                            Para calcular el valor real recuperado en dólares, primero convertimos el pago a Bolívares teóricos a tasa BCV y luego lo dividimos por la tasa Binance de mercado del día del pago.
                                            <br>
                                            Fórmula de Conversión Real: <code>USD Real = (Pago USD Cubierto * Tasa BCV) / Tasa Binance</code>
                                            <br>
                                            Matemática: <code>({{ number_format($selectedPaymentDetails['payment_usd'], 2) }} * {{ number_format($selectedPaymentDetails['bcv_rate'], 2) }}) / {{ number_format($selectedPaymentDetails['binance_rate'], 2) }} = ${{ number_format(($selectedPaymentDetails['payment_usd'] * $selectedPaymentDetails['bcv_rate']) / ($selectedPaymentDetails['binance_rate'] ?: 1), 2) }}</code>.
                                            <br>
                                            Neto Real: <code>Neto Real = USD Real - Flete Prop. - Comisión Prop.</code>
                                            <br>
                                            Matemática Neto: <code>${{ number_format(($selectedPaymentDetails['payment_usd'] * $selectedPaymentDetails['bcv_rate']) / ($selectedPaymentDetails['binance_rate'] ?: 1), 2) }} - ${{ number_format($selectedPaymentDetails['payment_freight_prop'], 2) }} - ${{ number_format($selectedPaymentDetails['payment_commission_prop'], 2) }} = ${{ number_format($selectedPaymentDetails['net_real_usd'], 2) }}</code>.
                                            @if($netDiff >= -0.0001)
                                                El neto recuperado de ${{ number_format($selectedPaymentDetails['net_real_usd'], 2) }} cubre el costo base de ${{ number_format($selectedPaymentDetails['payment_base_prop'], 2) }}.
                                            @else
                                                <br><strong class="text-danger">PÉRDIDA CAMBIARIA:</strong> A pesar de cumplir con la tasa de facturación, la diferencia entre la tasa BCV y la tasa Binance de mercado genera un saldo neto real de ${{ number_format($selectedPaymentDetails['net_real_usd'], 2) }}, el cual no es suficiente para cubrir el costo base de ${{ number_format($selectedPaymentDetails['payment_base_prop'], 2) }}.
                                            @endif
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light py-2">
                        <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal" data-bs-dismiss="modal" wire:click="closeAuditDetails">
                            <i class="fas fa-times mr-1"></i> Cerrar Auditoría
                        </button>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Modal Finalize Warning -->
    <div class="modal fade" id="modalFinalizeWarning" tabindex="-1" role="dialog" aria-labelledby="modalFinalizeWarningLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 15px; overflow: hidden;">
                <div class="modal-header bg-warning text-dark py-3 border-0">
                    <h5 class="modal-title font-weight-bold d-flex align-items-center text-dark" id="modalFinalizeWarningLabel">
                        <i class="fas fa-exclamation-triangle mr-2 text-dark" style="font-size: 1.3rem;"></i>
                        Pagos sin Conciliar en Planilla
                    </h5>
                    <button type="button" class="btn-close close text-dark border-0 bg-transparent" data-dismiss="modal" aria-label="Close" style="font-size: 1.5rem; line-height: 1; outline: none; opacity: 0.9;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-4 text-dark" style="background: #f8f9fa;">
                    <p class="mb-3">
                        Esta planilla contiene pagos aprobados que aún <strong>no han sido conciliados en el banco</strong>.
                    </p>
                    <p class="mb-0">
                        ¿Cómo desea proceder?
                    </p>
                </div>
                <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary font-weight-bold" data-dismiss="modal">
                        Cancelar
                    </button>
                    <div>
                        <button type="button" wire:click="confirmFinalizeAudit(false)" class="btn btn-danger font-weight-bold mr-1" data-dismiss="modal">
                            Finalizar sin conciliar
                        </button>
                        <button type="button" wire:click="confirmFinalizeAudit(true)" class="btn btn-success font-weight-bold" data-dismiss="modal">
                            Conciliar todo y finalizar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('my-scripts')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('show-audit-modal', (event) => {
            $('#modalAuditDetail').modal('show');
        });
        Livewire.on('close-audit-modal', (event) => {
            $('#modalAuditDetail').modal('hide');
        });
        Livewire.on('show-finalize-warning-modal', (event) => {
            $('#modalFinalizeWarning').modal('show');
        });
        Livewire.on('close-finalize-warning-modal', (event) => {
            $('#modalFinalizeWarning').modal('hide');
        });
    });
</script>
@endpush
