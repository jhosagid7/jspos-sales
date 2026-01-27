<div class="card">
    <div class="card-header">
        <div>
            @if ($editing && $customer->id)
                <h5>Editar Cliente | <small class="text-info">{{ $customer->name }}</small></h5>
            @else
                <h5>Crear Nuevo Cliente</h5>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="row g-xl-5 g-3">
            {{-- Left Sidebar --}}
            <div class="col-xxl-3 col-xl-4 box-col-4e sidebar-left-wrapper">
                <ul class="nav flex-column nav-pills me-3" id="customer-pills-tab" role="tablist">
                    {{-- Tab 1: Generales --}}
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
                    {{-- Tab 2: Comisiones --}}
                    <li class="nav-item mb-2">
                        <a class="nav-link {{ $tab == 2 ? 'active' : '' }} d-flex align-items-center gap-4 p-3" 
                           wire:click.prevent="$set('tab',2)" href="#">
                            <i class="fa fa-percentage fa-2x"></i>
                            <div>
                                <h6 class="mb-0">Comisiones</h6>
                                <small class="{{ $tab == 2 ? 'text-white' : 'text-muted' }}">Sobrescribir comisiones</small>
                            </div>
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Right Content --}}
            <div class="col-xxl-9 col-xl-8 box-col-8 position-relative">
                <div class="tab-content" id="customer-pills-tabContent">
                    
                    {{-- Tab 1: Generales --}}
                    <div class="tab-pane fade {{ $tab == 1 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="row g-2">
                                <div class="col-sm-12">
                                    <label class="form-label">Nombre <span class="txt-danger">*</span></label>
                                    <input wire:model="customer.name" id='inputFocus' class="form-control" type="text" placeholder="nombre">
                                    @error('customer.name') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">CC/Nit <span class="txt-danger">*</span></label>
                                    <input wire:model="customer.taxpayer_id" class="form-control" type="text" placeholder="cc/nit">
                                    @error('customer.taxpayer_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Teléfono</label>
                                    <input wire:model="customer.phone" class="form-control" type="text" placeholder="teléfono" maxlength="15">
                                    @error('customer.phone') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-12 mt-3">
                                    <label class="form-label">Dirección <span class="txt-danger">*</span></label>
                                    <input wire:model="customer.address" class="form-control" type="text" placeholder="dirección">
                                    @error('customer.address') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Ciudad <span class="txt-danger">*</span></label>
                                    <input wire:model="customer.city" class="form-control" type="text" placeholder="ciudad" maxlength="100">
                                    @error('customer.city') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Email</label>
                                    <input wire:model="customer.email" class="form-control" type="email" placeholder="correo@ejemplo.com">
                                    @error('customer.email') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Tipo <span class="txt-danger">*</span></label>
                                    <select class="form-control" wire:model="customer.type">
                                        <option value="0" selected disabled>Seleccionar</option>
                                        <option value="Mayoristas">Mayoristas</option>
                                        <option value="Consumidor Final">Consumidor Final</option>
                                        <option value="Descuento1">Descuento1</option>
                                        <option value="Descuento2">Descuento2</option>
                                        <option value="Otro">Otro</option>
                                    </select>
                                    @error('customer.type') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 mt-3">
                                    <label class="form-label">Vendedor</label>
                                    <select class="form-control" wire:model="customer.seller_id">
                                        <option value="0">Seleccionar</option>
                                        @foreach($sellers as $seller)
                                            <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                                        @endforeach
                                    </select>
                                    @error('customer.seller_id') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Tab 2: Comisiones --}}
                    <div class="tab-pane fade {{ $tab == 2 ? 'active show' : '' }}" role="tabpanel">
                        <div class="sidebar-body">
                            <div class="form-group mt-3">
                                <h6 class="text-info">Sobrescribir Comisiones (Opcional)</h6>
                                <small class="text-muted">Dejar en blanco para usar la configuración del vendedor o global.</small>
                            </div>
                            
                            <div class="row">
                                <div class="col-sm-6 form-group mt-2">
                                    <span class="form-label">Nivel 1: Días (<=)</span>
                                    <input wire:model="customerCommission1Threshold" class="form-control" type="number" placeholder="Heredado">
                                    @error('customerCommission1Threshold') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 form-group mt-2">
                                    <span class="form-label">Nivel 1: Porcentaje (%)</span>
                                    <input wire:model="customerCommission1Percentage" class="form-control" type="number" step="0.01" placeholder="Heredado">
                                    @error('customerCommission1Percentage') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>

                                <div class="col-sm-6 form-group mt-2">
                                    <span class="form-label">Nivel 2: Días (<=)</span>
                                    <input wire:model="customerCommission2Threshold" class="form-control" type="number" placeholder="Heredado">
                                    @error('customerCommission2Threshold') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                                <div class="col-sm-6 form-group mt-2">
                                    <span class="form-label">Nivel 2: Porcentaje (%)</span>
                                    <input wire:model="customerCommission2Percentage" class="form-control" type="number" step="0.01" placeholder="Heredado">
                                    @error('customerCommission2Percentage') <span class="text-danger">{{ $message }}</span> @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
    <div class="card-footer d-flex justify-content-between p-3">
        <button class="btn btn-secondary" wire:click="cancelEdit">
            <i class="fas fa-times mr-1"></i> Cancelar
        </button>
        
        <button class="btn btn-primary" wire:click="Store">
            <i class="fas fa-check mr-1"></i> {{ $customer->id ? 'Actualizar' : 'Crear Cliente' }}
        </button>
    </div>
</div>
