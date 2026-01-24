<div>
    <div class="row">

        <!-- Form View (Hidden by default, shown when editing) -->
        <div class="col-sm-12 {{ !$editing ? 'd-none' : 'd-block' }}">
            @include('livewire.users.form')
        </div>

        <!-- List View (Shown by default, hidden when editing) -->
        <div class="col-sm-12 {{ $editing ? 'd-none' : 'd-block' }}">
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
                        <div class="contact-edit chat-alert" wire:click='Add'>
                            <button class="btn btn-primary btn-sm"><i class="icon-plus"></i> Nuevo</button>
                        </div>
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
                                        <td>
                                            <span class="badge {{ $objUser->status == 'Active' ? 'badge-light-success' : 'badge-light-danger' }}">
                                                {{ $objUser->status == 'Active' ? 'Activo' : 'Bloqueado' }}
                                            </span>
                                        </td>
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
                                                </div>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">Sin resultados</td>
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
                                @if($history)
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
                                @endif
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
                // $('#inputFocus').focus() // Validar si inputFocus existe en el nuevo form
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
