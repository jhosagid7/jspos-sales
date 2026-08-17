<div>
    <div wire:ignore.self class="modal fade" id="modalPayment" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white">
                        <i class="fa fa-cash-register me-2"></i>
                        REGISTRAR PAGO
                        @if($customerName)
                            <small class="d-block mt-1" style="font-size: 0.7rem;">Cliente: {{ $customerName }}</small>
                        @endif
                    </h5>
                    <button class="btn-close btn-close-white" type="button" data-dismiss="modal" aria-label="Close" onclick="$('#modalPayment').modal('hide')"></button>
                </div>

                <div class="modal-body">
                    @php
                        // First try to find currency matching the Debt Currency Code
                        $currentCurrency = collect($currencies)->firstWhere('code', $currencyCode);
                        if (!$currentCurrency) {
                             $primaryCurrency = collect($currencies)->firstWhere('is_primary', 1);
                             $symbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                        } else {
                             $symbol = $currentCurrency->symbol;
                        }
                    @endphp

                    <style>
                        .btn-outline-wallet {
                            color: #f39c12 !important;
                            border-color: #f39c12 !important;
                            background-color: transparent !important;
                        }
                        .btn-outline-wallet:hover {
                            color: #fff !important;
                            background-color: #e67e22 !important;
                            border-color: #d35400 !important;
                        }
                        .btn-active-wallet {
                            color: #fff !important;
                            background-color: #f39c12 !important;
                            border-color: #e67e22 !important;
                            box-shadow: 0 4px 8px rgba(243, 156, 18, 0.3) !important;
                        }
                        .btn-pay-method {
                            padding: 15px 10px;
                            border-radius: 8px;
                            transition: all 0.3s ease;
                        }
                    </style>

                    <div class="row align-items-start">
                        {{-- Left Column: Summary & Method --}}
                        <div class="col-md-6">
                            {{-- Summary --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-file-invoice-dollar me-2"></i>Resumen</h6>
                                </div>
                                <div class="card-body">
                                    {{-- Adjustment Alert (Early Payment) --}}
                                    @if($adjustment)
                                        @php
                                            $adjType = $adjustment['rule_type'] ?? 'early_payment';
                                            // Dynamic color: Green/Warning if applied, Secondary if unchecked
                                            $adjColor = $applyAdjustment ? ($adjType == 'early_payment' ? 'success' : 'warning') : 'secondary';
                                            $adjTitle = $adjType == 'early_payment' ? 'Descuento' : 'Recargo';
                                            $adjIcon = $adjType == 'early_payment' ? 'arrow-down' : 'arrow-up';
                                        @endphp
                                        <div class="alert alert-{{ $adjColor }} mb-3 p-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa fa-{{ $adjIcon }} fa-2x me-3"></i>
                                                    <div>
                                                        <h6 class="alert-heading fw-bold mb-0" style="font-size: 0.9rem;">
                                                            {{ "¡Aplica $adjTitle!" }}
                                                        </h6>
                                                        <div class="fw-bold">
                                                            {{ $adjustment['percentage'] }}% ({{ $symbol }}{{ number_format($adjustment['amount'], 2) }})
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" wire:model.live="applyAdjustment" wire:click="toggleAdjustment" id="toggleAdjustment">
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- USD Discount Alert --}}
                                    {{-- Show ONLY if Eligible for USD Discount (No VED payments, etc) --}}
                                    @if($allowDiscounts && $usdAdjustment)
                                        <div class="alert alert-{{ $applyUsdDiscount ? 'info' : 'secondary' }} mb-3 p-2">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div class="d-flex align-items-center">
                                                    <i class="fa fa-percentage fa-2x me-3"></i>
                                                    <div>
                                                        <h6 class="alert-heading fw-bold mb-0" style="font-size: 0.9rem;">
                                                            Desc. Pago Divisa (Al Liquidar)
                                                        </h6>
                                                        <div class="fw-bold">
                                                            {{ $usdPaymentDiscountPercent }}% ({{ $symbol }}{{ number_format($usdAdjustment['amount'] ?? $fixedUsdDiscountAmount, 2) }})
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" wire:model.live="applyUsdDiscount" wire:click="toggleUsdDiscount" id="toggleUsdDiscount">
                                                </div>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Calculation Details --}}
                                    <ul class="list-group list-group-flush mb-2">
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1">
                                            <span>Deuda Actual:</span>
                                            <span class="fw-bold">{{ $symbol }}{{ number_format($totalToPay, 2) }}</span>
                                        </li>
                                        
                                        {{-- Only show Adjustment if actively applied --}}
                                        @if($adjustment && $applyAdjustment)
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 text-muted">
                                                <span>{{ $adjustment['rule_type'] == 'early_payment' ? 'Descuento' : 'Recargo' }} ({{ $adjustment['percentage'] }}%):</span>
                                                <span class="fw-bold text-danger">
                                                    -{{ $symbol }}{{ number_format($adjustment['amount'], 2) }}
                                                </span>
                                            </li>
                                        @endif
                                        
                                        {{-- Only show USD Discount if actively applied --}}
                                        @if($usdAdjustment && $applyUsdDiscount)
                                            <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-1 text-muted">
                                                <span>Desc. Divisa ({{ $usdAdjustment['percentage'] }}%):</span>
                                                <span class="fw-bold text-danger">
                                                    -{{ $symbol }}{{ number_format($usdAdjustment['amount'], 2) }}
                                                </span>
                                            </li>
                                        @endif
                                        
                                        <li class="list-group-item d-flex justify-content-between align-items-center px-0 py-2 border-top">
                                            <span class="fs-5 fw-bold text-primary">TOTAL A PAGAR:</span>
                                            <span class="fs-5 fw-bold text-primary">
                                                @php
                                                    $finalTotal = $totalToPay;
                                                    if ($adjustment && $applyAdjustment) {
                                                        $finalTotal -= $adjustment['amount']; 
                                                    }
                                                    if ($usdAdjustment && $applyUsdDiscount) {
                                                        $finalTotal -= $usdAdjustment['amount'];
                                                    }
                                                @endphp
                                                {{ $symbol }}{{ number_format($finalTotal, 2) }}
                                            </span>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            {{-- Method Selector --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-credit-card me-2"></i>Método de Pago</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row g-2 justify-content-center">
                                        <div class="col-4">
                                            <button type="button" wire:click="$set('paymentMethod', 'cash')"
                                                class="btn w-100 btn-pay-method {{ $paymentMethod === 'cash' ? 'btn-success' : 'btn-outline-success' }}">
                                                <i class="fa fa-money-bill-wave fa-2x d-block mb-2"></i>
                                                <small class="d-block">Efectivo</small>
                                            </button>
                                        </div>
                                        @module('module_advanced_payments')
                                        <div class="col-4">
                                            <button type="button" wire:click="$set('paymentMethod', 'bank')"
                                                class="btn w-100 btn-pay-method {{ $paymentMethod === 'bank' ? 'btn-primary' : 'btn-outline-primary' }}">
                                                <i class="fa fa-university fa-2x d-block mb-2"></i>
                                                <small class="d-block">Banco / Zelle</small>
                                            </button>
                                        </div>
                                        @endmodule
                                        
                                        @if($customerId && $walletBalance > 0)
                                        <div class="col-4">
                                            <button type="button" wire:click="$set('paymentMethod', 'wallet')"
                                                class="btn w-100 btn-pay-method {{ $paymentMethod === 'wallet' ? 'btn-active-wallet' : 'btn-outline-wallet' }}">
                                                <i class="fa fa-wallet fa-2x d-block mb-2"></i>
                                                <small class="d-block text-truncate fw-bold">Billetera ({{ $symbol }}{{ number_format($walletBalance, 2) }})</small>
                                            </button>
                                        </div>
                                        @endif
                                        
                                        @can('payments.create_credit_note')
                                        <div class="col-12 mt-2">
                                            <button type="button" wire:click="$set('paymentMethod', 'credit_note')"
                                                class="btn w-100 btn-pay-method {{ $paymentMethod === 'credit_note' ? 'btn-warning text-dark' : 'btn-outline-warning' }}">
                                                <i class="fa fa-file-invoice fa-lg me-2"></i>
                                                Nota de Crédito Manual (Ajuste)
                                            </button>
                                        </div>
                                        @endcan

                                        @can('manage_debit_notes')
                                        <div class="col-12 mt-2">
                                            <button type="button" wire:click="$set('paymentMethod', 'debit_note')"
                                                class="btn w-100 btn-pay-method {{ $paymentMethod === 'debit_note' ? 'btn-danger' : 'btn-outline-danger' }}">
                                                <i class="fa fa-file-invoice-dollar fa-lg me-2"></i>
                                                Nota de Débito Manual (Incremento)
                                            </button>
                                        </div>
                                        @endcan
                                    </div>
                                </div>
                            </div>

                            {{-- Input Form --}}
                            <div class="card">
                                <div class="card-body">
                                    {{-- CASH --}}
                                    @if($paymentMethod === 'cash')
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label class="form-label">Monto</label>
                                                <input class="form-control" type="number" wire:model="amount" placeholder="0.00" wire:keydown.enter="addPayment">
                                                @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Moneda</label>
                                                <select class="form-control" wire:model.live="paymentCurrency">
                                                    @foreach($currencies as $curr)
                                                        <option value="{{ $curr->code }}">{{ $curr->label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-12">
                                                <label class="form-label">Fecha de Pago</label>
                                                <input type="date" class="form-control" wire:model.live="paymentDate">
                                                @error('paymentDate') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>

                                            {{-- CASH VED EXTENDED FIELDS --}}
                                            @if(in_array($paymentCurrency, ['VED', 'VES']))
                                                <div class="col-md-12 mt-2">
                                                    <div class="card border border-primary border-opacity-25 bg-light bg-opacity-50">
                                                        <div class="card-body p-3">
                                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                                <label class="form-label mb-0 fw-bold"><i class="fa fa-coins me-1"></i> Tasa de Cambio (VED/Bs.)</label>
                                                                
                                                                {{-- Request special rate button --}}
                                                                @if(empty($currentApproval))
                                                                    <button type="button" wire:click="$toggle('showCustomRateRequest')" class="btn btn-xs btn-outline-primary border-0 py-0 px-1" style="font-size: 0.75rem;">
                                                                        <i class="fa fa-key me-1"></i> Solicitar Tasa Especial
                                                                    </button>
                                                                @endif
                                                            </div>

                                                            {{-- Strict Select Dropdown when options are available and not overridden by approved custom rate --}}
                                                            @if($currentApproval && $currentApproval['status'] === 'approved')
                                                                <div class="input-group">
                                                                    <span class="input-group-text bg-success text-white"><i class="fa fa-check-circle"></i></span>
                                                                    <input type="text" class="form-control fw-bold text-success bg-white" readonly value="{{ number_format($customExchangeRate, 2) }} Bs. (Tasa Especial Aprobada)">
                                                                    <button type="button" wire:click="cancelCustomRateRequest" class="btn btn-outline-danger" title="Quitar tasa especial">
                                                                        <i class="fa fa-times"></i>
                                                                    </button>
                                                                </div>
                                                                <small class="text-success mt-1 d-block">
                                                                    <i class="fa fa-info-circle"></i> Autorizado por: <strong>{{ $currentApproval['approver']['name'] ?? 'Supervisor' }}</strong>. Motivo: <em>"{{ $currentApproval['reason'] }}"</em>
                                                                </small>
                                                            @elseif($currentApproval && $currentApproval['status'] === 'pending')
                                                                {{-- Pending Approval State --}}
                                                                <div class="alert alert-warning mb-2 py-2 px-3 border-warning" wire:poll.5s="checkApprovalStatus">
                                                                    <div class="d-flex align-items-center justify-content-between text-start">
                                                                        <div>
                                                                            <i class="fa fa-spinner fa-spin me-2"></i>
                                                                            Solicitud Pendiente: <strong>{{ number_format($currentApproval['custom_rate'], 2) }} VED</strong>
                                                                            <small class="d-block text-muted">Motivo: "{{ $currentApproval['reason'] }}"</small>
                                                                        </div>
                                                                        <button type="button" wire:click="cancelCustomRateRequest" class="btn btn-xs btn-outline-danger border-0 text-white shadow-none">
                                                                            Cancelar
                                                                        </button>
                                                                    </div>

                                                                    {{-- OTP Code Authorization Entry --}}
                                                                    <div class="mt-2 border rounded p-2 bg-light text-start text-dark">
                                                                        <label class="form-label mb-1 fw-bold text-success" style="font-size: 0.78rem;">
                                                                            <i class="fa fa-unlock me-1"></i> ¿Tiene el código de autorización rápida?
                                                                        </label>
                                                                        <div class="input-group">
                                                                            <input type="text" class="form-control form-control-sm text-center fw-bold text-success bg-white border border-success" placeholder="Código de 6 dígitos" wire:model="otpCode" maxlength="6" wire:keydown.enter="validateOtpCode">
                                                                            <button class="btn btn-sm btn-success text-white py-1 px-3 fw-bold" type="button" wire:click="validateOtpCode">
                                                                                Validar
                                                                            </button>
                                                                        </div>
                                                                        @error('otpCode') <small class="text-danger d-block mt-1" style="font-size: 0.7rem;">{{ $message }}</small> @enderror
                                                                    </div>

                                                                    {{-- Remote Supervisor Buttons (if logged user is supervisor) --}}
                                                                    @if(auth()->user()->hasRole('Super Admin') || auth()->user()->can('payments.approve_custom_rate'))
                                                                        <div class="mt-2 pt-2 border-top border-warning d-flex gap-2">
                                                                            <button type="button" wire:click="approveCustomRateRemotely({{ $currentApproval['id'] }})" class="btn btn-xs btn-success text-white py-1">
                                                                                <i class="fa fa-check me-1"></i> Aprobar Solicitud
                                                                            </button>
                                                                            <button type="button" wire:click="rejectCustomRateRemotely({{ $currentApproval['id'] }})" class="btn btn-xs btn-danger py-1">
                                                                                <i class="fa fa-times me-1"></i> Rechazar
                                                                            </button>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                {{-- On-Site Supervisor credentials verification --}}
                                                                <div class="border rounded p-2 bg-white mt-2 text-dark text-start">
                                                                    <small class="fw-bold text-secondary d-block mb-1"><i class="fa fa-shield-alt me-1"></i> Autorización de Supervisor en Sitio</small>
                                                                    <div class="row g-1">
                                                                        <div class="col-6">
                                                                            <input type="email" class="form-control form-control-sm" placeholder="Email Supervisor" wire:model="supervisorEmail">
                                                                            @error('supervisorEmail') <small class="text-danger" style="font-size: 0.7rem;">{{ $message }}</small> @enderror
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <input type="password" class="form-control form-control-sm" placeholder="Contraseña PIN" wire:model="supervisorPassword">
                                                                            @error('supervisorPassword') <small class="text-danger" style="font-size: 0.7rem;">{{ $message }}</small> @enderror
                                                                        </div>
                                                                        <div class="col-12 mt-1">
                                                                            <button type="button" wire:click="approveCustomRateLocally" class="btn btn-sm btn-primary w-100 py-1" style="font-size: 0.75rem;">
                                                                                <i class="fa fa-fingerprint me-1"></i> Verificar y Autorizar
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                {{-- Dropdown Select of rates --}}
                                                                @if(!empty($rateOptions))
                                                                    <select class="form-control form-select fw-bold text-primary" wire:model.live="customExchangeRate">
                                                                        @foreach($rateOptions as $option)
                                                                            <option value="{{ $option['rate'] }}">{{ $option['label'] }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                @else
                                                                    {{-- Fallback standard input --}}
                                                                    <input type="number" step="0.000001" class="form-control text-primary fw-bold" wire:model.live="customExchangeRate" placeholder="Usar del sistema">
                                                                @endif
                                                                @if($customExchangeRate) 
                                                                    <div class="mt-2 d-flex flex-column gap-1 align-items-start text-start">
                                                                        <small class="text-info"><i class="fa fa-info-circle me-1"></i> Tasa VED activa: <strong>{{ number_format($customExchangeRate, 2) }} Bs.</strong></small>
                                                                        @if($isUSDInvoice)
                                                                            <span class="badge bg-warning text-dark py-1 px-2 fw-bold mt-1" style="font-size: 0.72rem; border-radius: 4px; display: inline-block;">
                                                                                <i class="fa fa-exclamation-triangle me-1"></i> Factura para cobrar en Divisa (Tasa BCV bloqueada)
                                                                            </span>
                                                                        @else
                                                                            <span class="badge bg-success text-white py-1 px-2 fw-bold mt-1" style="font-size: 0.72rem; border-radius: 4px; display: inline-block;">
                                                                                <i class="fa fa-check-circle me-1"></i> Factura con Diferencial (Permite tasa BCV)
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            {{-- Special Rate Request Form (Inline collapsible) --}}
                                                            @if($showCustomRateRequest && empty($currentApproval))
                                                                <div class="mt-3 p-3 border rounded bg-white text-start text-dark">
                                                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                                                        <h6 class="mb-0 text-primary" style="font-size: 0.85rem;"><i class="fa fa-key me-1"></i> Solicitar Tasa Especial</h6>
                                                                        <button type="button" wire:click="$set('showCustomRateRequest', false)" class="btn-close" style="font-size: 0.7rem;"></button>
                                                                    </div>
                                                                    @if(auth()->user()->can('payments.approve_custom_rate') || auth()->user()->hasRole('Super Admin'))
                                                                        <div class="row g-2">
                                                                            <div class="col-12 text-start">
                                                                                <label class="form-label" style="font-size: 0.75rem;">Tasa Manual Propuesta</label>
                                                                                <input type="number" step="0.01" class="form-control form-control-sm text-start" placeholder="0.00" wire:model="proposedCustomRate">
                                                                                @error('proposedCustomRate') <small class="text-danger">{{ $message }}</small> @enderror
                                                                            </div>
                                                                            <div class="col-12 text-start">
                                                                                <label class="form-label" style="font-size: 0.75rem;">Justificación Obligatoria <span class="text-danger">*</span></label>
                                                                                <textarea class="form-control form-control-sm text-start" rows="2" placeholder="Explique por qué requiere usar una tasa diferente..." wire:model="customRateReason"></textarea>
                                                                                @error('customRateReason') <small class="text-danger">{{ $message }}</small> @enderror
                                                                            </div>
                                                                            <div class="col-12 mt-2 d-flex gap-2">
                                                                                <button type="button" wire:click="autoApproveCustomRate" class="btn btn-sm btn-success text-white flex-grow-1">
                                                                                    <i class="fa fa-magic me-1"></i> Auto-Aprobar Tasa
                                                                                </button>
                                                                                <button type="button" wire:click="$set('showCustomRateRequest', false)" class="btn btn-sm btn-secondary">
                                                                                    Cancelar
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    @else
                                                                        <div class="row g-2">
                                                                            <div class="col-12 text-start">
                                                                                <label class="form-label" style="font-size: 0.75rem;">Tasa Manual Propuesta</label>
                                                                                <input type="number" step="0.01" class="form-control form-control-sm text-start" placeholder="0.00" wire:model="proposedCustomRate">
                                                                                @error('proposedCustomRate') <small class="text-danger">{{ $message }}</small> @enderror
                                                                            </div>
                                                                            <div class="col-12 text-start">
                                                                                <label class="form-label" style="font-size: 0.75rem;">Justificación Obligatoria <span class="text-danger">*</span></label>
                                                                                <textarea class="form-control form-control-sm text-start" rows="2" placeholder="Explique por qué requiere usar una tasa diferente..." wire:model="customRateReason"></textarea>
                                                                                @error('customRateReason') <small class="text-danger">{{ $message }}</small> @enderror
                                                                            </div>

                                                                            <div class="col-12 mt-2">
                                                                                <button type="button" wire:click="requestCustomRateApproval" class="btn btn-sm btn-warning text-dark w-100 py-2 fw-bold">
                                                                                    <i class="fa fa-paper-plane me-1"></i> Enviar Solicitud (Remoto)
                                                                                </button>
                                                                            </div>

                                                                            <div class="col-12 text-center my-2 text-muted" style="font-size: 0.7rem;">
                                                                                — O BIEN —
                                                                            </div>

                                                                            <div class="col-12 text-start border rounded p-2 bg-light">
                                                                                <small class="fw-bold text-secondary d-block mb-1"><i class="fa fa-shield-alt me-1"></i> Autorización de Supervisor en Sitio</small>
                                                                                <div class="row g-1">
                                                                                    <div class="col-6">
                                                                                        <input type="email" class="form-control form-control-sm" placeholder="Email Supervisor" wire:model="supervisorEmail">
                                                                                        @error('supervisorEmail') <small class="text-danger" style="font-size: 0.7rem;">{{ $message }}</small> @enderror
                                                                                    </div>
                                                                                    <div class="col-6">
                                                                                        <input type="password" class="form-control form-control-sm" placeholder="Contraseña PIN" wire:model="supervisorPassword">
                                                                                        @error('supervisorPassword') <small class="text-danger" style="font-size: 0.7rem;">{{ $message }}</small> @enderror
                                                                                    </div>
                                                                                    <div class="col-12 mt-1">
                                                                                        <button type="button" wire:click="approveCustomRateLocally" class="btn btn-sm btn-primary w-100 py-1" style="font-size: 0.75rem;">
                                                                                            <i class="fa fa-fingerprint me-1"></i> Verificar y Autorizar en Sitio
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-12 mt-2 text-end">
                                                                                <button type="button" wire:click="$set('showCustomRateRequest', false)" class="btn btn-sm btn-secondary px-3">
                                                                                    Cancelar
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>
                                            @endif

                                            <div class="col-12">
                                                <button class="btn btn-success w-100" wire:click="addPayment">Agregar Pago</button>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- BANK / ZELLE --}}
                                    @if($paymentMethod === 'bank')
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <label class="form-label">Banco / Plataforma</label>
                                                <select class="form-control" wire:model.live="bankId">
                                                    <option value="">Seleccione...</option>
                                                    @foreach($banks as $b)
                                                        <option value="{{ $b->id }}">{{ $b->name }} ({{ $b->currency_code }})</option>
                                                    @endforeach
                                                </select>
                                                @error('bankId') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>

                                            @if($isZelleSelected)
                                                {{-- ZELLE FIELDS --}}
                                                
                                                @if($zelleStatusMessage)
                                                    <div class="col-12">
                                                        <div class="alert alert-{{ $zelleStatusType }} mb-0">
                                                            <i class="fa fa-{{ $zelleStatusType == 'danger' ? 'exclamation-circle' : 'info-circle' }} me-1"></i>
                                                            {{ $zelleStatusMessage }}
                                                        </div>
                                                    </div>
                                                @endif

                                                <div class="col-12">
                                                    <label class="form-label">Referencia (Opcional)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-barcode"></i>
                                                        </span>
                                                        <input type="text" wire:model="zelleReference" class="form-control" placeholder="Referencia">
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Nombre del Emisor <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-user"></i>
                                                        </span>
                                                        <input type="text" wire:model.live="zelleSender" class="form-control" placeholder="Nombre del titular">
                                                    </div>
                                                    @error('zelleSender') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Fecha (Opcional)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-calendar"></i>
                                                        </span>
                                                        <input type="date" wire:model.live="zelleDate" class="form-control">
                                                    </div>
                                                    @error('zelleDate') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Monto <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-dollar-sign"></i>
                                                        </span>
                                                        <input type="number" wire:model.live="zelleAmount" class="form-control" placeholder="0.00">
                                                    </div>
                                                    @error('zelleAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Comprobante (Foto) <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">
                                                            <i class="fas fa-camera"></i>
                                                        </span>
                                                        <input type="file" wire:model="zelleImage" class="form-control" accept="image/*">
                                                    </div>
                                                    @error('zelleImage') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    @if ($zelleImage)
                                                        <div class="mt-2">
                                                            <img src="{{ $zelleImage->temporaryUrl() }}" class="img-thumbnail" style="max-height: 100px;">
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                <div class="col-12 mt-3">
                                                    <div class="alert alert-info py-2">
                                                        <small><i class="fa fa-info-circle me-1"></i> El monto a usar en esta venta se calculará automáticamente.</small>
                                                    </div>
                                                    <label class="form-label">Monto a usar en esta venta</label>
                                                    <input class="form-control" type="number" wire:model="amount" placeholder="Monto a aplicar">
                                                    @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>
            
                                                <div class="col-12">
                                                    @php
                                                        $selectedBankObj = $bankId ? $banks->find($bankId) : null;
                                                        $selectedBankNameLower = $selectedBankObj ? strtolower($selectedBankObj->name) : '';
                                                        $isUsdtSelected = (str_contains($selectedBankNameLower, 'usdt') || str_contains($selectedBankNameLower, 'binance') || str_contains($selectedBankNameLower, 'cripto'));
                                                    @endphp
                                                    <button class="btn btn-purple w-100" style="background-color: #6f42c1; color: white;" wire:click="addPayment">
                                                        {{ $isUsdtSelected ? 'Agregar Pago USDT' : 'Agregar Pago Zelle' }}
                                                    </button>
                                                </div>

                                            @elseif($isVedBankSelected)
                                                {{-- VED BANK FIELDS (Detailed) --}}
                                                <div class="col-12">
                                                    <div class="alert alert-info py-2 mb-0">
                                                        <small><i class="fa fa-info-circle me-1"></i> Se requieren detalles para pagos en Bolívares.</small>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Monto a Usar</label>
                                                    <input 
                                                        class="form-control" 
                                                        oninput="validarInputNumber(this)"
                                                        wire:model.live="amount" 
                                                        type="number"
                                                        placeholder="0.00">
                                                    @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-12">
                                                    @if($bankStatusMessage)
                                                        <div class="alert alert-{{ $bankStatusType }} mb-2">
                                                            <i class="fa fa-{{ $bankStatusType == 'danger' ? 'exclamation-circle' : 'info-circle' }} me-1"></i>
                                                            {{ $bankStatusMessage }}
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Monto del Depósito (Total)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-money-bill-wave"></i></span>
                                                        <input 
                                                            class="form-control" 
                                                            oninput="validarInputNumber(this)"
                                                            wire:model.live="bankGlobalAmount" 
                                                            type="number"
                                                            placeholder="Monto Original">
                                                    </div>
                                                    @error('bankGlobalAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Referencia (Últimos 5 dígitos)</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                                                        <input 
                                                            class="form-control" 
                                                            wire:model.live="bankReference" 
                                                            type="text"
                                                            placeholder="Ej: 12345">
                                                    </div>
                                                    @error('bankReference') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Fecha de Pago</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                                        <input type="date" wire:model.live="bankDate" class="form-control">
                                                    </div>
                                                    @error('bankDate') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>

                                                <div class="col-md-12 mt-2">
                                                    <div class="card border border-primary border-opacity-25 bg-light bg-opacity-50">
                                                        <div class="card-body p-3 text-start">
                                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                                <label class="form-label mb-0 fw-bold"><i class="fa fa-coins me-1"></i> Tasa de Cambio (VED/Bs.)</label>
                                                                
                                                                {{-- Request special rate button --}}
                                                                @if(empty($currentApproval))
                                                                    <button type="button" wire:click="$toggle('showCustomRateRequest')" class="btn btn-xs btn-outline-primary border-0 py-0 px-1" style="font-size: 0.75rem;">
                                                                        <i class="fa fa-key me-1"></i> Solicitar Tasa Especial
                                                                    </button>
                                                                @endif
                                                            </div>

                                                            {{-- Strict Select Dropdown when options are available and not overridden by approved custom rate --}}
                                                            @if($currentApproval && $currentApproval['status'] === 'approved')
                                                                <div class="input-group">
                                                                    <span class="input-group-text bg-success text-white"><i class="fa fa-check-circle"></i></span>
                                                                    <input type="text" class="form-control fw-bold text-success bg-white text-start" readonly value="{{ number_format($customExchangeRate, 2) }} Bs. (Tasa Especial Aprobada)">
                                                                    <button type="button" wire:click="cancelCustomRateRequest" class="btn btn-outline-danger" title="Quitar tasa especial">
                                                                        <i class="fa fa-times"></i>
                                                                    </button>
                                                                </div>
                                                                <small class="text-success mt-1 d-block text-start">
                                                                    <i class="fa fa-info-circle"></i> Autorizado por: <strong>{{ $currentApproval['approver']['name'] ?? 'Supervisor' }}</strong>. Motivo: <em>"{{ $currentApproval['reason'] }}"</em>
                                                                </small>
                                                            @elseif($currentApproval && $currentApproval['status'] === 'pending')
                                                                {{-- Pending Approval State --}}
                                                                <div class="alert alert-warning mb-2 py-2 px-3 border-warning" wire:poll.5s="checkApprovalStatus">
                                                                    <div class="d-flex align-items-center justify-content-between text-start">
                                                                        <div>
                                                                            <i class="fa fa-spinner fa-spin me-2"></i>
                                                                            Solicitud Pendiente: <strong>{{ number_format($currentApproval['custom_rate'], 2) }} VED</strong>
                                                                            <small class="d-block text-muted">Motivo: "{{ $currentApproval['reason'] }}"</small>
                                                                        </div>
                                                                        <button type="button" wire:click="cancelCustomRateRequest" class="btn btn-xs btn-outline-danger border-0 text-white shadow-none">
                                                                            Cancelar
                                                                        </button>
                                                                    </div>

                                                                    {{-- OTP Code Authorization Entry --}}
                                                                    <div class="mt-2 border rounded p-2 bg-light text-start text-dark">
                                                                        <label class="form-label mb-1 fw-bold text-success" style="font-size: 0.78rem;">
                                                                            <i class="fa fa-unlock me-1"></i> ¿Tiene el código de autorización rápida?
                                                                        </label>
                                                                        <div class="input-group">
                                                                            <input type="text" class="form-control form-control-sm text-center fw-bold text-success bg-white border border-success" placeholder="Código de 6 dígitos" wire:model="otpCode" maxlength="6" wire:keydown.enter="validateOtpCode">
                                                                            <button class="btn btn-sm btn-success text-white py-1 px-3 fw-bold" type="button" wire:click="validateOtpCode">
                                                                                Validar
                                                                            </button>
                                                                        </div>
                                                                        @error('otpCode') <small class="text-danger d-block mt-1" style="font-size: 0.7rem;">{{ $message }}</small> @enderror
                                                                    </div>

                                                                    {{-- Remote Supervisor Buttons (if logged user is supervisor) --}}
                                                                    @if(auth()->user()->hasRole('Super Admin') || auth()->user()->can('payments.approve_custom_rate'))
                                                                        <div class="mt-2 pt-2 border-top border-warning d-flex gap-2">
                                                                            <button type="button" wire:click="approveCustomRateRemotely({{ $currentApproval['id'] }})" class="btn btn-xs btn-success text-white py-1">
                                                                                <i class="fa fa-check me-1"></i> Aprobar Solicitud
                                                                            </button>
                                                                            <button type="button" wire:click="rejectCustomRateRemotely({{ $currentApproval['id'] }})" class="btn btn-xs btn-danger py-1">
                                                                                <i class="fa fa-times me-1"></i> Rechazar
                                                                            </button>
                                                                        </div>
                                                                    @endif
                                                                </div>

                                                                {{-- On-Site Supervisor credentials verification --}}
                                                                <div class="border rounded p-2 bg-white mt-2 text-start text-dark">
                                                                    <small class="fw-bold text-secondary d-block mb-1"><i class="fa fa-shield-alt me-1"></i> Autorización de Supervisor en Sitio</small>
                                                                    <div class="row g-1">
                                                                        <div class="col-6">
                                                                            <input type="email" class="form-control form-control-sm text-start" placeholder="Email Supervisor" wire:model="supervisorEmail">
                                                                            @error('supervisorEmail') <small class="text-danger" style="font-size: 0.7rem;">{{ $message }}</small> @enderror
                                                                        </div>
                                                                        <div class="col-6">
                                                                            <input type="password" class="form-control form-control-sm text-start" placeholder="Contraseña PIN" wire:model="supervisorPassword">
                                                                            @error('supervisorPassword') <small class="text-danger" style="font-size: 0.7rem;">{{ $message }}</small> @enderror
                                                                        </div>
                                                                        <div class="col-12 mt-1">
                                                                            <button type="button" wire:click="approveCustomRateLocally" class="btn btn-sm btn-primary w-100 py-1" style="font-size: 0.75rem;">
                                                                                <i class="fa fa-fingerprint me-1"></i> Verificar y Autorizar
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @else
                                                                {{-- Dropdown Select of rates --}}
                                                                @if(!empty($rateOptions))
                                                                    <select class="form-control form-select fw-bold text-primary text-start" wire:model.live="customExchangeRate">
                                                                        @foreach($rateOptions as $option)
                                                                            <option value="{{ $option['rate'] }}">{{ $option['label'] }}</option>
                                                                        @endforeach
                                                                    </select>
                                                                @else
                                                                    {{-- Fallback standard input --}}
                                                                    <input type="number" step="0.000001" class="form-control text-primary fw-bold text-start" wire:model.live="customExchangeRate" placeholder="Usar del sistema">
                                                                @endif

                                                                @if($customExchangeRate) 
                                                                    <div class="mt-2 d-flex flex-column gap-1 align-items-start text-start">
                                                                        <small class="text-info"><i class="fa fa-info-circle me-1"></i> Tasa VED activa: <strong>{{ number_format($customExchangeRate, 2) }} Bs.</strong></small>
                                                                        @if($isUSDInvoice)
                                                                            <span class="badge bg-warning text-dark py-1 px-2 fw-bold mt-1" style="font-size: 0.72rem; border-radius: 4px; display: inline-block;">
                                                                                <i class="fa fa-exclamation-triangle me-1"></i> Factura para cobrar en Divisa (Tasa BCV bloqueada)
                                                                            </span>
                                                                        @else
                                                                            <span class="badge bg-success text-white py-1 px-2 fw-bold mt-1" style="font-size: 0.72rem; border-radius: 4px; display: inline-block;">
                                                                                <i class="fa fa-check-circle me-1"></i> Factura con Diferencial (Permite tasa BCV)
                                                                            </span>
                                                                        @endif
                                                                    </div>
                                                                @endif
                                                            @endif

                                                            {{-- Special Rate Request Form (Inline collapsible) --}}
                                                            @if($showCustomRateRequest && empty($currentApproval))
                                                                <div class="mt-3 p-3 border rounded bg-white text-start text-dark">
                                                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                                                        <h6 class="mb-0 text-primary" style="font-size: 0.85rem;"><i class="fa fa-key me-1"></i> Solicitar Tasa Especial</h6>
                                                                        <button type="button" wire:click="$set('showCustomRateRequest', false)" class="btn-close" style="font-size: 0.7rem;"></button>
                                                                    </div>
                                                                    @if(auth()->user()->can('payments.approve_custom_rate') || auth()->user()->hasRole('Super Admin'))
                                                                        <div class="row g-2">
                                                                            <div class="col-12 text-start">
                                                                                <label class="form-label" style="font-size: 0.75rem;">Tasa Manual Propuesta</label>
                                                                                <input type="number" step="0.01" class="form-control form-control-sm text-start" placeholder="0.00" wire:model="proposedCustomRate">
                                                                                @error('proposedCustomRate') <small class="text-danger">{{ $message }}</small> @enderror
                                                                            </div>
                                                                            <div class="col-12 text-start">
                                                                                <label class="form-label" style="font-size: 0.75rem;">Justificación Obligatoria <span class="text-danger">*</span></label>
                                                                                <textarea class="form-control form-control-sm text-start" rows="2" placeholder="Explique por qué requiere usar una tasa diferente..." wire:model="customRateReason"></textarea>
                                                                                @error('customRateReason') <small class="text-danger">{{ $message }}</small> @enderror
                                                                            </div>
                                                                            <div class="col-12 mt-2 d-flex gap-2">
                                                                                <button type="button" wire:click="autoApproveCustomRate" class="btn btn-sm btn-success text-white flex-grow-1">
                                                                                    <i class="fa fa-magic me-1"></i> Auto-Aprobar Tasa
                                                                                </button>
                                                                                <button type="button" wire:click="$set('showCustomRateRequest', false)" class="btn btn-sm btn-secondary">
                                                                                    Cancelar
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    @else
                                                                        <div class="row g-2">
                                                                            <div class="col-12 text-start">
                                                                                <label class="form-label" style="font-size: 0.75rem;">Tasa Manual Propuesta</label>
                                                                                <input type="number" step="0.01" class="form-control form-control-sm text-start" placeholder="0.00" wire:model="proposedCustomRate">
                                                                                @error('proposedCustomRate') <small class="text-danger">{{ $message }}</small> @enderror
                                                                            </div>
                                                                            <div class="col-12 text-start">
                                                                                <label class="form-label" style="font-size: 0.75rem;">Justificación Obligatoria <span class="text-danger">*</span></label>
                                                                                <textarea class="form-control form-control-sm text-start" rows="2" placeholder="Explique por qué requiere usar una tasa diferente..." wire:model="customRateReason"></textarea>
                                                                                @error('customRateReason') <small class="text-danger">{{ $message }}</small> @enderror
                                                                            </div>

                                                                            <div class="col-12 mt-2">
                                                                                <button type="button" wire:click="requestCustomRateApproval" class="btn btn-sm btn-warning text-dark w-100 py-2 fw-bold">
                                                                                    <i class="fa fa-paper-plane me-1"></i> Enviar Solicitud (Remoto)
                                                                                </button>
                                                                            </div>

                                                                            <div class="col-12 text-center my-2 text-muted" style="font-size: 0.7rem;">
                                                                                — O BIEN —
                                                                            </div>

                                                                            <div class="col-12 text-start border rounded p-2 bg-light">
                                                                                <small class="fw-bold text-secondary d-block mb-1"><i class="fa fa-shield-alt me-1"></i> Autorización de Supervisor en Sitio</small>
                                                                                <div class="row g-1">
                                                                                    <div class="col-6">
                                                                                        <input type="email" class="form-control form-control-sm" placeholder="Email Supervisor" wire:model="supervisorEmail">
                                                                                        @error('supervisorEmail') <small class="text-danger" style="font-size: 0.7rem;">{{ $message }}</small> @enderror
                                                                                    </div>
                                                                                    <div class="col-6">
                                                                                        <input type="password" class="form-control form-control-sm" placeholder="Contraseña PIN" wire:model="supervisorPassword">
                                                                                        @error('supervisorPassword') <small class="text-danger" style="font-size: 0.7rem;">{{ $message }}</small> @enderror
                                                                                    </div>
                                                                                    <div class="col-12 mt-1">
                                                                                        <button type="button" wire:click="approveCustomRateLocally" class="btn btn-sm btn-primary w-100 py-1" style="font-size: 0.75rem;">
                                                                                            <i class="fa fa-fingerprint me-1"></i> Verificar y Autorizar en Sitio
                                                                                        </button>
                                                                                    </div>
                                                                                </div>
                                                                            </div>

                                                                            <div class="col-12 mt-2 text-end">
                                                                                <button type="button" wire:click="$set('showCustomRateRequest', false)" class="btn btn-sm btn-secondary px-3">
                                                                                    Cancelar
                                                                                </button>
                                                                            </div>
                                                                        </div>
                                                                    @endif
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="col-md-6">
                                                    <label class="form-label">Comprobante (Foto) <span class="text-danger">*</span></label>
                                                    <div class="input-group">
                                                        <span class="input-group-text"><i class="fas fa-camera"></i></span>
                                                        <input type="file" wire:model="bankImage" class="form-control" accept="image/*">
                                                    </div>
                                                    @error('bankImage') <span class="text-danger small">{{ $message }}</span> @enderror
                                                    @if ($bankImage)
                                                        <div class="mt-2">
                                                            <img src="{{ $bankImage->temporaryUrl() }}" class="img-thumbnail" style="max-height: 100px;">
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="col-12">
                                                    <label class="form-label">Nota (Opcional)</label>
                                                    <input class="form-control" wire:model.live="bankNote" type="text" placeholder="Observaciones...">
                                                </div>

                                                <div class="col-12">
                                                    <button class="btn btn-primary w-100" wire:click="addPayment" type="button">
                                                        <i class="fa fa-plus-circle me-2"></i>Agregar Pago Detallado
                                                    </button>
                                                </div>
                                            @else
                                                {{-- STANDARD BANK FIELDS --}}
                                                <div class="col-md-12">
                                                    <label class="form-label">Fecha de Pago</label>
                                                    <input type="date" class="form-control" wire:model.live="paymentDate">
                                                    @error('paymentDate') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">Monto</label>
                                                    <input class="form-control" type="number" wire:model="amount" placeholder="0.00">
                                                    @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="col-md-6">
                                                    <label class="form-label">N° Cuenta</label>
                                                    <input class="form-control" type="text" wire:model="accountNumber">
                                                    @error('accountNumber') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="col-12">
                                                    <label class="form-label">Referencia</label>
                                                    <input class="form-control" type="text" wire:model="depositNumber">
                                                    @error('depositNumber') <span class="text-danger small">{{ $message }}</span> @enderror
                                                </div>
                                                <div class="col-12">
                                                    <button class="btn btn-primary w-100" wire:click="addPayment">Agregar Pago</button>
                                                </div>
                                            @endif
                                        </div> {{-- Closes row g-3 --}}
                                    @endif

                                    {{-- WALLET --}}
                                    @if($paymentMethod === 'wallet')
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="alert alert-warning mb-0 border-warning">
                                                    <i class="fa fa-info-circle me-1"></i> El cliente dispone de <strong>{{ $symbol }}{{ number_format($walletBalance, 2) }}</strong> en su billetera virtual.
                                                </div>
                                            </div>
                                            <div class="col-md-12">
                                                <label class="form-label">Monto a Usar</label>
                                                <input class="form-control border-warning" type="number" wire:model="amount" placeholder="0.00" wire:keydown.enter="addPayment">
                                                @error('amount') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-warning w-100" wire:click="addPayment">Usar Saldo</button>
                                            </div>
                                        </div>
                                    @endif
 
                                    {{-- CREDIT NOTE --}}
                                    @if($paymentMethod === 'credit_note')
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="alert alert-warning py-2 small mb-0">
                                                    <i class="fa fa-info-circle me-1"></i> Esto generará una Nota de Crédito manual para ajustar el saldo de la factura, sin afectar el inventario.
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Monto del Ajuste</label>
                                                <div class="input-group">
                                                    <select class="form-select" style="max-width: 100px;" wire:model.live="paymentCurrency">
                                                        @foreach($currencies as $curr)
                                                            <option value="{{ $curr->code }}">{{ $curr->code }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input class="form-control" type="number" step="0.01" wire:model="manualCreditAmount" placeholder="0.00" wire:keydown.enter="addCreditNote">
                                                </div>
                                                @error('manualCreditAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Motivo</label>
                                                <input class="form-control" type="text" wire:model="manualCreditReason" placeholder="Ej: Redondeo, Bonificación" wire:keydown.enter="addCreditNote">
                                                @error('manualCreditReason') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-warning w-100" wire:click="addCreditNote">Aplicar Ajuste / NC</button>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- DEBIT NOTE --}}
                                    @if($paymentMethod === 'debit_note')
                                        <div class="row g-3">
                                            <div class="col-12">
                                                <div class="alert alert-danger py-2 small mb-0 bg-opacity-10 text-danger border-danger">
                                                    <i class="fa fa-info-circle me-1"></i> Esto generará una Nota de Débito manual que <strong>incrementará</strong> la deuda del cliente.
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Monto del Incremento</label>
                                                <div class="input-group">
                                                    <select class="form-select" style="max-width: 100px;" wire:model.live="paymentCurrency">
                                                        @foreach($currencies as $curr)
                                                            <option value="{{ $curr->code }}">{{ $curr->code }}</option>
                                                        @endforeach
                                                    </select>
                                                    <input class="form-control" type="number" step="0.01" wire:model="manualDebitAmount" placeholder="0.00" wire:keydown.enter="addDebitNote">
                                                </div>
                                                @error('manualDebitAmount') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label">Concepto</label>
                                                <input class="form-control" type="text" wire:model="manualDebitReason" placeholder="Ej: Recargo por mora, Reajuste" wire:keydown.enter="addDebitNote">
                                                @error('manualDebitReason') <span class="text-danger small">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-danger w-100" wire:click="addDebitNote">Aplicar Incremento / ND</button>
                                            </div>
                                        </div>
                                    @endif
 
                                </div>
                            </div>
                        </div>

                        {{-- Right Column: List & Totals --}}
                        <div class="col-md-6">
                            {{-- Payments List --}}
                            <div class="card mb-3">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0"><i class="fa fa-list me-2"></i>Pagos Agregados</h6>
                                </div>
                                <div class="card-body p-0">
                                    <div class="table-responsive" style="max-height: 300px;">
                                        <table class="table table-sm table-hover mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Método</th>
                                                    <th>Monto</th>
                                                    <th>Equiv.</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($payments as $index => $p)
                                                    <tr>
                                                        <td>
                                                            <span class="badge {{ $p['method'] == 'credit_note' ? 'bg-warning text-dark' : ($p['method'] == 'wallet' ? 'bg-warning' : ($p['method'] == 'usdt' ? 'bg-success' : 'bg-secondary')) }}">
                                                                {{ $p['method'] == 'credit_note' ? 'AJUSTE / NC' : ($p['method'] == 'wallet' ? 'BILLETERA' : ($p['method'] == 'usdt' ? 'USDT ($)' : strtoupper($p['method']))) }}
                                                            </span>
                                                            @if($p['method'] == 'bank') <br><small>{{ $p['bank_name'] }}</small> @endif
                                                            @if($p['method'] == 'zelle') 
                                                                <br><small>Zelle: {{ $p['zelle_sender'] }}</small>
                                                                @if(isset($p['zelle_file_url']) && $p['zelle_file_url'])
                                                                    <br><a href="{{ $p['zelle_file_url'] }}" target="_blank"><i class="fa fa-image"></i> Ver</a>
                                                                @endif
                                                            @endif
                                                            @if($p['method'] == 'usdt') 
                                                                <br><small>USDT: {{ $p['zelle_sender'] }}</small>
                                                                @if(isset($p['zelle_file_url']) && $p['zelle_file_url'])
                                                                    <br><a href="{{ $p['zelle_file_url'] }}" target="_blank"><i class="fa fa-image"></i> Ver Comprobante</a>
                                                                @endif
                                                            @endif
                                                            @if($p['method'] == 'credit_note')
                                                                <br><small>Motivo: {{ $p['note'] }}</small>
                                                            @elseif($p['method'] == 'wallet')
                                                                <br><small>Pago con Billetera Virtual</small>
                                                            @endif
                                                        </td>
                                                        <td>{{ $p['symbol'] }}{{ number_format($p['amount'], 2) }}</td>
                                                        <td>{{ $symbol }}{{ number_format($p['amount_in_primary'], 2) }}</td>
                                                        <td>
                                                            <button class="btn btn-danger btn-sm" wire:click="removePayment({{ $index }})">
                                                                 <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach

                                                @foreach($debitNotes as $index => $dn)
                                                    <tr class="table-danger">
                                                        <td>
                                                            <span class="badge bg-danger">INCREMENTO / ND</span>
                                                            <br><small>Concepto: {{ $dn['reason'] }}</small>
                                                        </td>
                                                        <td>{{ $dn['symbol'] }}{{ number_format($dn['amount'], 2) }}</td>
                                                        <td>{{ $symbol }}{{ number_format($dn['amount_in_primary'], 2) }}</td>
                                                        <td>
                                                            <button class="btn btn-danger btn-sm" wire:click="removeDebitNote({{ $index }})">
                                                                <i class="fa fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                @endforeach

                                                @if(empty($payments) && empty($debitNotes))
                                                    <tr>
                                                        <td colspan="4" class="text-center py-3 text-muted">No hay pagos o incrementos agregados</td>
                                                    </tr>
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {{-- Totals --}}
                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Total Pagado:</span>
                                        <span class="fw-bold text-success">{{ $symbol }}{{ number_format($totalPaid, 2) }}</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span>Restante:</span>
                                        <span class="fw-bold {{ $remaining > 0 ? 'text-danger' : 'text-success' }}">
                                            {{ $symbol }}{{ number_format($remaining, 2) }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {{-- Change --}}
                            @if($change > 0)
                                <div class="card">
                                    <div class="card-header bg-warning bg-opacity-10">
                                        <h6 class="mb-0 text-dark"><i class="fa fa-exchange-alt me-2"></i>Vuelto: {{ $symbol }}{{ number_format($change, 2) }}</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row g-2">
                                            <div class="col-6 col-md-5">
                                                <select class="form-control form-control-sm" wire:model="selectedChangeCurrency">
                                                    <option value="">Moneda...</option>
                                                    @foreach($currencies as $c)
                                                        <option value="{{ $c->code }}">{{ $c->label }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-6 col-md-4">
                                                <input type="number" class="form-control form-control-sm" wire:model="selectedChangeAmount" placeholder="Monto">
                                            </div>
                                            <div class="col-12 col-md-3">
                                                <button class="btn btn-success btn-sm w-100" wire:click="addChangeDistribution"><i class="fa fa-plus"></i></button>
                                            </div>
                                        </div>

                                        <ul class="list-group mt-2">
                                            @foreach($changeDistribution as $idx => $cd)
                                                <li class="list-group-item d-flex justify-content-between align-items-center p-1">
                                                    <small>{{ $cd['symbol'] }}{{ number_format($cd['amount'], 2) }} {{ $cd['currency'] }}</small>
                                                    <button class="btn btn-xs btn-danger" wire:click="removeChangeDistribution({{ $idx }})">&times;</button>
                                                </li>
                                            @endforeach
                                        </ul>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" onclick="$('#modalPayment').modal('hide')">Cancelar</button>
                    
                    @if($totalDebitNotes > 0 && $totalPaid <= 0)
                        <button type="button" class="btn btn-danger" wire:click="submitDebitNoteOnly">
                            <i class="fa fa-plus-circle me-2"></i>Confirmar Solo Incremento
                        </button>
                    @endif

                    @if($canPay)
                        <button type="button" class="btn btn-primary" wire:click="submit('pay')" {{ ($remaining > 0.01 && !$allowPartialPayment) ? 'disabled' : '' }}>
                            <i class="fa fa-check me-2"></i>Confirmar Pago
                        </button>
                    @endif

                    @if($canUpload)
                        <button type="button" class="btn btn-warning text-dark" wire:click="submit('upload')" {{ ($remaining > 0.01 && !$allowPartialPayment) ? 'disabled' : '' }}>
                            <i class="fa fa-cloud-upload-alt me-2"></i>Subir Pago
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <script>
        window.addEventListener('show-payment-modal', event => {
            $('#modalPayment').modal('show');
        });
        
        window.addEventListener('close-payment-modal', event => {
            $('#modalPayment').modal('hide');
        });
    </script>
</div>
