<div wire:ignore.self class="modal fade" id="modalAiInvoiceScan" tabindex="-1" role="dialog" aria-labelledby="modalAiInvoiceScanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden;">
            {{-- Header con degradado morado IA --}}
            <div class="modal-header text-white px-4 py-3" style="background: linear-gradient(135deg, #6610f2, #6f42c1);">
                <div class="d-flex align-items-center gap-2">
                    <i class="fas fa-magic fa-lg mr-2"></i>
                    <div>
                        <h5 class="modal-title font-weight-bold mb-0 text-white" id="modalAiInvoiceScanLabel">
                            Carga de Factura con IA (Visión OCR)
                        </h5>
                        <small class="text-white-50">
                            @if($aiScanStep === 'review')
                                Revisión interactiva de artículos y vinculación de catálogo
                            @else
                                Extrae automáticamente proveedor, artículos, cantidades y costos
                            @endif
                        </small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-dismiss="modal" aria-label="Close" onclick="$('#modalAiInvoiceScan').modal('hide')"></button>
            </div>

            <div class="modal-body p-4" style="max-height: 75vh; overflow-y: auto;">
                
                {{-- PASO 1: CARGA DE ARCHIVO --}}
                @if ($aiScanStep === 'upload')
                    {{-- Banner de Instrucción --}}
                    <div class="alert alert-light border shadow-none mb-3 p-3" style="border-radius: 10px; background-color: #f8f9fa;">
                        <div class="d-flex align-items-start">
                            <i class="fas fa-info-circle text-primary mt-1 mr-3 fa-lg"></i>
                            <div class="small text-muted">
                                Sube una <strong>fotografía clara (JPG, PNG)</strong> o un <strong>documento PDF</strong> de la factura o nota de entrega de tu proveedor.
                                El modelo de Inteligencia Artificial procesará los renglones y te permitirá confirmar o seleccionar los productos exactos de tu inventario.
                            </div>
                        </div>
                    </div>

                    {{-- Zona de Carga de Archivo --}}
                    <div class="mb-4">
                        <label class="form-label font-weight-bold text-dark mb-2">
                            <i class="fas fa-file-invoice text-secondary mr-1"></i> Selecciona la Factura / Nota de Entrega:
                        </label>
                        
                        <div class="border rounded-3 p-4 text-center" 
                             style="border: 2px dashed #6f42c1 !important; background-color: #faf8ff; border-radius: 12px;">
                            
                            <input type="file" 
                                   id="aiInvoiceFileInput" 
                                   class="d-none" 
                                   wire:model="invoiceFile" 
                                   accept="image/png,image/jpeg,image/jpg,application/pdf">

                            <label for="aiInvoiceFileInput" class="btn btn-outline-purple mb-2 shadow-sm px-4 py-2" style="cursor: pointer; border-color: #6f42c1; color: #6f42c1;">
                                <i class="fas fa-cloud-upload-alt mr-2"></i> Elegir Archivo o Tomar Foto
                            </label>

                            <div class="text-muted small">
                                Formatos aceptados: <strong>JPG, PNG, PDF</strong> (Máximo 10 MB)
                            </div>

                            {{-- Indicador de subida del archivo al navegador --}}
                            <div wire:loading wire:target="invoiceFile" class="mt-3">
                                <span class="spinner-border spinner-border-sm text-purple mr-1" style="color: #6f42c1;"></span>
                                <span class="small font-weight-bold text-purple" style="color: #6f42c1;">Cargando documento en el navegador...</span>
                            </div>
                        </div>

                        @error('invoiceFile')
                            <div class="text-danger small mt-2">
                                <i class="fas fa-exclamation-circle mr-1"></i> {{ $message }}
                            </div>
                        @enderror
                    </div>

                    {{-- Preview del Archivo Seleccionado --}}
                    @if ($invoiceFile && !$errors->has('invoiceFile'))
                        <div class="card bg-light border-0 mb-3 shadow-none" style="border-radius: 10px;">
                            <div class="card-body p-3 d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    @if (str_contains($invoiceFile->getMimeType(), 'image'))
                                        <img src="{{ $invoiceFile->temporaryUrl() }}" alt="Vista Previa" class="rounded mr-3 shadow-sm" style="width: 55px; height: 55px; object-fit: cover;">
                                    @else
                                        <div class="rounded bg-danger text-white d-flex align-items-center justify-content-center mr-3" style="width: 55px; height: 55px;">
                                            <i class="fas fa-file-pdf fa-2x"></i>
                                        </div>
                                    @endif
                                    <div>
                                        <h6 class="font-weight-bold mb-0 text-truncate" style="max-width: 320px;">
                                            {{ $invoiceFile->getClientOriginalName() }}
                                        </h6>
                                        <small class="text-muted">
                                            {{ round($invoiceFile->getSize() / 1024, 1) }} KB &bull; Listo para procesar
                                        </small>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-light text-danger rounded-circle" wire:click="$set('invoiceFile', null)" title="Quitar archivo">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    @endif

                    {{-- Spinner de Análisis IA en Ejecución --}}
                    <div wire:loading wire:target="processInvoiceWithAi" class="w-100 text-center py-4">
                        <div class="spinner-grow text-purple mb-3" style="color: #6f42c1; width: 3rem; height: 3rem;" role="status"></div>
                        <h6 class="font-weight-bold text-purple" style="color: #6f42c1;">
                            Analizando documento con Inteligencia Artificial...
                        </h6>
                        <p class="small text-muted mb-0">
                            Gemini Visión está extrayendo renglones, costos unitarios y cantidades.
                        </p>
                    </div>

                {{-- PASO 2: REVISIÓN INTERACTIVA Y VINCULACIÓN DE PRODUCTOS --}}
                @else
                    {{-- Barra de Resumen del Documento --}}
                    <div class="d-flex flex-wrap align-items-center justify-content-between p-3 mb-4 rounded border shadow-sm" style="background-color: #fcfbfe;">
                        <div class="mb-2 mb-md-0">
                            <span class="badge badge-light border text-purple font-weight-bold text-uppercase mb-1">
                                <i class="fas fa-check-double mr-1"></i> Factura Procesada
                            </span>
                            <h6 class="font-weight-bold text-dark mb-0">
                                Proveedor: <span class="text-purple">{{ $aiScanSummary['supplier'] ?? 'No identificado' }}</span>
                                @if(!empty($aiScanSummary['invoice_number']))
                                    &bull; N°: <span class="text-secondary">{{ $aiScanSummary['invoice_number'] }}</span>
                                @endif
                            </h6>
                        </div>
                        <div class="d-flex align-items-center gap-2">
                            @php
                                $matchedTotal = count(array_filter($aiProcessedItems, fn($i) => $i['status'] === 'matched'));
                                $pendingTotal = count(array_filter($aiProcessedItems, fn($i) => $i['status'] === 'pending'));
                            @endphp
                            <span class="badge badge-success px-3 py-2 font-weight-bold" style="font-size: 0.85rem;">
                                <i class="fas fa-shopping-cart mr-1"></i> {{ $matchedTotal }} en Carrito
                            </span>
                            @if($pendingTotal > 0)
                                <span class="badge badge-warning text-dark px-3 py-2 font-weight-bold" style="font-size: 0.85rem;">
                                    <i class="fas fa-exclamation-circle mr-1"></i> {{ $pendingTotal }} por Vincular
                                </span>
                            @else
                                <span class="badge badge-info px-3 py-2 font-weight-bold" style="font-size: 0.85rem;">
                                    <i class="fas fa-check-circle mr-1"></i> 100% Vinculados
                                </span>
                            @endif
                        </div>
                    </div>

                    {{-- SECCIÓN DE RENGLONES PENDIENTES POR VINCULAR --}}
                    @if ($pendingTotal > 0)
                        <div class="mb-4">
                            <div class="d-flex align-items-center justify-content-between mb-3">
                                <h6 class="font-weight-bold text-dark mb-0">
                                    <i class="fas fa-tags text-warning mr-2"></i> Renglones con nombres similares (Elige el correcto):
                                </h6>
                                <small class="text-muted">Selecciona el producto correspondiente de tu catálogo para cada renglón</small>
                            </div>

                            <div class="row">
                                @foreach ($aiProcessedItems as $idx => $item)
                                    @if ($item['status'] === 'pending')
                                        <div class="col-12 mb-3">
                                            <div class="card border-warning shadow-none" style="border-radius: 12px; background-color: #fffdf7;">
                                                <div class="card-body p-3">
                                                    {{-- Datos del Renglón de la Factura --}}
                                                    <div class="d-flex flex-wrap justify-content-between align-items-start mb-2 pb-2 border-bottom">
                                                        <div>
                                                            <span class="badge badge-warning text-dark px-2 py-1 mb-1 font-weight-bold">
                                                                Renglón Factura #{{ $idx + 1 }}
                                                            </span>
                                                            <h6 class="font-weight-bold text-dark mb-1" style="font-size: 1rem;">
                                                                {{ $item['description'] }}
                                                            </h6>
                                                        </div>
                                                        <div class="text-md-right">
                                                            <span class="badge badge-light border text-dark mr-2 px-2 py-1">
                                                                Cant: <strong>{{ $item['quantity'] }}</strong>
                                                            </span>
                                                            <span class="badge badge-light border text-success px-2 py-1 font-weight-bold">
                                                                Costo Fac: ${{ number_format($item['unit_price'], 2) }}
                                                            </span>
                                                        </div>
                                                    </div>

                                                    {{-- Selector de Sugerencias de Catálogo --}}
                                                    <div class="row align-items-center mt-3">
                                                        <div class="col-lg-8 col-md-7 col-sm-12 mb-2 mb-md-0">
                                                            <label class="small text-muted font-weight-bold mb-1">
                                                                <i class="fas fa-boxes text-purple mr-1"></i> Coincidencias encontradas en tu inventario:
                                                            </label>
                                                            <select wire:model="selectedMatches.{{ $idx }}" class="form-control form-control-sm border-purple font-weight-bold">
                                                                <option value="">-- Selecciona el producto de tu catálogo --</option>
                                                                @foreach ($item['suggestions'] as $sug)
                                                                    <option value="{{ $sug['id'] }}">
                                                                        📦 {{ $sug['name'] }} [SKU: {{ $sug['sku'] }}]
                                                                    </option>
                                                                @endforeach
                                                            </select>
                                                        </div>

                                                        <div class="col-lg-4 col-md-5 col-sm-12 text-md-right pt-md-3">
                                                            <button type="button" 
                                                                    wire:click="linkInvoiceItem({{ $idx }})" 
                                                                    class="btn btn-sm text-white shadow-sm font-weight-bold w-100 py-2" 
                                                                    style="background: linear-gradient(135deg, #28a745, #218838); border-radius: 8px;">
                                                                <i class="fas fa-plus-circle mr-1"></i> Vincular y Agregar al Carrito
                                                            </button>
                                                        </div>
                                                    </div>

                                                    {{-- Buscador manual interactivo por renglón --}}
                                                    <div class="mt-2 pt-2 border-top" x-data="{ openManual: false }">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <a href="javascript:void(0)" @click="openManual = !openManual" class="small text-purple text-decoration-none font-weight-bold">
                                                                <i class="fas fa-search-plus mr-1"></i>
                                                                <span x-text="openManual ? 'Ocultar buscador manual' : '¿No ves el producto exacto arriba? Escribe aquí para buscar otro en tu catálogo'"></span>
                                                            </a>
                                                        </div>

                                                        <div x-show="openManual" x-cloak class="mt-2">
                                                            <input type="text" 
                                                                   placeholder="Escribe el nombre o código de tu producto en inventario..." 
                                                                   wire:input.debounce.300ms="searchCustomProductForAiItem({{ $idx }}, $event.target.value)" 
                                                                   class="form-control form-control-sm bg-white border">

                                                            @if (!empty($customSearchResults[$idx]))
                                                                <div class="list-group mt-2 shadow-sm small" style="max-height: 150px; overflow-y: auto;">
                                                                    @foreach ($customSearchResults[$idx] as $cp)
                                                                        <button type="button" 
                                                                                wire:click="selectCustomProductForAiItem({{ $idx }}, {{ $cp['id'] }})" 
                                                                                class="list-group-item list-group-item-action py-2 px-3 d-flex justify-content-between align-items-center">
                                                                            <div>
                                                                                <i class="fas fa-box text-purple mr-2"></i>
                                                                                <strong>{{ $cp['name'] }}</strong>
                                                                            </div>
                                                                            <span class="badge badge-light border text-dark">{{ $cp['sku'] }} &bull; Seleccionar</span>
                                                                        </button>
                                                                    @endforeach
                                                                </div>
                                                            @endif
                                                        </div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- SECCIÓN DE PRODUCTOS YA VINCULADOS / AGREGADOS AL CARRITO --}}
                    @php
                        $matchedRows = array_filter($aiProcessedItems, fn($i) => $i['status'] === 'matched');
                    @endphp
                    @if (count($matchedRows) > 0)
                        <div class="mt-3" x-data="{ openCartList: true }">
                            <div class="d-flex align-items-center justify-content-between bg-light p-2 rounded mb-2 border">
                                <span class="font-weight-bold text-success small">
                                    <i class="fas fa-check-circle mr-1"></i> Productos ya cargados al Carrito de Compras ({{ count($matchedRows) }})
                                </span>
                                <button type="button" @click="openCartList = !openCartList" class="btn btn-sm btn-light py-0 px-2 text-muted">
                                    <i class="fas" :class="openCartList ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </button>
                            </div>

                            <div x-show="openCartList" class="table-responsive" style="max-height: 220px; overflow-y: auto;">
                                <table class="table table-sm table-bordered bg-white small mb-0 shadow-none">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Producto en Inventario</th>
                                            <th>Texto en Factura Proveedor</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-end">Costo Unitario</th>
                                            <th class="text-center">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($matchedRows as $m)
                                            <tr>
                                                <td class="font-weight-bold text-dark">
                                                    📦 {{ $m['matched_product_name'] ?? $m['description'] }}
                                                    @if(!empty($m['matched_product_sku']))
                                                        <br><small class="text-muted">SKU: {{ $m['matched_product_sku'] }}</small>
                                                    @endif
                                                </td>
                                                <td class="text-muted">{{ $m['description'] }}</td>
                                                <td class="text-center font-weight-bold">{{ $m['quantity'] }}</td>
                                                <td class="text-end font-weight-bold text-success">${{ number_format($m['unit_price'], 2) }}</td>
                                                <td class="text-center">
                                                    <span class="badge badge-success px-2 py-1">
                                                        <i class="fas fa-check mr-1"></i> En Carrito
                                                    </span>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                @endif

            </div>

            {{-- Footer según el paso --}}
            <div class="modal-footer bg-light px-4 py-3 d-flex justify-content-between">
                @if ($aiScanStep === 'upload')
                    <button type="button" class="btn btn-secondary px-3" data-dismiss="modal" onclick="$('#modalAiInvoiceScan').modal('hide')">
                        Cerrar
                    </button>
                    <button type="button" 
                            class="btn text-white px-4 shadow-sm" 
                            style="background: linear-gradient(135deg, #6610f2, #6f42c1);" 
                            wire:click="processInvoiceWithAi" 
                            wire:loading.attr="disabled"
                            @if(!$invoiceFile) disabled @endif>
                        <span wire:loading wire:target="processInvoiceWithAi" class="spinner-border spinner-border-sm mr-1"></span>
                        <i wire:loading.remove wire:target="processInvoiceWithAi" class="fas fa-magic mr-1"></i>
                        Procesar Factura con IA
                    </button>
                @else
                    <button type="button" wire:click="resetAiScan" class="btn btn-outline-secondary px-3 shadow-sm">
                        <i class="fas fa-arrow-left mr-1"></i> Escanear otra Factura
                    </button>
                    <button type="button" 
                            class="btn text-white px-4 shadow-sm font-weight-bold" 
                            style="background: linear-gradient(135deg, #6610f2, #6f42c1);"
                            data-dismiss="modal" 
                            onclick="$('#modalAiInvoiceScan').modal('hide')">
                        <i class="fas fa-shopping-cart mr-2"></i> Listo, Ver Carrito
                    </button>
                @endif
            </div>
        </div>
    </div>
</div>
