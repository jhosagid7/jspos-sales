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

    /* DARK MODE FIXES */
    body.dark-mode .card {
        background-color: #343a40 !important;
        color: #fff !important;
        border: 1px solid #6c757d;
    }
    body.dark-mode .card-header {
        border-bottom-color: #6c757d;
    }
    body.dark-mode .content-wrapper, 
    body.dark-mode .main-footer {
        background-color: #454d55 !important;
        color: #fff;
    }
    
    /* Mobile Table Dark Fix */
    body.dark-mode .table-mobile-cards tbody tr {
        background-color: #343a40 !important;
        border-color: #6c757d !important;
        color: #fff !important;
    }
    body.dark-mode .table-mobile-cards tbody td {
        color: #fff !important;
    }
    body.dark-mode .table-mobile-cards .badge {
        color: #fff;
    }

    /* TomSelect Dark Fix */
    body.dark-mode .ts-control, 
    body.dark-mode .ts-input {
        background-color: #343a40 !important;
        color: #fff !important;
        border-color: #6c757d !important;
    }
    body.dark-mode .ts-dropdown, 
    body.dark-mode .ts-dropdown .option {
        background-color: #343a40 !important;
        color: #fff !important;
    }
    body.dark-mode .ts-dropdown .option:hover,
    body.dark-mode .ts-dropdown .active {
        background-color: #3f474e !important;
    }

    /* SweetAlert Dark Fix */
    body.dark-mode .swal-modal {
        background-color: #343a40 !important;
        border: 1px solid #6c757d;
    }
    body.dark-mode .swal-title {
        color: #fff !important;
    }
    body.dark-mode .swal-text {
        background-color: transparent !important;
        color: #e4e4e4 !important;
        border: none !important;
    }
    body.dark-mode .swal-button {
        background-color: #007bff;
    }

    /* Inputs Dark Fix */
    body.dark-mode .form-control {
        background-color: #343a40 !important;
        color: #fff !important;
        border-color: #6c757d !important;
    }
    body.dark-mode .input-group-text {
        background-color: #3f474e !important;
        color: #fff !important;
        border-color: #6c757d !important;
    }

    /* Modal Dark Fix */
    body.dark-mode .modal-content {
        background-color: #343a40 !important;
        color: #fff !important;
    }
    body.dark-mode .modal-header,
    body.dark-mode .modal-footer {
        border-color: #6c757d !important;
    }
    body.dark-mode .close {
        color: #fff !important;
        text-shadow: none;
        opacity: 0.8;
    }
    
    /* Select2 / General Selects */
    body.dark-mode select.form-control {
        background-color: #343a40 !important;
        color: #fff !important;
    }
    
    /* Breadcrumb Fix */
    body.dark-mode .breadcrumb {
        background-color: transparent !important;
    }

    /* Navbar Dark Fix */
    body.dark-mode .main-header {
        background-color: #343a40 !important;
        border-color: #6c757d !important;
    }
    body.dark-mode .main-header .nav-link {
        color: rgba(255,255,255,0.8) !important;
    }
    body.dark-mode .main-header .nav-link:hover {
        color: #fff !important;
    }
    
    /* Table Dark Fix Globally */
    body.dark-mode .table {
        color: #fff !important;
        background-color: transparent !important;
    }
    body.dark-mode .table thead th {
        color: #fff !important;
        border-bottom-color: #6c757d !important;
    }
    body.dark-mode .table td,
    body.dark-mode .table th {
        border-color: #6c757d !important;
    }
    body.dark-mode .table-striped tbody tr:nth-of-type(odd) {
        background-color: rgba(255,255,255,.05) !important;
    }
    
    /* Input Placeholder & Focus */
    body.dark-mode .form-control::placeholder {
        color: #ced4da !important;
        opacity: 0.7;
    }
    body.dark-mode .form-control:focus {
        background-color: #3f474e !important;
        color: #fff !important;
        border-color: #80bdff !important;
    }

    /* Fix POS Buttons (Ordenes, Cancelar, etc) */
    body.dark-mode .btn.txt-dark {
        color: #fff !important;
        border-color: rgba(255,255,255,0.2) !important;
    }
    body.dark-mode .btn.txt-dark:hover {
        background-color: rgba(255,255,255,0.1) !important;
    }
    /* Generic Mobile Table Card View (Flex Logic) */
    @media (max-width: 768px) {
        .table-mobile-details thead {
            display: none;
        }
        .table-mobile-details tbody tr {
            display: block;
            margin-bottom: 1rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 10px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            position: relative;
        }
        .table-mobile-details td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border: none;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
            text-align: right;
            width: 100% !important;
        }
        .table-mobile-details td:last-child {
            border-bottom: none;
            justify-content: flex-end;
            margin-top: 5px;
        }
        .table-mobile-details td::before {
            content: attr(data-label);
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.85rem;
            color: #777;
            text-align: left;
            flex: 0 0 40%;
            margin-right: 10px;
        }
        
        /* Dark Mode Support for table-mobile-details */
        body.dark-mode .table-mobile-details tbody tr {
            background-color: #343a40 !important;
            border-color: #6c757d !important;
        }
        body.dark-mode .table-mobile-details td {
             border-bottom-color: #6c757d !important;
             color: #fff !important;
        }
        body.dark-mode .table-mobile-details td::before {
            color: #ccc !important;
        }
    }
</style>
