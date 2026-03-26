<div>
    <form class="row g-3">
        <div class="col-12">
            <div class="alert alert-light-primary border-primary">
                <i class="fa fa-info-circle fa-lg me-2 text-primary"></i> 
                <strong>Actualización Masiva de Precios</strong><br>
                Usa esta herramienta para aumentar o disminuir los precios de un grupo de productos de forma rápida (por porcentaje). Solo puedes aplicar el cambio filtrando por Categoría y/o Proveedor para evitar alteraciones globales accidentales.
            </div>
        </div>

        <div class="col-sm-12 col-md-6 mt-3">
            <label class="form-label font-weight-bold">1. ¿Qué precio deseas modificar? <span class="text-danger">*</span></label>
            <div class="d-flex flex-column gap-2">
                <div class="form-check radio radio-primary">
                    <input class="form-check-input" type="radio" name="targetPrice" id="targetCost" value="cost" wire:model.live="target_price">
                    <label class="form-check-label text-dark" for="targetCost">
                        <i class="fa fa-truck text-muted me-1"></i> Costo de Compra
                    </label>
                </div>
                <div class="form-check radio radio-primary">
                    <input class="form-check-input" type="radio" name="targetPrice" id="targetSale" value="price" wire:model.live="target_price">
                    <label class="form-check-label text-dark" for="targetSale">
                        <i class="fa fa-tag text-success me-1"></i> Precio de Venta
                    </label>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-6 mt-3">
            <label class="form-label font-weight-bold">2. Porcentaje a Aplicar (%) <span class="text-danger">*</span></label>
            <div class="input-group">
                <input wire:model.lazy="percentage" type="number" step="0.01" class="form-control form-control-lg text-primary fw-bold text-center" placeholder="Ej: 10 o -5">
                <span class="input-group-text bg-primary text-white">%</span>
            </div>
            <small class="text-muted mt-1 d-block">
                Usa números positivos (ej. 10) para aumentar, o negativos (ej. -5) para disminuir.
            </small>
        </div>

        <div class="col-12 mt-4"><hr></div>

        <div class="col-sm-12">
            <h6 class="text-secondary mb-3"><i class="fa fa-filter me-2"></i> 3. Selecciona los Filtros</h6>
        </div>

        <div class="col-sm-12 col-md-6">
            <label class="form-label">Por Categoría</label>
            <select wire:model.live="selected_category" class="form-control form-select">
                <option value="">-- Todas las categorías --</option>
                @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                @endforeach
            </select>
        </div>

        @if(config('tenant.modules.module_purchases', true))
        <div class="col-sm-12 col-md-6">
            <label class="form-label">Por Proveedor</label>
            <select wire:model.live="selected_supplier" class="form-control form-select">
                <option value="">-- Todos los proveedores --</option>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        @endif

        {{-- Resultados Previos --}}
        <div class="col-12 mt-4">
            @if(empty($selected_category) && empty($selected_supplier))
                <div class="alert alert-light-warning">
                    <i class="fa fa-exclamation-triangle text-warning me-2"></i> 
                    Para habilitar el botón de aplicación masiva, debes seleccionar al menos <strong>una Categoría</strong> o <strong>un Proveedor</strong>.
                </div>
            @else
                <div class="alert {{ $affected_count > 0 ? 'alert-light-info' : 'alert-light-danger' }}">
                    <i class="fa {{ $affected_count > 0 ? 'fa-check-circle text-info' : 'fa-times-circle text-danger' }} me-2"></i> 
                    Se encontraron <strong>{{ $affected_count }} producto(s)</strong> que coinciden con los filtros seleccionados a los cuales se les aplicará el <strong>{{ $percentage }}%</strong> al <strong>{{ $target_price == 'cost' ? 'Costo de Compra' : 'Precio de Venta' }}</strong>.
                </div>

                @if($affected_count > 0 && floatval($percentage) != 0)
                    <div class="d-flex justify-content-end mt-3">
                        <button class="btn btn-primary btn-lg" wire:click.prevent="previewUpdate" wire:loading.attr="disabled">
                            <i class="fa fa-bolt me-2"></i> Previsualizar y Ejecutar Cambio
                        </button>
                    </div>
                @endif
            @endif
        </div>
    </form>

    {{-- Confirmación Modal / Overlay en la misma vista --}}
    @if($confirming)
        <div class="position-absolute w-100 h-100 top-0 start-0 bg-white" style="z-index: 10; border-radius: 10px;">
            <div class="d-flex gap-4 p-5 flex-column justify-content-center align-items-center h-100 text-center" style="background: rgba(235, 240, 247, 0.8);">
                <i class="fa fa-exclamation-triangle fa-5x text-warning mb-3"></i>
                <h3 class="fw-bold">¿Estás completamente seguro?</h3>
                <p class="text-dark fs-5">
                    Estás a punto de modificar el <strong>{{ $target_price == 'cost' ? 'Costo de Compra' : 'Precio de Venta' }}</strong> de <strong>{{ $affected_count }} producto(s)</strong>, 
                    aplicando un {{ floatval($percentage) > 0 ? 'aumento' : 'descuento' }} del <strong>{{ $percentage }}%</strong>.
                </p>
                <div class="alert alert-danger w-75">
                    <strong>¡Atención!</strong> Esta acción en masa no se puede deshacer y los precios cambiarán inmediatamente en el Catálogo, POS y todo el sistema en general.
                </div>
                
                <div class="d-flex gap-3 mt-3 w-100 justify-content-center">
                    <button class="btn btn-light btn-lg px-5 border" wire:click.prevent="cancelUpdate" wire:loading.attr="disabled">
                        <i class="fa fa-times me-2"></i> Cancelar
                    </button>
                    <button class="btn btn-danger btn-lg px-5" wire:click.prevent="applyUpdate" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="applyUpdate"><i class="fa fa-check me-2"></i> Sí, Ejecutar Actualización</span>
                        <span wire:loading wire:target="applyUpdate"><i class="fa fa-spin fa-spinner me-2"></i> Aplicando...</span>
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
