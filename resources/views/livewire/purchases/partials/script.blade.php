@push('my-scripts')
<script>
    var inputSup
    var tomselect
    function resetProductSearch(){
            const el = document.getElementById('inputSearchProduct')
            var tomSel = el.tomselect
            tomSel.clear()
            tomSel.focus()  
    }
    function resetSupplierSearch(){
            const input = document.getElementById('inputSupplier')
            var tomselect = input.tomselect
            tomselect.clear()
            tomselect.focus()   
    }

    document.onkeydown = function(e) {   

         // f1 proveedor search
         if (e.keyCode == '112') { 
            e.preventDefault()
            resetSupplierSearch()
        }

         //f2 => buscador
         if (e.keyCode == '113') { 
            e.preventDefault()
            resetProductSearch() 
        }

        //f6 => precio iva
        if (e.keyCode == '117') {     
            e.preventDefault()
            document.getElementById('inputPrecioIva').value =''
            document.getElementById('inputPrecioIva').focus()
        }

       

       // f3 => giro empresa
        if (e.keyCode == '114') {
            e.preventDefault()
           $('#modalTempGiro').modal('show')
        }

        // F9 registrar venta (efectivo y tarjetas)
        if (e.keyCode == '120') { 
            e.preventDefault()
            $('#btnFormasPago').trigger('click')
            //cardVisible('cardSave','cardTotales')
        }
        // F10
        
        if (e.keyCode == '121') {
            e.preventDefault()
            $('#btnSaveSale').trigger('click')
        }

        //alt +z
        if (e.altKey && e.key === 'z') { 
            inputSup = document.getElementById('inputSupplier')
            tomselect = input.tomselect
            tomselect.clear()
            tomselect.focus()
        }

        
      }

function updateQty(product_id,uid, option) {

    var qty = parseInt(document.getElementById('p'+ product_id).value)
    if(option == 'increment')
     qty +=1
    else
    qty -=1

    if(qty <1) {
        @this.removeItem(product_id)
    } else {
        @this.updateQty(uid, qty)
    }
}

document.addEventListener('livewire:init', function() {

Livewire.on('initPay', event => {   
                $('#modalPayConfirm').modal('show')
               
                setTimeout(() => {
                    document.getElementById('inputNote').value = ''
                    document.getElementById('inputNote').focus()
                }, 700)
            
            
})


Livewire.on('reset-tom', event => {

    inputSup = document.getElementById('inputSupplier')
    tomselect = inputSup.tomselect
    tomselect.clear()

})

Livewire.on('close-modal-customer-create', event => {
    $('#modalCustomerCreate').modal('hide')
})

Livewire.on('focus-search', event => {    
   setTimeout(() => {
    resetProductSearch() 
   }, 800)
})

Livewire.on('reverse', event => {
    var input = document.querySelector('#tblProducts #p'+ event.id)
    if (input) {
        console.log(input);
        input.value = input.getAttribute('data-qty')
    }
})


$('#modalCustomerCreate').on('shown.bs.modal', function () {
                setTimeout(() => {
                    document.getElementById('inputcname').value = ''
                    document.getElementById('inputcname').focus()
                }, 700)
})

Livewire.on('close-modal', event => {
    $('#modalPayConfirm').modal('hide')
    document.getElementById('inputFlete').value =''
})

Livewire.on('show-modal', event => {
    $('#' + event).modal('show')
})

Livewire.on('hide-modal', event => {
    $('#' + event).modal('hide')
})



  //buscar cualquier rut en sistema
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
                            if (supplier !== null && typeof supplier !== 'undefined') {
                             Livewire.dispatch('purchase_supplier', {supplier: supplier})
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
  
  
   


})




           

           
    function validarInputNumber(input) {
            // expresión regular para validar el formato del número
            var regex = /^\d+(\.\d{1,2})?$/;
    
            // Validar si el valor del input coincide con la expresión regular
            if (!regex.test(input.value)) {        
                input.value = ''
            }
    }
    function justNumber(input) {
    // Expresión regular para validar el formato del número
    var regex = /^\d*\.?\d{0,2}$/;
    
    // Validar si el valor del input coincide con la expresión regular
    if (!regex.test(input.value)) {
        // Si el valor no coincide, deshabilitar la entrada del último caracter ingresado
        input.value = input.value.slice(0, -1);
    }
}



    function cancelSale() {
        swal({
        title: '¿CONFIRMAS CANCELAR LA COMPRA?',
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
      }).then((willCancel) => {
        if (willCancel) {
            Livewire.dispatch('cancelSale')
        }
      });
    }


function initPartialPay() {
    $('#modalPartialPayment').modal('show')
}

function processOrder() {
    $('#modalProcessOrder').modal('show')
}

function DestroyOrder(rowId) {
    swal({
        title: '¿CONFIRMAS ELIMINAR LA ORDEN?',
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
            Livewire.dispatch('DestroyOrder', {
                purchaseId: rowId
            })
        }
    });
}

</script>
@endpush