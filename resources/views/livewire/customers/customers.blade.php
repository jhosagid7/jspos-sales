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
