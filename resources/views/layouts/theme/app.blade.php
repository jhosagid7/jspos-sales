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
    <div class="wrapper">
        <!-- Navbar -->
        @include('layouts.theme.header')
        <!-- /.navbar -->

        <!-- Main Sidebar Container -->
        @include('layouts.theme.sidebar')

        <!-- Content Wrapper. Contains page content -->
        <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            @include('layouts.theme.breadcrumb')
            
            <!-- Main content -->
            <section class="content">
                <div class="container-fluid">
                    {{ $slot }}
                </div>
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <!-- Footer -->
        @include('layouts.theme.footer')
    </div>

    <!-- scripts -->
    @include('layouts.theme.scripts')

    {{-- Custom scripts --}}
    @stack('my-scripts')


</body>

</html>
