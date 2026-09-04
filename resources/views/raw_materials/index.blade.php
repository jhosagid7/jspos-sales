@extends('layouts.app')
@section('title', 'Catálogo de Materia Prima y Precios')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1">📦 Catálogo de Materia Prima & Historial de Precios</h3>
        <p class="text-white-50 mb-0">Precios base de importación con cargos logísticos (Transporte y Recargos) y auditoría temporal inmutable.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('formulas.index') }}" class="btn btn-outline-warning fw-bold">
            <i class="bi bi-bezier2 me-1"></i> Fórmulas de Preparación
        </a>
        @if(Auth::user()->isSuperAdmin())
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#newMaterialModal">
                <i class="bi bi-plus-circle me-1"></i> Nueva Materia Prima
            </button>
        @endif
    </div>
</div>

<!-- Tabla de Materias Primas -->
<div class="card-custom">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 class="fw-bold text-white mb-0">
                <i class="bi bi-boxes text-info me-2"></i> Materias Primas Registradas
            </h5>
            <small class="text-white-50" id="materialsCounter">Mostrando {{ $materials->count() }} de {{ $materials->count() }} insumos</small>
        </div>
        <span class="badge bg-dark border border-info text-info font-monospace py-2 px-3 fs-6">
            📦 Total: {{ $materials->count() }}
        </span>
    </div>

    <!-- Buscador Inteligente -->
    <div class="row g-2 mb-3 align-items-center">
        <div class="col-md-8">
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-info">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="smartMaterialSearch" class="form-control bg-dark text-white border-secondary fs-6" placeholder="🔍 Buscar insumo (ej. 'recuperado', '7000f', 'pigmento', 'panelo', '3003')..." autocomplete="off">
                <button type="button" id="clearMaterialSearchBtn" class="btn btn-outline-secondary" style="display: none;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <span class="badge bg-secondary py-2 w-100" id="materialFilterBadge">Todos los Insumos</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-custom mb-0 align-middle">
            <thead>
                <tr>
                    <th>Código & Material</th>
                    <th class="text-end">Precio Base ($/Kg)</th>
                    <th class="text-end">Transporte ($/Kg)</th>
                    <th class="text-end">Recargo ($/Kg)</th>
                    <th class="text-end">Precio Final ($/Kg)</th>
                    <th>Estado Vigente</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody id="materialsTableBody">
                @forelse($materials as $mat)
                    @php
                        $searchableData = $mat->name . ' ' . $mat->code . ' ' . ($mat->description ?? '');
                    @endphp
                    <tr class="material-row" data-search="{{ strtolower($searchableData) }}">
                        <td>
                            <strong class="text-white fs-6">{{ $mat->name }}</strong>
                            <br><span class="badge bg-dark border border-secondary text-info font-monospace">{{ $mat->code }}</span>
                            @if($mat->description)
                                <small class="text-white-50 ms-1 d-block">{{ $mat->description }}</small>
                            @endif
                        </td>
                        <td class="text-end font-monospace text-info fs-6">
                            ${{ number_format($mat->base_price, 4) }}
                        </td>
                        <td class="text-end font-monospace text-white-50">
                            +${{ number_format($mat->transport_cost, 4) }}
                        </td>
                        <td class="text-end font-monospace text-white-50">
                            +${{ number_format($mat->surcharge, 4) }}
                        </td>
                        <td class="text-end font-monospace">
                            <span class="fs-5 fw-bold text-success">${{ number_format($mat->final_price, 4) }}</span>
                            <small class="text-white-50 d-block" style="font-size: 10px;">Base+Transp+Recargo</small>
                        </td>
                        <td>
                            <span class="badge bg-success">Activo</span>
                            @php
                                $histCount = $mat->priceHistories->count();
                            @endphp
                            <br><small class="text-white-50">{{ $histCount }} cambio(s) histórico(s)</small>
                        </td>
                        <td class="text-end">
                            <div class="d-inline-flex gap-1">
                                <button class="btn btn-outline-info btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#historyMaterialModal{{ $mat->id }}">
                                    📈 Historial
                                </button>
                                @if(Auth::user()->isSuperAdmin())
                                    <button class="btn btn-warning btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#updatePriceModal{{ $mat->id }}">
                                        💲 Actualizar Precio
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-white-50 py-4">No hay materias primas registradas.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <!-- Alerta de No Resultados -->
        <div id="noMaterialsFoundAlert" class="p-5 text-center text-white-50" style="display: none;">
            <i class="bi bi-search fs-1 d-block mb-2 text-warning"></i>
            <h5>No se encontraron materias primas con ese criterio.</h5>
        </div>
    </div>
</div>

