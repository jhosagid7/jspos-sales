<div class="card">
    <div class="card-header">
        <div>
            @if ($editing && $form->product_id > 0)
                <h5>Editar Producto | <small class="text-info">{{ $form->name }}</small>
                </h5>
            @else
                <h5>Crear Nuevo Producto</h5>
            @endif
        </div>

    </div>
    <div class="card-body">

        {{-- @if ($errors != null)
        <div class="alert alert-danger">
            <ul>
                @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif --}}


        <div class="row g-xl-5 g-3">
            <div class="col-xxl-3 col-xl-4 box-col-4e sidebar-left-wrapper">
                {{-- tabs --}}
                <ul class="nav flex-column nav-pills me-3" id="add-product-pills-tab" role="tablist">
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 1 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',1)" href="#">
                            <i class="fa fa-info-circle fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Generales</h6>
                                <small class="{{ $tab == 1 ? 'text-white' : 'text-muted' }}">Información básica</small>
                            </div>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 2 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',2)" href="#">
                            <i class="fa fa-images fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Galería</h6>
                                <small class="{{ $tab == 2 ? 'text-white' : 'text-muted' }}">Imágenes del producto</small>
                            </div>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 3 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',3)" href="#">
                            <i class="fa fa-tags fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Categorización</h6>
                                <small class="{{ $tab == 3 ? 'text-white' : 'text-muted' }}">Categoría y Proveedor</small>
                            </div>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 4 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',4)" href="#">
                            <i class="fa fa-list-ol fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Precios</h6>
                                <small class="{{ $tab == 4 ? 'text-white' : 'text-muted' }}">Lista de precios</small>
                            </div>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 5 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',5)" href="#">
                            <i class="fa fa-cubes fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Inventario</h6>
                                <small class="{{ $tab == 5 ? 'text-white' : 'text-muted' }}">Stock y Alertas</small>
                            </div>
                        </a>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 6 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',6)" href="#">
                            <i class="fa fa-users fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Proveedores</h6>
                                <small class="{{ $tab == 6 ? 'text-white' : 'text-muted' }}">Asignar Proveedores</small>
                            </div>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 7 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',7)" href="#">
                            <i class="fa fa-puzzle-piece fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Componentes</h6>
                                <small class="{{ $tab == 7 ? 'text-white' : 'text-muted' }}">Productos Compuestos</small>
                            </div>
                        </a>
                    </li>

                </ul>
            </div>
            <div class="col-xxl-9 col-xl-8 box-col-8 position-relative">
                <div class="tab-content" id="add-product-pills-tabContent">
                    <div class="tab-pane fade {{ $tab == 1 ? 'active show' : '' }}" id="detail-product" role="tabpanel"
                        aria-labelledby="detail-product-tab">
                        <div class="sidebar-body">
                            <form class="row g-2">
                                {{-- name --}}
                                <div class="col-sm-12 col-md-8">
                                    <label class="form-label">Nombre <span class="txt-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-box"></i></span>
                                        <input wire:model="form.name" class="form-control" type="text" placeholder="Ej: Coca Cola 2L">
                                    </div>
                                    @error('form.name')
                                        <span class="text-danger">{{ $message }}</span>
                                    @enderror
                                </div>
                                {{-- sku --}}
                                <div class="col-sm-12 col-md-4">
                                    <label class="form-label">Sku</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-barcode"></i></span>
                                        <input wire:model="form.sku" class="form-control" type="text" placeholder="Código de barras">
                                    </div>
                                </div>
                                {{-- description --}}
                                <div class="col-sm-12 mb-3">
                                    <div class="toolbar-box" wire:ignore>
                                        <div id="toolbar2"><span class="ql-formats">
                                                <select class="ql-size"></select></span><span class="ql-formats">
                                                <button class="ql-bold">Bold </button>
                                                <button class="ql-italic">Italic </button>
                                                <button class="ql-underline">underline</button>
                                                <button class="ql-strike">Strike </button></span><span
                                                class="ql-formats">
                                                <button class="ql-list" value="ordered">List </button>
                                                <button class="ql-list" value="bullet"> </button>
                                                <button class="ql-indent" value="-1"> </button>
                                                <button class="ql-indent" value="+1"></button></span><span
                                                class="ql-formats">
                                                <button class="ql-link"></button>
                                                <button class="ql-image"></button>
                                                <button class="ql-video"></button></span></div>
                                        <div id="editor2"></div>
                                    </div>
                                </div>
                                {{-- type --}}
                                <div class="col-sm-12 col-md-3">
                                    <label class="form-label">Tipo <span class="txt-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-shapes"></i></span>
                                        <select wire:model="form.type" class="form-control form-select" required="">
                                            <option value="service">Servicio</option>
                                            <option value="physical">Producto Físico</option>
                                        </select>
                                    </div>
                                    {{-- @error('type') <span class="text-danger">{{ $message }}</span>
                                    @enderror --}}
                                </div>
                                {{-- status --}}
                                <div class="col-sm-12 col-md-3">
                                    <label class="form-label">Estatus <span class="txt-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-check-circle"></i></span>
                                        <select wire:model="form.status" class="form-control form-select" required="">
                                            <option value="available" selected>Disponible</option>
                                            <option value="out_of_stock">Sin Stock</option>
                                        </select>
                                    </div>
                                    {{-- @error('status') <span class="text-danger">{{ $message }}</span>
                                    @enderror --}}
                                </div>
                                {{-- Cost, Price, Margin Wrapper --}}
                                <div class="col-sm-12" x-data="{
                                    cost: @entangle('form.cost'),
                                    price: @entangle('form.price'),
                                    margin: 0,
                                    calculateMargin() {
                                        if(this.cost > 0 && this.price > 0) {
                                            this.margin = ((this.price - this.cost) / this.price * 100).toFixed(2);
                                        } else {
                                            this.margin = 0;
                                        }
                                    },
                                    calculatePrice() {
                                        if(this.cost > 0 && this.margin > 0) {
                                            // Price = Cost / (1 - Margin%)
                                            this.price = (this.cost / (1 - (this.margin / 100))).toFixed(2);
                                        }
                                    }
                                }" x-init="calculateMargin()">
                                    <div class="row">
                                        {{-- cost --}}
                                        <div class="col-sm-12 col-md-4">
                                            <label class="form-label">Costo de Compra</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input wire:model="form.cost" x-model="cost" @input="calculateMargin()" class="form-control numerico" type="number" placeholder="0.00">
                                            </div>
                                        </div>
                                        {{-- price --}}
                                        <div class="col-sm-12 col-md-4">
                                            <label class="form-label">Precio de Venta</label>
                                            <div class="input-group">
                                                <span class="input-group-text">$</span>
                                                <input wire:model="form.price" x-model="price" @input="calculateMargin()" class="form-control numerico" type="number" placeholder="0.00">
                                            </div>
                                        </div>
                                        {{-- margin --}}
                                        <div class="col-sm-12 col-md-4">
                                            <label class="form-label">Margen de Ganancia (%)</label>
                                            <div class="input-group">
                                                <span class="input-group-text">%</span>
                                                <input x-model="margin" @input="calculatePrice()" class="form-control numerico" type="number" placeholder="0.00">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- stock fields moved to Inventory tab --}}

                            </form>
                            <div class="mt-3">

                            </div>
                        </div>
                    </div>
                    <div class="tab-pane fade {{ $tab == 2 ? 'active show' : '' }}" id="gallery-product"
                        role="tabpanel" aria-labelledby="gallery-product-tab">
                        <div class="sidebar-body">
                            <div class="product-upload">
                                <p>Galeria de Imagenes </p>
                                <form class="dropzone dropzone-light" id="multiFileUploadA" action="/upload.php">
                                    <div class="dz-message needsclick">
                                        <svg>
                                            <use href="../assets/svg/icon-sprite.svg#file-upload"></use>
                                        </svg>
                                        <span class="note needsclick">SVG,PNG,JPG or GIF </span>
                                    </div>
                                </form>
                            </div>
                            <input type="file" class="form-control" wire:model="form.gallery"
                                accept="image/x-png,image/jpeg" style="height:44px" multiple id="inputImg">
                            {{-- @error('gallery.*')
                            <span style="color: red;">{{ $message }}</span>
                            @enderror --}}

                            <div class="mt-2">
                                <div wire:loading wire:target="form.gallery">Cargando imágenes...</div>
                                @if (!empty($form->gallery))
                                    <div class="row">
                                        @foreach ($form->gallery as $photo)
                                            <div class="col-6 col-sm-4 col-md-3 col-lg-2 mb-3">
                                                <div class="media">
                                                    <img src="{{ $photo->temporaryUrl() }}"
                                                        class="img-fluid rounded img-40" alt="img">
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>


                        </div>
                    </div>
                    <div class="tab-pane fade {{ $tab == 3 ? 'active show' : '' }}" id="category-product"
                        role="tabpanel" aria-labelledby="category-product-tab">
                        <div class="sidebar-body">
                            <form>
                                <div class="row g-lg-4 g-3">
                                    <div class="col-12">
                                        <div class="row g-3">
                                            <div class="col-sm-6">
                                                <div class="row g-2">
                                                    <div class="col-12">
                                                        <label class="form-label m-0">Categoría <span
                                                                class="txt-danger">*</span></label>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fa fa-tags"></i></span>
                                                            <select wire:model="form.category_id" class="form-control form-select">
                                                                <option value="0" disabled>
                                                                    Seleccionar</option>
                                                                @foreach ($categories as $category)
                                                                    <option value="{{ $category->id }}"
                                                                        {{ $category->id == $form->category_id ? 'selected' : '' }}>
                                                                        {{ $category->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        @error('form.category_id')
                                                            <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="category-buton">
                                                    <a class="btn button-primary" href="#!"
                                                        wire:click="modal('category')">
                                                        <i class="me-2 fa fa-plus">
                                                        </i>Nueva Categoría </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <div class="row g-3">
                                            <div class="col-sm-6">
                                                <div class="row g-2">
                                                    <div class="col-12">
                                                        <label class="form-label m-0">Proveedor <span
                                                                class="txt-danger">*</span></label>
                                                    </div>
                                                    <div class="col-12">
                                                        <div class="input-group">
                                                            <span class="input-group-text"><i class="fa fa-truck"></i></span>
                                                            <select wire:model="form.supplier_id" class="form-control form-select"
                                                                id="supplier">
                                                                <option value="0" disabled> Seleccionar</option>
                                                                @foreach ($suppliers as $supplier)
                                                                    <option value="{{ $supplier->id }}"
                                                                        {{ $supplier->id == $form->supplier_id ? 'selected' : '' }}>
                                                                        {{ $supplier->name }}
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                        @error('form.supplier_id')
                                                            <span class="text-danger">{{ $message }}</span>
                                                        @enderror
                                                    </div>
                                                </div>
                                            </div>

                                            <div class="col-12">
                                                <div class="category-buton"><a class="btn button-primary"
                                                        href="#!" wire:click="modal('supplier')"><i
                                                            class="me-2 fa fa-plus">
                                                        </i>Nuevo Proveedor </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                            </form>
                        </div>
                    </div>
                    <div class="tab-pane fade {{ $tab == 4 ? 'active show' : '' }}" id="pricings" role="tabpanel"
                        aria-labelledby="pricings-tab">
                        <div class="sidebar-body">
                            <form class="price-wrapper">
                                <div class="row g-3 custom-input" x-data="{
                                    cost: @entangle('form.cost'),
                                    price: @entangle('form.value'),
                                    margin: 0,
                                    calculateMargin() {
                                        if(this.cost > 0 && this.price > 0) {
                                            this.margin = ((this.price - this.cost) / this.price * 100).toFixed(2);
                                        } else {
                                            this.margin = 0;
                                        }
                                    },
                                    calculatePrice() {
                                        if(this.cost > 0 && this.margin > 0) {
                                            this.price = (this.cost / (1 - (this.margin / 100))).toFixed(2);
                                        }
                                    }
                                }">

                                    <div class="col-sm-3">
                                        <label class="form-label" for="initialCost">Precio de Venta <span
                                                class="txt-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input wire:model="form.value" x-model="price" @input="calculateMargin()" class="form-control numerico" type="number" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-sm-3">
                                        <label class="form-label">Margen (%)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">%</span>
                                            <input x-model="margin" @input="calculatePrice()" class="form-control numerico" type="number" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="col-sm-12 col-md-2">
                                        <button wire:click.prevent="storeTempPrice"
                                            class="btn btn-primary mt-4">Agregar</button>
                                    </div>
                                </div>
                                <div class="row g-3 mt-3">
                                    {{-- @json($form) --}}
                                    <div class="col-sm-12 col-md-4">
                                        <table class="table table-light">
                                            <thead class="thead-light">
                                                <tr>
                                                    <th>Precio</th>
                                                    <th class="text-right">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($form->values as $item)
                                                    <tr wire:key="{{ $item['id'] }}">
                                                        {{-- <td>{{ $item }}</td> --}}
                                                        <td>${{ $item['price'] }} 
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-light btn-sm"
                                                                wire:click.prevent="removeTempPrice({{ $item['id'] }})">
                                                                <i class="fa fa-trash fa-2x"></i>
                                                            </button>
                                                        </td>

                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                            </form>
                        </div>
                    </div>

                    {{-- Inventory Tab --}}
                    <div class="tab-pane fade {{ $tab == 5 ? 'active show' : '' }}" id="inventory" role="tabpanel">
                        <div class="sidebar-body">
                            <form class="row g-3">
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label">Administrar Stock</label>
                                    <select wire:model="form.manage_stock" class="form-control form-select">
                                        <option value="1">Si, Controlar Stock</option>
                                        <option value="0">Vender sin Límites</option>
                                    </select>
                                </div>
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label">Stock Actual</label>
                                    @if(!empty($form->product_components) && !$form->is_pre_assembled)
                                        <input type="text" class="form-control" value="Calculado Dinámicamente" disabled>
                                        <small class="text-info">El stock depende de los componentes disponibles.</small>
                                    @else
                                        <input wire:model="form.stock_qty" class="form-control" type="number">
                                    @endif
                                </div>
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label">Stock Mínimo (Alerta)</label>
                                    <input wire:model="form.low_stock" class="form-control" type="number">
                                </div>
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label">Stock Máximo (Ideal)</label>
                                    <input wire:model="form.max_stock" class="form-control" type="number">
                                </div>
                            </form>

                            <div class="col-sm-12 mt-4">
                                <h6>Distribución de Stock por Depósito</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm table-bordered table-striped">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Depósito</th>
                                                <th class="text-center">Stock</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($form->stock_details as $detail)
                                                <tr>
                                                    <td>{{ $detail['warehouse_name'] }}</td>
                                                    <td class="text-center">{{ $detail['stock'] }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="2" class="text-center">No hay información de stock disponible</td>
                                                </tr>
                                            @endforelse
                                            <tr class="table-info fw-bold">
                                                <td>TOTAL</td>
                                                <td class="text-center">{{ collect($form->stock_details)->sum('stock') }}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Suppliers Tab --}}
                    <div class="tab-pane fade {{ $tab == 6 ? 'active show' : '' }}" id="suppliers" role="tabpanel">
                        <div class="sidebar-body">
                            <form class="row g-3">
                                <div class="col-sm-12 col-md-5">
                                    <label class="form-label">Proveedor</label>
                                    <select wire:model="form.temp_supplier_id" class="form-control form-select">
                                        <option value="0">Seleccionar</option>
                                        @foreach ($suppliers as $supplier)
                                            <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.temp_supplier_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-12 col-md-4">
                                    <label class="form-label">Costo</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input wire:model="form.supplier_cost" class="form-control numerico" type="number" placeholder="0.00">
                                    </div>
                                    @error('form.supplier_cost') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-12 col-md-3">
                                    <button wire:click.prevent="addSupplier" class="btn btn-primary mt-4">Agregar</button>
                                </div>
                            </form>

                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Proveedor</th>
                                            <th>Costo</th>
                                            <th>Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($form->product_suppliers as $index => $item)
                                            <tr>
                                                <td>{{ $item['name'] }}</td>
                                                <td>${{ number_format($item['cost'], 2) }}</td>
                                                <td>
                                                    <button wire:click.prevent="removeSupplier({{ $index }})" class="btn btn-danger btn-sm">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    {{-- Components Tab --}}
                    <div class="tab-pane fade {{ $tab == 7 ? 'active show' : '' }}" id="components" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="row g-3 position-relative">
                                <div class="col-sm-12 col-md-6">
                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" id="preAssembledSwitch" wire:model="form.is_pre_assembled">
                                        <label class="form-check-label" for="preAssembledSwitch">Kit Pre-ensamblado (Stock Manual)</label>
                                    </div>
                                    <small class="text-muted">
                                        Activado: Creas stock manualmente descontando componentes.<br>
                                        Desactivado: Stock calculado dinámicamente según componentes.
                                    </small>
                                </div>
                                <div class="col-sm-12 col-md-6">
                                    <label class="form-label">Costo Adicional (Mano de obra, etc)</label>
                                    <div class="input-group">
                                        <span class="input-group-text">$</span>
                                        <input wire:model.live.debounce.500ms="form.additional_cost" class="form-control numerico" type="number" placeholder="0.00">
                                    </div>
                                </div>

                                <div class="col-sm-12">
                                    <label class="form-label">Buscar Componente</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fa fa-search"></i></span>
                                        <input wire:model.live.debounce.300ms="search_component" class="form-control" type="text" placeholder="Escribe para buscar...">
                                    </div>
                                    
                                    @if(!empty($component_search_results))
                                        <ul class="list-group mt-1 w-100" style="z-index: 1000; max-height: 200px; overflow-y: auto;">
                                            @foreach($component_search_results as $result)
                                                <li class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                                    style="cursor: pointer;"
                                                    wire:click="addComponent({{ $result->id }}, '{{ $result->name }}')">
                                                    <span>{{ $result->name }} ({{ $result->sku }})</span>
                                                    <span class="badge badge-primary">Agregar</span>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Componente</th>
                                            <th width="150">Cantidad</th>
                                            <th width="100">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($form->product_components as $index => $item)
                                            <tr>
                                                <td>{{ $item['name'] }}</td>
                                                <td>
                                                    <input type="number" 
                                                           class="form-control form-control-sm" 
                                                           value="{{ $item['quantity'] }}"
                                                           wire:change="updateComponentQty({{ $index }}, $event.target.value)"
                                                           min="0.01" step="0.01">
                                                </td>
                                                <td>
                                                    <button wire:click.prevent="removeComponent({{ $index }})" class="btn btn-danger btn-sm">
                                                        <i class="fa fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>



                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between">
        <button wire:click.prevent="cancel" class="btn btn-light">
            Volver a Productos
        </button>

        @if ($editing && $form->product_id == 0)
            <button wire:click.prevent="Store" class="btn btn-primary">
                <i class="fa fa-save"></i> Registrar Producto
            </button>
        @else
            <button wire:click.prevent="Update" class="btn btn-dark">
                Actualizar Producto
            </button>
        @endif
    </div>
</div>
