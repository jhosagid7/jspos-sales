<div>
    <div class="row">
        <div class="col-sm-12 col-md-3 ">
            <div class="card mb-3">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light mb-0">Opciones</h5>
                </div>

                <div class="card-body">
                    <div class="mt-3">
                        @if ($customer != null)
                            <span> {{ $customer['name'] }} <i class="icofont icofont-verification-check"></i></span>
                        @else
                            <span class="f-14"><b>Cliente</b></span>
                        @endif
                        <div class="input-group" wire:ignore>
                            <input class="form-control" type="text" id="inputCustomer" placeholder="F2">
                            <span class="input-group-text list-light">
                                <i class="search-icon" data-feather="user"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <span class="f-14"><b>Factura</b></span>
                        <div class="input-group">
                            <input wire:model="searchFactura" wire:keydown.enter.prevent="searchData" class="form-control" type="text" placeholder="Ej: 105 o F000105">
                            <span class="input-group-text list-light">
                                <i class="search-icon" data-feather="file-text"></i>
                            </span>
                        </div>
                    </div>

                    <div class="mt-3">
                        <span class="f-14"><b>Operador (Cajero/Oficina)</b></span>
                        <select wire:model="user_id" class="form-control form-control-sm">
                            <option value="0">Seleccionar</option>
                            @foreach ($users as $user)
                                <option value="{{ $user->id }}">
                                    {{ $user->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-3">
                        <span class="f-14"><b>Vendedor</b></span>
                        <select wire:model="seller_id" class="form-control form-control-sm">
                            <option value="0">Seleccionar</option>
                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}">
                                    {{ $seller->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mt-3">
                        <span class="f-14"><b>Chofer / Ruta</b></span>
                        <select wire:model="filter_driver_id" class="form-control form-control-sm">
                            <option value="all">Todos</option>
                            <option value="with_route">Con Ruta Asignada</option>
                            <option value="without_route">Sin Ruta Asignada</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                            @endforeach
                        </select>
                    </div>


                    <div class="mt-4">
                        <span class="f-14"><b>Fecha desde</b></span>
                        <div class="input-group datepicker">
                            <input class="form-control flatpickr-input active" id="dateFrom" type="text"
                                autocomplete="off">
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="f-14"><b>Hasta</b></span>
                        <div class="input-group datepicker">
                            <input class="form-control flatpickr-input active" id="dateTo" type="text"
                                autocomplete="off">
                        </div>
                    </div>

                    <div class="mt-3">
                        <span class="f-14"><b>Tipo</b></span>
                        <select wire:model='type' class="form-control">
                            <option value="0">Todas</option>
                            <option value="cash">Contado</option>
                            <option value="credit">Crédito</option>
                        </select>
                    </div>

                    <div class="mt-4">
                        <button wire:key="btn-consultar" wire:click.prevent="searchData" class="btn btn-dark w-100">
                            <i class="fa fa-search"></i> Consultar
                        </button>
                        <button wire:key="btn-pdf-preview" wire:click.prevent="openPdfPreview" class="btn btn-danger text-white w-100 mt-2" @if(!$showReport) disabled @endif>
                            <i class="fas fa-file-pdf"></i> Vista Previa PDF
                        </button>
                    </div>


                </div>
            </div>

            <!-- Column Config (Admin Style) -->
            <div class="card">
                <div class="p-1 card-header bg-primary text-white text-center">
                    <h6 class="mb-0 text-white"><i class="fa fa-cog"></i> Configuración de Columnas</h6>
                </div>
                <div class="card-body p-2">
                    <div class="row">
                        <div class="col-sm-12 col-md-12 mb-3">
                            <span class="f-14"><b>Agrupar por</b></span>
                            <select wire:model.live="groupBy" class="form-control form-control-sm">
                                <option value="none">Sin Agrupar</option>
                                <option value="date">Por Fecha</option>
                                <option value="seller_id">Por Vendedor</option>
                                <option value="driver_id">Por Chofer / Ruta</option>
                                <option value="customer_id">Por Cliente</option>
                                <option value="user_id">Por Operador</option>
                            </select>
                        </div>

                        <div class="col-12 mb-1">
                            <hr class="mt-0 mb-2">
                            <h6 class="txt-light">Columnas</h6>
                        </div>

                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_folio" wire:model.live="columns.folio">
                                <label class="custom-control-label f-12" for="col_folio">Folio</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_cliente" wire:model.live="columns.cliente">
                                <label class="custom-control-label f-12" for="col_cliente">Cliente</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_operador" wire:model.live="columns.operador">
                                <label class="custom-control-label f-12" for="col_operador">Operador</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_vendedor" wire:model.live="columns.vendedor">
                                <label class="custom-control-label f-12" for="col_vendedor">Vendedor</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_base" wire:model.live="columns.base">
                                <label class="custom-control-label f-12" for="col_base">Base</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_porcentaje" wire:model.live="columns.porcentaje">
                                <label class="custom-control-label f-12" for="col_porcentaje">% Aplicado</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_comision" wire:model.live="columns.comision">
                                <label class="custom-control-label f-12" for="col_comision">Comisión</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_flete" wire:model.live="columns.flete">
                                <label class="custom-control-label f-12" for="col_flete">Flete</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_recargo" wire:model.live="columns.recargo">
                                <label class="custom-control-label f-12" for="col_recargo">Recargo</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_diferencial" wire:model.live="columns.diferencial">
                                <label class="custom-control-label f-12" for="col_diferencial">Diferencial</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_total" wire:model.live="columns.total">
                                <label class="custom-control-label f-12" for="col_total">Total</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_credito" wire:model.live="columns.credito">
                                <label class="custom-control-label f-12" for="col_credito">Crédito (USD)</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_acuerdo" wire:model.live="columns.acuerdo">
                                <label class="custom-control-label f-12" for="col_acuerdo">Acuerdo</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_articulos" wire:model.live="columns.articulos">
                                <label class="custom-control-label f-12" for="col_articulos">Artículos</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_estatus" wire:model.live="columns.estatus">
                                <label class="custom-control-label f-12" for="col_estatus">Estatus</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_tipo" wire:model.live="columns.tipo">
                                <label class="custom-control-label f-12" for="col_tipo">Tipo</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_fecha" wire:model.live="columns.fecha">
                                <label class="custom-control-label f-12" for="col_fecha">Fecha</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>



        <div class="col-sm-12 col-md-9">
            <div class="card card-absolute">
                <div class="card-header bg-dark">
                    <h5 class="txt-light">Resultados de la consulta</h5>
                </div>

                <div class="card-body">
                    <div class="row note-labels">
                        <div class="col-sm-12 col-md-5"></div>
                        <div class="col-sm-12 col-md-4"></div>
                        <div class="col-sm-12 col-md-3 text-end">
                            <span class="badge badge-light-success f-18" {{ $totales == 0 ? 'hidden' : '' }}>Total
                                Ventas:
                                ${{ round($totales, 2) }}</span>
                        </div>
                    </div>

                    @if($isGrouped && !empty($availableGroups))
                    <div class="card mb-3 border-info">
                        <div class="card-header bg-info text-white p-2">
                            <h6 class="mb-0 text-white"><i class="fa fa-filter"></i> Mostrar Grupos</h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="d-flex flex-wrap">
                                @foreach($availableGroups as $key => $name)
                                    <div class="custom-control custom-checkbox mr-4 mb-2" wire:key="group_chk_container_{{ $key }}">
                                        <input type="checkbox" class="custom-control-input" id="group_chk_{{ $key }}" value="{{ $key }}" wire:model.live="selectedGroups">
                                        <label class="custom-control-label f-14 text-dark font-weight-bold" for="group_chk_{{ $key }}">
                                            {{ $name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    @endif

                    @php
                        $loopData = $isGrouped ? $groupedSales : [['name' => '', 'sales' => $sales]];
                    @endphp
                    
                    @foreach($loopData as $groupKey => $groupData)
                    <div wire:key="group-row-{{ $groupKey }}">
                    @if($isGrouped)
                    <div class="mt-4">
                        <h5 class="txt-primary mb-2"><i class="fa fa-folder-open"></i> {{ $groupData['name'] }}
                            <span class="badge badge-light-success float-right f-14">Subtotal: ${{ number_format($groupData['total_usd'], 2) }}</span>
                        </h5>
                    @else
                    <div class="mt-3 table-responsive">
                    @endif
                        <div class="table-responsive">
                            <table class="table table-responsive-md table-hover" {!! !$isGrouped ? 'id="tblSalesRpt"' : '' !!}>
                                <thead class="thead-primary">
                                    <tr class="text-center">
                                    @if($columns['folio']) <th>Folio</th> @endif
                                    @if($columns['cliente']) <th>Cliente</th> @endif
                                    @if($columns['operador']) <th>Operador</th> @endif
                                    @if($columns['vendedor']) <th>Vendedor</th> @endif
                                    @if($columns['base']) <th>Base</th> @endif
                                    @if($columns['porcentaje']) <th>%</th> @endif
                                    @if($columns['comision']) <th>Comisión</th> @endif
                                    @if($columns['flete']) <th>Flete</th> @endif
                                    @if($columns['recargo']) <th>Recargo</th> @endif
                                    @if($columns['diferencial']) <th>Dif.</th> @endif
                                    @if($columns['total']) <th>Total</th> @endif
                                    @if($columns['credito']) <th>Crédito (USD)</th> @endif
                                    @if($columns['acuerdo']) <th>Acuerdo</th> @endif
                                    @if($columns['articulos']) <th>Articulos</th> @endif
                                    @if($columns['estatus']) <th>Estatus</th> @endif
                                    @if($columns['tipo']) <th>Tipo</th> @endif
                                    @if($columns['fecha']) <th>Fecha</th> @endif
                                    <th></th>

                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($groupData['sales'] as $sale)
                                    @php
                                        // Calcular montos pagados por moneda
                                        $paidPerCurrency = [];
                                        $totalPaidUSD = 0;
                                        
                                        foreach($currencies as $currency) {
                                            $paidPerCurrency[$currency->code] = 0;
                                        }

                                        // Sumar pagos
                                        foreach($sale->paymentDetails as $payment) {
                                            // Asignar a la moneda correspondiente
                                            if(isset($paidPerCurrency[$payment->currency_code])) {
                                                $paidPerCurrency[$payment->currency_code] += $payment->amount;
                                            }
                                            
                                            // Calcular equivalente en USD para el total pagado
                                            // Si la moneda del pago es la principal, usar primary_exchange_rate
                                            // Si no, usar exchange_rate del pago (asumiendo que exchange_rate es valor vs USD)
                                            // O mejor: convertir todo a USD.
                                            // Si el pago tiene exchange_rate, amount / exchange_rate = USD
                                            // Si el pago es en USD, exchange_rate es 1.
                                            
                                            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                                            $totalPaidUSD += ($payment->amount / $rate);
                                        }

                                        // Sumar pagos posteriores (Abonos)
                                        foreach($sale->payments as $payment) {
                                            // Asignar a la moneda correspondiente
                                            $curr = $payment->currency; // Payment model uses 'currency' column
                                            
                                            // Fallback for legacy data if needed, or if currency code matches
                                            if(isset($paidPerCurrency[$curr])) {
                                                $paidPerCurrency[$curr] += $payment->amount;
                                            }
                                            
                                            // Add Discount to USD bucket (Value Settled)
                                            // Only if NOT surcharge (overdue), because surcharge is already included in payment amount (extra money paid).
                                            // Discount means we paid LESS cash, but settled MORE debt.
                                            if(isset($payment->discount_applied) && $payment->discount_applied > 0 && $payment->rule_type !== 'overdue') {
                                                if(isset($paidPerCurrency['USD'])) {
                                                    $paidPerCurrency['USD'] += $payment->discount_applied;
                                                }
                                            }

                                            // Sum totals for calculation
                                            $rate = $payment->exchange_rate > 0 ? $payment->exchange_rate : 1;
                                            $amountUSD = $payment->amount / $rate;
                                            
                                            // For Total Paid USD calculation (used for Credit calculation below)
                                            // If Surcharge: Paid $110. Principal $100.
                                            // Effective Principal Paid = $110 - $10 = $100.
                                            // If Discount: Paid $90. Principal $100.
                                            // Effective Principal Paid = $90 + $10 = $100.
                                            
                                            $adjustment = $payment->discount_applied ?? 0;
                                            if($payment->rule_type === 'overdue') {
                                                $totalPaidUSD += ($amountUSD - $adjustment);
                                            } else {
                                                $totalPaidUSD += ($amountUSD + $adjustment);
                                            }
                                        }
                                        
                                        // Si es venta de contado sin pagos registrados (legacy o simple cash), 
                                        // asumir que se pagó todo en la moneda principal o según 'cash' field?
                                        // El modelo Sale tiene 'cash' que es el monto pagado.
                                        // Si no hay pagos en la tabla payments, usar $sale->cash y $sale->primary_currency_code
                                        
                                        if($sale->paymentDetails->count() == 0 && $sale->type == 'cash') {
                                            $code = $sale->primary_currency_code ?? 'VED'; // Fallback
                                            if(isset($paidPerCurrency[$code])) {
                                                $paidPerCurrency[$code] += $sale->cash;
                                            }
                                            // Convertir cash a USD usando primary_exchange_rate
                                            $rate = $sale->primary_exchange_rate > 0 ? $sale->primary_exchange_rate : 1;
                                            $totalPaidUSD += ($sale->cash / $rate);
                                        }

                                        // Calcular Crédito Restante en USD
                                        // Si está pagada, es 0. Si no, Total USD - Total Pagado USD
                                        $creditUSD = 0;
                                        if($sale->status != 'paid' && $sale->status != 'returned') {
                                            $creditUSD = max(0, $sale->total_usd - $totalPaidUSD);
                                        }

                                        // Calcular desgloses de recargos dinámicamente o leerlos físicamente
                                        $base = $sale->base_amount > 0 ? floatval($sale->base_amount) : 0;
                                        $commPercent = $sale->resolved_commission_percent;
                                        $freightPercent = $sale->resolved_freight_percent;
                                        $diffPercent = $sale->resolved_exchange_diff_percent;
                                        $markupPercent = $sale->resolved_base_markup_percent;
                                        $isSequential = $sale->created_at >= \App\Services\ConfigurationService::getSequentialCutOffDate();

                                        if ($isSequential) {
                                            $surchargePercent = (((1 + ($commPercent + $freightPercent + $markupPercent) / 100) * (1 + $diffPercent / 100)) - 1) * 100;
                                        } else {
                                            $surchargePercent = $commPercent + $freightPercent + $markupPercent + $diffPercent;
                                        }
                                        
                                        if ($base == 0 && $sale->total_usd > 0) {
                                            if (!$isSequential) {
                                                if ($surchargePercent > 0) {
                                                    $base = $sale->total_usd / (1 + ($surchargePercent / 100));
                                                } else {
                                                    $base = $sale->total_usd;
                                                }
                                            } else {
                                                $base = ($sale->total_usd / (1 + ($diffPercent / 100))) / (1 + (($commPercent + $freightPercent + $markupPercent) / 100));
                                            }
                                        }
                                        
                                        $commAmt = $sale->commission_amount > 0 ? floatval($sale->commission_amount) : ($base * $commPercent / 100);
                                        $freightAmt = $sale->freight_amount > 0 ? floatval($sale->freight_amount) : ($base * $freightPercent / 100);
                                        $markupAmt = $sale->base_markup_amount > 0 ? floatval($sale->base_markup_amount) : ($base * $markupPercent / 100);
                                        
                                        if ($isSequential) {
                                            $diffAmt = ($base + $commAmt + $freightAmt + $markupAmt) * ($diffPercent / 100);
                                        } else {
                                            $diffAmt = $sale->exchange_diff_amount > 0 ? floatval($sale->exchange_diff_amount) : ($base * $diffPercent / 100);
                                        }

                                        // Guard to fix display if base is stored in local currency (e.g. VED/COP) instead of USD
                                        if ($base > ($sale->total_usd * 1.5) && $sale->primary_exchange_rate > 1) {
                                            $base = $base / $sale->primary_exchange_rate;
                                            $commAmt = $commAmt / $sale->primary_exchange_rate;
                                            $freightAmt = $freightAmt / $sale->primary_exchange_rate;
                                            $markupAmt = $markupAmt / $sale->primary_exchange_rate;
                                            $diffAmt = $diffAmt / $sale->primary_exchange_rate;
                                        }
                                    @endphp
                                    <tr class="text-center {{ $sale->deletion_requested_at ? 'table-warning' : '' }}">
                                        @if($columns['folio'])
                                        <td>
                                            {{ $sale->invoice_number ?? $sale->id }}
                                            @foreach ($sale->returns as $return)
                                                @php $isManual = $return->details->count() === 0; @endphp
                                                <a href="{{ route('pos.returns.generateCreditNotePdf', $return->id) }}" 
                                                   target="_blank" 
                                                   class="ms-1" 
                                                   title="{{ $isManual ? 'Nota de Crédito (Ajuste)' : 'Nota de Crédito (Devolución)' }} #{{ $return->id }}">
                                                    <i class="fas fa-file-invoice" style="color: {{ $isManual ? '#fd7e14' : '#ffc107' }};"></i>
                                                </a>
                                            @endforeach
                                        </td>
                                        @endif
                                        @if($columns['cliente']) <td>{{ $sale->customer->name }}</td> @endif
                                        @if($columns['operador']) <td>{{ optional($sale->user)->name ?? 'N/A' }}</td> @endif
                                        @if($columns['vendedor']) <td>{{ optional(optional($sale->customer)->seller)->name ?? 'N/A' }}</td> @endif
                                        @if($columns['base']) <td class="text-right">${{ number_format($base, 2) }}</td> @endif
                                        @if($columns['porcentaje']) <td>{{ number_format($surchargePercent, 1) }}%</td> @endif
                                        @if($columns['comision'])
                                        <td class="text-right text-success">
                                            ${{ number_format($commAmt, 2) }}
                                            @if($commPercent > 0)
                                                <br><small class="text-muted">({{ number_format($commPercent, 1) }}%)</small>
                                            @endif
                                        </td>
                                        @endif
                                        @if($columns['flete'])
                                        <td class="text-right text-info">
                                            ${{ number_format($freightAmt, 2) }}
                                            @if($freightPercent > 0)
                                                <br><small class="text-muted">({{ number_format($freightPercent, 1) }}%)</small>
                                            @endif
                                        </td>
                                        @endif
                                        @if($columns['recargo'])
                                        <td class="text-right text-success">
                                            ${{ number_format($markupAmt, 2) }}
                                            @if($markupPercent > 0)
                                                <br><small class="text-muted">({{ number_format($markupPercent, 1) }}%)</small>
                                            @endif
                                        </td>
                                        @endif
                                        @if($columns['diferencial'])
                                        <td class="text-right text-warning">
                                            ${{ number_format($diffAmt, 2) }}
                                            @if($diffPercent > 0)
                                                <br><small class="text-muted">({{ number_format($diffPercent, 1) }}%)</small>
                                            @endif
                                        </td>
                                        @endif
                                        @if($columns['total']) <td class="text-right font-weight-bold">${{ number_format($sale->total_usd, 2) }}</td> @endif
                                        @if($columns['credito'])
                                        <td>
                                            @if($creditUSD > 0.01)
                                                <span class="text-danger">${{ number_format($creditUSD, 2) }}</span>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        @endif
                                        @if($columns['acuerdo'])
                                        <td>
                                            @if($sale->payment_agreement == 'BCV')
                                                <span class="badge badge-info">BCV</span>
                                            @elseif($sale->payment_agreement == 'USD')
                                                <span class="badge badge-success">USD</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $sale->payment_agreement ?: 'N/A' }}</span>
                                            @endif
                                        </td>
                                        @endif
                                        
                                        @if($columns['articulos']) <td>{{ $sale->items }}</td> @endif
                                        @if($columns['estatus'])
                                        <td>
                                            @if($sale->deletion_requested_at || $sale->status == 'returned')
                                                @if($sale->deletion_requested_at && $sale->status != 'returned')
                                                    <span class="badge badge-warning">Solicitud Borrado</span>
                                                @else
                                                    <span class="badge badge-danger">returned</span>
                                                @endif

                                                @if($sale->deletion_reason)
                                                    <div class="mt-1">
                                                        <small class="text-dark"><b>Motivo:</b> {{ $sale->deletion_reason }}</small>
                                                    </div>
                                                @endif
                                            @else
                                                <span
                                                    class="badge f-12 {{ $sale->status == 'paid' ? 'badge-success' : ($sale->status == 'return' ? 'badge-warning' : ($sale->status == 'pending' ? 'badge-warning' : 'badge-danger')) }} ">{{ $sale->status }}</span>
                                            @endif
                                        </td>
                                        @endif
                                        @if($columns['tipo']) <td>{{ $sale->type }}</td> @endif
                                        @if($columns['fecha']) <td>{{ $sale->created_at }}</td> @endif
                                        <td class="text-primary"></td>

                                        <td data-container="body" data-bs-toggle="tooltip" data-bs-placement="top"
                                            data-bs-html="true" data-bs-title="<b>Ver los detalles de la venta</b>">

                                            @if($sale->deletion_requested_at && $sale->status != 'returned')
                                                {{-- PENDING APPROVAL STATE --}}
                                                @can('sales.approve_deletion')
                                                    <button onclick="ConfirmDelete({{ $sale->id }}, '{{ addslashes($sale->deletion_reason ?? '') }}')"
                                                        class="border-0 btn btn-outline-success btn-xs" title="Aprobar Eliminación">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button wire:click="RejectDeletion({{ $sale->id }})"
                                                        class="border-0 btn btn-outline-danger btn-xs" title="Rechazar Eliminación">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                @else
                                                    <span class="badge badge-warning">Pendiente de Aprobación</span>
                                                @endcan
                                            @else
                                                {{-- NORMAL STATE --}}
                                                <button {{ $sale->status == 'returned' ? 'disabled' : '' }}
                                                    class="border-0 btn btn-outline-dark btn-xs"
                                                    onclick="ConfirmDelete({{ $sale->id }})" title="Eliminar">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            @endif

                                            <button
                                                {{ $sale->status == 'returned' || $sale->status == 'paid' ? 'disabled' : '' }}
                                                wire:click.prevent="getSaleDetailNote({{ $sale->id }})"
                                                class="border-0 btn btn-outline-dark btn-xs" title="Editar Nota">
                                                <i class="fas fa-edit"></i>
                                            </button>

                                            <button wire:click.prevent="getSaleDetail({{ $sale->id }})"
                                                class="border-0 btn btn-outline-dark btn-xs" title="Ver Detalles">
                                                <i class="fas fa-list"></i>
                                            </button>

                                            @if($sale->history_count > 0)
                                                @can('sales.view_history')
                                                <button wire:click.prevent="getSaleHistory({{ $sale->id }})"
                                                    class="border-0 btn btn-outline-info btn-xs" title="Ver Historial de Cambios">
                                                    <i class="fas fa-history"></i>
                                                </button>
                                                @endcan
                                            @endif

                                            <button wire:click.prevent="editDriver({{ $sale->id }})"
                                                class="border-0 btn btn-outline-dark btn-xs" title="Asignar Chofer">
                                                <i class="fas fa-truck text-primary"></i>
                                            </button>

                                            @php
                                                $canEditAnytime = auth()->user()->can('sales.edit_anytime');
                                                $canEditTemp = auth()->user()->can('sales.edit_temporary') && $sale->is_within_edit_window;
                                            @endphp

                                            @if($canEditAnytime || $canEditTemp)
                                                <button wire:click.prevent="editSale({{ $sale->id }})"
                                                    class="border-0 btn btn-outline-dark btn-xs" title="Editar Factura">
                                                    <i class="fas fa-pencil-alt text-warning"></i>
                                                </button>
                                            @endif

                                            @if($sale->driver_id)
                                                <a class="border-0 btn btn-outline-dark btn-xs"
                                                    href="{{ route('delivery.tracking', $sale->id) }}"
                                                    target="_blank" title="Rastreo">
                                                    <i class="fas fa-map-marker-alt text-info"></i>
                                                </a>
                                            @endif

                                            <a class="border-0 btn btn-outline-dark btn-xs link-offset-2 link-underline link-underline-opacity-0 {{ $sale->status == 'returned' ? 'disabled' : '' }}"
                                                href="{{ route('pos.sales.generatePdfInvoice', $sale->id) }}"
                                                target="_blank" title="PDF"><i
                                                    class="text-danger fas fa-file-pdf"></i>
                                            </a>

                                        </td>

                                    </tr>
                                @empty
                                    <tr>
                                         <td colspan="13" class="text-center">Sin ventas</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        </div>
                        
                        @if(!$isGrouped)
                        <div class="mt-2">
                            @if (!is_array($sales))
                                {{ $sales->links() }}
                            @endif
                        </div>
                        @endif
                    </div>
                    </div>
                    @endforeach



                </div>
                <div class="p-1 card-footer d-flex justify-content-between">

                </div>
            </div>
        </div>
        @include('livewire.reports.sale-detail')
        @livewire('sales.returns-component')
        @include('livewire.reports.sale-detail-note')
        
        <!-- Modal Historial de Auditoría -->
        <div class="modal fade" id="modalHistory" tabindex="-1" role="dialog" wire:ignore.self>
            <div class="modal-dialog modal-xl" role="document">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">Historial de Cambios - Venta #{{ $sale_id }}</h5>
                        <button type="button" class="close text-white" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        @if(count($saleHistory) > 0)
                            <div class="table-responsive">
                                <table class="table table-bordered table-striped">
                                    <thead class="bg-light">
                                        <tr class="text-center">
                                            <th>Fecha</th>
                                            <th>Usuario</th>
                                            <th>Detalle de la Modificación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($saleHistory as $log)
                                            <tr>
                                                <td class="text-center" style="width: 15%;">
                                                    {{ \Carbon\Carbon::parse($log['created_at'])->format('d/m/Y H:i:s') }}
                                                </td>
                                                <td class="text-center" style="width: 15%;">
                                                    <span class="badge badge-secondary">{{ $log['user']['name'] }}</span>
                                                </td>
                                                <td>
                                                    @php
                                                        $old = $log['old_data'];
                                                        $new = $log['new_data'];
                                                    @endphp
                                                    
                                                    <div class="row">
                                                        <div class="col-md-6 border-right">
                                                            <div class="bg-light p-2 mb-2"><strong>ESTADO ANTERIOR:</strong></div>
                                                            <ul class="list-unstyled">
                                                                @isset($old['details'])
                                                                    @foreach($old['details'] as $d)
                                                                        <li>
                                                                            <i class="fas fa-minus-circle text-danger me-1"></i> 
                                                                            {{ $d['quantity'] ?? '0' }}x {{ $d['product']['name'] ?? 'Producto' }} 
                                                                            (${{ number_format($d['sale_price'] ?? 0, 2) }})
                                                                        </li>
                                                                    @endforeach
                                                                    <li class="mt-2 text-primary">
                                                                        <strong>Total: ${{ number_format($old['total_usd'] ?? $old['total'] ?? 0, 2) }}</strong>
                                                                    </li>
                                                                @endisset
                                                            </ul>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <div class="bg-light p-2 mb-2"><strong>ESTADO ACTUAL:</strong></div>
                                                            <ul class="list-unstyled">
                                                                @isset($new['details'])
                                                                    @foreach($new['details'] as $d)
                                                                        <li>
                                                                            <i class="fas fa-plus-circle text-success me-1"></i> 
                                                                            {{ $d['quantity'] ?? '0' }}x {{ $d['product']['name'] ?? 'Producto' }} 
                                                                            (${{ number_format($d['sale_price'] ?? 0, 2) }})
                                                                        </li>
                                                                    @endforeach
                                                                    <li class="mt-2 text-primary">
                                                                        <strong>Total Actual: ${{ number_format($new['total_usd'] ?? $new['total'] ?? 0, 2) }}</strong>
                                                                    </li>
                                                                @endisset
                                                            </ul>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fas fa-history fa-4x text-muted mb-3"></i>
                                <p class="text-muted">No se han registrado ediciones para esta venta aún.</p>
                            </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        
        {{-- Modal: Assign Driver --}}
        <div wire:ignore.self class="modal fade" id="modalDriver" tabindex="-1" aria-labelledby="modalDriverLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header bg-dark">
                        <h5 class="modal-title text-white" id="modalDriverLabel">
                            <i class="fas fa-truck"></i> Asignar Chofer a Factura #{{ $selectedSaleId }}
                        </h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Seleccionar Chofer</label>
                            <select wire:model="driver_id" class="form-control">
                                <option value="">Ninguno / Sin Chofer</option>
                                @foreach($drivers as $driver)
                                    <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                        <button type="button" class="btn btn-primary" wire:click="updateDriver">Actualizar Chofer</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .swal-text {
            background-color: #FEFAE3;
            padding: 17px;
            border: 1px solid #F0E1A1;
            display: block;
            margin: 22px;
            text-align: center;
            color: #61534e;
        }
        .rest {
            display: block !important;
        }
        .swal-content__input {
            border: 1px solid #dbdbdb;
            color: #333;
        } 
    </style>

    <script>
        document.addEventListener('livewire:init', () => {
            flatpickr("#dateFrom", {
                dateFormat: "Y/m/d",
                locale: "es",
                theme: "confetti",
                onChange: function(selectedDates, dateStr, instance) {
                    console.log(dateStr);
                    @this.set('dateFrom', dateStr)
                }
            })
            flatpickr("#dateTo", {
                dateFormat: "Y/m/d",
                locale: "es",
                theme: "confetti",
                onChange: function(selectedDates, dateStr, instance) {
                    @this.set('dateTo', dateStr)
                }
            })

            if (document.querySelector('#inputCustomer')) {
                new TomSelect('#inputCustomer', {
                    maxItems: 1,
                    valueField: 'id',
                    labelField: 'name',
                    searchField: ['name', 'address', 'taxpayer_id'],
                    load: function(query, callback) {
                        var url = "{{ route('data.customers') }}" + '?q=' + encodeURIComponent(
                            query)
                        fetch(url)
                            .then(response => response.json())
                            .then(json => {
                                callback(json);
                            }).catch(() => {
                                callback();
                            });
                    },
                    onChange: function(value) {
                        var customer = this.options[value]
                        Livewire.dispatch('sale_customer', {
                            customer: customer
                        })

                    },
                    render: {
                        option: function(item, escape) {
                            var doc = item.taxpayer_id ? ' - ' + escape(item.taxpayer_id) : '';
                            return `<div class="py-1 d-flex">
            <div>
                <div class="mb-0">
                    <span class="h5 text-info">
                        <b class="text-dark">${ escape(item.id) }
                    </span>
                    <span class="text-warning">| ${ escape(item.name.toUpperCase()) }${doc}</span>
                </div>
            </div>
        </div>`;
                        },
                    },
                });
            }

        })

        document.addEventListener('show-detail', event => {
            $('#modalSaleDetail').modal('show')
        })
        document.addEventListener('show-detail-note', event => {
            $('#modalSaleDetailNote').modal('show')
        })
        document.addEventListener('close-detail-note', event => {
            $('#modalSaleDetailNote').modal('hide')
        })

        document.addEventListener('show-driver-modal', event => {
            $('#modalDriver').modal('show')
        })
        document.addEventListener('hide-driver-modal', event => {
            $('#modalDriver').modal('hide')
        })
    </script>
    <script>
        document.addEventListener('livewire:init', () => {

            Livewire.on('init-new', (event) => {
                document.getElementById('inputFocus').focus()
            })



        })


        function ConfirmDelete(saleId, currentReason = '') {
            swal({
                title: currentReason ? 'Aprobar Eliminación' : 'Solicitar/Eliminar Venta',
                text: 'Ingresa el motivo de la eliminación para continuar:',
                content: {
                    element: "input",
                    attributes: {
                        placeholder: "Escribe la razón aquí...",
                        type: "text",
                        value: currentReason, // Pre-fill with existing reason if approving
                    },
                },
                icon: 'warning',
                buttons: {
                    cancel: "Cancelar",
                    confirm: {
                        text: currentReason ? "Confirmar y Eliminar" : "Enviar",
                        closeModal: true,
                    }
                },
                dangerMode: true,
            }).then((reason) => {
                if (reason === null) return; // Cancelled
                
                if (reason.trim() === "") {
                    swal("¡Error!", "Debes ingresar un motivo para proceder", "error");
                    return;
                }
                
                Livewire.dispatch('DestroySale', { saleId: saleId, reason: reason });
            });
        }

    </script>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('show-detail', (event) => {
                $('#modalDetail').modal('show')
            })
            Livewire.on('show-detail-note', (event) => {
                $('#modalDetailNote').modal('show')
            })
            Livewire.on('close-detail-note', (event) => {
                $('#modalDetailNote').modal('hide')
            })
            Livewire.on('show-history', (event) => {
                $('#modalHistory').modal('show')
            })
            Livewire.on('show-driver', (event) => {
                $('#modalDriver').modal('show')
            })
            Livewire.on('close-driver', (event) => {
                $('#modalDriver').modal('hide')
            })
            Livewire.on('update-header', (data) => {
                // Actualizar elementos del breadcrumb
                // data.map -> .rfx (Total Costo)
                // data.child -> .active (Total Venta)
                // data.rest -> .rest (Ganancia)
                
                const rfx = document.querySelector('.breadcrumb-item.rfx');
                // El elemento active puede ser ambiguo, mejor buscar por posición o contexto
                // En breadcrumb.blade.php: icon, rfx, active, rest
                const breadcrumbItems = document.querySelectorAll('.breadcrumb .breadcrumb-item');
                
                if (breadcrumbItems.length >= 4) {
                    // index 1: rfx
                    // index 2: active
                    // index 3: rest
                    if (data.map) breadcrumbItems[1].innerText = data.map;
                    if (data.child) breadcrumbItems[2].innerText = data.child;
                    if (data.rest) breadcrumbItems[3].innerText = data.rest;
                } else {
                    // Fallback a selectores de clase si la estructura cambia
                    const active = document.querySelector('.breadcrumb-item.active');
                    const rest = document.querySelector('.breadcrumb-item.rest');
                    if (rfx && data.map) rfx.innerText = data.map;
                    if (active && data.child) active.innerText = data.child;
                    if (rest && data.rest) rest.innerText = data.rest;
                }
            })
        })
    </script>

    {{-- PDF Viewer in Modal --}}
    @if($showPdfModal)
    <div class="modal show" style="display: block; opacity: 1; background: rgba(0,0,0,0.7); z-index: 9999;" tabindex="-1" role="dialog"
         x-init="document.body.style.overflow = 'hidden'; return () => { document.body.style.overflow = 'auto'; }">
        <div class="modal-dialog modal-xl" role="document" style="height: 90vh; max-width: 95vw; margin-top: 5vh;">
            <div class="modal-content" style="height: 100%;">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white">Vista Previa: Reporte de Ventas General</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closePdfPreview" aria-label="Close"></button>
                </div>
                <div class="modal-body p-0" style="height: calc(100% - 60px);">
                    @if($pdfUrl)
                        <iframe src="{{ $pdfUrl }}" style="width: 100%; height: 100%; border: none;"></iframe>
                    @else
                        <div class="d-flex justify-content-center align-items-center" style="height: 100%;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closePdfPreview">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
