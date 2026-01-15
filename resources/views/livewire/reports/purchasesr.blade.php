<div>
    <div class="row">
        <div class="col-sm-12 col-md-3 ">
            <div class="card">
                <div class="card-header bg-warning p-1">
                    <h5 class="txt-light text-center">Opciones</h5>
                </div>

                <div class="card-body">
                    @if($supplier_id != null)
                    <span> {{ $supplier_id['name'] }} <i class="fas fa-check"></i></span>
                    @else
                    <span class="f-14"><b>Elige Proveedor</b></span>
                    @endif
                    <div class="input-group" wire:ignore>
                        <input class="form-control" type="text" id="inputSupplier" placeholder="F1">
                        <span class="input-group-text list-light">
                            <i class="search-icon" data-feather="user"></i>
                        </span>
                    </div>


                    <div class="mt-5">
                        <span class="f-14"><b>Fecha desde</b></span>
                        <div class="input-group datepicker">
                            <input class="form-control flatpickr-input active" id="dateFrom" type="text"
                                autocomplete="off">
                        </div>
                    </div>
                    <div class="mt-2">
                        <span class="f-14"><b>Hasta</b></span>
                        <div class="input-group datepicker">
                            <input class="form-control flatpickr-input active" id="dateTo" type="text"
                                autocomplete="off">
                        </div>
                    </div>

                    <div class="mt-2">
                        <span class="f-14"><b>Tipo</b></span>
                        <select wire:model='type' class="form-control">
                            <option value="0">Todas</option>
                            <option value="cash">Contado</option>
                            <option value="credit">Crédito</option>
                        </select>
                    </div>

                    <div class="mt-5">
                        <button wire:click.prevent="$set('showReport', true)" class="btn btn-dark" {{ $supplier_id==null
                            && ($dateFrom==null && $dateTo==null) ? 'disabled' : '' }}>
                            Consultar
                        </button>
                    </div>


                </div>
            </div>

        </div>



        <div class="col-sm-12 col-md-9">
            <div class="card card-absolute">
                <div class="card-header bg-warning">
                    <h5 class="txt-light">Resultados de la consulta</h5>
                </div>

                <div class="card-body">
                    <div class="row note-labels">
                        <div class="col-sm-12 col-md-5"></div>
                        <div class="col-sm-12 col-md-4"></div>
                        <div class="col-sm-12 col-md-3 text-end">
                            <span class="badge badge-success f-18" {{ $totales==0 ? 'hidden' : '' }}>Total
                                Compras:
                                ${{ round($totales,2) }}</span>
                        </div>
                    </div>
                    <div class="table-responsive mt-3">
                        <table class="table table-responsive-md table-hover" id="tblPurchasesRpt">
                            <thead class="thead-primary">
                                <tr class="text-center">
                                    <th>Folio</th>
                                    <th>Proveedor</th>
                                    <th>Total</th>
                                    <th>Articulos</th>
                                    <th>Estatus</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th></th>

                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($purchases as $purchase)
                                <tr class="text-center">
                                    <td>{{$purchase->id }}</td>
                                    <td>{{$purchase->supplier ? $purchase->supplier->name : 'Sin Proveedor' }}</td>
                                    <td>${{$purchase->total }}</td>
                                    <td>{{$purchase->items }}</td>
                                    <td><span class="badge f-12 {{ $purchase->status == 'paid' ? 'badge-success' : ($purchase->status == 'return' ? 'badge-warning' : ($purchase->status == 'pending' ? 'badge-warning' : 'badge-danger')) }} ">{{$purchase->status }}</span></td>
                                    <td>{{$purchase->type }}</td>
                                    <td>{{$purchase->created_at }}</td>
                                    <td class="text-primary"></td>

                                    <td data-container="body" data-bs-toggle="tooltip" data-bs-placement="top"
                                        data-bs-html="true" data-bs-title="<b>Ver los detalles de la compra</b>">

                                        <button wire:click.prevent="getPurchaseDetail({{ $purchase->id }})"
                                            class="btn btn-outline-dark btn-xs border-0">
                                            <i class="fas fa-list"></i>
                                        </button>
                                    </td>

                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center">Sin compras</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <div class="mt-2">
                            @if(!is_array($purchases))
                            {{$purchases->links()}}
                            @endif
                        </div>
                    </div>



                </div>
                <div class="card-footer d-flex justify-content-between p-1">

                </div>
            </div>
        </div>
        @include('livewire.reports.purchase-detail')
    </div>

    <style>
        .ts-dropdown {
            z-index: 1000000 !important;
        }
    </style>
    <script>
        document.addEventListener('livewire:init', () => {   
            flatpickr("#dateFrom", {
                dateFormat: "Y/m/d",
                locale: "es",
                theme: "confetti",    
                onChange: function(selectedDates, dateStr, instance) {
                    console.log(dateStr);
                    @this.set('dateFrom',dateStr)
                }
            })
            flatpickr("#dateTo", {
                dateFormat: "Y/m/d",
                locale: "es",
                theme: "confetti",    
                onChange: function(selectedDates, dateStr, instance) {                    
                    @this.set('dateTo',dateStr)
                }
            })

    

            if (document.querySelector('#inputSupplier')) {
                    new TomSelect('#inputSupplier', {
                        maxItems: 1,
                        valueField: 'id',
                        labelField: 'name',
                        searchField: ['name', 'address'],
                        load: function(query, callback) {
                            var url = "{{ route('data.suppliers') }}" + '?q=' + encodeURIComponent(
                                query)
                            fetch(url)
                                .then(response => response.json())
                                .then(json => {                                    
                                    callback(json);
                                    //console.log(json);
                                }).catch(() => {
                                    callback();
                                });
                        },
                        onChange: function(value) {                                                         
                            var supplier = this.options[value] 
                            Livewire.dispatch('purchase_supplier', {supplier: supplier})

                        },
                        render: {
                            option: function(item, escape) {
                                return `<div class="py-1 d-flex">
            <div>
                <div class="mb-0">
                    <span class="h5 text-info">
                        <b class="text-dark">${ escape(item.id) } 
                    </span>                    
                    <span class="text-warning">|${ escape(item.name.toUpperCase()) }</span>                   
                </div>
            </div>
        </div>`;
                            },
                        },
                    });
    }


    })

    document.addEventListener('show-detail', event=> {
        $('#modalSaleDetail').modal('show')
    })
    </script>


</div>