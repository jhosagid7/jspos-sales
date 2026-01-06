<div>
    @push('my-styles')
        <link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/quill.snow.css') }}">
    @endpush

    @include('livewire.products.modal')

    <div class="row">

        <div class="col-sm-12 {{ !$editing ? 'd-none' : 'd-block' }}">

            @include('livewire.products.form')

        </div>


        <div class="col-sm-12 {{ $editing ? 'd-none' : 'd-block' }}">
            <div class="card right-sidebar-chat">

                <div class="right-sidebar-title">
                    <div class="common-space">
                        <div class="chat-time group-chat">
                            <h4 class="mb-0">Productos</h4>
                        </div>


                        <div class="d-flex gap-2">
                            {{-- search --}}
                            <div class="job-filter mb-2">
                                <div class="faq-form">
                                    <input wire:model.live='search' class="form-control" type="text"
                                        placeholder="Buscar.."><i class="search-icon" data-feather="search"></i>
                                </div>
                            </div>

                            <button class="btn btn-primary" wire:click='addNew'>
                                <i class="fa fa-plus"></i> Nuevo
                            </button>

                            {{-- <button class="btn btn-info" type="button" data-bs-toggle="modal"
                                data-bs-target="#modalProduct">Tooltips and popovers</button> --}}

                            {{-- <div class="contact-edit chat-alert">
                                <svg class="dropdown-toggle" role="menu" data-bs-toggle="dropdown"
                                    aria-expanded="false">
                                    <use href="../assets/svg/icon-sprite.svg#menubar"></use>
                                </svg>
                                <div class="dropdown-menu dropdown-menu-end"><a class="dropdown-item" href="#!">View
                                        details</a><a class="dropdown-item" href="#!">
                                        Send messages</a><a class="dropdown-item" href="#!">
                                        Add to favorites</a></div>
                            </div> --}}
                        </div>
                    </div>
                </div>
                {{-- <div class="card-header border-l-primary border-2 d-flex">
                    <h4 class="mb-0">Productos</h4>
                </div> --}}
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-responsive-md table-hover ">
                            <thead class="thead-primary">
                                <tr>
                                    <th class="text-center" width="100"></th>
                                    <th class="text-left">Descripción</th>
                                    <th class="text-center">Precios</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Estado</th>
                                    <th class="text-center">Existencia</th>
                                    <th class="text-center">Categoría</th>
                                    <th class="text-center">Proveedor</th>
                                    <th class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($products as $product)
                                    <tr>
                                        <td>
                                            <div class="product-box">
                                                <div class="product-img">
                                                    <img alt="photo" class="img-fluid rounded img-40"
                                                        src="{{ asset($product->photo) }}"
                                                        data-src="{{ asset($product->photo) }}">
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-left">
                                            <div class="txt-primary">
                                                {{ $product->name }}
                                            </div>
                                            @if ($product->sku)
                                                <small class="text-info">sku:{{ $product->sku }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            @if ($product->priceList->count() > 0)
                                                [{{ '$' . implode(', ', $product->priceList->pluck('price')->toArray()) }}]
                                            @else
                                                ${{ $product->price }}
                                            @endif
                                        </td>
                                        <td class="text-center">
                                            {{ $product->type == 'service' ? 'SERVICIO' : 'PRODUCTO' }}
                                        </td>
                                        <td class="text-center">
                                            {{ $product->status == 'available'
                                                ? 'DISPONIBLE'
                                                : 'SIN
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                    STOCK' }}
                                        </td>
                                        <td class="text-center">
                                            <div>
                                                <span
                                                    class="badge {{ $product->stock_qty > $product->low_stock ? 'badge-light-success' : 'badge-light-danger' }} ">Stock:{{ $product->stock_qty }}</span>
                                            </div>
                                            <small>Mínimo:{{ $product->low_stock }}</small>
                                        </td>
                                        <td class="text-center">{{ $product->category->name }}</td>
                                        <td class="text-center">{{ $product->supplier->name }}</td>

                                        @if (app('fun')->getCurrentRole() != 'TECNICO')
                                            <td class="text-center">
                                                <div class="btn-group btn-group-pill" role="group"
                                                    aria-label="Basic example">
                                                    <button class="btn btn-light btn-sm"
                                                        wire:click="Edit({{ $product->id }})"><i
                                                            class="fa fa-edit fa-2x"></i>

                                                    </button>
                                                    @if (!$product->sales()->exists() && !$product->purchases()->exists())
                                                        <button class="btn btn-light btn-sm"
                                                            onclick="Confirm({{ $product->id }})">
                                                            <i class="fa fa-trash fa-2x"></i>
                                                        </button>
                                                    @endif

                                                </div>

                                            </td>
                                        @endif

                                    </tr>
                                @empty

                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer p-1">
                    {{ $products->links() }}
                </div>
            </div>
        </div>


    </div>

    @push('my-scripts')
    @endpush

    <script>
        let editor4
        document.addEventListener('livewire:init', () => {

            Livewire.on('modalCatalogue', (event) => {
                $('#modalProduct').modal('show')
            })

            Livewire.on('update-quill-content', (event) => {

                var content = editor4.clipboard.convert(event.content)

                editor4.setContents(content)

            })

            Livewire.on('close-modal', (event) => {
                $('#modalProduct').modal('hide')
                // $('#supplier').val($('#supplier option:last').val());

                setTimeout(() => {

                }, 500);

            })


            //  Livewire.hook('morph.updated', ({ el, component }) => {
            //     prepareEditor()
            //  })



            prepareEditor()

        })

        function prepareEditor() {
            if (document.getElementById('toolbar2') != null && !editor4) {
                console.log('si');
                editor4 = new Quill("#editor2", {
                    modules: {
                        toolbar: "#toolbar2"
                    },
                    theme: "snow",
                    placeholder: "Ingresa la descripción completa del producto",
                })

                // escuchat evento 'text-change' del editor
                editor4.on('selection-change', function(range, oldRange, source) {
                    if (range === null && oldRange !== null) {
                        // Obtiene el contenido del editor
                        var content = editor4.root.innerHTML;

                        // Pasa el contenido a una propiedad de Livewire
                        Livewire.dispatch('quilContent', {
                            content: content
                        })
                        console.log('updateContent', content)
                    }
                });


            }
        }


        function validateInput(input) {
            // Expresión regular que coincide con números enteros positivos,
            // con hasta dos decimales y un solo punto decimal
            //var regex = /^\d+(\.\d{1,2})?$/
            var regex = /^\d{1,11}(\.\d{1,2})?$/;

            // Comprueba si la entrada del usuario coincide con la expresión regular
            if (!regex.test(input.value)) {
                input.value = ''
            }
        }

        // Selecciona todos los elementos input con la clase numerico
        var inputs = document.querySelectorAll('input.numerico')

        // Aplica la función de validación a cada input
        inputs.forEach(function(input) {
            input.addEventListener('input', function() {
                validateInput(input)
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

</div>
