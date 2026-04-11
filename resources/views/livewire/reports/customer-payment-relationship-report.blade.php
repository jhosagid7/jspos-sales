<div>
    <div class="row sales layout-top-spacing">
        {{-- PDF Preview Modal --}}
        @if($showPdfModal)
        <div class="modal show" style="display: block; background: rgba(0,0,0,0.7); z-index: 9999;" tabindex="-1" role="dialog" wire:key="pdf-modal">
            <div class="modal-dialog modal-xl" role="document" style="height: 90vh; max-width: 95vw; margin-top: 5vh;">
                <div class="modal-content" style="height: 100%;">
                    <div class="modal-header bg-dark text-white">
                        <h5 class="modal-title text-white">Vista Previa: Relación de Cobros</h5>
                        <button type="button" class="close text-white" wire:click="closePdfPreview" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0" style="height: calc(100% - 60px);">
                        @if($pdfUrl)
                            <iframe src="{{ $pdfUrl }}" style="width: 100%; height: 100%; border: none;"></iframe>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" wire:click="closePdfPreview">Cerrar</button>
                    </div>
                </div>
            </div>
        </div>
        <script>
            document.body.style.overflow = 'hidden';
        </script>
        @else
        <script>
            document.body.style.overflow = 'auto';
        </script>
        @endif

        <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>Relación de Cobros por Cliente</b>
                </h4>
            </div>

            <div class="widget-content">
                <div class="row mb-3">
                    <div class="col-sm-12 col-md-3">
                        <label>Fecha Desde</label>
                        <input type="date" wire:model.live="dateFrom" class="form-control">
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <label>Fecha Hasta</label>
                        <input type="date" wire:model.live="dateTo" class="form-control">
                    </div>
                    <div class="col-sm-12 col-md-6">
                        <div class="form-group" wire:ignore>
                            <label>Filtrar por Cliente (Escriba el nombre o RIF para buscar)</label>
                            <input type="text" id="custSelect" class="form-control" placeholder="Nombre, RIF o ID del cliente">
                        </div>
                        <small class="text-muted">Si se deja vacío, se mostrarán todos los clientes con movimientos en el periodo.</small>
                    </div>
                </div>
                
                <div class="row mb-3">
                    <div class="col-sm-12 col-md-2">
                        <label>Desde Factura</label>
                        <input type="text" wire:model.live="invoice_from" class="form-control" placeholder="Ej: 100">
                    </div>
                    <div class="col-sm-12 col-md-2">
                        <label>Hasta Factura</label>
                        <input type="text" wire:model.live="invoice_to" class="form-control" placeholder="Ej: 200">
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <label>Vendedor</label>
                        <select wire:model.live="seller_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-12 col-md-2">
                        <label>Operador</label>
                        <select wire:model.live="operator_id" class="form-control">
                            <option value="">Todos</option>
                            @foreach($operators as $op)
                                <option value="{{ $op->id }}">{{ $op->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <label>Moneda</label>
                        <select wire:model.live="currency" class="form-control">
                            <option value="">Todas</option>
                            @foreach($currencies as $curr)
                                <option value="{{ $curr->code }}">{{ $curr->code }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-sm-12 col-md-4">
                        <button wire:click="searchData" class="btn btn-primary btn-block">
                            <i class="fas fa-search"></i> Consultar
                        </button>
                    </div>
                    <div class="col-sm-12 col-md-2">
                         <button wire:click="clearCustomers" class="btn btn-outline-danger btn-block" title="Limpiar Filtros">
                            <i class="fas fa-eraser"></i> Limpiar
                        </button>
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <button wire:click="openPdfPreview" wire:loading.attr="disabled" class="btn btn-info btn-block" {{ !$showReport ? 'disabled' : '' }} style="background-color: #17a2b8; border-color: #17a2b8;" wire:key="btn-preview">
                            <span wire:loading.remove wire:target="openPdfPreview">
                                <i class="fas fa-eye"></i> Previsualizar
                            </span>
                            <span wire:loading wire:target="openPdfPreview">
                                <i class="fas fa-spinner fa-spin"></i> Cargando...
                            </span>
                        </button>
                    </div>
                    <div class="col-sm-12 col-md-3">
                        <button wire:click="generatePdf" wire:loading.attr="disabled" class="btn btn-danger btn-block" {{ !$showReport ? 'disabled' : '' }} wire:key="btn-export">
                            <span wire:loading.remove wire:target="generatePdf">
                                <i class="fas fa-file-pdf"></i> Exportar PDF
                            </span>
                            <span wire:loading wire:target="generatePdf">
                                <i class="fas fa-spinner fa-spin"></i> Generando...
                            </span>
                        </button>
                    </div>
                </div>

                @if($showReport && count($summary) > 0)
                    <div class="row mt-4 mb-4">
                        <div class="col-sm-12 col-md-6">
                            <div class="card bg-light">
                                <div class="card-header bg-dark text-white p-2">
                                    <strong>Resumen por Canal de Ingreso</strong>
                                </div>
                                <div class="card-body p-0">
                                    <table class="table table-bordered table-sm mb-0">
                                        <thead class="bg-secondary text-white">
                                            <tr>
                                                <th>MÉTODO / BANCO</th>
                                                <th class="text-right">ORIGINAL</th>
                                                <th class="text-right">USD EQUIV.</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($summary as $item)
                                                <tr>
                                                    <td>{{ $item['name'] }}</td>
                                                    <td class="text-right">{{ number_format($item['amount'], 2) }}</td>
                                                    <td class="text-right">${{ number_format($item['equiv'], 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                        <tfoot class="bg-dark text-white">
                                            <tr>
                                                <th class="text-right">TOTAL INGRESO:</th>
                                                <th colspan="2" class="text-right">${{ number_format($totalIngreso, 2) }}</th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-3">
                             <div class="card bg-light">
                                <div class="card-header bg-dark text-white p-2 text-center">
                                    <strong>Resumen Divisas</strong>
                                </div>
                                <div class="card-body p-0">
                                     <table class="table table-bordered table-sm mb-0">
                                        <tbody>
                                            @foreach($totalsByCurrency as $curr => $amt)
                                                <tr>
                                                    <td><strong>{{ $curr }}:</strong></td>
                                                    <td class="text-right">{{ number_format($amt, 2) }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                     </table>
                                </div>
                             </div>
                        </div>
                    </div>
                @endif

                <div class="table-responsive">
                    <table class="table table-bordered table-striped mt-1">
                        <thead class="text-white" style="background: #3B3F5C">
                            <tr>
                                <th>CLIENTE / OPERACIÓN</th>
                                <th>FECHA PAGO</th>
                                <th>FECHA EMISIÓN</th>
                                <th class="text-center">DÍAS</th>
                                <th>DOCUMENTO</th>
                                <th>DESCRIPCIÓN</th>
                                <th class="text-right">MONTO (USD)</th>
                                <th class="text-right">INGRESO (USD)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($groupedData as $customerId => $sales)
                                @php 
                                    $firstItem = $sales->first()->first(); 
                                    $custMonto = 0;
                                    $custIngreso = 0;
                                @endphp
                                <tr class="bg-secondary text-white">
                                    <td colspan="8">
                                        <strong>#{{ $firstItem['customer_id'] }} - {{ strtoupper($firstItem['customer_name']) }}</strong>
                                        <span class="ml-2">({{ $firstItem['customer_doc'] }})</span>
                                    </td>
                                </tr>
                                
                                @foreach($sales as $saleId => $items)
                                    @php 
                                        $invoiceHeader = $items->first(); 
                                        $subtotalMonto = $items->sum('monto');
                                        $subtotalIngreso = $items->sum('ingreso');
                                        $custMonto += $subtotalMonto;
                                        $custIngreso += $subtotalIngreso;
                                    @endphp
                                    {{-- Optional: Invoice Sub-header --}}
                                    <tr style="background-color: #f0f0f0;">
                                        <td colspan="8" class="pl-4">
                                            <strong>Documento: {{ $invoiceHeader['doc_number'] }}</strong>
                                            <span class="ml-3 small">Emisión: {{ $invoiceHeader['date_emit']->format('d/m/Y') }}</span>
                                        </td>
                                    </tr>

                                    @foreach($items as $item)
                                        <tr class="{{ $item['type'] == 'N/C' ? 'table-warning text-danger' : '' }}">
                                            <td class="pl-5">{{ $item['type'] }}</td>
                                            <td>{{ $item['date_pay']->format('d/m/Y') }}</td>
                                            <td>{{ $item['date_emit']->format('d/m/Y') }}</td>
                                            <td class="text-center">
                                                <span class="badge {{ $item['days'] > 0 ? 'badge-danger' : 'badge-success' }}">
                                                    {{ $item['days'] }}
                                                </span>
                                            </td>
                                            <td>{{ $item['doc_number'] }}</td>
                                            <td>
                                                <small>{{ $item['description'] }}</small>
                                            </td>
                                            <td class="text-right">{{ number_format($item['monto'], 2) }}</td>
                                            <td class="text-right"><strong>{{ number_format($item['ingreso'], 2) }}</strong></td>
                                        </tr>
                                    @endforeach
                                    {{-- Subtotal per Invoice --}}
                                    <tr class="table-sm">
                                        <td colspan="6" class="text-right font-italic small">Subtotal Doc {{ $invoiceHeader['doc_number'] }}:</td>
                                        <td class="text-right font-weight-bold" style="border-top: 1px solid #dee2e6;">{{ number_format($subtotalMonto, 2) }}</td>
                                        <td class="text-right font-weight-bold" style="border-top: 1px solid #dee2e6;">{{ number_format($subtotalIngreso, 2) }}</td>
                                    </tr>
                                @endforeach
                                {{-- Total per Customer --}}
                                <tr class="bg-dark text-white table-sm">
                                    <th colspan="6" class="text-right">TOTAL {{ strtoupper($firstItem['customer_name']) }}:</th>
                                    <th class="text-right">${{ number_format($custMonto, 2) }}</th>
                                    <th class="text-right">${{ number_format($custIngreso, 2) }}</th>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center">REALICE UNA CONSULTA CON LOS FILTROS SUPERIORES</td>
                                </tr>
                            @endforelse
                        </tbody>
                        @if($groupedData->count() > 0)
                            <tfoot class="bg-dark text-white">
                                <tr>
                                    <th colspan="6" class="text-right">TOTAL GENERAL:</th>
                                    <th class="text-right">${{ number_format($totalMonto, 2) }}</th>
                                    <th class="text-right">${{ number_format($totalIngreso, 2) }}</th>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        const config = {
            maxItems: 1,
            valueField: 'id',
            labelField: 'name',
            searchField: ['name', 'taxpayer_id', 'id'],
            load: function(query, callback) {
                var url = "{{ route('data.customers') }}" + '?q=' + encodeURIComponent(query);
                fetch(url)
                    .then(response => response.json())
                    .then(json => {
                        callback(json);
                    }).catch(() => {
                        callback();
                    });
            },
            render: {
                option: function(item, escape) {
                    return `<div class="py-1">
                        <span class="font-weight-bold text-dark">${escape(item.name.toUpperCase())}</span>
                        <br><small class="text-muted">ID: ${escape(item.id)} ${item.taxpayer_id ? '| DOC: ' + escape(item.taxpayer_id) : ''}</small>
                    </div>`;
                }
            }
        };

        const tomCust = new TomSelect('#custSelect', {
            ...config,
            onChange: (val) => @this.selectCustomer(val)
        });

        Livewire.on('clear-customer-select', () => {
            tomCust.clear();
        });
    });
</script>

    </div> <!-- row -->
</div> <!-- wrapper -->
