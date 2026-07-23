@php
    $posTitle = session('pos') ?: 'Panel de Control';
    $posIcon = 'fas fa-layer-group';
    $posIconColor = '#007bff';
    $darkPosIconColor = '#3f6791';

    $lowerTitle = strtolower($posTitle);

    if (str_contains($lowerTitle, 'venta') || str_contains($lowerTitle, 'caja') || str_contains($lowerTitle, 'cobro')) {
        $posIcon = 'fas fa-shopping-cart';
        $posIconColor = '#28a745'; // Green
        $darkPosIconColor = '#49c464';
    } elseif (str_contains($lowerTitle, 'producci') || str_contains($lowerTitle, 'fábrica') || str_contains($lowerTitle, 'fabrica') || str_contains($lowerTitle, 'soplado')) {
        $posIcon = 'fas fa-industry';
        $posIconColor = '#dc3545'; // Red
        $darkPosIconColor = '#e4606d';
    } elseif (str_contains($lowerTitle, 'product') || str_contains($lowerTitle, 'inventario') || str_contains($lowerTitle, 'categor') || str_contains($lowerTitle, 'compra') || str_contains($lowerTitle, 'proveedor')) {
        $posIcon = 'fas fa-box-open';
        $posIconColor = '#17a2b8'; // Cyan
        $darkPosIconColor = '#3abaf4';
    } elseif (str_contains($lowerTitle, 'cliente') || str_contains($lowerTitle, 'usuario') || str_contains($lowerTitle, 'rol') || str_contains($lowerTitle, 'operador')) {
        $posIcon = 'fas fa-users';
        $posIconColor = '#fd7e14'; // Orange
        $darkPosIconColor = '#ff9f4a';
    } elseif (str_contains($lowerTitle, 'finanza') || str_contains($lowerTitle, 'auditor')) {
        $posIcon = 'fas fa-file-invoice-dollar';
        $posIconColor = '#28a745'; // Green
        $darkPosIconColor = '#49c464';
    } elseif (str_contains($lowerTitle, 'reporte') || str_contains($lowerTitle, 'análisis') || str_contains($lowerTitle, 'analisis') || str_contains($lowerTitle, 'desempeño')) {
        $posIcon = 'fas fa-chart-line';
        $posIconColor = '#6f42c1'; // Purple
        $darkPosIconColor = '#b18cf8';
    } elseif (str_contains($lowerTitle, 'configuraci') || str_contains($lowerTitle, 'ajuste') || str_contains($lowerTitle, 'setting')) {
        $posIcon = 'fas fa-cogs';
        $posIconColor = '#6c757d'; // Gray
        $darkPosIconColor = '#adb5bd';
    }
@endphp

<style>
    .custom-page-title {
        background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        padding: 15px 20px;
        margin-bottom: 20px;
        border-left: 5px solid #007bff;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    /* Adaptación al Modo Oscuro (dark-mode) de AdminLTE */
    .dark-mode .custom-page-title {
        background: linear-gradient(135deg, #343a40 0%, #2b3035 100%);
        box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        border-left: 5px solid #3f6791;
    }
    
    .custom-page-title h4 {
        margin: 0;
        font-weight: 700;
        color: #343a40;
        font-size: 1.25rem;
        letter-spacing: 0.3px;
        display: flex;
        align-items: center;
    }
    
    .dark-mode .custom-page-title h4 {
        color: #f8f9fa;
    }
    
    .custom-page-title h4 i {
        color: var(--icon-color, #007bff);
        margin-right: 12px;
        font-size: 1.4rem;
    }
    
    .dark-mode .custom-page-title h4 i {
        color: var(--icon-color-dark, #3f6791);
    }
    
    .header-info-chip {
        font-size: 13px;
        font-weight: 600;
        padding: 6px 12px;
        border-radius: 50rem; /* Pill shape */
        display: inline-flex;
        align-items: center;
        background-color: #f8f9fa;
        border: 1px solid #e9ecef;
        color: #495057;
    }
    
    .dark-mode .header-info-chip {
        background-color: #212529;
        border-color: #495057;
        color: #ced4da;
    }
    
    .header-info-chip.chip-primary {
        background-color: rgba(0, 123, 255, 0.1);
        border-color: rgba(0, 123, 255, 0.2);
        color: #007bff;
    }
    
    .dark-mode .header-info-chip.chip-primary {
        background-color: rgba(63, 103, 145, 0.2);
        color: #66a0d6;
        border-color: rgba(63, 103, 145, 0.4);
    }
    
    .header-info-chip.chip-success {
        background-color: rgba(40, 167, 69, 0.1);
        border-color: rgba(40, 167, 69, 0.2);
        color: #28a745;
    }
    
    .dark-mode .header-info-chip.chip-success {
        background-color: rgba(40, 167, 69, 0.15);
        color: #49c464;
    }
</style>

<div class="container-fluid mt-3">
    <div class="custom-page-title" style="--icon-color: {{ $posIconColor }}; --icon-color-dark: {{ $darkPosIconColor }};">
        <div>
            <h4>
                <i class="{{ $posIcon }}"></i> 
                {{ $posTitle }}
            </h4>
        </div>
        
        <div class="d-none d-md-flex align-items-center" style="gap: 12px;">
            <a href="{{ route('sales') }}" class="text-secondary" style="font-size: 1.2rem; transition: color 0.2s;"><i class="fas fa-home"></i></a>
            
            @if(session('map'))
                <span class="header-info-chip" id="header-map">{{ session('map') }}</span>
            @endif
            
            @if(session('child'))
                <span class="header-info-chip chip-primary" id="header-child">{{ session('child') }}</span>
            @endif
            
            @if(session('rest'))
                <span class="header-info-chip chip-success" id="header-rest">{{ session('rest') }}</span>
            @endif
        </div>
    </div>
</div>