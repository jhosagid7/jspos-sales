<!-- Google Font: Source Sans Pro -->
<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
<!-- Font Awesome -->
<link rel="stylesheet" href="{{ asset('assets/css/all.min.css') }}">
<!-- AdminLTE -->
<link rel="stylesheet" href="{{ asset('assets/css/adminlte.min.css') }}">

<!-- Plugins -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/sweetalert2.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/toastify.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/tom.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/flatpickr/flatpickr.min.css') }}">
<link rel="stylesheet" type="text/css" href="{{ asset('assets/js/flat-pickr/confetti.css') }}">

<!-- Custom Responsive CSS for Sales View -->
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/custom-responsive.css') }}?v={{ time() }}">

<style>
    .customizer-links {
        display: none !important;
    }

    /*
    .ts-control {
        padding: 0px !important;
        border-style: none;
        border-width: 0px !important;
    } */

    select.crypto-select {
        position: absolute;
        top: 50%;
        transform: translateY(-50%);
        font-size: 12px;
        right: 2px;
        width: auto;
        background-position: right 0.25rem center;
        padding: 8px;
        border: none;
        font-weight: 500;
        background-size: 8px;
    }

    .balance-card {
        padding: 10px !important;
    }

    .rest {
        display: none !important;
    }

    .text-purple {
        color: purple
    }

    .logo-wrapper {
        display: flex;
        align-items: center;
        /* Centra verticalmente */
    }

    .logo-wrapper img {
        margin-right: 0px;
        /* Espacio entre la imagen y el texto */
    }

    .logo-wrapper b {
        white-space: nowrap;
        /* Evita que el texto se divida en varias líneas */
        overflow: hidden;
        /* Oculta el texto que se desborda */
        text-overflow: ellipsis;
        /* Muestra "..." si el texto es demasiado largo */
    }
</style>
