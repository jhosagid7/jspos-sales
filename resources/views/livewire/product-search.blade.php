<div>
    <div>
        Agregar productos
        <div class="faq-form">
            <input class="form-control form-control-lg" type="text" placeholder="Ingresa el Código o Descripción [F2]"
                id="inputSearchProduct">
            <i class="search-icon" data-feather="search"></i>
        </div>
    </div>

</div>

@push('my-scripts')
    <script>
        document.addEventListener('livewire:init', function() {
        if (document.querySelector('#inputSearchProduct')) {
                    new TomSelect('#inputSearchProduct', {
                        maxItems: 1,
                        valueField: 'id',
                        labelField: 'name',
                        searchField: ['name', 'sku'],
                        load: function(query, callback) {
                            var url = "{{ route('data.products') }}" + '?q=' + encodeURIComponent(
                                query)
                            fetch(url)
                                .then(response => response.json())
                                .then(json => {                                    
                                    callback(json)                                    
                                }).catch(() => {
                                    callback();
                                });
                        },
                        onChange: function(value) {                                                         
                            var product = this.options[value]  
                            if(product != null){
                                Livewire.dispatch('purchase_product', {product: value})
                            }
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

Livewire.on('focus-cost', event => {  
    console.log(event.element)
    setTimeout(() => {

        //var input = document.querySelector('#tblProducts #c'+ event.element)
        var input = document.querySelector('#tblProducts #c'+ event.uid)

    // focus
    if (input) {
        input.focus()
        input.select()
    }     
    }, 1000)
})
})

    </script>
@endpush