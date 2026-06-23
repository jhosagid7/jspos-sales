<div>
    <div class="row">
        <div class="col-sm-12 col-md-3">
            <!-- Options Card -->
            <div class="card mb-3">
                <div class="p-1 card-header bg-dark">
                    <h5 class="text-center txt-light mb-0">Opciones</h5>
                </div>

                <div class="card-body">
                    <!-- Sellers Selector (Checkboxes) -->
                    <div class="mt-3">
                        <span class="f-14"><b>Vendedores</b></span>
                        <div class="border p-2 rounded mt-1" style="max-height: 200px; overflow-y: auto; background-color: #f8f9fa;">
                            @foreach ($sellers as $seller)
                                <div class="custom-control custom-checkbox mb-1">
                                    <input type="checkbox" class="custom-control-input" id="seller_{{ $seller->id }}" value="{{ $seller->id }}" wire:model="selectedSellers">
                                    <label class="custom-control-label f-12" for="seller_{{ $seller->id }}">{{ $seller->name }}</label>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Deactivated Checkbox -->
                    <div class="custom-control custom-checkbox mt-3 ml-2">
                        <input type="checkbox" class="custom-control-input" id="show_deleted" wire:model="showDeleted">
                        <label class="custom-control-label f-12" for="show_deleted">Ver Desactivados / Eliminados</label>
                    </div>

                    <div class="mt-4">
                        <button wire:key="btn-consultar" wire:click.prevent="searchData" class="btn btn-dark w-100">
                            <i class="fa fa-search"></i> Consultar
                        </button>
                        <button wire:key="btn-pdf-preview" wire:click.prevent="openPdfPreview" class="btn btn-danger text-white w-100 mt-2" @if(!$showReport) disabled @endif>
                            <i class="fas fa-file-pdf"></i> Vista Previa PDF
                        </button>
                    </div>
                </div>
            </div>

            <!-- Column Config Card -->
            <div class="card">
                <div class="p-1 card-header bg-primary text-white text-center">
                    <h6 class="mb-0 text-white"><i class="fa fa-cog"></i> Configuración de Columnas</h6>
                </div>
                <div class="card-body p-2">
                    <div class="row">
                        <!-- Grouping Selector -->
                        <div class="col-sm-12 col-md-12 mb-3">
                            <span class="f-14"><b>Agrupar por</b></span>
                            <select wire:model.live="groupBy" class="form-control form-control-sm">
                                <option value="none">Sin Agrupar</option>
                                <option value="seller_id">Por Vendedor</option>
                            </select>
                        </div>

                        <div class="col-12 mb-1">
                            <hr class="mt-0 mb-2">
                            <h6 class="txt-light">Columnas</h6>
                        </div>

                        <!-- Column Toggles -->
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_name" wire:model.live="columns.name">
                                <label class="custom-control-label f-12" for="col_name">Nombre/Cliente</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_taxpayer" wire:model.live="columns.taxpayer_id">
                                <label class="custom-control-label f-12" for="col_taxpayer">Identificación (Cédula/RIF)</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_address" wire:model.live="columns.address">
                                <label class="custom-control-label f-12" for="col_address">Dirección</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_city" wire:model.live="columns.city">
                                <label class="custom-control-label f-12" for="col_city">Ciudad</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_phone" wire:model.live="columns.phone">
                                <label class="custom-control-label f-12" for="col_phone">Teléfono</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_seller" wire:model.live="columns.seller">
                                <label class="custom-control-label f-12" for="col_seller">Vendedor</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_wallet" wire:model.live="columns.wallet_balance">
                                <label class="custom-control-label f-12" for="col_wallet">Saldo Billetera</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_zone" wire:model.live="columns.zone">
                                <label class="custom-control-label f-12" for="col_zone">Zona</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_allow_credit" wire:model.live="columns.allow_credit">
                                <label class="custom-control-label f-12" for="col_allow_credit">Permite Crédito</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_credit_limit" wire:model.live="columns.credit_limit">
                                <label class="custom-control-label f-12" for="col_credit_limit">Límite Crédito</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_credit_days" wire:model.live="columns.credit_days">
                                <label class="custom-control-label f-12" for="col_credit_days">Días Crédito</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_notifications" wire:model.live="columns.notifications">
                                <label class="custom-control-label f-12" for="col_notifications">Notificaciones (WA/Email)</label>
                            </div>
                        </div>
                        <div class="col-12 mb-1">
                            <div class="custom-control custom-checkbox ml-2">
                                <input type="checkbox" class="custom-control-input" id="col_status" wire:model.live="columns.status">
                                <label class="custom-control-label f-12" for="col_status">Estado (Activo/Inactivo)</label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Column -->
        <div class="col-sm-12 col-md-9">
            <div class="card card-absolute">
                <div class="card-header bg-dark">
                    <h5 class="txt-light">Resultados de la consulta</h5>
                </div>

                <div class="card-body">
                    @if(!$showReport)
                        <div class="alert alert-info text-center">
                            Selecciona los filtros en el menú lateral y haz clic en "Consultar" para ver los clientes.
                        </div>
                    @else
                        @php
                            $isGrouped = ($groupBy === 'seller_id');
                            $loopData = $isGrouped ? $customers : ['' => $customers];
                        @endphp

                        @forelse($loopData as $groupName => $items)
                            @if($isGrouped)
                                <div class="mt-4 mb-2">
                                    <h5 class="txt-primary">
                                        <i class="fa fa-user-tie"></i> Vendedor: {{ $groupName ?: 'Sin Vendedor' }}
                                        <span class="badge badge-light-success float-right f-14">Total Clientes: {{ $items->count() }}</span>
                                    </h5>
                                </div>
                            @endif

                            <div class="table-responsive">
                                <table class="table table-hover table-striped">
                                    <thead class="thead-primary">
                                        <tr class="text-center">
                                            @if($columns['name']) <th>Cliente</th> @endif
                                            @if($columns['taxpayer_id']) <th>Identificación</th> @endif
                                            @if($columns['address']) <th>Dirección</th> @endif
                                            @if($columns['city']) <th>Ciudad</th> @endif
                                            @if($columns['phone']) <th>Teléfono</th> @endif
                                            @if($columns['seller']) <th>Vendedor</th> @endif
                                            @if($columns['wallet_balance']) <th>Billetera</th> @endif
                                            @if($columns['zone']) <th>Zona</th> @endif
                                            @if($columns['allow_credit']) <th>Crédito</th> @endif
                                            @if($columns['credit_limit']) <th>Límite</th> @endif
                                            @if($columns['credit_days']) <th>Días</th> @endif
                                            @if($columns['notifications']) <th>Notif. WA/Email</th> @endif
                                            @if($columns['status']) <th>Estado</th> @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($items as $customer)
                                            <tr class="text-center {{ $customer->deleted_at ? 'table-danger' : '' }}">
                                                @if($columns['name']) <td>{{ $customer->name }}</td> @endif
                                                @if($columns['taxpayer_id']) <td>{{ $customer->taxpayer_id }}</td> @endif
                                                @if($columns['address']) <td class="text-left">{{ $customer->address }}</td> @endif
                                                @if($columns['city']) <td>{{ $customer->city }}</td> @endif
                                                @if($columns['phone']) <td>{{ $customer->phone }}</td> @endif
                                                @if($columns['seller']) <td>{{ $customer->seller->name ?? 'Sin Vendedor' }}</td> @endif
                                                @if($columns['wallet_balance']) <td>${{ number_format($customer->wallet_balance, 2) }}</td> @endif
                                                @if($columns['zone']) <td>{{ $customer->zone }}</td> @endif
                                                @if($columns['allow_credit']) <td>{{ $customer->allow_credit ? 'SÍ' : 'NO' }}</td> @endif
                                                @if($columns['credit_limit']) <td>${{ number_format($customer->credit_limit, 2) }}</td> @endif
                                                @if($columns['credit_days']) <td>{{ $customer->credit_days }}</td> @endif
                                                @if($columns['notifications'])
                                                    <td class="text-left f-11">
                                                        <strong>WA:</strong> V: {{ $customer->whatsapp_notify_sales ? 'SÍ' : 'NO' }} | P: {{ $customer->whatsapp_notify_payments ? 'SÍ' : 'NO' }}<br>
                                                        <strong>Email:</strong> V: {{ $customer->email_notify_sales ? 'SÍ' : 'NO' }} | P: {{ $customer->email_notify_payments ? 'SÍ' : 'NO' }}
                                                    </td>
                                                @endif
                                                @if($columns['status'])
                                                    <td>
                                                        @if($customer->deleted_at)
                                                            <span class="badge badge-danger">Desactivado</span>
                                                        @else
                                                            <span class="badge badge-success">Activo</span>
                                                        @endif
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="20" class="text-center text-muted">No se encontraron clientes.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        @empty
                            <div class="alert alert-warning text-center">
                                No se encontraron clientes para los filtros seleccionados.
                            </div>
                        @endforelse
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- PDF Viewer Modal -->
    @if($showPdfModal)
    <div class="modal show" style="display: block; opacity: 1; background: rgba(0,0,0,0.7); z-index: 9999;" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-xl" role="document" style="height: 90vh; max-width: 95vw; margin-top: 5vh;">
            <div class="modal-content" style="height: 100%;">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title text-white">Vista Previa: Reporte de Clientes por Vendedor</h5>
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
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" wire:click="closePdfPreview">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
