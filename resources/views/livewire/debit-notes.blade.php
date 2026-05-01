<div>
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-md-12 col-sm-12 col-12">
            <div class="widget widget-chart-one">
                <div class="widget-heading">
                    <h4 class="card-title">
                        <b>Notas de Débito</b>
                    </h4>
                    <ul class="tabs tab-pills">
                        <li>
                            <a href="javascript:void(0)" class="tabmenu bg-dark" data-toggle="modal" data-target="#theModal">Agregar Nota</a>
                        </li>
                    </ul>
                </div>

                <div class="widget-content">
                    <div class="row justify-content-between mb-3">
                        <div class="col-lg-4 col-md-4 col-sm-12">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" wire:model.live="search" placeholder="Buscar por ND, Cliente o Concepto..." class="form-control">
                            </div>
                        </div>

                        <div class="col-lg-3 col-md-3 col-sm-12">
                            <div class="form-group">
                                <label>Desde</label>
                                <input type="date" wire:model.live="dateFrom" class="form-control">
                            </div>
                        </div>
                        <div class="col-lg-3 col-md-3 col-sm-12">
                            <div class="form-group">
                                <label>Hasta</label>
                                <input type="date" wire:model.live="dateTo" class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mt-1">
                            <thead class="text-white" style="background: #3B3F5C">
                                <tr>
                                    <th class="table-th text-white text-center">NÚMERO</th>
                                    <th class="table-th text-white text-center">CLIENTE</th>
                                    <th class="table-th text-white text-center">CONCEPTO</th>
                                    <th class="table-th text-white text-center">REFERENCIA</th>
                                    <th class="table-th text-white text-center">MONTO</th>
                                    <th class="table-th text-white text-center">ESTADO</th>
                                    <th class="table-th text-white text-center">FECHA</th>
                                    <th class="table-th text-white text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($notes as $n)
                                <tr>
                                    <td class="text-center"><h6>{{ $n->debit_number }}</h6></td>
                                    <td class="text-center"><h6>{{ $n->customer->name }}</h6></td>
                                    <td><small>{{ $n->concept }}</small></td>
                                    <td class="text-center">
                                        @if($n->sale)
                                            <span class="badge badge-info">Fac #{{ $n->sale->invoice_number }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <h6>{{ $n->currency }} {{ number_format($n->amount, 2) }}</h6>
                                        @if($n->currency != 'USD')
                                            <small class="text-muted">($ {{ number_format($n->amount / $n->exchange_rate, 2) }})</small>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <span class="badge {{ $n->status == 'paid' ? 'badge-success' : ($n->status == 'voided' ? 'badge-danger' : 'badge-warning') }}">
                                            {{ strtoupper($n->status) }}
                                        </span>
                                    </td>
                                    <td class="text-center">{{ \Carbon\Carbon::parse($n->created_at)->format('d-m-Y') }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('debit-note.pdf', $n->id) }}" target="_blank" class="btn btn-info btn-sm" title="Imprimir PDF">
                                            <i class="fas fa-file-pdf"></i>
                                        </a>
                                        @if($n->status == 'pending')
                                            <button wire:click="voidNote({{ $n->id }})" class="btn btn-danger btn-sm" title="Anular">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $notes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div wire:ignore.self class="modal fade" id="theModal" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-dark">
                    <h5 class="modal-title text-white">Nueva Nota de Débito</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group">
                                <label>Cliente</label>
                                <div wire:ignore>
                                    <input type="text" id="customerSearch" class="form-control" placeholder="Buscar cliente...">
                                </div>
                                @error('customer_id') <span class="text-danger er">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-6">
                            <div class="form-group">
                                <label>Factura Relacionada (Opcional)</label>
                                <div wire:ignore>
                                    <select id="saleSearch" class="form-control">
                                        <option value="">Ninguna / Saldo General</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-4">
                            <div class="form-group">
                                <label>Monto</label>
                                <input type="number" step="0.01" wire:model="amount" class="form-control" placeholder="0.00">
                                @error('amount') <span class="text-danger er">{{ $message }}</span> @enderror
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-4">
                            <div class="form-group">
                                <label>Moneda</label>
                                <select wire:model="currency" class="form-control">
                                    <option value="USD">USD</option>
                                    <option value="VED">VED</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-sm-12 col-md-4">
                            <div class="form-group">
                                <label>Tasa de Cambio</label>
                                <input type="number" step="0.0001" wire:model="exchange_rate" class="form-control">
                            </div>
                        </div>
                        <div class="col-sm-12">
                            <div class="form-group">
                                <label>Concepto / Razón</label>
                                <textarea wire:model="concept" class="form-control" rows="3" placeholder="Ej: Saldo inicial sistema anterior, Reajuste de precio, etc."></textarea>
                                @error('concept') <span class="text-danger er">{{ $message }}</span> @enderror
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-dark close-btn text-info" data-dismiss="modal">Cerrar</button>
                    <button type="button" wire:click.prevent="store()" class="btn btn-dark close-modal">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', function() {
            var tomCustomer = new TomSelect('#customerSearch', {
                maxItems: 1,
                valueField: 'id',
                labelField: 'name',
                searchField: ['name', 'taxpayer_id', 'phone'],
                load: function(query, callback) {
                    var url = "{{ route('data.customers') }}" + '?q=' + encodeURIComponent(query)
                    fetch(url)
                        .then(response => response.json())
                        .then(json => {
                            callback(json)
                        }).catch(() => {
                            callback();
                        });
                },
                onChange: function(value) {
                    if (value) {
                        Livewire.dispatch('set-customer', { id: value });
                    }
                },
                render: {
                    option: function(item, escape) {
                        return `<div class="py-1 d-flex">
                            <div>
                                <div class="mb-0">
                                    <span class="h5 text-info"><b class="text-dark">${ escape(item.id) }</b></span>
                                    <span class="text-warning">| ${ escape(item.name.toUpperCase()) }</span>
                                    ${item.taxpayer_id ? `<small class="text-muted"> - ${escape(item.taxpayer_id)}</small>` : ''}
                                </div>
                            </div>
                        </div>`;
                    }
                }
            });

            var tomSale = new TomSelect('#saleSearch', {
                maxItems: 1,
                valueField: 'id',
                labelField: 'text',
                searchField: ['text'],
                options: [
                    { id: '', text: 'Ninguna / Saldo General' }
                ],
                onChange: function(value) {
                    @this.set('sale_id', value);
                }
            });

            // Refresh sales options when customer changes
            Livewire.on('update-sales', (data) => {
                tomSale.clearOptions();
                tomSale.addOption({ id: '', text: 'Ninguna / Saldo General' });
                
                if (data.sales && data.sales.length > 0) {
                    data.sales.forEach(sale => {
                        tomSale.addOption({
                            id: sale.id,
                            text: 'Factura #' + sale.invoice_number + ' (Total: ' + parseFloat(sale.total).toFixed(2) + ')'
                        });
                    });
                }
                tomSale.setValue('');
            });

            window.livewire.on('close-modal', msg => {
                $('#theModal').modal('hide');
                tomCustomer.clear();
                tomSale.clear();
            });

            $('#theModal').on('shown.bs.modal', function () {
                tomCustomer.clear();
                tomSale.clear();
            });
        });
    </script>
</div>
