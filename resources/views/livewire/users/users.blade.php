<div>
    <div class="row">

        <div class="col-md-8">
            <div class="card height-equal">
                <div class="card-header border-l-primary border-2">
                    <div class="row">
                        <div class="col-sm-12 col-md-8">
                            <h4>Usuarios</h4>
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
                                    <th width="25%">Usuario</th>
                                    <th width="40%">Email</th>
                                    <th width="25%">Estatus</th>
                                    <th width="10%">Role</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $objUser)
                                    <tr>
                                        <td> {{ $objUser->name }}</td>
                                        <td>{{ $objUser->email }}</td>
                                        <td>{{ $objUser->status == 'Active' ? 'Activo' : 'Bloqueado' }}</td>
                                        <td>{{ $objUser->profile == 0 ? '' : $objUser->profile }}</td>
                                        <td class="text-center">
                                            @if (Auth::user()->hasRole('Admin'))
                                                <div class="btn-group btn-group-pill" role="group">
                                                    <button class="btn btn-light btn-sm"
                                                        wire:click="Edit({{ $objUser->id }})"><i
                                                            class="fa fa-edit fa-2x"></i></button>
                                                    <button class="btn btn-light btn-sm"
                                                        onclick="confirmDestroy({{ $objUser->id }})"
                                                        {{ $objUser->sales->count() == 0 ? '' : 'disabled' }}><i
                                                            class="fa fa-trash fa-2x"></i></button>
                                                </div>
                                            @elseif (!$objUser->hasRole('Admin'))
                                                <div class="btn-group btn-group-pill" role="group">
                                                    <button class="btn btn-light btn-sm"
                                                        wire:click="Edit({{ $objUser->id }})"><i
                                                            class="fa fa-edit fa-2x"></i></button>
                                                    <button class="btn btn-light btn-sm"
                                                        onclick="confirmDestroy({{ $objUser->id }})"
                                                        {{ $objUser->sales->count() == 0 ? '' : 'disabled' }}><i
                                                            class="fa fa-trash fa-2x"></i></button>
                                                    <button class="btn btn-light btn-sm"
                                                        wire:click="viewHistory({{ $objUser->id }})" title="Historial de Cambios"><i
                                                            class="fa fa-clock-o fa-2x"></i></button>
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3">Sin resultados</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer p-1">
                    {{ $users->links() }}
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card card-absolute">
                <div class="card-header bg-primary">
                    <h5 class="txt-light">{{ $editing ? 'Editar Usuario' : 'Crear Usuario' }}</h5>
                </div>

                <div class="card-body">

                    <div class="form-group">
                        <span>Nombre <span class="txt-danger">*</span></span>
                        <input wire:model="user.name" id='inputFocus' type="text"
                            class="form-control form-control-lg" placeholder="nombre">
                        @error('user.name')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mt-3">
                        <span class="form-label">Email <span class="txt-danger">*</span></span>
                        <input wire:model="user.email" class="form-control" type="text">
                        @error('user.email')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mt-3">
                        <span>Password <span class="txt-danger">*</span></span>
                        <input wire:model="pwd" type="password" class="form-control form-control-lg"
                            placeholder="password">
                        @error('pwd')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="form-group mt-3">
                        <span>Perfil <span class="txt-danger">*</span></span>
                        @if (Auth::user()->roles[0]->name == 'Admin')
                            <select wire:model.live="user.profile" class="form-select form-control-sm">
                                <option value="0">Seleccionar </option>
                                @foreach ($roles as $rol)
                                    <option value="{{ $rol->name }}"
                                        {{ $user->hasRole($rol->name) == $user->profile ? 'selected' : '' }}>
                                        {{ $rol->name }}
                                    </option>
                                @endforeach
                            </select>
                        @else
                            @if ($user->hasRole('Admin'))
                                <span class="mr-6">No se puede editar</span>
                            @else
                                <select wire:model.live="user.profile" class="form-select form-control-sm">
                                    <option value="0">Seleccionar</option>
                                    @foreach ($roles as $rol)
                                        @if ($rol->name != 'Admin')
                                            <option value="{{ $rol->name }}"
                                                {{ $user->hasRole($rol->name) ? 'selected' : '' }}>
                                                {{ $rol->name }}
                                            </option>
                                        @endif
                                    @endforeach
                                </select>
                            @endif
                        @endif

                        @error('user.profile')
                            <span class="text-danger">{{ $message }}</span>
                        @enderror
                    </div>

                    @if($user->profile == 'Vendedor')
                    <div class="row">
                        <div class="col-sm-12">
                            <h6 class="text-info">Configuración Vendedor Foráneo</h6>
                        </div>
                        <div class="col-sm-4 form-group mt-3">
                            <span class="form-label">Comisión (%)</span>
                            <input wire:model="commission_percent" class="form-control" type="number" step="0.01" min="0" max="100">
                            @error('commission_percent') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-sm-4 form-group mt-3">
                            <span class="form-label">Flete (%)</span>
                            <input wire:model="freight_percent" class="form-control" type="number" step="0.01" min="0" max="100">
                            @error('freight_percent') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-sm-4 form-group mt-3">
                            <span class="form-label">Dif. Cambiario (%)</span>
                            <input wire:model="exchange_diff_percent" class="form-control" type="number" step="0.01" min="0" max="1000">
                            @error('exchange_diff_percent') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-sm-12 form-group mt-3">
                            <span class="form-label">Lote Actual</span>
                            <input wire:model="current_batch" class="form-control" type="text" placeholder="Ej: 1">
                            <small class="text-muted">Identificador del lote actual de ventas</small>
                        </div>

                        <div class="col-sm-12 mt-3">
                            <h6 class="text-info">Sobrescribir Comisiones (Opcional)</h6>
                            <small class="text-muted">Dejar en blanco para usar la configuración global.</small>
                        </div>
                        
                        <div class="col-sm-6 form-group mt-2">
                            <span class="form-label">Nivel 1: Días (<=)</span>
                            <input wire:model="sellerCommission1Threshold" class="form-control" type="number" placeholder="Global">
                            @error('sellerCommission1Threshold') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-sm-6 form-group mt-2">
                            <span class="form-label">Nivel 1: Porcentaje (%)</span>
                            <input wire:model="sellerCommission1Percentage" class="form-control" type="number" step="0.01" placeholder="Global">
                            @error('sellerCommission1Percentage') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-sm-6 form-group mt-2">
                            <span class="form-label">Nivel 2: Días (<=)</span>
                            <input wire:model="sellerCommission2Threshold" class="form-control" type="number" placeholder="Global">
                            @error('sellerCommission2Threshold') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                        <div class="col-sm-6 form-group mt-2">
                            <span class="form-label">Nivel 2: Porcentaje (%)</span>
                            <input wire:model="sellerCommission2Percentage" class="form-control" type="number" step="0.01" placeholder="Global">
                            @error('sellerCommission2Percentage') <span class="text-danger">{{ $message }}</span> @enderror
                        </div>
                    </div>
                    @endif



                </div>
                <div class="card-footer d-flex justify-content-between">
                    <button class="btn btn-light  hidden {{ $editing ? 'd-block' : 'd-none' }}"
                        wire:click="cancelEdit">Cancelar
                    </button>

                    <button class="btn btn-info  save" wire:click.prevent="Store">Guardar</button>
                </div>
            </div>
        </div>

    </div>
    <!-- Modal History -->
    <div class="modal fade" id="modalHistory" tabindex="-1" role="dialog" aria-labelledby="modalHistoryLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="modalHistoryLabel">Historial de Configuraciones</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" wire:click="closeHistory"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Comisión %</th>
                                    <th>Flete %</th>
                                    <th>Diferencial %</th>
                                </tr>
                            </thead>
                            <tbody wire:key="history-table-{{ $viewingUserId }}">
                                @forelse($history as $record)
                                    <tr>
                                        <td>{{ $record->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ number_format($record->commission_percent, 2) }}%</td>
                                        <td>{{ number_format($record->freight_percent, 2) }}%</td>
                                        <td>{{ number_format($record->exchange_diff_percent, 2) }}%</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center">No hay historial disponible</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="closeHistory">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    @push('my-scripts')
        <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('init-new', (event) => {
                $('#inputFocus').focus()
            })
            Livewire.on('show-history-modal', (event) => {
                $('#modalHistory').modal('show')
            })
            Livewire.on('close-history-modal', (event) => {
                $('#modalHistory').modal('hide')
            })
        })

        function confirmDestroy(id) {
            swal({
                title: '¿CONFIRMAS ELIMINAR EL REGISTRO?',
                text: "",
                icon: "warning",
                buttons: true,
                dangerMode: true,
                buttons: {
                    cancel: "Cancelar",
                    catch: {
                        text: "Aceptar"
                    }
                },
            }).then((willDelete) => {
                if (willDelete) {
                    Livewire.dispatch('destroyUser', {
                        id: id
                    })
                }
            });
        }
    </script>
    @endpush

</div>
```
