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
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>JSPOS v1.7</title>

    @include('layouts.theme.styles')

    @stack('my-styles')

</head>

{{-- class="dark-only" --}}
@php
    $theme = auth()->user()->theme ?? [];
    $bodyClasses = [];
    if(!empty($theme['dark_mode']) && filter_var($theme['dark_mode'], FILTER_VALIDATE_BOOLEAN)) $bodyClasses[] = 'dark-mode';
    // Force Fixed Layout and Fixed Navbar by default unless explicitly disabled
    if(!isset($theme['layout_navbar_fixed'])) {
        $bodyClasses[] = 'layout-navbar-fixed';
    } elseif(filter_var($theme['layout_navbar_fixed'], FILTER_VALIDATE_BOOLEAN)) {
        $bodyClasses[] = 'layout-navbar-fixed';
    }

    if(!empty($theme['sidebar_collapse']) && filter_var($theme['sidebar_collapse'], FILTER_VALIDATE_BOOLEAN)) $bodyClasses[] = 'sidebar-collapse';

    // Force Fixed Sidebar by default unless explicitly disabled
    if(!isset($theme['layout_fixed'])) {
        $bodyClasses[] = 'layout-fixed';
    } elseif(filter_var($theme['layout_fixed'], FILTER_VALIDATE_BOOLEAN)) {
        $bodyClasses[] = 'layout-fixed';
    }

    if(!empty($theme['sidebar_mini']) && filter_var($theme['sidebar_mini'], FILTER_VALIDATE_BOOLEAN)) $bodyClasses[] = 'sidebar-mini';
    if(!empty($theme['sidebar_mini_md']) && filter_var($theme['sidebar_mini_md'], FILTER_VALIDATE_BOOLEAN)) $bodyClasses[] = 'sidebar-mini-md';
    if(!empty($theme['sidebar_mini_xs']) && filter_var($theme['sidebar_mini_xs'], FILTER_VALIDATE_BOOLEAN)) $bodyClasses[] = 'sidebar-mini-xs';
    if(!empty($theme['layout_footer_fixed']) && filter_var($theme['layout_footer_fixed'], FILTER_VALIDATE_BOOLEAN)) $bodyClasses[] = 'layout-footer-fixed';
    if(!empty($theme['body_text_sm']) && filter_var($theme['body_text_sm'], FILTER_VALIDATE_BOOLEAN)) $bodyClasses[] = 'text-sm';
    
    // Default classes to ensure basic layout works if nothing stored
    if(empty($bodyClasses)) {
        $bodyClasses[] = 'sidebar-mini';
        $bodyClasses[] = 'sidebar-collapse';
        $bodyClasses[] = 'layout-fixed';
        $bodyClasses[] = 'layout-navbar-fixed';
    }
    
    $bodyClassString = implode(' ', array_unique($bodyClasses));
@endphp

<body class="{{ $bodyClassString }}">
    <div class="wrapper">
        <!-- Preloader -->
        <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="{{ asset('assets/login/img/favicon-card.ico') }}" alt="SystemLogo" height="60" width="60">
        </div>

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
                    @if(isset($slot))
                        {{ $slot }}
                    @else
                        @yield('content')
                    @endif
                </div>
            </section>
            <!-- /.content -->
        </div>
        <!-- /.content-wrapper -->

        <!-- Control Sidebar -->
        <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
        </aside>
        <!-- /.control-sidebar -->

        <!-- Footer -->
        @include('layouts.theme.footer')
    </div>


    <!-- scripts -->
    @include('layouts.theme.scripts')

    {{-- Custom scripts --}}
    @stack('my-scripts')


</body>

</html>
