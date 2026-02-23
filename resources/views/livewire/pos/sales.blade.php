<div>
    <div class="row">
        <div class="col-sm-12 col-md-9 mb-3 mb-md-0">
            @include('livewire.pos.partials.items')
        </div>
        <div class="col-sm-12 col-md-3">
            <div class="card card-primary card-outline customer-sticky">
                <div class="card-header">
                    <h3 class="card-title">Resumen</h3>
                    <div class="card-tools">
                        @can('sales.create_customer')
                        <button type="button" class="btn btn-tool" data-toggle="modal" data-target="#modalCustomerCreate">
                            <i class="fas fa-plus"></i> Crear Cliente
                        </button>
                        @endcan
                    </div>
                    
                    <!-- Modal-->
                    <div wire:ignore.self class="modal fade" id="modalCustomerCreate" tabindex="-1"
                        aria-labelledby="modalCustomerCreate" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header bg-primary">
                                    <h5 class="modal-title" id="modalCustomerCreate">Registrar Cliente</h5>
                                    <button class="close" type="button" data-dismiss="modal" aria-label="Close">
                                        <span aria-hidden="true">&times;</span>
                                    </button>
                                </div>
                                <div class="modal-body">
                                    <form class="row g-3 needs-validation" novalidate="">
                                        <div class="col-sm-12 form-group">
                                            <label class="form-label">Nombre <span class="text-danger">*</span></label>
                                            <input wire:model='cname' class="form-control" type="text" placeholder="Ingresa el nombre" maxlength="45" id="inputcname">
                                            @error('cname') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label class="form-label">CC/Nit <span class="text-danger">*</span></label>
                                            <input wire:model='ctaxpayerId' class="form-control" type="text" maxlength="65" id="inputctaxpayerId">
                                            @error('ctaxpayerId') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label class="form-label">Email</label>
                                            <input wire:model='cemail' class="form-control" type="text" maxlength="65" id="inputcemail">
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label class="form-label">Teléfono</label>
                                            <input wire:model='cphone' class="form-control" type="number" maxlength="15" id="inputcphone">
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label class="form-label">Dirección <span class="text-danger">*</span></label>
                                            <input wire:model='caddress' class="form-control" type="text" maxlength="255" id="inputcaddress">
                                            @error('caddress') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label class="form-label">Ciudad <span class="text-danger">*</span></label>
                                            <input wire:model='ccity' class="form-control" type="text" maxlength="255" id="inputccity">
                                            @error('ccity') <span class="text-danger">{{ $message }}</span> @enderror
                                        </div>
                                        <div class="col-sm-12 form-group">
                                            <label>Tipo <span class="text-danger">*</span></label>
                                            <select wire:model="ctype" class="form-control">
                                                <option value="Consumidor Final">Consumidor Final</option>
                                                <option value="Mayoristas">Mayoristas</option>
                                                <option value="Descuento1">Descuento1</option>
                                                <option value="Descuento2">Descuento2</option>
                                                <option value="Otro">Otro</option>
                                            </select>
                                        </div>
                                        <div class="col-md-12 d-flex justify-content-end">
                                            <button wire:click.prevent='storeCustomer' class="btn btn-primary" type="submit">Registrar</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-body p-3">
                    {{-- Invoice Currency Selector --}}
                    <div class="form-group mb-3 border-bottom pb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="font-weight-bold mb-0">Moneda Factura / Ticket:</label>
                            @if($invoiceExchangeRate != 1)
                                <span class="badge badge-info" style="font-size: 0.9em;">
                                    Tasa: {{ number_format($invoiceExchangeRate, 2) }}
                                </span>
                            @endif
                        </div>
                        <select wire:model.live="invoiceCurrency_id" class="form-control form-control-sm" {{ auth()->user()->can('sales.change_invoice_currency') ? '' : 'disabled' }}>
                             @if($currencies)
                                 @foreach($currencies as $c)
                                     <option value="{{ $c->id }}">{{ $c->name }} ({{ $c->code }})</option>
                                 @endforeach
                             @endif
                        </select>
                    </div>

                    <div class="form-group">
                        @can('sales.manage_adjustments')
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="mb-0">
                                @if ($customer != null)
                                    {{ $customer['name'] ?? '' }} <i class="fas fa-check-circle text-success"></i>
                                @else
                                    Cliente
                                @endif
                            </label>
                            
                            @module('module_commissions')
                            <div class="custom-control custom-switch" title="{{ $sellerConfig ? '' : 'Seleccione un cliente con conf. de vendedor para habilitar' }}">
                                <input type="checkbox" class="custom-control-input" id="customSwitch1" wire:model.live="applyCommissions" {{ $sellerConfig ? '' : 'disabled' }}>
                                <label class="custom-control-label" for="customSwitch1" style="font-size: 0.8rem;">
                                    Aplicar Comisiones 
                                    @if(!$sellerConfig) <i class="fas fa-lock text-muted" style="font-size: 0.7em;"></i> @endif
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="mb-0"></label>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="switchApplyFreight" wire:model.live="applyFreight">
                                <label class="custom-control-label" for="switchApplyFreight" style="font-size: 0.8rem;">
                                    Aplicar Solo Flete
                                </label>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="mb-0"></label>
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="switchBreakdownFreight" wire:model.live="is_freight_broken_down">
                                <label class="custom-control-label" for="switchBreakdownFreight" style="font-size: 0.8rem;">
                                    Desglosar Flete
                                </label>
                            </div>
                        </div>
                        @endmodule
                        @else
                        {{-- VISTA PARA VENDEDORES FORÁNEOS (SIN PERMISO DE AJUSTES) --}}
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="mb-0 font-weight-bold">
                                @if ($customer != null)
                                    {{ $customer['name'] ?? '' }} <i class="fas fa-check-circle text-success"></i>
                                @else
                                    Cliente
                                @endif
                            </label>
                            {{-- Solo mostramos indicador visual discreto, sin controles --}}
                            @if($sellerConfig)
                                <span class="badge badge-light text-muted" style="font-size: 0.75rem;" title="Comisiones Aplicadas Automáticamente">
                                    <i class="fas fa-check-double"></i> Tarifa Foránea
                                </span>
                            @endif
                        </div>
                        @endcan
                        
                        
                        @if($sellerConfig)
                            @php
                                $alertClass = 'alert-success'; // Default: no debt or all current
                                if(isset($customer['total_debt']) && $customer['total_debt'] > 0) {
                                    $alertClass = $customer['has_overdue'] ? 'alert-danger' : 'alert-warning';
                                }
                            @endphp
                            
                            <div class="alert {{ $alertClass }} p-2" style="font-size: 0.85rem;">
                                <strong><i class="fas fa-info-circle"></i> Precios Foráneos</strong>
                                
                                @if(isset($customer['seller_name']))
                                    <br><small><strong>Vendedor:</strong> {{ $customer['seller_name'] }}</small>
                                @endif
                                
                                <br><small>
                                    Com: {{ $sellerConfig->commission_percent ?? 0 }}%
                                    @cannot('system.is_foreign_seller')
                                     | Flete: {{ $sellerConfig->freight_percent ?? 0 }}% | Dif: {{ $sellerConfig->exchange_diff_percent ?? 0 }}%
                                    @endcannot
                                </small>
                                
                                @if(isset($customer['allow_credit']) && $customer['allow_credit'])
                                    <hr class="my-1">
                                    <small><strong>Crédito:</strong> {{ $customer['credit_days'] ?? 0 }} días | Límite: ${{ number_format($customer['credit_limit'] ?? 0, 2) }}</small>
                                @endif
                                
                                @if(isset($customer['usd_payment_discount']) && $customer['usd_payment_discount'] > 0)
                                    <br><small><strong>Pago Divisa:</strong> {{ $customer['usd_payment_discount'] }}% desc.</small>
                                @endif
                                
                                
                                @module('module_credits')
                                {{-- Outstanding Invoices --}}
                                @if(isset($customer['outstanding_invoices']) && count($customer['outstanding_invoices']) > 0)
                                    <hr class="my-1">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <small><strong>Facturas Pendientes: {{ count($customer['outstanding_invoices']) }}</strong></small>
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" data-toggle="modal" data-target="#modalOutstandingInvoices" style="font-size: 0.75rem;">
                                            <i class="fas fa-eye"></i> Ver Detalle
                                        </button>
                                    </div>
                                    <small><strong>Total Deuda: ${{ number_format($customer['total_debt'], 2) }}</strong></small>
                                @endif
                                
                                {{-- Discount Rules --}}
                                @if(isset($customer['seller_discount_rules']) && count($customer['seller_discount_rules']) > 0)
                                    <hr class="my-1">
                                    <small><strong>Pronto Pago/Mora (Vendedor):</strong></small>
                                    @foreach($customer['seller_discount_rules'] as $rule)
                                        <br><small class="ml-2">{{ $rule['days_from'] }}-{{ $rule['days_to'] }} días: {{ $rule['discount_percentage'] > 0 ? '+' : '' }}{{ $rule['discount_percentage'] }}%</small>
                                    @endforeach
                                @endif
                                
                                @if(isset($customer['customer_discount_rules']) && count($customer['customer_discount_rules']) > 0)
                                    <hr class="my-1">
                                    <small><strong>Pronto Pago/Mora (Cliente):</strong></small>
                                    @foreach($customer['customer_discount_rules'] as $rule)
                                        <br><small class="ml-2">{{ $rule['days_from'] }}-{{ $rule['days_to'] }} días: {{ $rule['discount_percentage'] > 0 ? '+' : '' }}{{ $rule['discount_percentage'] }}%</small>
                                    @endforeach
                                @endif
                                @endmodule
                            </div>
                        @endif

                        @module('module_multi_warehouse')
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-warehouse"></i></span>
                            </div>
                            <select wire:model.live="warehouse_id" class="form-control" @cannot('sales.switch_warehouse') disabled @endcannot>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}">{{ $w->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endmodule

                        @module('module_delivery')
                        <div class="input-group mb-2">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-truck"></i></span>
                            </div>
                            <select wire:model="driver_id" class="form-control" @cannot('sales.select_driver') disabled @endcannot>
                                <option value="">Seleccionar Chofer (Opcional)</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endmodule

                        <div class="input-group" wire:ignore>
                            <input class="form-control" type="text" id="inputCustomer" placeholder="Buscar Cliente (Shift + C)">
                            <div class="input-group-append">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                            </div>
                        </div>
                    </div>

                    @php
                        $primaryCurrency = collect($currencies)->firstWhere('is_primary', 1);
                        $symbol = $primaryCurrency ? $primaryCurrency->symbol : '$';
                    @endphp

                    <div class="table-responsive">
                        <table class="table table-sm table-borderless">
                            <tr>
                                <td class="text-muted">Artículos:</td>
                                <td class="text-right font-weight-bold">{{ $itemsCart }}</td>
                            </tr>
                            <tr>
                                <td class="text-muted">Subtotal:</td>
                                <td class="text-right font-weight-bold">{{ $displayCurrency ? $displayCurrency->symbol : '$' }}{{ formatMoney($this->displaySubtotalCart) }}</td>
                            </tr>
                            <tr class="border-bottom">
                                <td class="text-muted">I.V.A.:</td>
                                <td class="text-right font-weight-bold">{{ $displayCurrency ? $displayCurrency->symbol : '$' }}{{ formatMoney($this->displayIvaCart) }}</td>
                            </tr>
                            @if($is_freight_broken_down)
                                @cannot('system.is_foreign_seller')
                                <tr class="border-bottom">
                                    <td class="text-muted">Flete Total:</td>
                                    <td class="text-right">
                                        <div class="input-group input-group-sm justify-content-end">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text p-1">{{ $displayCurrency ? $displayCurrency->symbol : '$' }}</span>
                                            </div>
                                            <input type="number" class="form-control form-control-sm text-right" 
                                                wire:model.lazy="total_freight" 
                                                style="max-width: 100px;">
                                        </div>
                                    </td>
                                </tr>
                                @endcannot
                            @endif
                            <tr>
                                <td class="h5 font-weight-bold">TOTAL:</td>
                                <td class="h5 font-weight-bold text-primary text-right">
                                    {{ $displayCurrency ? $displayCurrency->symbol : '$' }}
                                    {{ formatMoney($this->displayTotalCart) }}
                                </td>
                            </tr>
                        </table>
                    </div>

                    {{-- Multi-currency display --}}
                    @if($currencies && $currencies->count() > 1)
                        <div class="mt-2 p-2 bg-light rounded">
                            <h6 class="font-weight-bold text-muted small mb-2 border-bottom pb-1">Referencias:</h6>
                            @foreach($currencies as $currency)
                                @if($currency->id !== $invoiceCurrency_id)
                                    @php
                                        // Calculate converted amount
                                        // Base total is $totalCart (in Primary Currency / USD)
                                        // Target Amount = TotalCart * CurrencyRate
                                        // Assuming TotalCart IS in Primary Currency (USD)
                                        
                                        // If primary currency rate is not 1, we might need to normalize.
                                        // But typically storeOrder logic suggests totalCart is in primary currency.
                                        
                                        $rate = $currency->exchange_rate;
                                        $convertedAmount = $totalCart * $rate;
                                        
                                        // If Primary Currency is NOT USD (e.g. rate != 1), and we want to convert from Primary to Target.
                                        // Amount = (Total / PrimaryRate) * TargetRate?
                                        // Verify Primary Currency
                                        $primary = collect($currencies)->firstWhere('is_primary', true);
                                        if($primary && $primary->exchange_rate != 0) {
                                             $amountInBase = $totalCart / $primary->exchange_rate; // Normalized to Base 1
                                             $convertedAmount = $amountInBase * $rate;
                                        }
                                    @endphp
                                    <div class="d-flex justify-content-between text-muted small">
                                        <span>{{ $currency->code }}:</span>
                                        <span>{{ $currency->symbol }}{{ formatMoney($convertedAmount) }}</span>
                                    </div>
                                @endif
                            @endforeach
                            
                            {{-- USD/BCV Display --}}
                            {{-- Show this if we are NOT in USD (or NOT in primary?) --}}
                            {{-- User wants "USD/BCV" reference. usually useful when paying in Bolivares --}}
                            @can('sales.show_exchange_rate')
                            @if($config && $config->bcv_rate > 0)
                                 @php
                                    // Logic: What is the USD equivalent of the VED Total at BCV Rate?
                                    // 1. Get VED Total
                                    $vedCurrency = collect($currencies)->firstWhere('code', 'VED') ?? collect($currencies)->firstWhere('code', 'VES');
                                    
                                    if($vedCurrency) {
                                        // Calculate VED Total
                                        $primary = collect($currencies)->firstWhere('is_primary', true);
                                        $vedRate = $vedCurrency->exchange_rate;
                                        
                                        if($primary && $primary->exchange_rate > 0) {
                                             $amountInBase = $totalCart / $primary->exchange_rate; 
                                             $vedTotal = $amountInBase * $vedRate;
                                        } else {
                                             $vedTotal = $totalCart * $vedRate;
                                        }
                                        
                                        $usdBcv = $vedTotal / $config->bcv_rate;
                                    }
                                 @endphp
                                 
                                 @if(isset($usdBcv))
                                    <div class="d-flex justify-content-between text-danger font-weight-bold small mt-1 pt-1 border-top">
                                        <span>USD/BCV:</span>
                                        <span>${{ formatMoney($usdBcv) }}</span>
                                    </div>
                                 @endif
                            @endif
                            @endcan
                        </div>
                    @endif

                    @can('orders.save')
                        <button wire:click.prevent="storeOrder" class="btn btn-outline-primary btn-block mt-3">
                            <i class="fas fa-save mr-2"></i> Guardar Orden
                            <small class="d-block text-muted" style="font-size: 0.7rem;">Shift + G</small>
                        </button>
                    @endcan

                    @can('payments.methods')
                        <hr>
                        <h6 class="text-center font-weight-bold mb-3">Método de Pago</h6>
                        <div class="row justify-content-center">
                            @can('payments.method_cash')
                                <div class="col-4 text-center mb-2" wire:click="initPayment(1)" style="cursor: pointer;">
                                    <div class="btn btn-outline-success btn-block p-2">
                                        <i class="fas fa-money-bill-wave fa-2x mb-1"></i>
                                        <div style="font-size: 0.7rem;">Efectivo</div>
                                    </div>
                                </div>
                            @endcan
                            
                            @module('module_credits')
                            @can('payments.method_credit')
                                @php
                                    $creditEnabled = !empty($customer['id']) && 
                                                     !empty($creditConfig['allow_credit']) && 
                                                     $creditConfig['allow_credit'] === true;
                                @endphp
                                <div class="col-4 text-center mb-2" 
                                     @if($creditEnabled) wire:click="initPayment(2)" style="cursor: pointer;" @else style="cursor: not-allowed; opacity: 0.5;" @endif>
                                    <div class="btn btn-outline-info btn-block p-2 {{ !$creditEnabled ? 'disabled' : '' }}">
                                        <i class="fas fa-credit-card fa-2x mb-1"></i>
                                        <div style="font-size: 0.7rem;">Crédito</div>
                                    </div>
                                </div>
                            @endcan
                            @endmodule
                            
                            {{-- Banco - OCULTO por solicitud del usuario
                            @can('payments.method_bank')
                                <div class="col-4 text-center mb-2" wire:click="initPayment(3)" style="cursor: pointer;">
                                    <div class="btn btn-outline-secondary btn-block p-2">
                                        <i class="fas fa-university fa-2x mb-1"></i>
                                        <div style="font-size: 0.7rem;">Banco</div>
                                    </div>
                                </div>
                            @endcan
                            --}}
                        </div>
                    @endcan
                </div>
            </div>
        </div>
    </div>

    @include('livewire.pos.partials.payCash')
    @include('livewire.pos.partials.payDeposit')
    @include('livewire.pos.partials.process-order')
    @include('livewire.pos.partials.script')
    @include('livewire.pos.partials.shortcuts')
    @include('livewire.pos.partials.unit-selection-modal')

    <!-- Modal Stock Warning -->
    <div wire:ignore.self class="modal fade" id="modalStockWarning" tabindex="-1" role="dialog" aria-labelledby="modalStockWarningLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark" id="modalStockWarningLabel">
                        <i class="fas fa-exclamation-triangle"></i> Advertencia de Stock Reservado
                    </h5>
                </div>
                <div class="modal-body">
                    <p class="text-center">{!! $stockWarningMessage !!}</p>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal" wire:click="$set('stockWarningMessage', null)">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                    @if($maxAvailableQty > 0)
                        <button type="button" class="btn btn-info" wire:click="adjustToMax">
                            <i class="fas fa-check-circle"></i> Ajustar a {{ $maxAvailableQty }}
                        </button>
                    @endif
                    <button type="button" class="btn btn-warning font-weight-bold" wire:click="forceAddProduct">
                        <i class="fas fa-check"></i> Continuar de todos modos
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
             Livewire.on('show-stock-warning', (event) => {
                $('#modalStockWarning').modal('show');
            });
            Livewire.on('close-stock-warning', (event) => {
                $('#modalStockWarning').modal('hide');
            });
        });
    </script>
    </script>
    <script src="{{ asset('assets/js/keypress.js') }}"></script>

    {{-- Modal: Outstanding Invoices Details --}}
    <div wire:ignore.self class="modal fade" id="modalOutstandingInvoices" tabindex="-1" aria-labelledby="modalOutstandingInvoicesLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title text-white" id="modalOutstandingInvoicesLabel">
                        <i class="fas fa-file-invoice-dollar"></i> Facturas Pendientes
                        @if(isset($customer['name']))
                            - {{ $customer['name'] }}
                        @endif
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    @if(isset($customer['outstanding_invoices']) && count($customer['outstanding_invoices']) > 0)
                        <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th>#Factura</th>
                                        <th>Emisión</th>
                                        <th>Vencimiento</th>
                                        <th class="text-right">Original</th>
                                        <th class="text-right">Abonos</th>
                                        <th class="text-right">Saldo</th>
                                        <th class="text-center">Estado</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($customer['outstanding_invoices'] as $inv)
                                        <tr class="{{ $inv['is_overdue'] ? 'table-danger' : '' }}">
                                            <td><strong>{{ $inv['invoice_number'] }}</strong></td>
                                            <td>{{ $inv['created_at'] }}</td>
                                            <td>{{ $inv['due_date'] }}</td>
                                            <td class="text-right">{{ $inv['currency_symbol'] ?? '$' }} {{ number_format($inv['total'], 2) }}</td>
                                            <td class="text-right">{{ $inv['currency_symbol'] ?? '$' }} {{ number_format($inv['paid'], 2) }}</td>
                                            <td class="text-right"><strong>{{ $inv['currency_symbol'] ?? '$' }} {{ number_format($inv['pending'], 2) }}</strong></td>
                                            <td class="text-center">
                                                @if($inv['is_overdue'])
                                                    <span class="badge badge-danger">VENCIDA</span>
                                                @else
                                                    <span class="badge badge-warning">PENDIENTE</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="font-weight-bold bg-light">
                                    <tr>
                                        <td colspan="5" class="text-right font-weight-bold">TOTAL DEUDA:</td>
                                        <td class="text-right text-danger font-weight-bold">
                                            @if(isset($customer['debt_totals']))
                                                @foreach($customer['debt_totals'] as $currency => $data)
                                                    <div>
                                                        {{ $data['symbol'] }} {{ number_format($data['total'], 2) }} <small class="text-muted">({{ $currency }})</small>
                                                    </div>
                                                @endforeach
                                            @else
                                                ${{ number_format($customer['total_debt'], 2) }}
                                            @endif
                                        </td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    @else
                        <p class="text-center text-muted">No hay facturas pendientes.</p>
                    @endif
                </div>
                <div class="modal-footer">
                    <a href="{{ route('customer.debt.pdf', $customer['id'] ?? 0) }}" 
                       target="_blank" 
                       class="btn btn-primary"
                       @if(!isset($customer['id'])) disabled @endif>
                        <i class="fas fa-file-pdf"></i> Generar PDF
                    </a>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
</div>
