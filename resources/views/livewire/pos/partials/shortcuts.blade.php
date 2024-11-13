<script>
    document.addEventListener('livewire:init', function() {
        var listener = new window.keypress.Listener();

        listener.simple_combo("f1", function() {
            console.log('f1')
            document.getElementById('inputSearch').value = ''
            document.getElementById('inputSearch').focus()
        })

        listener.simple_combo("f2", function() {
            console.log("You pressed f2");
            this.processOrder();
            Livewire.dispatch('close-process-order');
        });

        listener.simple_combo("f3", function() {
            console.log("You pressed f3");
            this.cancelSale();
        });

        listener.simple_combo("f4", function() {
            console.log("You pressed f4");
            this.initPartialPay();
            this.closePartialPayment();

        });

        listener.simple_combo("f5", function() {
            console.log("You pressed f5");
            Livewire.dispatch('printLast');
        });

        listener.simple_combo("shift q", function() {
            console.log("You pressed shift q");
            document.getElementById('inputCustomer-ts-control').value = ''
            document.getElementById('inputCustomer-ts-control').focus()
        });








        listener.simple_combo("shift g", function() {
            console.log("You pressed alt and z");
            Livewire.dispatch('storeOrder');

        });
        listener.simple_combo("shift d", function() {
            console.log("You pressed alt and z");
            this.showCustomerCreate();
            this.closeCustomerCreate();

        });






        // listener.simple_combo("f7", function() {
        //     console.log('print last : f10')
        //     livewire.emit('print-last')
        // })

        // listener.simple_combo("f4", function() {
        //     var total = parseFloat(document.getElementById('hiddenTotal').value)
        //     if (total > 0) {
        //         Confirm(0, 'clearCart', '¿SEGUR@ DE ELIMINAR EL CARRITO?')
        //     } else {
        //         noty('AGREGA PRODUCTOS A LA VENTA')
        //     }
        // })
    });
</script>
