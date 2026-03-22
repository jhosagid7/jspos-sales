<div>
    <div class="row">
        <!-- Sidebar Options -->
        <div class="col-sm-12 col-md-3">
            <div class="card mb-3">
                <div class="p-1 card-header bg-dark text-white text-center">
                    <h5 class="mb-0 text-white">Opciones de Despacho</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="f-14 font-weight-bold">Chofer / Transportista</label>
                        <select wire:model="driver_id" class="form-control form-control-sm">
                            <option value="all">Todos los Choferes</option>
                            @foreach ($drivers as $driver)
                                <option value="{{ $driver->id }}">{{ $driver->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="f-14 font-weight-bold">Vendedor</label>
                        <select wire:model="seller_id" class="form-control form-control-sm">
                            <option value="all">Todos los Vendedores</option>
                            @foreach ($sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="f-14 font-weight-bold">Fecha Desde</label>
                        <input type="date" wire:model="dateFrom" class="form-control form-control-sm">
                    </div>
                    
                    <div class="mb-3">
                        <label class="f-14 font-weight-bold">Hasta</label>
                        <input type="date" wire:model="dateTo" class="form-control form-control-sm">
                    </div>

                    <div class="mt-4">
                        <button wire:click="generateReport" class="btn btn-dark w-100 mb-2">
                             <i class="fa fa-search"></i> Consultar
                        </button>
                        <button wire:click="openPdfPreview" class="btn btn-info text-white w-100"
                            {{ !$showReport || count($sales) < 1 ? 'disabled' : '' }}>
                            <i class="fa fa-file-pdf-o"></i> Previsualizar PDF
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
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_invoice" wire:model.live="columns.invoice">
                                <label class="custom-control-label f-12" for="col_invoice">Nro Factura</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_dest" wire:model.live="columns.destination">
                                <label class="custom-control-label f-12" for="col_dest">Entrega (Ciudad)</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_cust" wire:model.live="columns.customer">
                                <label class="custom-control-label f-12" for="col_cust">Nombre Cliente</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_base" wire:model.live="columns.base">
                                <label class="custom-control-label f-12" for="col_base">Importe Base</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_perc" wire:model.live="columns.percent">
                                <label class="custom-control-label f-12" for="col_perc">% Aplicado</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_comm" wire:model.live="columns.commission">
                                <label class="custom-control-label f-12" for="col_comm">Comisión ($)</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_freight" wire:model.live="columns.freight">
                                <label class="custom-control-label f-12" for="col_freight">Flete ($)</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_diff" wire:model.live="columns.differential">
                                <label class="custom-control-label f-12" for="col_diff">Diferencial ($)</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_total" wire:model.live="columns.total">
                                <label class="custom-control-label f-12" for="col_total">Monto Nota</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_date" wire:model.live="columns.date">
                                <label class="custom-control-label f-12" for="col_date">Fecha</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Signatures Config -->
            <div class="card mt-3 shadow-sm">
                <div class="p-1 card-header bg-info text-white text-center">
                    <h6 class="mb-0 text-white"><i class="fa fa-pencil"></i> Configuración de Firmas</h6>
                </div>
                <div class="card-body p-2">
                    <div class="row">
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="sig_entregado" wire:model.live="signatures.entregado">
                                <label class="custom-control-label f-12" for="sig_entregado">Entregado por</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="sig_chofer" wire:model.live="signatures.chofer">
                                <label class="custom-control-label f-12" for="sig_chofer">Chofer</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="sig_vendedor" wire:model.live="signatures.vendedor">
                                <label class="custom-control-label f-12" for="sig_vendedor">Vendedor</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="sig_operador" wire:model.live="signatures.operador">
                                <label class="custom-control-label f-12" for="sig_operador">Operador (Caja)</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="sig_recibido" wire:model.live="signatures.recibido">
                                <label class="custom-control-label f-12" for="sig_recibido">Recibido (Taller/Alm.)</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="sig_administrador" wire:model.live="signatures.administrador">
                                <label class="custom-control-label f-12" for="sig_administrador">Administrador</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="sig_gerente" wire:model.live="signatures.gerente">
                                <label class="custom-control-label f-12" for="sig_gerente">Gerente</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Table -->
        <div class="col-sm-12 col-md-9">
            <div class="card card-absolute">
                <div class="card-header bg-dark p-2">
                    <h5 class="txt-light mb-0"><i class="fa fa-list"></i> Resultados de Despacho</h5>
                </div>

                <div class="card-body">
                    <div class="mt-2 table-responsive">
                        <table class="table table-hover table-sm">
                            <thead class="thead-light">
                                <tr class="text-center f-12">
                                    <th>Chofer</th>
                                    @if($columns['invoice']) <th>Factura</th> @endif
                                    @if($columns['destination']) <th>Entrega</th> @endif
                                    @if($columns['customer']) <th class="text-left">Cliente</th> @endif
                                    @if($columns['base']) <th>Base</th> @endif
                                    @if($columns['percent']) <th>%</th> @endif
                                    @if($columns['commission']) <th>Comisión</th> @endif
                                    @if($columns['freight']) <th>Flete</th> @endif
                                    @if($columns['differential']) <th>Dif.</th> @endif
                                    @if($columns['total']) <th>Total</th> @endif
                                    @if($columns['date']) <th>Fecha</th> @endif
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($sales as $sale)
                                    @php
                                        $increments = ($sale->applied_commission_percent + $sale->applied_freight_percent + $sale->applied_exchange_diff_percent) / 100;
                                        $baseAmount = $sale->total_usd / (1 + $increments);
                                        $commAmt = $baseAmount * ($sale->applied_commission_percent / 100);
                                        $freightAmt = $baseAmount * ($sale->applied_freight_percent / 100);
                                        $diffAmt = $baseAmount * ($sale->applied_exchange_diff_percent / 100);
                                    @endphp
                                    <tr class="text-center f-12">
                                         <td>
                                             <a href="{{ route('driver.dashboard', ['driverId' => $sale->driver_id]) }}" class="badge badge-light-primary">
                                                 {{ $sale->driver->name ?? 'N/A' }}
                                             </a>
                                         </td>
                                        @if($columns['invoice']) <td>{{ $sale->invoice_number ?? $sale->id }}</td> @endif
                                        @if($columns['destination']) <td>{{ $sale->customer->city ?? 'N/A' }}</td> @endif
                                        @if($columns['customer']) <td class="text-left">{{ $sale->customer->name }}</td> @endif
                                        @if($columns['base']) <td class="text-right">${{ number_format($baseAmount, 2) }}</td> @endif
                                        @if($columns['percent']) <td>{{ number_format($increments * 100, 1) }}%</td> @endif
                                        @if($columns['commission']) <td class="text-right text-success">${{ number_format($commAmt, 2) }}</td> @endif
                                        @if($columns['freight']) <td class="text-right text-info">${{ number_format($freightAmt, 2) }}</td> @endif
                                        @if($columns['differential']) <td class="text-right text-warning">${{ number_format($diffAmt, 2) }}</td> @endif
                                        @if($columns['total']) <td class="text-right font-weight-bold">${{ number_format($sale->total_usd, 2) }}</td> @endif
                                        @if($columns['date']) <td>{{ $sale->created_at->format('d/m/Y') }}</td> @endif
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="15" class="text-center py-4 text-muted">No se encontraron ventas con los filtros aplicados.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-3">
                            @if ($sales && !is_array($sales))
                                {{ $sales->links() }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- PDF Viewer Modal --}}
    @if($showPdfModal)
    <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.7); z-index: 9999;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document" style="height: 90vh; max-width: 95vw; margin-top: 5vh;">
            <div class="modal-content" style="height: 100%;">
                <div class="modal-header bg-dark text-white p-2">
                    <h5 class="modal-title font-weight-bold"><i class="fa fa-file-pdf-o"></i> Vista Previa: Relación de Despacho</h5>
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
                <div class="modal-footer p-2">
                    <button type="button" class="btn btn-secondary btn-sm" wire:click="closePdfPreview">Cerrar</button>
                    <a href="{{ $pdfUrl }}&download=1" class="btn btn-danger btn-sm"><i class="fa fa-download"></i> Descargar PDF</a>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
