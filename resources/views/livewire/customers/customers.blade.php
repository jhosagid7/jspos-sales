<div>
    <div class="row">

        <div class="col-md-8">
            <div class="card height-equal">
                <div class="card-header border-l-primary border-2">
                    <div class="row">
                        <div class="col-sm-12 col-md-8">
                            <h4>Clientes</h4>
                        </div>
                        <div class="col-sm-12 col-md-3">
                            {{-- search --}}
                            <div class="job-filter mb-2">
                                <div class="faq-form">
                                    <input wire:model.live='search' class="form-control" type="text"
                                        placeholder="Buscar.."><i class="search-icon" data-feather="search"></i>
                                </div>
                            </div>
                        </div>
                        <div class="contact-edit chat-alert" wire:click='Add'><i class="icon-plus"></i></div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-responsive-md table-hover">
                            <thead class="thead-primary">
                                <tr>
                                    <th width="25%">Cliente</th>
                                    <th width="40%">Dirección</th>
                                    <th width="20%">Ciudad</th>
                                    <th width="25%">Teléfono</th>
                                    <th width="25%">CC/Nit</th>
                                    <th width="15%">Vendedor</th>
                                    <th width="10%">Tipo</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($customers as $item)
                                    <tr>
                                        <td> {{ $item->name }}</td>
                                        <td>{{ $item->address }}</td>
                                        <td>{{ $item->city }}</td>
                                        <td>{{ $item->phone }}</td>
                                        <td>{{ $item->taxpayerId }}</td>
                                        <td>{{ $item->seller ? $item->seller->name : 'N/A' }}</td>
                                        <td>{{ $item->type }}</td>
                                        <td class="text-center">


                                            <div class="btn-group btn-group-pill" role="group"
                                                aria-label="Basic example">
                                                <button class="btn btn-light btn-sm"
                                                    wire:click="Edit({{ $item->id }})"><i
                                                        class="fa fa-edit fa-2x"></i>

                                                </button>
                                                {{-- @if (!$item->sales()->exists()) --}}
                                                <button class="btn btn-light btn-sm"
                                                    onclick="Confirm('customers',{{ $item->id }})">
                                                    <i class="fa fa-trash fa-2x"></i>
                                                </button>
                                                {{-- @endif --}}
                                            </div>

                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3">Sin clientes</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer p-1">
                    {{ $customers->links() }}
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-absolute">
                <div class="card-header bg-primary">
                    <h5 class="txt-light">{{ $editing ? 'Editar Cliente' : 'Crear Cliente' }}</h5>
                </div>

                <div class="card-body">

                    <div class="form-group">
                        <label>Nombre <span class="txt-danger">*</span></label>
                        <input wire:model.defer="customer.name" id='inputFocus' type="text"
                            class="form-control form-control-lg" placeholder="nombre">
                        @error('customer.name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>CC/Nit <span class="txt-danger">*</span></label>
                        <input wire:model.defer="customer.taxpayer_id" type="text"
                            class="form-control form-control-lg" placeholder="cc/nit">
                        @error('customer.taxpayer_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label class="form-label">Dirección <span class="txt-danger">*</span></label>
                        <input wire:model="customer.address" class="form-control" type="text">
                        @error('customer.address')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Ciudad <span class="txt-danger">*</span></label>
                        <input wire:model.defer="customer.city" type="text" class="form-control form-control-lg"
                            placeholder="ciudad" maxlength="100">
                        @error('customer.city')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Teléfono</label>
                        <input wire:model.defer="customer.phone" type="text" class="form-control form-control-lg"
                            placeholder="teléfono" maxlength="15">
                        @error('customer.phone')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>
                    <div class="form-group">
                        <label>Tipo <span class="txt-danger">*</span></label>
                        <select class="form-control" wire:model="customer.type">
                            <option value="0" selected disabled>Seleccionar</option>
                            <option value="Mayoristas">Mayoristas</option>
                            <option value="Consumidor Final">Consumidor Final</option>
                            <option value="Descuento1">Descuento1</option>
                            <option value="Descuento2">Descuento2</option>
                            <option value="Otro">Otro</option>
                        </select>
                        @error('customer.type')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label>Vendedor</label>
                        <select class="form-control" wire:model="customer.seller_id">
                            <option value="0">Seleccionar</option>
                            @foreach($sellers as $seller)
                                <option value="{{ $seller->id }}">{{ $seller->name }}</option>
                            @endforeach
                        </select>
                        @error('customer.seller_id')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

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
                <div class="card-footer d-flex justify-content-between">
                    <button class="btn btn-light  hidden {{ $editing ? 'd-block' : 'd-none' }}"
                        wire:click="cancelEdit">Cancelar
                    </button>

                    <button class="btn btn-info  save" wire:click="Store">Guardar</button>
                </div>
            </div>
        </div>

    </div>
    @push('my-scripts')
        <script>
            document.addEventListener('livewire:init', () => {

                Livewire.on('init-new', (event) => {
                    document.getElementById('inputFocus').focus()
                })
            })
        </script>
    @endpush

</div>
