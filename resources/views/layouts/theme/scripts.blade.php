<!-- jQuery -->
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('assets/js/adminlte.min.js') }}"></script>

<!-- Plugins -->
<script src="{{ asset('assets/js/sweet-alert/sweetalert.min.js') }}"></script>
{{-- <script src="{{ asset('assets/js/theme-customizer/customizer.js') }}"></script> --}}
<script src="{{ asset('assets/js/editors/quill.js') }}"></script>
<script src="{{ asset('assets/js/toastify.js') }}"></script>
<script src="{{ asset('assets/js/tom.js') }}"></script>
<script src="{{ asset('assets/js/tooltip-init.js') }}"></script>

<script src="{{ asset('assets/js/flat-pickr/flatpickr.js') }}"></script>
{{-- <script src="{{ asset('assets/js/flat-pickr/custom-flatpickr.js') }}"></script> --}}
<script src="{{ asset('assets/js/flat-pickr/es.js') }}"></script>
<script src="https://code.highcharts.com/highcharts.js"></script>


<script>
  //custom

  document.addEventListener('livewire:init', () => {   



    flatpickr(".flatpicker", {
        dateFormat: "d/m/Y",
        locale: "es",
        theme: "confetti" 
    })

    window.addEventListener('update-header', event => {
        const map = document.getElementById('header-map')
        const child = document.getElementById('header-child')
        const rest = document.getElementById('header-rest')

        if(map) map.innerText = event.detail.map
        if(child) child.innerText = event.detail.child
        if(rest) rest.innerText = event.detail.rest
    })

    
    if (!window.notyListenerAdded) {
        // Listener for window events (legacy/manual dispatch)
        window.addEventListener('noty', event => {   
            Toastify({
                text:  event.detail.msg,
                duration: 4000,
                gravity: 'bottom',
                style: {
                    background: "linear-gradient(to right,  #d35400,  #34495e )",
                },
            }).showToast();
        })
        
        // Listener for Livewire v3 dispatch
        // Listener for Livewire v3 dispatch (Commented out to avoid double notification with window listener)
        /*
        Livewire.on('noty', data => {   
            // Handle both object with msg property or direct string (fallback)
            let msg = data.msg || data; 
            let type = data.type || 'info';
            
            let bg = "linear-gradient(to right,  #d35400,  #34495e )"; // Default / Info
            if(type === 'success') bg = "linear-gradient(to right, #00b09b, #96c93d)";
            if(type === 'error') bg = "linear-gradient(to right, #ff5f6d, #ffc371)";
            if(type === 'warning') bg = "linear-gradient(to right, #f85032, #e73827)";

            Toastify({
                text:  msg,
                duration: 4000,
                gravity: 'bottom',
                style: {
                    background: bg,
                },
            }).showToast();
        })
        */

        // Variable Modal Listeners
        Livewire.on('show-variable-modal', () => {
            console.log('Event received: show-variable-modal');
            var modal = $('#variableItemModal');
            console.log('Modal found:', modal.length);
            
            if(modal.length > 0) {
                modal.modal('show');
            } else {
                alert('Error: Modal not found in DOM');
            }
        });

        Livewire.on('close-variable-modal', () => {
            $('#variableItemModal').modal('hide');
        });

        Livewire.on('noty2', data => {
            let msg = data.msg || data;
            swal({
                title:'Info',
                text: msg,
                icon: 'success',
                buttons: {
                    confirm: {
                        text: "OK",
                        value: true,
                        visible: true,
                        className: "btn btn-primary",
                        closeModal: true
                    }
                },
                timer: 5000
            })
        })
        
        window.notyListenerAdded = true;
    }

    // window.addEventListener('error', event => {   
    //   swal({
    //     title: "oops",
    //     text: event.detail.msg,
    //     icon: "error",
    //     buttons: {
    //       cancel: {
    //         text: "Cerrar",
    //         value: null,
    //         visible: true,
    //         closeModal: true
    //       }
    //     },
    //     timer: 5000
    //   });
      
    // })


    function Confirm(componentName, rowId) {          
      Swal.fire({
      title: '¿CONFIRMAS ELIMINAR EL REGISTRO?',
      text: "",
      showCancelButton: true,
      confirmButtonColor: '#3085d6',
      cancelButtonColor: '#d33',
      confirmButtonText: 'Aceptar'
      }).then((result) => {
      if (result.value) {    
          showProcessing()
          window.livewire.emitTo(componentName, 'Destroy', rowId)
      }
      })
    }


  })



</script>

<script src="{{ asset('assets/js/demo.js') }}"></script>