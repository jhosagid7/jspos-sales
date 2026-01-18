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
<link rel="stylesheet" type="text/css" href="{{ asset('assets/css/vendors/icofont.css') }}">

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

    /* Custom Scrollbar */
    ::-webkit-scrollbar {
        width: 14px; /* Slightly wider */
        height: 14px;
    }
    ::-webkit-scrollbar-track {
        background: #e0e0e0; /* Darker track */
        border-left: 1px solid #dcdcdc;
    }
    ::-webkit-scrollbar-thumb {
        background: #555; /* Much darker thumb */
        border-radius: 0; /* Square for more visibility or keep rounded */
        border: 2px solid #e0e0e0; /* Creates padding effect */
    }
    ::-webkit-scrollbar-thumb:hover {
        background: #333; /* Almost black on hover */
    }

    /* Mobile POS Optimization */
    @media (max-width: 768px) {
        /* Hide Table Header */
        .table-mobile-cards thead {
            display: none;
        }

        /* Make Rows look like Cards */
        .table-mobile-cards tbody tr {
            display: block;
            margin-bottom: 15px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            padding: 10px;
            position: relative;
        }

        /* Make Cells block or flex */
        .table-mobile-cards tbody td {
            display: block;
            border: none;
            padding: 5px 0;
            text-align: left;
            width: 100% !important; /* Override width attributes */
        }

        /* Specific Column Styling */
        
        /* Code & Name (Row 1) */
        .table-mobile-cards tbody td:nth-child(1) { /* Code */
            display: inline-block;
            width: auto !important;
            font-size: 0.85rem;
        }
        .table-mobile-cards tbody td:nth-child(2) { /* Name */
            font-size: 1.1rem;
            font-weight: bold;
            margin-bottom: 5px;
            color: #333;
        }

        /* Price & Qty (Row 2 - Flex) */
        .table-mobile-cards tbody td:nth-child(3), /* Price */
        .table-mobile-cards tbody td:nth-child(4) { /* Qty */
            display: inline-block;
            width: 48% !important;
            vertical-align: middle;
        }

        /* Total & Actions (Row 3 - Flex) */
        .table-mobile-cards tbody td:nth-child(5) { /* Total */
            display: inline-block;
            width: auto !important;
            font-size: 1.2rem;
            font-weight: bold;
            color: #444;
            margin-top: 10px;
        }
        
        .table-mobile-cards tbody td:nth-child(6) { /* Actions */
            position: absolute;
            top: 10px;
            right: 10px;
            width: auto !important;
            padding: 0;
        }

        /* Input sizing adjustments */
        .input-group-sm > .form-control, 
        .input-group-sm > .input-group-prepend > .btn, 
        .input-group-sm > .input-group-append > .btn {
            height: calc(2.2em + 2px); /* Taller inputs for touch */
            font-size: 1rem;
        }
        
        /* Sticky Summary */
        .customer-sticky {
            position: static; /* Reset sticky on mobile if needed, or keep it */
        }
    }
</style>
