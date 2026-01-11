<script>
    var tomselect
    var inputTom
    document.onkeydown = function(e) {

        // // f1 focus search
        // if (e.keyCode == '112') {
        //     e.preventDefault()
        //     document.getElementById('inputSearch').value = ''
        //     document.getElementById('inputSearch').focus()
        // }

        //f6 => precio iva
        // if (e.keyCode == '117') {
        //     e.preventDefault()
        //     document.getElementById('inputPrecioIva').value = ''
        //     document.getElementById('inputPrecioIva').focus()
        // }

        // //f2 => clientes
        // if (e.keyCode == '113') {
        //     e.preventDefault()
        //     $('#modalTempClient').modal('show')
        // }

        // // f3 => giro empresa
        // if (e.keyCode == '114') {
        //     e.preventDefault()
        //     $('#modalTempGiro').modal('show')
        // }

        // // F9 registrar venta (efectivo y tarjetas)
        // if (e.keyCode == '120') {
        //     e.preventDefault()
        //     $('#btnFormasPago').trigger('click')
        //     //cardVisible('cardSave','cardTotales')
        // }
        // // F10

        // if (e.keyCode == '121') {
        //     e.preventDefault()
        //     $('#btnSaveSale').trigger('click')
        // }

        // //alt +z
        // if (e.altKey && e.key === 'z') {
        //     inputTom = document.getElementById('inputCustomer');
        //     tomselect = input.tomselect
        //     tomselect.clear()
        //     tomselect.focus()
        // }


    }

    function updateQty(uid, option) {

        var qty = parseInt(document.getElementById('qty-' + uid).value)
        if (option == 'increment')
            qty += 1
        else
            qty -= 1

        if (qty < 1) {
            @this.removeItem(uid) // Note: removeItem expects ID, check if it handles UID or PID. Sales.php removeItem handles both.
        } else {
            @this.updateQty(uid, qty)
        }
    }

    document.addEventListener('livewire:init', function() {

        Livewire.on('initPay', event => {
            $(event.payType == 3 ? '#modalDeposit' : '#modalCash')
                .modal('show')
            // $(event.payType == 3 ? '#modalDeposit' : '#modalCash').modal('show')


            if (event.payType != 3) {
                setTimeout(() => {
                    @this.clearCashAmount()
                    document.getElementById('inputCash').value = null
                    document.getElementById('phoneNumber').value = null
                    document.getElementById('inputCash').focus()
                }, 700)
            }


        })


        Livewire.on('close-modalPay', event => {

            //var div = document.querySelector('.item')
            inputTom = document.getElementById('inputCustomer')
            tomselect = inputTom.tomselect
            tomselect.clear()
            //tomselect.focus()
            $('#' + event.element).modal('hide')
        })

        Livewire.on('close-modal-customer-create', event => {
            $('#modalCustomerCreate').modal('hide')
        })

        Livewire.on('refresh', event => {
            document.getElementById('inputSearch').value = ''
            document.getElementById('inputSearch').focus()
        })


        Livewire.on('clear-input-price', event => {

        })

        $('#modalCustomerCreate').on('shown.bs.modal', function() {
            setTimeout(() => {
                document.getElementById('inputcname').value = ''
                document.getElementById('inputctaxpayerId').value = ''
                document.getElementById('inputcemail').value = ''
                document.getElementById('inputcphone').value = ''
                document.getElementById('inputcaddress').value = ''
                document.getElementById('inputccity').value = ''
                document.getElementById('inputcname').focus()
            }, 200)
        })



        //buscar cualquier rut en sistema
        if (document.querySelector('#inputCustomer')) {
            new TomSelect('#inputCustomer', {
                maxItems: 1,
                valueField: 'id',
                labelField: 'name',
                searchField: ['name', 'address'],
                load: function(query, callback) {
                    var url = "{{ route('data.customers') }}" + '?q=' + encodeURIComponent(
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
                    var customer = this.options[value]
                    console.log('customer ' + value)
                    if (customer !== null && typeof customer !== 'undefined') {
                        Livewire.dispatch('sale_customer', {
                            customer: customer
                        })
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
                <\/div>
            <\/div>
        <\/div>`;
                    },
                },
            });
        }

        Livewire.on('close-process-order', event => {
            $('#modalProcessOrder').modal('hide')
        })

    }) // livewire init







    function validarInputNumber(input) {
        // Expresión regular para validar el formato del número (permite decimales intermedios)
        var regex = /^\d*\.?\d{0,2}$/;

        // Validar si el valor del input coincide con la expresión regular
        if (!regex.test(input.value)) {
            // Si el valor no coincide, deshabilitar la entrada del último caracter ingresado
            input.value = input.value.slice(0, -1);
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
            title: '¿CONFIRMAS CANCELAR LA VENTA?',
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
                    orderId: rowId
                })
            }
        });

    }

    function initPartialPay() {
        $('#modalPartialPayment').modal('show')
    }

    function processOrder() {
        $('#modalProcessOrder').modal('show')
    }

    function closePartialPayment() {
        $('#modalPartialPayment').modal('hide')
    }

    function showCustomerCreate() {
        $('#modalCustomerCreate').modal('show')
    }

    function closeCustomerCreate() {
        $('#modalCustomerCreate').modal('hide')
    }
</script>
