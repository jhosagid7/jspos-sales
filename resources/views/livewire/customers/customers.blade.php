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
                    <div class="row align-items-center">
                        <div class="col-sm-12 col-md-4">
                            <h4>Clientes</h4>
                        </div>
                        <div class="col-sm-12 col-md-8 d-flex align-items-center justify-content-end gap-2 flex-wrap">
                            {{-- search --}}
                            <div class="job-filter mb-0" style="min-width: 200px;">
                                <div class="faq-form">
                                    <input wire:model.live='search' class="form-control" type="text"
                                        placeholder="Buscar.."><i class="search-icon" data-feather="search"></i>
                                </div>
                            </div>

                            <div class="d-flex align-items-center bg-light-warning px-3 py-1 rounded-pill" style="border: 1px solid #ffc10745;">
                                <div class="form-check form-switch mb-0">
                                    <input class="form-check-input" type="checkbox" id="showDeleted" wire:model.live="showDeleted" style="cursor:pointer;">
                                    <label class="form-check-label text-dark mb-0 ms-1" for="showDeleted" style="cursor:pointer; font-weight: 500; font-size: 13px;">
                                        Ver Eliminados
                                    </label>
                                </div>
                            </div>

                            @can('customers.create')
                            <div class="contact-edit chat-alert mb-0" wire:click='Add'>
                                <button class="btn btn-primary btn-sm"><i class="icon-plus"></i> Nuevo</button>
                            </div>
                            @endcan

                            @can('customers.create') {{-- Or a specific permission for import --}}
                            <div class="contact-edit chat-alert mb-0">
                                 <a href="{{ route('customers.import') }}" class="btn btn-secondary btn-sm"><i class="fas fa-file-import"></i> Importar</a>
                            </div>
                            @endcan
                        </div>
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
                                    @module('module_commissions')
                                    <th width="15%">Vendedor</th>
                                    @endmodule
                                    <th width="10%">Billetera</th>
                                    
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($customers as $item)
                                    <tr>
                                        <td> 
                                            <div class="fw-bold">{{ $item->name }}</div>
                                            @module('module_credits')
                                            @php
                                                $creditConfig = \App\Services\CreditConfigService::getCreditConfig($item, $item->seller);
                                                $isMoroso = \App\Services\CreditConfigService::hasUnpaidOverdueInvoices($item);
                                            @endphp
                                            <div class="mt-1 d-flex flex-wrap" style="gap: 5px;">
                                                @if($creditConfig['allow_credit'])
                                                    <span class="badge badge-success shadow-sm" title="Límite: {{ $creditConfig['credit_limit'] ? '$'.number_format($creditConfig['credit_limit'], 2) : 'Ilimitado' }}">
                                                        <i class="fas fa-credit-card"></i> {{ $creditConfig['credit_limit'] ? '$'.number_format($creditConfig['credit_limit'], 0) : 'Ilimitado' }}
                                                    </span>
                                                    <span class="badge badge-info shadow-sm" title="Plazo para pagar">
                                                        <i class="fas fa-clock"></i> {{ $creditConfig['credit_days'] }}d
                                                    </span>
                                                @else
                                                    <span class="badge badge-secondary shadow-sm">
                                                        <i class="fas fa-ban"></i> Sin Crédito
                                                    </span>
                                                @endif

                                                @if($item->credit_status === 'new')
                                                    <span class="badge badge-warning shadow-sm">
                                                        <i class="fas fa-star"></i> Nuevo
                                                    </span>
                                                @endif

                                                @if($isMoroso)
                                                    @php
                                                        $overdueStats = \App\Services\CreditConfigService::getOverdueStats($item);
                                                    @endphp
                                                    <span class="badge badge-danger shadow-sm" title="{{ $overdueStats['count'] }} facturas atrasadas. Deuda: ${{ number_format($overdueStats['debt'], 2) }}">
                                                        <i class="fas fa-exclamation-triangle"></i> Moroso (${{ number_format($overdueStats['debt'], 0) }} / {{ $overdueStats['max_days'] }}d)
                                                    </span>
                                                @endif

                                                @if($creditConfig['usd_payment_discount'])
                                                    <span class="badge badge-primary shadow-sm" title="Descuento en Divisas">
                                                        <i class="fas fa-tags"></i> -{{ number_format($creditConfig['usd_payment_discount'], 0) }}% USD
                                                    </span>
                                                @endif
                                            </div>
                                            @endmodule
                                        </td>
                                        <td>{{ $item->address }}</td>
                                        <td>{{ $item->city }}</td>
                                        <td>{{ $item->phone }}</td>
                                        <td>{{ $item->taxpayerId }}</td>
                                        @module('module_commissions')
                                        <td>{{ $item->seller ? $item->seller->name : 'N/A' }}</td>
                                        @endmodule
                                        <td class="text-center fw-bold {{ $item->wallet_balance > 0 ? 'text-success' : '' }}">
                                            ${{ number_format($item->wallet_balance, 2) }}
                                        </td>
                                        
                                        <td class="text-center">


                                            <div class="btn-group btn-group-pill" role="group"
                                                aria-label="Basic example">
                                                @if($item->deleted_at)
                                                    @can('customers.edit')
                                                    <button class="btn btn-warning btn-sm"
                                                        wire:click="Restore({{ $item->id }})" title="Restaurar">
                                                        <i class="fa fa-undo fa-2x"></i>
                                                    </button>
                                                    @endcan
                                                @else
                                                    @can('customers.edit')
                                                    <button class="btn btn-light btn-sm"
                                                        wire:click="Edit({{ $item->id }})"><i
                                                            class="fa fa-edit fa-2x"></i>

                                                    </button>
                                                    @endcan
                                                    @can('customers.delete')
                                                    <button class="btn btn-light btn-sm"
                                                        onclick="Confirm({{ $item->id }})">
                                                        <i class="fa fa-trash fa-2x"></i>
                                                    </button>
                                                    @endcan
                                                @endif
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
                                    <th>Recargo %</th>
                                    <th>Diferencial %</th>
                                    <th>Lote</th>
                                    <th>Acuerdo</th>
                                </tr>
                            </thead>
                            <tbody wire:key="history-table-{{ $viewingCustomerId }}">
                                @if($history)
                                @forelse($history as $record)
                                    <tr>
                                        <td>{{ $record->created_at->format('d/m/Y H:i') }}</td>
                                        <td>{{ number_format($record->commission_percent, 2) }}%</td>
                                        <td>{{ number_format($record->freight_percent, 2) }}%</td>
                                        <td>{{ number_format($record->base_markup_percent, 2) }}%</td>
                                        <td>{{ number_format($record->exchange_diff_percent, 2) }}%</td>
                                        <td>{{ $record->current_batch }}</td>
                                        <td>
                                            @if($record->agreement)
                                                <span title="{{ $record->agreement }}">{{ Str::limit($record->agreement, 30) }}</span>
                                            @else
                                                <span class="text-muted">-</span>
                                            @endif
                                        </td>
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

            function Confirm(rowId) {
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
                }).then((willDestroy) => {
                    if (willDestroy) {
                        Livewire.dispatch('Destroy', {
                            id: rowId
                        })
                    }
                });
            }
        </script>
    @endpush

</div>
