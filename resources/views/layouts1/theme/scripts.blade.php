<!-- latest jquery-->
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<!-- Bootstrap js-->
<script src="{{ asset('assets/js/bootstrap/bootstrap.bundle.min.js') }}"></script>
<!-- feather icon js-->
<script src="{{ asset('assets/js/icons/feather-icon/feather.min.js') }}"></script>
<script src="{{ asset('assets/js/icons/feather-icon/feather-icon.js') }}"></script>
<!-- scrollbar js-->
<script src="{{ asset('assets/js/scrollbar/simplebar.js') }}"></script>
<script src="{{ asset('assets/js/scrollbar/custom.js') }}"></script>
<!-- Sidebar jquery-->
<script src="{{ asset('assets/js/config.js') }}"></script>
<!-- Plugins JS start-->
<script src="{{ asset('assets/js/sidebar-menu.js') }}"></script>
<script src="{{ asset('assets/js/sidebar-pin.js') }}"></script>
<script src="{{ asset('assets/js/slick/slick.min.js') }}"></script>
<script src="{{ asset('assets/js/slick/slick.js') }}"></script>
<script src="{{ asset('assets/js/header-slick.js') }}"></script>
<script src="{{ asset('assets/js/prism/prism.min.js') }}"></script>
<script src="{{ asset('assets/js/clipboard/clipboard.min.js') }}"></script>
<script src="{{ asset('assets/js/custom-card/custom-card.js') }}"></script>
<script src="{{ asset('assets/js/typeahead/handlebars.js') }}"></script>
<script src="{{ asset('assets/js/typeahead/typeahead.bundle.js') }}"></script>
<script src="{{ asset('assets/js/typeahead/typeahead.custom.js') }}"></script>
<script src="{{ asset('assets/js/typeahead-search/handlebars.js') }}"></script>
<script src="{{ asset('assets/js/typeahead-search/typeahead-custom.js') }}"></script>
<script src="{{ asset('assets/js/sweet-alert/sweetalert.min.js') }}"></script>
<!-- Plugins JS Ends-->
<!-- Theme js-->
<script src="{{ asset('assets/js/script.js') }}"></script>
<script src="{{ asset('assets/js/theme-customizer/customizer.js') }}"></script>
<script src="{{ asset('assets/js/editors/quill.js') }}"></script>
<script src="{{ asset('assets/js/toastify.js') }}"></script>
<script src="{{ asset('assets/js/tom.js') }}"></script>
<script src="{{ asset('assets/js/tooltip-init.js') }}"></script>

<script src="{{ asset('assets/js/flat-pickr/flatpickr.js') }}"></script>
{{-- <script src="{{ asset('assets/js/flat-pickr/custom-flatpickr.js') }}"></script> --}}
<script src="{{ asset('assets/js/flat-pickr/es.js') }}"></script>


<script>
  //custom

  document.addEventListener('livewire:init', () => {   

    flatpickr(".flatpicker", {
        dateFormat: "d/m/Y",
        locale: "es",
        theme: "confetti" 
    })

    
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


    window.addEventListener('noty2', event => {   
      swal({
        title:'info',
      text: event.detail.msg,
      buttons: {
        cancel: false,
        confirm: {
          text: "OK",
          value: true,
          visible: true,
          closeModal: true
        }
      },
      timer: 5000
    })
    })

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