<!-- Modales de Historial y Actualización de Precios (Fuera de la tabla para HTML válido) -->
@foreach($materials as $mat)
    <!-- Modal de Historial y Evolución Temporal de Precios -->
    <div class="modal fade" id="historyMaterialModal{{ $mat->id }}" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content bg-dark text-white border-secondary">
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-info">
                        <i class="bi bi-clock-history me-2"></i> Evolución de Precios: {{ $mat->name }}
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-white-50 small mb-3">
                        Historial completo de auditoría. Ningún precio pasado se sobreescribe para mantener intactos los balances de producción anteriores.
                    </p>

                    <div class="table-responsive">
                        <table class="table table-sm table-custom mb-0 align-middle">
                            <thead>
                                <tr class="bg-black">
                                    <th>Vigencia Desde</th>
                                    <th>Vigencia Hasta</th>
                                    <th class="text-end">Precio Base</th>
                                    <th class="text-end">Transp.</th>
                                    <th class="text-end">Recargo</th>
                                    <th class="text-end">Precio Final</th>
                                    <th>Motivo / Auditor</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($mat->priceHistories as $h)
                                    <tr>
                                        <td class="font-monospace text-info">
                                            {{ $h->valid_from ? $h->valid_from->format('d/m/Y H:i') : '-' }}
                                        </td>
                                        <td class="font-monospace">
                                            @if($h->valid_to)
                                                <span class="text-white-50">{{ $h->valid_to->format('d/m/Y H:i') }}</span>
                                            @else
                                                <span class="badge bg-success">ACTUAL</span>
                                            @endif
                                        </td>
                                        <td class="text-end font-monospace">${{ number_format($h->base_price, 4) }}</td>
                                        <td class="text-end font-monospace text-white-50">+${{ number_format($h->transport_cost, 4) }}</td>
                                        <td class="text-end font-monospace text-white-50">+${{ number_format($h->surcharge, 4) }}</td>
                                        <td class="text-end font-monospace text-success fw-bold">${{ number_format($h->final_price, 4) }}</td>
                                        <td>
                                            <small class="text-warning d-block">{{ $h->notes ?? 'Sin nota' }}</small>
                                            <small class="text-white-50">{{ $h->creator->name ?? 'Sistema' }}</small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal para Actualizar Precio con Auditoría -->
    @if(Auth::user()->isSuperAdmin())
    <div class="modal fade" id="updatePriceModal{{ $mat->id }}" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content bg-dark text-white border-secondary">
                <form action="{{ route('raw_materials.update_price', $mat->id) }}" method="POST">
                    @csrf
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title fw-bold text-warning">
                            <i class="bi bi-cash-stack me-2"></i> Actualizar Precio: {{ $mat->name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 small mb-3">
                            Al guardar, el precio actual pasará al historial y el nuevo precio entrará en vigencia a partir de este momento.
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-white-50">Precio Base de Importación ($ / KG)</label>
                            <input type="number" step="0.0001" name="base_price" class="form-control text-info fw-bold fs-5" value="{{ $mat->base_price }}" required id="base_price_{{ $mat->id }}" oninput="calcFinalPrice('{{ $mat->id }}')">
                        </div>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small text-white-50">Transporte ($/KG)</label>
                                <input type="number" step="0.0001" name="transport_cost" class="form-control" value="{{ $mat->transport_cost }}" required id="transport_cost_{{ $mat->id }}" oninput="calcFinalPrice('{{ $mat->id }}')">
                            </div>
                            <div class="col-6">
                                <label class="form-label small text-white-50">Recargo ($/KG)</label>
                                <input type="number" step="0.0001" name="surcharge" class="form-control" value="{{ $mat->surcharge }}" required id="surcharge_{{ $mat->id }}" oninput="calcFinalPrice('{{ $mat->id }}')">
                            </div>
                        </div>

                        <div class="p-3 bg-black rounded border border-success mb-3 text-center">
                            <small class="text-white-50 d-block">Nuevo Precio Final Calculado:</small>
                            <span class="fs-3 fw-bold text-success" id="final_preview_{{ $mat->id }}">${{ number_format($mat->final_price, 4) }}</span>
                            <small class="text-white-50">/ KG</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small text-white-50">Motivo del Ajuste (Auditoría)</label>
                            <input type="text" name="notes" class="form-control" placeholder="Ej. Aumento de flete marítimo o nuevo proveedor" required>
                        </div>
                    </div>
                    <div class="modal-footer border-secondary">
                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning btn-sm fw-bold">Registrar Nuevo Precio</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
@endforeach

<!-- Modal para Crear Nueva Materia Prima -->
@if(Auth::user()->isSuperAdmin())
<div class="modal fade" id="newMaterialModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content bg-dark text-white border-secondary">
            <form action="{{ route('raw_materials.store') }}" method="POST">
                @csrf
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-primary">Registrar Nueva Materia Prima</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-white-50">Nombre del Material</label>
                        <input type="text" name="name" class="form-control" placeholder="Ej. POLIETILENO 7000F" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-white-50">Código Único (SKU)</label>
                        <input type="text" name="code" class="form-control" placeholder="MP-7000F" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small text-white-50">Precio Base ($/KG)</label>
                        <input type="number" step="0.0001" name="base_price" class="form-control text-info fw-bold" placeholder="1.8500" required id="new_base_price" oninput="calcNewMatPrice()">
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-white-50">Transporte ($/KG)</label>
                            <input type="number" step="0.0001" name="transport_cost" class="form-control" value="0.1300" required id="new_transport" oninput="calcNewMatPrice()">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-white-50">Recargo ($/KG)</label>
                            <input type="number" step="0.0001" name="surcharge" class="form-control" value="0.1300" required id="new_surcharge" oninput="calcNewMatPrice()">
                        </div>
                    </div>

                    <div class="p-3 bg-black rounded border border-success mb-3 text-center">
                        <small class="text-white-50 d-block">Precio Final Estimado:</small>
                        <span class="fs-4 fw-bold text-success" id="new_final_preview">$0.0000</span>
                        <small class="text-white-50">/ KG</small>
                    </div>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">Crear Materia Prima</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('smartMaterialSearch');
    const clearBtn = document.getElementById('clearMaterialSearchBtn');
    const rows = Array.from(document.querySelectorAll('.material-row'));
    const counterElem = document.getElementById('materialsCounter');
    const noResultsElem = document.getElementById('noMaterialsFoundAlert');
    const filterBadge = document.getElementById('materialFilterBadge');
    const totalCount = rows.length;

    function normalizeText(text) {
        if (!text) return '';
        return text.toString()
            .toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]/g, ' ');
    }

    function normalizeCompact(text) {
        if (!text) return '';
        return text.toString()
            .toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "")
            .replace(/[^a-z0-9]/g, '');
    }

    function executeFilter() {
        const query = searchInput.value.trim().toLowerCase();
        const compactQuery = normalizeCompact(query);
        const queryTokens = normalizeText(query).split(/\s+/).filter(Boolean);

        let visibleCount = 0;

        rows.forEach(row => {
            const rowSearch = (row.getAttribute('data-search') || '').toLowerCase();

            if (!query) {
                row.style.display = '';
                visibleCount++;
                return;
            }

            const compactTarget = normalizeCompact(rowSearch);
            const targetTokens = normalizeText(rowSearch).split(/\s+/).filter(Boolean);

            let match = false;

            if (compactQuery && compactTarget.includes(compactQuery)) {
                match = true;
            }

            if (!match && queryTokens.length > 0) {
                const allTokensMatch = queryTokens.every(qToken => {
                    const qCompact = normalizeCompact(qToken);
                    return targetTokens.some(tToken => tToken.includes(qToken)) || compactTarget.includes(qCompact);
                });
                if (allTokensMatch) {
                    match = true;
                }
            }

            if (match) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        if (counterElem) {
            counterElem.innerText = `Mostrando ${visibleCount} de ${totalCount} insumos`;
        }
        if (noResultsElem) {
            noResultsElem.style.display = visibleCount === 0 ? 'block' : 'none';
        }
        if (clearBtn) {
            clearBtn.style.display = query ? 'inline-block' : 'none';
        }
        if (filterBadge) {
            if (query) {
                filterBadge.className = 'badge bg-warning text-dark py-2 w-100 fw-bold';
                filterBadge.innerText = `${visibleCount} encontrados`;
            } else {
                filterBadge.className = 'badge bg-secondary py-2 w-100';
                filterBadge.innerText = 'Todos los Insumos';
            }
        }
    }

    searchInput.addEventListener('input', executeFilter);
    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        executeFilter();
        searchInput.focus();
    });
});

function calcFinalPrice(id) {
    const base = parseFloat(document.getElementById('base_price_' + id).value) || 0;
    const trans = parseFloat(document.getElementById('transport_cost_' + id).value) || 0;
    const sur = parseFloat(document.getElementById('surcharge_' + id).value) || 0;
    const total = base + trans + sur;
    document.getElementById('final_preview_' + id).innerText = '$' + total.toFixed(4);
}

function calcNewMatPrice() {
    const base = parseFloat(document.getElementById('new_base_price').value) || 0;
    const trans = parseFloat(document.getElementById('new_transport').value) || 0;
    const sur = parseFloat(document.getElementById('new_surcharge').value) || 0;
    const total = base + trans + sur;
    document.getElementById('new_final_preview').innerText = '$' + total.toFixed(4);
}
</script>
@endsection
