<div>
    <!-- Modal Principal: Crear Producto desde Compras -->
    <div wire:ignore.self class="modal fade" id="modalCreateProduct" tabindex="-1" role="dialog" aria-labelledby="modalCreateProductLabel" aria-hidden="true" data-backdrop="static">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content shadow-lg border-0" style="border-radius: 14px;">
                
                <!-- Encabezado del Modal -->
                <div class="modal-header bg-primary text-white py-3 px-4" style="border-top-left-radius: 14px; border-top-right-radius: 14px;">
                    <div class="d-flex align-items-center">
                        <div class="bg-white text-primary rounded-circle p-2 mr-3 d-flex align-items-center justify-content-center" style="width: 38px; height: 38px;">
                            <i class="fas fa-box-open fa-lg"></i>
                        </div>
                        <div>
                            <h5 class="modal-title font-weight-bold mb-0 text-white" id="modalCreateProductLabel">Nuevo Producto</h5>
                            <small class="text-white-50">Ficha completa de producto con todas las configuraciones avanzadas</small>
                        </div>
                    </div>
                    <button type="button" class="close text-white border-0 bg-transparent" data-dismiss="modal" aria-label="Close" wire:click="resetForm" style="font-size: 1.5rem; opacity: 0.85;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <!-- Barra de Navegación de Pestañas (Tabs) -->
                <div class="bg-light border-bottom px-4 pt-2">
                    <ul class="nav nav-pills flex-nowrap overflow-auto" id="quickProductTabs" role="tablist" style="gap: 6px;">
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 1 ? 'active font-weight-bold' : 'text-dark' }} py-2 px-3" 
                               wire:click.prevent="setTab(1)" href="#" style="border-radius: 8px; font-size: 0.82rem; white-space: nowrap;">
                                <i class="fas fa-info-circle mr-1"></i> Generales
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 3 ? 'active font-weight-bold' : 'text-dark' }} py-2 px-3" 
                               wire:click.prevent="setTab(3)" href="#" style="border-radius: 8px; font-size: 0.82rem; white-space: nowrap;">
                                <i class="fas fa-tags mr-1"></i> Categorización
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 5 ? 'active font-weight-bold' : 'text-dark' }} py-2 px-3" 
                               wire:click.prevent="setTab(5)" href="#" style="border-radius: 8px; font-size: 0.82rem; white-space: nowrap;">
                                <i class="fas fa-cubes mr-1"></i> Inventario & Stock
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 4 ? 'active font-weight-bold' : 'text-dark' }} py-2 px-3" 
                               wire:click.prevent="setTab(4)" href="#" style="border-radius: 8px; font-size: 0.82rem; white-space: nowrap;">
                                <i class="fas fa-list-ol mr-1"></i> Precios & Escalas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 6 ? 'active font-weight-bold' : 'text-dark' }} py-2 px-3" 
                               wire:click.prevent="setTab(6)" href="#" style="border-radius: 8px; font-size: 0.82rem; white-space: nowrap;">
                                <i class="fas fa-truck mr-1"></i> Proveedores
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 10 ? 'active font-weight-bold' : 'text-dark' }} py-2 px-3" 
                               wire:click.prevent="setTab(10)" href="#" style="border-radius: 8px; font-size: 0.82rem; white-space: nowrap;">
                                <i class="fas fa-gavel mr-1"></i> Fletes & Reglas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 2 ? 'active font-weight-bold' : 'text-dark' }} py-2 px-3" 
                               wire:click.prevent="setTab(2)" href="#" style="border-radius: 8px; font-size: 0.82rem; white-space: nowrap;">
                                <i class="fas fa-images mr-1"></i> Galería
                            </a>
                        </li>
                    </ul>
                </div>

                <!-- Cuerpo del Modal (Contenido de Pestañas) -->
                <div class="modal-body p-4" style="background-color: #fafbfc;">
                    
                    {{-- TAB 1: INFORMACIÓN GENERAL --}}
                    @if($tab == 1)
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-8 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Nombre del Producto <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0"><i class="fas fa-box text-muted"></i></span>
                                </div>
                                <input type="text" wire:model="form.name" class="form-control border-left-0 @error('form.name') is-invalid @enderror" placeholder="Ej: VASO TÉRMICO 16OZ (500 UND)" autofocus>
                            </div>
                            @error('form.name') <span class="text-danger small font-weight-bold">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-sm-12 col-md-4 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Código / SKU / Barras</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white border-right-0"><i class="fas fa-barcode text-muted"></i></span>
                                </div>
                                <input type="text" wire:model="form.sku" class="form-control border-left-0 @error('form.sku') is-invalid @enderror" placeholder="Auto o manual">
                            </div>
                            @error('form.sku') <span class="text-danger small font-weight-bold">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-sm-12 col-md-3 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Tipo <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light"><i class="fas fa-shapes text-muted"></i></span>
                                </div>
                                <select wire:model="form.type" class="form-control">
                                    <option value="physical">Producto Físico</option>
                                    <option value="service">Servicio</option>
                                </select>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-3 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Estatus <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-light"><i class="fas fa-check-circle text-muted"></i></span>
                                </div>
                                <select wire:model="form.status" class="form-control">
                                    <option value="available">Disponible</option>
                                    <option value="out_of_stock">Sin Stock</option>
                                </select>
                            </div>
                        </div>

                        {{-- Cost, Price, Margin Wrapper (Exacto al módulo de Productos con sincronización instantánea) --}}
                        <div class="col-sm-12 mb-3" x-data="{
                            cost: @entangle('form.cost').live,
                            price: @entangle('form.price').live,
                            margin: 0,
                            markup: 0,
                            init() {
                                this.calculatePercents();
                            },
                            calculatePercents() {
                                const c = parseFloat(this.cost) || 0;
                                const p = parseFloat(this.price) || 0;
                                if(c > 0 && p > 0) {
                                    this.margin = (((p - c) / p) * 100).toFixed(2);
                                    this.markup = (((p - c) / c) * 100).toFixed(2);
                                } else {
                                    this.margin = 0;
                                    this.markup = 0;
                                }
                            },
                            onCostInput() {
                                const c = parseFloat(this.cost) || 0;
                                const mk = parseFloat(this.markup) || 0;
                                const mg = parseFloat(this.margin) || 0;
                                
                                if (c > 0 && mk > 0) {
                                    this.price = (c * (1 + (mk / 100))).toFixed(4);
                                    const p = parseFloat(this.price) || 0;
                                    this.margin = (((p - c) / p) * 100).toFixed(2);
                                } else if (c > 0 && mg > 0) {
                                    let m = mg >= 100 ? 99.99 : mg;
                                    this.price = (c / (1 - (m / 100))).toFixed(4);
                                    const p = parseFloat(this.price) || 0;
                                    this.markup = (((p - c) / c) * 100).toFixed(2);
                                } else {
                                    this.calculatePercents();
                                }
                            },
                            calculatePriceFromMargin() {
                                const c = parseFloat(this.cost) || 0;
                                if(c > 0 && this.margin !== '') {
                                    let m = parseFloat(this.margin);
                                    if (m >= 100) m = 99.99;
                                    this.price = (c / (1 - (m / 100))).toFixed(4);
                                    const p = parseFloat(this.price) || 0;
                                    this.markup = (((p - c) / c) * 100).toFixed(2);
                                }
                            },
                            calculatePriceFromMarkup() {
                                const c = parseFloat(this.cost) || 0;
                                if(c > 0 && this.markup !== '') {
                                    let mk = parseFloat(this.markup);
                                    this.price = (c * (1 + (mk / 100))).toFixed(4);
                                    const p = parseFloat(this.price) || 0;
                                    this.margin = (((p - c) / p) * 100).toFixed(2);
                                }
                            }
                        }">
                            <div class="row">
                                <div class="col-sm-12 col-md-3 mb-2">
                                    <label class="form-label font-weight-bold text-dark small text-uppercase">Costo de Compra</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light font-weight-bold text-dark">$</span>
                                        </div>
                                        <input x-model="cost" @input="onCostInput()" class="form-control" type="number" placeholder="0.0000" step="0.0001">
                                    </div>
                                    @error('form.cost') <span class="text-danger small font-weight-bold">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-12 col-md-3 mb-2">
                                    <label class="form-label font-weight-bold text-success small text-uppercase"><i class="fas fa-arrow-up text-success"></i> Incremento (%)</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light text-success font-weight-bold">%</span>
                                        </div>
                                        <input x-model="markup" @input="calculatePriceFromMarkup()" class="form-control text-success font-weight-bold" type="number" placeholder="0.00" step="0.01">
                                    </div>
                                    <small class="text-muted" style="font-size: 0.65rem;">Costo + %</small>
                                </div>
                                <div class="col-sm-12 col-md-3 mb-2">
                                    <label class="form-label font-weight-bold text-secondary small text-uppercase"><i class="fas fa-chart-pie text-secondary"></i> Margen Ganancia</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-light text-secondary font-weight-bold">%</span>
                                        </div>
                                        <input x-model="margin" @input="calculatePriceFromMargin()" class="form-control text-secondary font-weight-bold" type="number" placeholder="0.00" step="0.01">
                                    </div>
                                    <small class="text-muted" style="font-size: 0.65rem;">Ganado / P. Ventas</small>
                                </div>
                                <div class="col-sm-12 col-md-3 mb-2">
                                    <label class="form-label font-weight-bold text-primary small text-uppercase">Precio de Venta</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-primary text-white font-weight-bold">$</span>
                                        </div>
                                        <input x-model="price" @input="calculatePercents()" class="form-control text-primary font-weight-bold" type="number" placeholder="0.0000" step="0.0001">
                                    </div>
                                    @error('form.price') <span class="text-danger small font-weight-bold">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>

                        <div class="col-sm-12 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Descripción</label>
                            <textarea wire:model="form.description" rows="2" class="form-control" placeholder="Ingresa la descripción del producto..."></textarea>
                        </div>

                        <div class="col-sm-12">
                            <div class="card p-3 border-0 bg-white shadow-sm" style="border-radius: 10px;">
                                <div class="row">
                                    <div class="col-sm-12 mb-2">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="swShowSales" wire:model="form.show_in_sales">
                                            <label class="custom-control-label small font-weight-bold text-primary" for="swShowSales">Mostrar en Área de Ventas y Listas de Precios</label>
                                            <small class="form-text text-muted d-block" style="font-size: 0.72rem;">
                                                Si se desactiva (OFF), este producto no aparecerá en el POS (punto de venta), en la aplicación móvil ni en el generador de listas de precios.
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 mb-2">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="swRawMaterial" wire:model="form.is_raw_material">
                                            <label class="custom-control-label small font-weight-bold text-warning" for="swRawMaterial">Es Insumo / Materia Prima (Soplados)</label>
                                            <small class="form-text text-muted d-block" style="font-size: 0.72rem;">
                                                Activa esta opción para clasificar este producto como insumo (materia prima) en el área de producción y soplado.
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 mb-2">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="swDecimal" wire:model="form.allow_decimal">
                                            <label class="custom-control-label small font-weight-bold" for="swDecimal">Permite Cantidades Decimales (Fraccionable)</label>
                                        </div>
                                    </div>
                                    <div class="col-sm-12 mb-2">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="swVariableQty" wire:model="form.is_variable_quantity">
                                            <label class="custom-control-label small font-weight-bold text-primary" for="swVariableQty">Venta por Peso/Separado (Bobinas)</label>
                                            <small class="form-text text-muted d-block" style="font-size: 0.72rem;">
                                                Activa esta opción para manejar stock por items individuales (ej. Bobinas con peso único). Al vender, deberás seleccionar el item específico.
                                            </small>
                                        </div>
                                    </div>
                                    <div class="col-sm-6 col-md-3 mb-2">
                                        <div class="custom-control custom-switch">
                                            <input type="checkbox" class="custom-control-input" id="swRawMat" wire:model="form.is_raw_material">
                                            <label class="custom-control-label small font-weight-bold" for="swRawMat">Es Materia Prima</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- TAB 3: CATEGORIZACIÓN & PROVEEDOR --}}
                    @if($tab == 3)
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-6 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Categoría <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select wire:model="form.category_id" class="form-control @error('form.category_id') is-invalid @enderror">
                                    <option value="0">-- Seleccione Categoría --</option>
                                    @foreach($categories as $cat)
                                        <option value="{{ $cat->id }}">{{ strtoupper($cat->name) }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-primary" type="button" wire:click="openCatalogueModal('category')" title="Nueva Categoría Rápida">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            @error('form.category_id') <span class="text-danger small font-weight-bold">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-sm-12 col-md-6 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Proveedor Principal <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select wire:model="form.supplier_id" class="form-control @error('form.supplier_id') is-invalid @enderror">
                                    <option value="0">-- Seleccione Proveedor --</option>
                                    @foreach($suppliers as $sup)
                                        <option value="{{ $sup->id }}">{{ strtoupper($sup->name) }}</option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-primary" type="button" wire:click="openCatalogueModal('supplier')" title="Nuevo Proveedor Rápido">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                </div>
                            </div>
                            @error('form.supplier_id') <span class="text-danger small font-weight-bold">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-sm-12 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Etiquetas de Búsqueda (Tags)</label>
                            <input type="text" wire:model="form.tags" class="form-control" placeholder="Separadas por coma: plastico, vasos, desechable, fiesta, 16oz">
                            <small class="text-muted">Las etiquetas permiten localizar este producto en el buscador escribiendo palabras clave adicionales.</small>
                        </div>
                    </div>
                    @endif

                    {{-- TAB 5: INVENTARIO & ALERTAS DE STOCK --}}
                    @if($tab == 5)
                    <div class="row g-3">
                        <div class="col-sm-12 mb-3">
                            <div class="custom-control custom-switch">
                                <input type="checkbox" class="custom-control-input" id="swManageStock" wire:model="form.manage_stock">
                                <label class="custom-control-label font-weight-bold" for="swManageStock">Controlar y Descontar Stock en Inventario</label>
                            </div>
                        </div>

                        <div class="col-sm-12 col-md-4 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Stock Inicial en Almacén</label>
                            <input type="number" step="any" min="0" wire:model="form.stock_qty" class="form-control font-weight-bold @error('form.stock_qty') is-invalid @enderror" placeholder="0">
                            <small class="text-muted">Cantidad física que ingresará al almacén por defecto al crearlo.</small>
                            @error('form.stock_qty') <span class="text-danger small font-weight-bold d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-sm-12 col-md-4 mb-3">
                            <label class="form-label font-weight-bold text-warning small text-uppercase">Stock Mínimo (Alerta de Reorden)</label>
                            <input type="number" step="any" min="0" wire:model="form.low_stock" class="form-control @error('form.low_stock') is-invalid @enderror" placeholder="0">
                            <small class="text-muted">Notifica cuando las existencias estén por agotarse.</small>
                            @error('form.low_stock') <span class="text-danger small font-weight-bold d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-sm-12 col-md-4 mb-3">
                            <label class="form-label font-weight-bold text-info small text-uppercase">Stock Máximo Sugerido</label>
                            <input type="number" step="any" min="0" wire:model="form.max_stock" class="form-control" placeholder="0">
                            <small class="text-muted">Límite recomendado de almacenamiento.</small>
                        </div>
                    </div>
                    @endif

                    {{-- TAB 4: PRECIOS ADICIONALES & ESCALAS POR VOLUMEN --}}
                    @if($tab == 4)
                    <div class="row g-3">
                        {{-- Lista de Precios Secundarios --}}
                        <div class="col-sm-12 col-md-6 mb-4">
                            <div class="card p-3 border-0 bg-white shadow-sm h-100" style="border-radius: 10px;">
                                <h6 class="font-weight-bold text-dark small text-uppercase mb-2">
                                    <i class="fas fa-tags text-primary mr-1"></i> Lista de Precios Adicionales (Precio 2, Precio 3...)
                                </h6>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">$</span>
                                    </div>
                                    <input type="number" step="0.0001" min="0" wire:model="tempPrice" class="form-control" placeholder="0.00">
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" wire:click="storeTempPrice">
                                            <i class="fas fa-plus"></i> Agregar
                                        </button>
                                    </div>
                                </div>

                                @if(!empty($form->values) && count($form->values) > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="small">#</th>
                                                <th class="small text-right">Precio Secundario</th>
                                                <th class="small text-center" width="50"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($form->values as $idx => $v)
                                            <tr>
                                                <td class="small">{{ $idx + 1 }}</td>
                                                <td class="text-right font-weight-bold small">${{ number_format($v['price'], 4) }}</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-xs btn-outline-danger border-0 p-0" wire:click="removeTempPrice('{{ $v['id'] }}')">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <div class="text-center text-muted small py-3">No hay precios secundarios configurados.</div>
                                @endif
                            </div>
                        </div>

                        {{-- Escalas por Volumen (Tiers) --}}
                        <div class="col-sm-12 col-md-6 mb-4">
                            <div class="card p-3 border-0 bg-white shadow-sm h-100" style="border-radius: 10px;">
                                <h6 class="font-weight-bold text-dark small text-uppercase mb-2">
                                    <i class="fas fa-layer-group text-success mr-1"></i> Precios por Escala / Mayorista
                                </h6>
                                <div class="row g-2 mb-3">
                                    <div class="col-6">
                                        <input type="number" step="any" min="1" wire:model="tempTierMinQty" class="form-control form-control-sm" placeholder="Cant. Mínima">
                                    </div>
                                    <div class="col-6">
                                        <div class="input-group input-group-sm">
                                            <input type="number" step="0.0001" min="0" wire:model="tempTierPrice" class="form-control" placeholder="Precio ($)">
                                            <div class="input-group-append">
                                                <button class="btn btn-success" type="button" wire:click="addPricingTier">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                @if(!empty($form->pricing_tiers) && count($form->pricing_tiers) > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="small">A partir de (Cant.)</th>
                                                <th class="small text-right">Precio Unitario</th>
                                                <th class="small text-center" width="50"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($form->pricing_tiers as $tIdx => $tier)
                                            <tr>
                                                <td class="small font-weight-bold">≥ {{ $tier['min_qty'] }}</td>
                                                <td class="text-right font-weight-bold text-success small">${{ number_format($tier['price'], 4) }}</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-xs btn-outline-danger border-0 p-0" wire:click="removePricingTier({{ $tIdx }})">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <div class="text-center text-muted small py-3">No hay escalas de precio por cantidad configuradas.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- TAB 6: PROVEEDORES ADICIONALES --}}
                    @if($tab == 6)
                    <div class="row g-3">
                        <div class="col-sm-12">
                            <div class="card p-3 border-0 bg-white shadow-sm" style="border-radius: 10px;">
                                <h6 class="font-weight-bold text-dark small text-uppercase mb-3">
                                    <i class="fas fa-truck text-info mr-1"></i> Asignar Otros Proveedores y sus Costos
                                </h6>
                                <div class="row g-2 mb-3">
                                    <div class="col-sm-12 col-md-6">
                                        <select wire:model="tempSupplierId" class="form-control">
                                            <option value="0">-- Seleccionar Proveedor --</option>
                                            @foreach($suppliers as $sup)
                                                <option value="{{ $sup->id }}">{{ strtoupper($sup->name) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-sm-12 col-md-4">
                                        <input type="number" step="0.0001" min="0" wire:model="tempSupplierCost" class="form-control" placeholder="Costo cotizado ($)">
                                    </div>
                                    <div class="col-sm-12 col-md-2">
                                        <button class="btn btn-info btn-block" type="button" wire:click="addSupplier">
                                            <i class="fas fa-plus"></i> Vincular
                                        </button>
                                    </div>
                                </div>

                                @if(!empty($form->product_suppliers) && count($form->product_suppliers) > 0)
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="small">Proveedor</th>
                                                <th class="small text-right">Costo Acordado ($)</th>
                                                <th class="small text-center" width="50"></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($form->product_suppliers as $sIdx => $ps)
                                            <tr>
                                                <td class="small font-weight-bold">{{ strtoupper($ps['name']) }}</td>
                                                <td class="text-right font-weight-bold text-primary small">${{ number_format($ps['cost'], 4) }}</td>
                                                <td class="text-center">
                                                    <button type="button" class="btn btn-xs btn-outline-danger border-0 p-0" wire:click="removeSupplier({{ $sIdx }})">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                @else
                                <div class="text-center text-muted small py-3">No hay proveedores alternativos asignados.</div>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endif

                    {{-- TAB 10: FLETES & REGLAS --}}
                    @if($tab == 10)
                    <div class="row g-3">
                        <div class="col-sm-12 col-md-4 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Tipo de Flete Aplicable</label>
                            <select wire:model="form.freight_type" class="form-control">
                                <option value="none">Sin Flete Específico</option>
                                <option value="percentage">Porcentaje (%)</option>
                                <option value="fixed">Monto Fijo ($ USD)</option>
                            </select>
                        </div>

                        <div class="col-sm-12 col-md-4 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Valor del Flete</label>
                            <input type="number" step="0.01" min="0" wire:model="form.freight_value" class="form-control" placeholder="0.00">
                        </div>

                        <div class="col-sm-12 col-md-4 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Grupo de Precios Especial</label>
                            <select wire:model="form.price_group_id" class="form-control">
                                <option value="">-- Sin Grupo Especial --</option>
                                @foreach($priceGroups as $pg)
                                    <option value="{{ $pg->id }}">{{ strtoupper($pg->name) }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    @endif

                    {{-- TAB 2: GALERÍA DE IMÁGENES --}}
                    @if($tab == 2)
                    <div class="row g-3">
                        <div class="col-sm-12 mb-3">
                            <label class="form-label font-weight-bold text-dark small text-uppercase">Seleccionar Fotografías del Producto</label>
                            <input type="file" wire:model="form.gallery" multiple accept="image/*" class="form-control-file border p-2 w-100 bg-white" style="border-radius: 8px;">
                            <small class="text-muted d-block mt-1">Formatos soportados: JPG, PNG, WEBP. Máximo 10 MB por imagen.</small>
                            <div wire:loading wire:target="form.gallery" class="text-primary small mt-1 font-weight-bold">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Cargando imágenes preliminares...
                            </div>
                        </div>

                        @if(!empty($form->gallery))
                        <div class="col-sm-12">
                            <div class="d-flex flex-wrap gap-2">
                                @foreach($form->gallery as $photo)
                                    @if($photo)
                                    <div class="position-relative border rounded p-1 bg-white mr-2 mb-2" style="width: 100px; height: 100px;">
                                        <img src="{{ $photo->temporaryUrl() }}" class="img-fluid rounded" style="width: 100%; height: 100%; object-fit: cover;">
                                    </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                        @endif
                    </div>
                    @endif

                </div>

                <!-- Pie del Modal (Acciones) -->
                <div class="modal-footer bg-light py-3 px-4 d-flex justify-content-between">
                    <div>
                        <span class="text-muted small">
                            <i class="fas fa-shield-alt text-success mr-1"></i> El producto se creará de forma inmediata en catálogo y se añadirá al carrito.
                        </span>
                    </div>
                    <div class="d-flex align-items-center" style="gap: 8px;">
                        <button type="button" class="btn btn-light px-4" data-dismiss="modal" wire:click="resetForm" style="border-radius: 8px;">
                            Cancelar
                        </button>
                        <button type="button" class="btn btn-primary px-4 shadow-sm" wire:click="store" wire:loading.attr="disabled" style="border-radius: 8px;">
                            <span wire:loading.remove wire:target="store">
                                <i class="fas fa-save mr-1"></i> Guardar Producto
                            </span>
                            <span wire:loading wire:target="store">
                                <i class="fas fa-spinner fa-spin mr-1"></i> Guardando...
                            </span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Mini-Modal: Creación Rápida de Categoría / Proveedor sin salir -->
    <div wire:ignore.self class="modal fade" id="modalQuickCatalogue" tabindex="-1" role="dialog" style="z-index: 1060;">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content shadow-lg border-0" style="border-radius: 12px;">
                <div class="modal-header bg-dark text-white py-2 px-3">
                    <h6 class="modal-title font-weight-bold mb-0 text-white">
                        {{ $btnCreateCategory ? 'Crear Nueva Categoría' : 'Crear Nuevo Proveedor' }}
                    </h6>
                    <button type="button" class="close text-white border-0 bg-transparent" onclick="$('#modalQuickCatalogue').modal('hide')">
                        <span>&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <div class="form-group mb-0">
                        <label class="form-label font-weight-bold small text-uppercase">Nombre</label>
                        <input type="text" wire:model="catalogueName" class="form-control" placeholder="Ingrese nombre...">
                    </div>
                </div>
                <div class="modal-footer py-2 px-3">
                    <button type="button" class="btn btn-light btn-sm" onclick="$('#modalQuickCatalogue').modal('hide')">Cerrar</button>
                    <button type="button" class="btn btn-primary btn-sm" wire:click="saveCatalogue">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    @push('my-scripts')
    <script>
        document.addEventListener('livewire:init', function() {
            Livewire.on('show-modal-create-product', function() {
                $('#modalCreateProduct').modal('show');
            });

            Livewire.on('close-modal-create-product', function() {
                $('#modalCreateProduct').modal('hide');
            });

            Livewire.on('show-quick-catalogue-modal', function() {
                $('#modalQuickCatalogue').modal('show');
            });

            Livewire.on('close-quick-catalogue-modal', function() {
                $('#modalQuickCatalogue').modal('hide');
            });
        });
    </script>
    @endpush
</div>
