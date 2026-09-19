<div>
    <div class="row sales layout-top-spacing">
        <div class="col-sm-12">
            <div class="widget widget-chart-one">
                <div class="widget-heading d-flex justify-content-between align-items-center">
                    <h4 class="card-title">
                        <b>MOVIMIENTOS DE PRODUCTO (KARDEX)</b>
                    </h4>
                    @if($product_id)
                    <div class="btn-group ml-auto">
                        <button class="btn btn-warning mr-2" wire:click="openCutModal">
                            <i class="fas fa-cut"></i> Establecer Corte de Inventario
                        </button>
                        <button class="btn btn-primary" wire:click="openModalPdf">
                            <i class="fas fa-file-pdf"></i> Previsualizar PDF
                        </button>
                    </div>
                    @endif
                </div>

                <div class="widget-content">
                    <div class="row">
                        <!-- FILTRO PRODUCTO INTELIGENTE -->
                        <div class="col-sm-12 col-md-4">
                            <div class="form-group mb-0 position-relative" 
                                x-data="{ 
                                    selectedIndex: -1,
                                    get itemCount() {
                                        return this.$refs.resultsList ? this.$refs.resultsList.children.length : 0;
                                    },
                                    navigate(direction) {
                                        if (this.itemCount === 0) return;
                                        if (direction === 'down') {
                                            this.selectedIndex = (this.selectedIndex < this.itemCount - 1) ? this.selectedIndex + 1 : 0;
                                        } else {
                                            this.selectedIndex = (this.selectedIndex > 0) ? this.selectedIndex - 1 : this.itemCount - 1;
                                        }
                                    },
                                    selectItem() {
                                        if (this.selectedIndex >= 0 && this.$refs.resultsList) {
                                            const item = this.$refs.resultsList.children[this.selectedIndex];
                                            if (item) item.click();
                                        }
                                    }
                                }">
                                
                                <label>Producto (SKU, Nombre, Tag)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    </div>
                                    <input type="text" 
                                        class="form-control" 
                                        placeholder="Buscar..."
                                        wire:model.live.debounce.300ms="search"
                                        @keydown.arrow-down.prevent="navigate('down')"
                                        @keydown.arrow-up.prevent="navigate('up')"
                                        @keydown.enter.prevent="selectItem()"
                                        @focus="$wire.searchProducts()"
                                    >
                                    @if($product_id)
                                    <div class="input-group-append">
                                        <button class="btn btn-outline-danger" wire:click="$set('product_id', null); $set('search', '')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                    @endif
                                </div>

                                <!-- Resultados de Busqueda -->
                                @if(!empty($products_results))
                                <ul x-ref="resultsList" class="list-group position-absolute w-100 shadow-lg search-results-list" style="z-index: 1050; max-height: 300px; overflow-y: auto;">
                                    @foreach($products_results as $index => $p)
                                    <li class="list-group-item list-group-item-action d-flex align-items-center p-2" 
                                        wire:click="selectProduct({{ $p['id'] }})" 
                                        style="cursor: pointer;"
                                        :class="{ 'bg-primary text-white': selectedIndex === {{ $index }} }"
                                        @mouseenter="selectedIndex = {{ $index }}">
                                        
                                        @if(isset($p['photo']) && $p['photo'])
                                            <img src="{{ asset($p['photo']) }}" class="rounded mr-2" style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="rounded mr-2 bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <i class="fas fa-image text-white-50"></i>
                                            </div>
                                        @endif

                                        <div class="d-flex flex-column">
                                            <span class="font-weight-bold" :class="{ 'text-white': selectedIndex === {{ $index }} }">
                                                {{ $p['sku'] }} - {{ $p['name'] }}
                                            </span>
                                            <small :class="{ 'text-white-50': selectedIndex === {{ $index }}, 'text-muted': selectedIndex !== {{ $index }} }">
                                                Cat: {{ $p['category']['name'] ?? 'S/C' }} | Stock: {{ $p['stock'] }}
                                            </small>
                                        </div>
                                    </li>
                                    @endforeach
                                </ul>
                                @endif
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-3">
                            <div class="form-group">
                                <label>Depósito/Almacén</label>
                                <select wire:model.live="selected_warehouse_id" wire:change="calculateMovements" class="form-control">
                                    <option value="all">TODOS LOS DEPÓSITOS</option>
                                    @foreach($warehouses as $wh)
                                        <option value="{{ $wh->id }}">{{ strtoupper($wh->name) }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-2">
                            <div class="form-group">
                                <label>Desde</label>
                                <input type="date" wire:model.live="dateFrom" wire:change="calculateMovements" class="form-control">
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-2">
                            <div class="form-group">
                                <label>Hasta</label>
                                <input type="date" wire:model.live="dateTo" wire:change="calculateMovements" class="form-control">
                            </div>
                        </div>
                    </div>

                    @if($product_id)
                    <!-- CARDS DE RESUMEN -->
                    <div class="row mt-4">
                        <div class="col-md-3">
                            <div class="card text-white bg-info mb-3 shadow">
                                <div class="card-body">
                                    <h6 class="card-title">Stock Inicial</h6>
                                    <h4 class="text-right">{{ number_format($initialStock, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-success mb-3 shadow">
                                <div class="card-body">
                                    <h6 class="card-title">Total Entradas (+)</h6>
                                    <h4 class="text-right">{{ number_format($totalIn, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-danger mb-3 shadow">
                                <div class="card-body">
                                    <h6 class="card-title">Total Salidas (-)</h6>
                                    <h4 class="text-right">{{ number_format($totalOut, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="card text-white bg-primary mb-3 shadow border-0">
                                <div class="card-body">
                                    <h6 class="card-title text-white">Existencia Final</h6>
                                    <h4 class="text-right">{{ number_format($finalStock, 2) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- TABLA DE MOVIMIENTOS -->
                    <div class="table-responsive mt-3">
                        <table class="table table-bordered table-striped table-hover mt-1">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th class="text-center">FECHA / HORA</th>
                                    <th>TIPO</th>
                                    <th class="text-center">REF #</th>
                                    <th>DEPÓSITO</th>
                                    <th>DETALLE (CLIENTE/PROV/MOTIVO)</th>
                                    <th>OPERADOR</th>
                                    <th class="text-center">ENTRADA (+)</th>
                                    <th class="text-center">SALIDA (-)</th>
                                    <th class="text-center">BALANZA</th>
                                </tr>
                            </thead>
                            <tbody>
                                @php $currentBalance = $initialStock; @endphp
                                @forelse($movements as $m)
                                    @php 
                                        if (isset($m->is_cut_reset) && $m->is_cut_reset == 1) {
                                            $currentBalance = floatval($m->quantity_in);
                                        } else {
                                            $currentBalance += ($m->quantity_in - $m->quantity_out);
                                        }
                                    @endphp
                                    <tr class="{{ isset($m->is_cut_reset) && $m->is_cut_reset == 1 ? 'table-warning' : '' }}" style="{{ isset($m->is_cut_reset) && $m->is_cut_reset == 1 ? 'border: 2px solid #e2a03f; background-color: #fff9e6;' : '' }}">
                                        <td class="text-center font-weight-bold">{{ \Carbon\Carbon::parse($m->movement_date)->format('d/m/Y H:i') }}</td>
                                        <td>
                                            @if($m->type == 'Corte de Inventario')
                                                <span class="badge badge-dark text-uppercase p-2 shadow-sm"><i class="fas fa-cut"></i> {{ $m->type }}</span>
                                            @elseif($m->type == 'Venta')
                                                <span class="badge badge-warning text-uppercase">{{ $m->type }}</span>
                                            @elseif($m->type == 'Compra')
                                                <span class="badge badge-success text-uppercase">{{ $m->type }}</span>
                                            @elseif($m->type == 'Cargo (Ajuste)' || $m->type == 'Devolución (NC)')
                                                <span class="badge badge-info text-uppercase">{{ $m->type }}</span>
                                            @elseif($m->type == 'Producción')
                                                <span class="badge text-white text-uppercase" style="background-color: #16a085;"><i class="fas fa-industry"></i> {{ $m->type }}</span>
                                            @elseif($m->type == 'Consumo Producción')
                                                <span class="badge text-white text-uppercase" style="background-color: #d35400;"><i class="fas fa-boxes"></i> {{ $m->type }}</span>
                                            @elseif(str_contains($m->type, 'Anulada'))
                                                <span class="badge badge-secondary text-uppercase"><i class="fas fa-undo"></i> {{ $m->type }}</span>
                                            @elseif(str_contains($m->type, 'Transferencia'))
                                                <span class="badge badge-primary text-uppercase"><i class="fas fa-exchange-alt"></i> {{ $m->type }}</span>
                                            @else
                                                <span class="badge badge-danger text-uppercase">{{ $m->type }}</span>
                                            @endif
                                        </td>
                                        <td class="text-center font-weight-bold">{{ $m->reference }}</td>
                                        <td class="font-weight-bold text-primary">{{ $m->warehouse_name ?? '-' }}</td>
                                        <td>
                                            @if(isset($m->is_cut_reset) && $m->is_cut_reset == 1)
                                                <strong class="text-dark"><i class="fas fa-check-circle text-success mr-1"></i>{{ $m->detail }}</strong>
                                            @else
                                                {{ $m->detail ?? '-' }}
                                            @endif
                                        </td>
                                        <td>{{ $m->operator }}</td>
                                        <td class="text-center text-success font-weight-bold">
                                            @if(isset($m->is_cut_reset) && $m->is_cut_reset == 1)
                                                <span class="badge badge-success" style="font-size: 13px;">= {{ number_format($m->quantity_in, 2) }}</span>
                                            @elseif($m->quantity_in > 0)
                                                +{{ number_format($m->quantity_in, 2) }}
                                            @endif
                                        </td>
                                        <td class="text-center text-danger font-weight-bold">
                                            {{ $m->quantity_out > 0 ? '-' . number_format($m->quantity_out, 2) : '' }}
                                        </td>
                                        <td class="text-center font-weight-bold {{ $currentBalance < 0 ? 'text-danger' : 'text-primary' }}" style="font-size: 14px;">
                                            {{ number_format($currentBalance, 2) }}
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center">No se encontraron movimientos.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @else
                    <div class="alert alert-info mt-4">
                        <i class="fas fa-info-circle"></i> Escribe en el buscador el SKU o nombre del producto para analizar sus movimientos.
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Corte de Inventario (Física por Depósito) -->
    <div wire:ignore.self class="modal fade" id="cutModal" tabindex="-1" role="dialog" aria-labelledby="cutModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title font-weight-bold" id="cutModalLabel">
                        <i class="fas fa-cut"></i> Establecer Corte de Inventario Físico
                    </h5>
                    <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <strong><i class="fas fa-info-circle"></i> ¿Cómo funciona el Corte de Inventario?</strong><br>
                        Ingresa el inventario físico real contado para cada depósito. El sistema calculará la diferencia y generará automáticamente los cargos o descargos de ajuste correspondientes bajo el motivo <em>"Corte de Inventario"</em>. La fecha inicial del Kardex se ajustará a hoy para que inicies el control limpio de las operaciones desde este punto.
                    </div>

                    @if(!empty($cut_warehouses))
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="bg-dark text-white">
                                <tr>
                                    <th>Depósito / Almacén</th>
                                    <th class="text-center">Stock Actual en Sistema</th>
                                    <th class="text-center" style="width: 220px;">Stock Físico Contado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($cut_warehouses as $whId => $whData)
                                <tr>
                                    <td class="font-weight-bold text-primary align-middle">
                                        <i class="fas fa-warehouse mr-1"></i> {{ $whData['name'] }}
                                    </td>
                                    <td class="text-center align-middle font-weight-bold {{ $whData['current_stock'] < 0 ? 'text-danger' : 'text-dark' }}">
                                        {{ number_format($whData['current_stock'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        <input type="number" 
                                            step="0.01" 
                                            min="0" 
                                            class="form-control text-center font-weight-bold" 
                                            style="font-size: 16px;" 
                                            wire:model="cut_warehouses.{{ $whId }}.counted_stock"
                                            placeholder="0.00">
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="form-group mt-3">
                        <label class="font-weight-bold">Notas u Observaciones del Corte:</label>
                        <input type="text" class="form-control" wire:model="cut_notes" placeholder="Ej: Toma física de inventario para arranque de nuevo ciclo">
                    </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-warning font-weight-bold" wire:click="saveInventoryCut" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveInventoryCut">
                            <i class="fas fa-check-circle"></i> Aplicar y Guardar Corte
                        </span>
                        <span wire:loading wire:target="saveInventoryCut">
                            <i class="fas fa-spinner fa-spin"></i> Aplicando Corte...
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Previsualización PDF Estilo Manual -->
    @if($showPdfModal)
    <div class="modal fade show" style="display: block; background: rgba(0,0,0,0.7); z-index: 9999;" tabindex="-1" role="dialog" wire:click.self="closeModalPdf">
        <div class="modal-dialog modal-xl" role="document" style="height: 90vh; max-width: 95vw; margin-top: 5vh;">
            <div class="modal-content" style="height: 100%;">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white">Kardex de Producto - Vista Previa</h5>
                    <button type="button" class="btn-close btn-close-white" wire:click="closeModalPdf" aria-label="Close" style="filter: invert(1); background: transparent; border: none; color: white; font-size: 24px;">&times;</button>
                </div>
                <div class="modal-body p-0" style="height: calc(100% - 60px);">
                    @if($pdfUrl)
                        <iframe src="{{ $pdfUrl . '&warehouse_id=' . $selected_warehouse_id }}" style="width: 100%; height: 100%; border: none;"></iframe>
                    @else
                        <div class="d-flex justify-content-center align-items-center" style="height: 100%;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="sr-only">Cargando...</span>
                            </div>
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" wire:click="closeModalPdf">Cerrar</button>
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

    <script>
        document.addEventListener('livewire:initialized', () => {
            Livewire.on('show-cut-modal', () => {
                $('#cutModal').modal('show');
            });
            Livewire.on('hide-cut-modal', () => {
                $('#cutModal').modal('hide');
            });
        });
    </script>
</div>

