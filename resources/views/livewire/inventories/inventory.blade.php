<div>
    <div class="card card-absolute">
        <div class="card-header bg-dark">
            <h5 class="txt-light text-center">Inventario de Productos</h5>
        </div>

        <div class="card-body">

            <div class=" table-responsive ">
                <table class="table table-bordered-vertical border-0" id="tblProducts">
                    <thead>
                        <tr class="text-center">

                            <th class="p-2 text-start" width="200">Código</th>
                            <th class="p-2 text-start">Descripción</th>
                            <th class="p-2" width="200">Stock</th>
                            <th class="p-2" width="200">Costo Compra</th>
                            <th class="p-2" width="200">Total Compra</th>
                            <th class="p-2" width="200">Precio Venta</th>
                            <th class="p-2" width="200">Total Venta</th>
                            <th class="p-2" width="200">Cantidad</th>
                            <th class="p-2" width="200"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($info as $item)
                        <tr class="text-center">
                            <td class="text-start">{{$item->sku}}</td>
                            <td class="text-start text-primary">{{$item->name}}</td>

                            <td style="background-color: rgb(253, 251, 224)">{{$item->stock_qty}}</td>
                            <td>{{$item->cost}}</td>
                            <td style="background-color: rgb(210, 247, 234)">${{ round($item->stock_qty *
                                $item->cost,2) }}
                            </td>
                            <td>{{$item->price}}</td>
                            <td style="background-color: rgb(210, 247, 234)">${{ round($item->stock_qty *
                                $item->price,2) }}
                            </td>
                            <td>
                                <input type="text" class="form-control form-control-sm text-center"
                                    oninput="justNumber(this)" id="qty{{$item->id}}" placeholder="cant" maxlength="10">
                            </td>
                            <td>
                                <div class="btn-group btn-group-pill" role="group" aria-label="Basic example">

                                    <button wire:click.prevent="Ajustar({{ $item->id }}, getValue({{$item->id}}), 2)"
                                        data-container="body" data-bs-toggle="tooltip" data-bs-placement="top"
                                        data-bs-html="true" data-bs-title="<b>Agregar cantidad al stock</b>"
                                        class="btn btn-dark btn-sm"><i class="fas fa-plus"></i>
                                    </button>

                                    <button wire:click.prevent="Ajustar({{ $item->id }}, getValue({{$item->id}}), 1)"
                                        data-container="body" data-bs-toggle="tooltip" data-bs-placement="top"
                                        data-bs-html="true" data-bs-title="<b>Restar cantidad al stock</b>"
                                        class="btn btn-warning btn-sm"><i class="fas fa-minus"></i>
                                    </button>


                                    <button wire:click.prevent="Ajustar({{ $item->id }}, getValue({{$item->id}}), 3)"
                                        class="btn btn-info btn-sm" data-container="body" data-bs-toggle="tooltip"
                                        data-bs-placement="top" data-bs-html="true"
                                        data-bs-title="<b>Ajustar cantidad del stock</b>"><i
                                            class="fas fa-calculator"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="text-center">Sin resultados</td>
                        </tr>
                        @endforelse

                    </tbody>
                </table>
            </div>


        </div>
        <div class="card-footer d-flex justify-content-between">
            {{-- <button class="btn btn-light  hidden {{$editing ? 'd-block' : 'd-none' }}"
                wire:click="cancelEdit">Cancelar
            </button>

            <button class="btn btn-info  save" wire:click="Store">Guardar</button> --}}
        </div>
    </div>
    <style>
        .page-title .row {
            /* display: none !important; */
        }

        .page-title .icon-location-pin {
            display: none !important;
        }

        .rfx {
            color: orange;
        }

        .breadcrumb {
            font-weight: bolder;
        }

        .breadcrumb-item {
            font-size: 1rem !important
        }

        #tblProducts td {
            padding: 0.5rem !important
        }

        .rest {
            display: block !important;
        }
    </style>

    <script>
        document.addEventListener('livewire:init', function() {
            Livewire.on('clear-input', event => { 
                document.getElementById('qty'+ event.id).value = ''
             })
       
         })
        function getValue(inputId) {
        var ele = document.getElementById('qty' + inputId)
        if(ele != null) {
            return parseInt(ele.value)
        } else {
            return 0
        }

        }
        

        function justNumber(input) {    
            var regex = /^\d*\.?\d{0,2}$/    
    
            if (!regex.test(input.value)) {                
                input.value = input.value.slice(0, -1)
            }
        }
    </script>
</div>