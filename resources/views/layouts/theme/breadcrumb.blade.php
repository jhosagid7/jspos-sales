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

    /* Estilos de la Cinta de Accesos Directos */
    .shortcuts-ribbon-container {
        min-width: 0;
    }
    .shortcuts-ribbon::-webkit-scrollbar {
        height: 3px;
    }
    .shortcuts-ribbon::-webkit-scrollbar-thumb {
        background: rgba(0,0,0,0.15);
        border-radius: 4px;
    }
    .shortcut-chip {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 5px 12px;
        border-radius: 50rem;
        font-size: 0.82rem;
        font-weight: 600;
        color: #495057;
        background-color: #ffffff;
        border: 1px solid #e2e8f0;
        text-decoration: none !important;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        white-space: nowrap;
        box-shadow: 0 1px 3px rgba(0,0,0,0.04);
        cursor: pointer;
    }
    .shortcut-chip:hover {
        background-color: #f8fafc;
        color: #007bff;
        border-color: #cbd5e1;
        transform: translateY(-1px);
        box-shadow: 0 3px 6px rgba(0,0,0,0.08);
    }
    .shortcut-chip.active {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
        color: #ffffff !important;
        border-color: #0056b3;
        box-shadow: 0 2px 6px rgba(0, 123, 255, 0.35);
    }
    .shortcut-chip.active i {
        color: #ffffff !important;
    }
    .shortcut-config-btn {
        padding: 5px 9px;
        background: transparent;
        border: 1px dashed #cbd5e1;
        color: #64748b;
    }
    .shortcut-config-btn:hover {
        background: #f1f5f9;
        border-color: #94a3b8;
        color: #1e293b;
    }
    .shortcut-config-btn:hover i {
        transform: rotate(45deg);
        transition: transform 0.25s ease;
    }

    /* Dark Mode para Accesos Directos */
    .dark-mode .shortcut-chip {
        background-color: #2b3035;
        border-color: #495057;
        color: #ced4da;
        box-shadow: 0 1px 3px rgba(0,0,0,0.2);
    }
    .dark-mode .shortcut-chip:hover {
        background-color: #343a40;
        color: #66a0d6;
        border-color: #6c757d;
        box-shadow: 0 3px 6px rgba(0,0,0,0.3);
    }
    .dark-mode .shortcut-chip.active {
        background: linear-gradient(135deg, #3f6791 0%, #2b4562 100%);
        border-color: #4b7ba8;
        color: #ffffff !important;
    }
    .dark-mode .shortcut-config-btn {
        background: transparent;
        border-color: #495057;
    }
    .dark-mode .shortcut-config-btn:hover {
        background-color: #343a40;
        border-color: #6c757d;
    }
</style>

@php
    $shortcuts = \App\Services\ShortcutService::getActiveShortcutsForUser();
@endphp

<div class="container-fluid mt-3">
    <div class="custom-page-title" style="--icon-color: {{ $posIconColor }}; --icon-color-dark: {{ $darkPosIconColor }};">
        <div class="page-title-heading d-flex align-items-center flex-shrink-0">
            <h4>
                <i class="{{ $posIcon }}"></i> 
                {{ $posTitle }}
            </h4>
        </div>
        
        @if(count($shortcuts) > 0)
        <div class="d-none d-lg-flex align-items-center mx-3 flex-grow-1 justify-content-center shortcuts-ribbon-container">
            <div class="shortcuts-ribbon d-flex align-items-center" style="gap: 8px; overflow-x: auto; max-width: 100%; padding: 2px 4px;">
                @foreach($shortcuts as $s)
                    <a 
                        href="{{ $s['url'] }}" 
                        class="shortcut-chip {{ $s['is_active'] ? 'active' : '' }}" 
                        title="{{ $s['label'] }}"
                    >
                        <i class="{{ $s['icon'] }}" style="color: {{ $s['is_active'] ? '#ffffff' : ($s['color'] ?? '#007bff') }}; font-size: 0.85rem;"></i>
                        <span>{{ $s['short_label'] ?? $s['label'] }}</span>
                    </a>
                @endforeach

                <button 
                    type="button" 
                    class="shortcut-chip shortcut-config-btn" 
                    data-toggle="modal" 
                    data-target="#shortcutsModal" 
                    title="Personalizar cinta de accesos directos"
                >
                    <i class="fas fa-cog text-secondary" style="font-size: 0.85rem;"></i>
                </button>
            </div>
        </div>
        @endif

        <div class="d-none d-md-flex align-items-center flex-shrink-0" style="gap: 12px;">
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

@include('layouts.theme.shortcuts-modal')