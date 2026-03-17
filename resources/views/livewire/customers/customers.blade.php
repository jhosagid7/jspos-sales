<div>
    <div class="row">

        {{-- Form View (Hidden by default, shown when editing) --}}
        <div class="col-sm-12 {{ !$editing ? 'd-none' : 'd-block' }}">
            @include('livewire.customers.form')
        </div>

        {{-- List View (Shown by default, hidden when editing) --}}
        <div class="col-sm-12 {{ $editing ? 'd-none' : 'd-block' }}">
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
                        @can('customers.create')
                        <div class="contact-edit chat-alert" wire:click='Add'>
                            <button class="btn btn-primary btn-sm"><i class="icon-plus"></i> Nuevo</button>
                        </div>
                        @endcan
                        @can('customers.create') {{-- Or a specific permission for import --}}
                        <div class="contact-edit chat-alert ml-2">
                             <a href="{{ route('customers.import') }}" class="btn btn-secondary btn-sm"><i class="fas fa-file-import"></i> Importar</a>
                        </div>
                        @endcan
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
                                    <th width="10%">Billetera</th>
                                    
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
                                        <td class="text-center fw-bold {{ $item->wallet_balance > 0 ? 'text-success' : '' }}">
                                            ${{ number_format($item->wallet_balance, 2) }}
                                        </td>
                                        
                                        <td class="text-center">


                                            <div class="btn-group btn-group-pill" role="group"
                                                aria-label="Basic example">
                                                @can('customers.edit')
                                                <button class="btn btn-light btn-sm"
                                                    wire:click="Edit({{ $item->id }})"><i
                                                        class="fa fa-edit fa-2x"></i>

                                                </button>
                                                @endcan
                                                {{-- @if (!$item->sales()->exists()) --}}
                                                @can('customers.delete')
                                                <button class="btn btn-light btn-sm"
                                                    onclick="Confirm('customers',{{ $item->id }})">
                                                    <i class="fa fa-trash fa-2x"></i>
                                                </button>
                                                @endcan
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

    </div>

    <!-- Modal History -->
    <div class="modal fade" id="modalHistory" tabindex="-1" role="dialog" aria-labelledby="modalHistoryLabel" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header bg-primary">
                    <h5 class="modal-title" id="modalHistoryLabel">Historial de Configuraciones (Cliente)</h5>
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
                                    <th>Lote</th>
                                </tr>
                            </thead>
                            <tbody wire:key="history-table-{{ $viewingCustomerId }}">
                                @if($history)
                                @forelse($history as $record)
                                    <tr>
                                        <td>{{ $record->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ number_format($record->commission_percent, 2) }}%</td>
                                        <td>{{ number_format($record->freight_percent, 2) }}%</td>
                                        <td>{{ number_format($record->exchange_diff_percent, 2) }}%</td>
                                        <td>{{ $record->current_batch }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center">No hay historial disponible</td>
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
                    document.getElementById('inputFocus').focus()
                })
                Livewire.on('show-history-modal', (event) => {
                    $('#modalHistory').modal('show')
                })
                Livewire.on('close-history-modal', (event) => {
                    $('#modalHistory').modal('hide')
                })
            })
        </script>
    @endpush

</div>
