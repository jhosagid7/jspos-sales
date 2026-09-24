<!-- jQuery -->
<script src="{{ asset('assets/js/jquery.min.js') }}"></script>
<!-- Bootstrap 4 -->
<script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
<!-- AdminLTE App -->
<script src="{{ asset('assets/js/adminlte.min.js') }}"></script>

<script>
    // Robust, fail-safe Sidebar Treeview Handler
    (function($) {
        if (!$) return;

        function bindSidebarTreeview() {
            // Unbind native AdminLTE unnamespaced listeners to prevent competing / double-toggle deadlocks
            $(document).off('click', '[data-widget="treeview"] .nav-link');
            $(window).off('load.lte.treeview');

            // Attach clean, single namespaced listener for all treeview toggle items
            $(document).off('click.appTreeview', '.nav-sidebar .nav-item > .nav-link')
                       .on('click.appTreeview', '.nav-sidebar .nav-item > .nav-link', function(e) {
                var $link = $(this);
                var $treeview = $link.next('.nav-treeview');

                // If this item has a submenu, toggle it
                if ($treeview.length > 0) {
                    e.preventDefault();
                    e.stopPropagation();

                    var $parent = $link.parent('.nav-item');
                    var isOpen = $parent.hasClass('menu-open');

                    if (isOpen) {
                        $parent.removeClass('menu-open menu-is-opening');
                        $treeview.stop(true, true).slideUp(200);
                    } else {
                        // Check if accordion mode is enabled
                        var $sidebar = $link.closest('.nav-sidebar');
                        if ($sidebar.attr('data-accordion') === 'true') {
                            var $siblings = $parent.siblings('.menu-open');
                            $siblings.removeClass('menu-open menu-is-opening');
                            $siblings.children('.nav-treeview').stop(true, true).slideUp(200);
                        }

                        $parent.addClass('menu-is-opening');
                        $treeview.stop(true, true).slideDown(200, function() {
                            $parent.addClass('menu-open').removeClass('menu-is-opening');
                        });
                    }
                }
            });
        }

        // Initialize immediately, on DOM ready, and on Livewire page transitions
        bindSidebarTreeview();
        $(document).ready(bindSidebarTreeview);
        document.addEventListener('livewire:init', bindSidebarTreeview);
        document.addEventListener('livewire:navigated', bindSidebarTreeview);
    })(window.jQuery);
</script>

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
                console.warn('Error: Variable Modal not found in DOM for show-variable-modal event');
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
        
        Livewire.on('open-pdf-tab', data => {
            let url = data.url || (Array.isArray(data) ? data[0].url : data);
            if (url) {
                window.open(url, '_blank');
            }
        });

        window.notyListenerAdded = true;
    }

    // Fix for "Session Expired" modal during rapid typing (419 errors)
    // Intercepts the status and prevents the annoying popup if it's a transient token error
    Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                console.warn('Livewire: Token mismatch (419) detected during rapid input. Silencing modal.');
                preventDefault(); 
                // Optional: If we want to force a refresh on 419, uncomment below:
                // window.location.reload();
            }
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


    // Auto-zoom for laptop screens (between 992px and 1400px)
    // Helps the POS sales table fit properly without squishing
    function applyAutoZoom() {
        if (window.innerWidth <= 1400 && window.innerWidth >= 992) {
            document.body.style.zoom = "0.9";
        } else {
            document.body.style.zoom = "1";
        }
    }
    
    // Apply on load and listen to resizes
    window.addEventListener('resize', applyAutoZoom);
    applyAutoZoom();



  })

</script>

<script src="{{ asset('assets/js/demo.js?v=2.1') }}"></script>