<div>
    <div class="card">
        <div class="card-header">
            <h5>Configuraciones del Sistema</h5>
        </div>
        <div class="card-body">
            <div class="row g-xl-5 g-3">
                {{-- Sidebar de Pestañas --}}
                <div class="col-xxl-3 col-xl-4 box-col-4e sidebar-left-wrapper">
                    <ul class="sidebar-left-icons nav nav-pills" id="settings-pills-tab" role="tablist">
                        {{-- Tab 1: Configuración General --}}
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 1 ? 'active' : '' }}" wire:click.prevent="$set('tab',1)"
                                id="general-settings-tab" data-bs-toggle="pill" href="#general-settings" role="tab"
                                aria-controls="general-settings" aria-selected="{{ $tab == 1 ? 'true' : 'false' }}">
                                <div class="nav-rounded">
                                    <div class="product-icons">
                                        <svg class="stroke-icon">
                                            <use href="../assets/svg/icon-sprite.svg#product-detail"></use>
                                        </svg>
                                    </div>
                                </div>
                                <div class="product-tab-content">
                                    <h6>Configuración General</h6>
                                    <p>Empresa y contacto</p>
                                </div>
                            </a>
                        </li>

                        {{-- Tab 2: Configuración de Ventas --}}
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 2 ? 'active' : '' }}" wire:click.prevent="$set('tab',2)"
                                id="sales-settings-tab" data-bs-toggle="pill" href="#sales-settings" role="tab"
                                aria-controls="sales-settings" aria-selected="{{ $tab == 2 ? 'true' : 'false' }}">
                                <div class="nav-rounded">
                                    <div class="product-icons">
                                        <svg class="stroke-icon">
                                            <use href="../assets/svg/icon-sprite.svg#product-category"></use>
                                        </svg>
                                    </div>
                                </div>
                                <div class="product-tab-content">
                                    <h6>Configuración de Ventas</h6>
                                    <p>Créditos y confirmación</p>
                                </div>
                            </a>
                        </li>

                        {{-- Tab 3: Configuración de Monedas --}}
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 3 ? 'active' : '' }}" wire:click.prevent="$set('tab',3)"
                                id="currencies-settings-tab" data-bs-toggle="pill" href="#currencies-settings" role="tab"
                                aria-controls="currencies-settings" aria-selected="{{ $tab == 3 ? 'true' : 'false' }}">
                                <div class="nav-rounded">
                                    <div class="product-icons">
                                        <svg class="stroke-icon">
                                            <use href="../assets/svg/icon-sprite.svg#pricing"></use>
                                        </svg>
                                    </div>
                                </div>
                                <div class="product-tab-content">
                                    <h6>Configuración de Monedas</h6>
                                    <p>Monedas y tasas</p>
                                </div>
                            </a>
                        </li>

                        {{-- Tab 4: Configuración de Bancos --}}
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 4 ? 'active' : '' }}" wire:click.prevent="$set('tab',4)"
                                id="banks-settings-tab" data-bs-toggle="pill" href="#banks-settings" role="tab"
                                aria-controls="banks-settings" aria-selected="{{ $tab == 4 ? 'true' : 'false' }}">
                                <div class="nav-rounded">
                                    <div class="product-icons">
                                        <svg class="stroke-icon">
                                            <use href="../assets/svg/icon-sprite.svg#stroke-ecommerce"></use>
                                        </svg>
                                    </div>
                                </div>
                                <div class="product-tab-content">
                                    <h6>Configuración de Bancos</h6>
                                    <p>Bancos y monedas</p>
                                </div>
                            </a>
                        </li>

                        {{-- Tab 5: Configuración de Comisiones --}}
                        <li class="nav-item">
                            <a class="nav-link {{ $tab == 5 ? 'active' : '' }}" wire:click.prevent="$set('tab',5)"
                                id="commissions-settings-tab" data-bs-toggle="pill" href="#commissions-settings" role="tab"
                                aria-controls="commissions-settings" aria-selected="{{ $tab == 5 ? 'true' : 'false' }}">
                                <div class="nav-rounded">
                                    <div class="product-icons">
                                        <svg class="stroke-icon">
                                            <use href="../assets/svg/icon-sprite.svg#stroke-charts"></use>
                                        </svg>
                                    </div>
                                </div>
                                <div class="product-tab-content">
                                    <h6>Configuración de Comisiones</h6>
                                    <p>Reglas globales</p>
                                </div>
                            </a>
                        </li>
                    </ul>
                </div>

                {{-- Contenido de las Pestañas --}}
                <div class="col-xxl-9 col-xl-8 box-col-8 position-relative">
                    <div class="tab-content" id="settings-pills-tabContent">
                        
                        {{-- TAB 1: CONFIGURACIÓN GENERAL --}}
                        <div class="tab-pane fade {{ $tab == 1 ? 'active show' : '' }}" id="general-settings" role="tabpanel"
                            aria-labelledby="general-settings-tab">
                            <div class="sidebar-body">
                                <form class="row g-3">
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">EMPRESA <span class="txt-danger">*</span></label>
                                        <input wire:model="businessName" type="text" class="form-control" maxlength="150">
                                        @error('businessName') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">TELÉFONO</label>
                                        <input wire:model="phone" type="text" class="form-control" maxlength="20">
                                        @error('phone') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">CC / NIT <span class="txt-danger">*</span></label>
                                        <input wire:model="taxpayerId" type="text" class="form-control" maxlength="35">
                                        @error('taxpayerId') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-3">
                                        <label class="form-label">IVA / VAT <span class="txt-danger">*</span></label>
                                        <input wire:model="vat" type="text" class="form-control">
                                        @error('vat') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-3">
                                        <label class="form-label">N° de Decimales <span class="txt-danger">*</span></label>
                                        <input wire:model="decimals" type="text" class="form-control">
                                        @error('decimals') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">IMPRESORA <span class="txt-danger">*</span></label>
                                        <input wire:model="printerName" type="text" class="form-control" maxlength="55">
                                        @error('printerName') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">CITY</label>
                                        <input wire:model="city" class="form-control" type="text" maxlength="255">
                                        @error('city') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">WEBSITE</label>
                                        <input wire:model="website" type="text" class="form-control" placeholder="www.website.com" maxlength="99">
                                        @error('website') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">LEYENDA</label>
                                        <input wire:model="leyend" type="text" class="form-control" maxlength="99">
                                        @error('leyend') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label">DIRECCIÓN</label>
                                        <textarea wire:model="address" class="form-control" cols="30" rows="2"></textarea>
                                        @error('address') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-primary" wire:click.prevent="saveConfig" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="saveConfig">Guardar Configuración</span>
                                            <span wire:loading wire:target="saveConfig">Guardando...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- TAB 2: CONFIGURACIÓN DE VENTAS --}}
                        <div class="tab-pane fade {{ $tab == 2 ? 'active show' : '' }}" id="sales-settings" role="tabpanel"
                            aria-labelledby="sales-settings-tab">
                            <div class="sidebar-body">
                                <form class="row g-3">
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">VENTAS CRÉDITO (DÍAS) <span class="txt-danger">*</span></label>
                                        <input wire:model="creditDays" type="number" class="form-control">
                                        @error('creditDays') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">COMPRAS CRÉDITO (DÍAS)</label>
                                        <input wire:model="creditPurchaseDays" class="form-control" type="text" maxlength="255">
                                        @error('creditPurchaseDays') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-sm-12">
                                        <label class="form-label">CODIGO DE CONFIRMACION</label>
                                        <textarea wire:model="confirmationCode" class="form-control" cols="30" rows="2"></textarea>
                                        @error('confirmationCode') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-primary" wire:click.prevent="saveConfig" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="saveConfig">Guardar Configuración</span>
                                            <span wire:loading wire:target="saveConfig">Guardando...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        {{-- TAB 3: CONFIGURACIÓN DE MONEDAS --}}
                        <div class="tab-pane fade {{ $tab == 3 ? 'active show' : '' }}" id="currencies-settings" role="tabpanel"
                            aria-labelledby="currencies-settings-tab">
                            <div class="sidebar-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <h6 class="mb-3">Moneda Principal</h6>
                                        <div class="row g-2">
                                            <div class="col-md-6">
                                                <label for="primaryCurrency">Seleccionar Moneda Principal</label>
                                                <select wire:model="primaryCurrency" class="form-control">
                                                    <option value="">Seleccione una moneda</option>
                                                    @foreach ($currencies as $currency)
                                                        <option value="{{ $currency->code }}" {{ $currency->code == $primaryCurrency ? 'selected' : '' }}>
                                                            {{ $currency->code }} ({{ $currency->label }})
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-6 d-flex align-items-end">
                                                <button wire:click="setPrimaryCurrency" class="btn btn-primary">Guardar Moneda Principal</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <hr>
                                        <h6 class="mb-3">Agregar Moneda Secundaria</h6>
                                        <div class="row g-2">
                                            <div class="col-md-3">
                                                <input wire:model="newCurrencyCode" type="text" class="form-control" placeholder="Código (ISO 4217)">
                                            </div>
                                            <div class="col-md-3">
                                                <input wire:model="newCurrencyLabel" type="text" class="form-control" placeholder="Label">
                                            </div>
                                            <div class="col-md-2">
                                                <input wire:model="newCurrencySymbol" type="text" class="form-control" placeholder="Símbolo">
                                            </div>
                                            <div class="col-md-2">
                                                <input wire:model="newExchangeRate" type="number" step="0.000001" class="form-control" placeholder="Tasa de Cambio">
                                            </div>
                                            <div class="col-md-2">
                                                <button wire:click="addCurrency" class="btn btn-primary w-100">Agregar</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <h6 class="mb-3 mt-3">Monedas Configuradas</h6>
                                        <div class="table-responsive">
                                                    <table class="table table-bordered">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>Código</th>
                                                        <th>Label</th>
                                                        <th>Símbolo</th>
                                                        <th>Tasa de Cambio</th>
                                                        <th>Principal</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($currencies as $currency)
                                                        <tr>
                                                            <td>{{ $currency->code }}</td>
                                                            <td>{{ $currency->label }}</td>
                                                            <td>{{ $currency->symbol }}</td>
                                                            <td>
                                                                <div class="input-group input-group-sm">
                                                                    <input type="number" step="0.000001" 
                                                                        class="form-control" 
                                                                        wire:model="editableRates.{{ $currency->id }}"
                                                                        {{ $currency->is_primary ? 'disabled' : '' }}>
                                                                    @if(!$currency->is_primary)
                                                                        <button class="btn btn-primary" wire:click="updateCurrencyRate({{ $currency->id }})">
                                                                            <i class="fa fa-save"></i>
                                                                        </button>
                                                                    @endif
                                                                </div>
                                                            </td>
                                                            <td>
                                                                @if($currency->is_primary)
                                                                    <span class="badge bg-success">Principal</span>
                                                                @endif
                                                            </td>
                                                            <td>
                                                                @if(!$currency->is_primary)
                                                                    <button wire:click="deleteCurrency('{{ $currency->id }}')" class="btn btn-danger btn-sm">
                                                                        <i class="fa fa-trash"></i>
                                                                    </button>
                                                                @endif
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

                        {{-- TAB 4: CONFIGURACIÓN DE BANCOS --}}
                        <div class="tab-pane fade {{ $tab == 4 ? 'active show' : '' }}" id="banks-settings" role="tabpanel"
                            aria-labelledby="banks-settings-tab">
                            <div class="sidebar-body">
                                <div class="row g-3">
                                    <div class="col-12">
                                        <h6 class="mb-3">Agregar Nuevo Banco</h6>
                                        <div class="row g-2">
                                            <div class="col-md-5">
                                                <label>Nombre del Banco</label>
                                                <input wire:model="newBankName" type="text" class="form-control" placeholder="Nombre">
                                                @error('newBankName') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-4">
                                                <label>Moneda del Banco</label>
                                                <select wire:model="newBankCurrency" class="form-control">
                                                    <option value="">Seleccione una moneda</option>
                                                    @foreach ($currencies as $currency)
                                                        <option value="{{ $currency->code }}">{{ $currency->code }} - {{ $currency->label }}</option>
                                                    @endforeach
                                                </select>
                                                @error('newBankCurrency') <span class="text-danger">{{ $message }}</span> @enderror
                                            </div>
                                            <div class="col-md-3 d-flex align-items-end">
                                                <button wire:click="addBank" class="btn btn-primary w-100">Agregar Banco</button>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-12">
                                        <h6 class="mb-3 mt-3">Bancos Configurados</h6>
                                        <div class="table-responsive">
                                            <table class="table table-bordered">
                                                <thead class="bg-light">
                                                    <tr>
                                                        <th>Nombre</th>
                                                        <th>Moneda</th>
                                                        <th>Acciones</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach ($banks as $bank)
                                                        <tr>
                                                            <td>{{ $bank->name }}</td>
                                                            <td>{{ $bank->currency_code }}</td>
                                                            <td>
                                                                <button wire:click="deleteBank({{ $bank->id }})" class="btn btn-danger btn-sm">
                                                                    <i class="fa fa-trash"></i> Eliminar
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

                        {{-- TAB 5: CONFIGURACIÓN DE COMISIONES --}}
                        <div class="tab-pane fade {{ $tab == 5 ? 'active show' : '' }}" id="commissions-settings" role="tabpanel"
                            aria-labelledby="commissions-settings-tab">
                            <div class="sidebar-body">
                                <form class="row g-3">
                                    <div class="col-12">
                                        <div class="alert alert-light-primary" role="alert">
                                            <i class="fas fa-info-circle"></i> Estas reglas se aplicarán si el Vendedor o el Cliente no tienen una configuración específica.
                                        </div>
                                    </div>
                                    
                                    <h6 class="mb-2">Nivel 1 (Pronto Pago)</h6>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">Días Límite (<=)</label>
                                        <input wire:model="globalCommission1Threshold" type="number" class="form-control" placeholder="Ej: 15">
                                        @error('globalCommission1Threshold') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">Porcentaje (%)</label>
                                        <input wire:model="globalCommission1Percentage" type="number" step="0.01" class="form-control" placeholder="Ej: 8">
                                        @error('globalCommission1Percentage') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-12"><hr></div>

                                    <h6 class="mb-2">Nivel 2 (Pago Tardío)</h6>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">Días Límite (<=)</label>
                                        <input wire:model="globalCommission2Threshold" type="number" class="form-control" placeholder="Ej: 30">
                                        @error('globalCommission2Threshold') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>
                                    <div class="col-sm-12 col-md-6">
                                        <label class="form-label">Porcentaje (%)</label>
                                        <input wire:model="globalCommission2Percentage" type="number" step="0.01" class="form-control" placeholder="Ej: 4">
                                        @error('globalCommission2Percentage') <span class="text-danger">{{ $message }}</span> @enderror
                                    </div>

                                    <div class="col-12">
                                        <button class="btn btn-primary" wire:click.prevent="saveConfig" wire:loading.attr="disabled">
                                            <span wire:loading.remove wire:target="saveConfig">Guardar Configuración</span>
                                            <span wire:loading wire:target="saveConfig">Guardando...</span>
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
