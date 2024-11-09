<!DOCTYPE html>
<html lang="es">

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="Melvin POS sistema de ventas" content="Sistema de ventas">
    <meta name="keywords" content="ventas, compras, inventarios, reportes">
    <meta name="author" content="luisfaxacademy.com">
    <link rel="icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">
    <link rel="shortcut icon" href="{{ asset('assets/images/favicon.ico') }}" type="image/x-icon">
    <title>JSPOS v1.7</title>

    @include('layouts.theme.styles')

    @stack('my-styles')

</head>

{{-- class="dark-only" --}}

<body>
    <!-- loader starts-->
    <div class="loader-wrapper">
        <div class="loader-index"> <span></span></div>
        <svg>
            <defs></defs>
            <filter id="goo">
                <fegaussianblur in="SourceGraphic" stddeviation="11" result="blur"></fegaussianblur>
                <fecolormatrix in="blur" values="1 0 0 0 0  0 1 0 0 0  0 0 1 0 0  0 0 0 19 -9" result="goo">
                </fecolormatrix>
            </filter>
        </svg>
    </div>
    <!-- loader ends-->
    <!-- tap on top starts-->
    <div class="tap-top"><i data-feather="chevrons-up"></i></div>
    <!-- tap on tap ends-->
    <!-- page-wrapper Start-->
    <div class="page-wrapper horizontal-wrapper" id="pageWrapper">
        <!-- Page Header Start-->
        @include('layouts.theme.header')
        <!-- Page Header Ends                              -->
        <!-- Page Body Start-->
        <div class="page-body-wrapper horizontal-menu">
            <!-- Page Sidebar Start-->
            @include('layouts.theme.sidebar')
            <!-- Page Sidebar Ends-->
            <div class="page-body">

                @include('layouts.theme.breadcrumb')

                <!-- Container-fluid starts-->
                <div class="container-fluid">
                    {{ $slot }}
                    {{-- <div class="row starter-main">

                    </div> --}}
                    <!-- Container-fluid Ends-->
                </div>

                <!-- footer start-->
                @include('layouts.theme.footer')

            </div>
        </div>
    </div>

    <!-- scripts -->
    @include('layouts.theme.scripts')

    {{-- Custom scripts --}}
    @stack('my-scripts')


</body>

</html>
