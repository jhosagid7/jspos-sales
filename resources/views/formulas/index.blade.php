@extends('layouts.app')
@section('title', 'Fórmulas de Preparación y Mezclas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h3 class="fw-bold mb-1">🧪 Fórmulas de Preparación & Costos Ponderados</h3>
        <p class="text-white-50 mb-0">Cálculo exacto del costo promedio por kilogramo ($/KG) de mezclas con historial inmutable de recetas.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('raw_materials.index') }}" class="btn btn-outline-info fw-bold">
            <i class="bi bi-boxes me-1"></i> Catálogo de Materia Prima
        </a>
        @if(Auth::user()->isSuperAdmin())
            <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#newFormulaModal">
                <i class="bi bi-plus-circle me-1"></i> Nueva Fórmula de Preparación
            </button>
        @endif
    </div>
</div>

<!-- Buscador Inteligente de Fórmulas -->
<div class="card-custom mb-4 py-3">
    <div class="row g-2 align-items-center">
        <div class="col-md-8">
            <div class="input-group">
                <span class="input-group-text bg-dark border-secondary text-warning">
                    <i class="bi bi-search"></i>
                </span>
                <input type="text" id="smartFormulaSearch" class="form-control bg-dark text-white border-secondary fs-6" placeholder="🔍 Buscar receta por nombre, código o ingrediente (ej. 'vivero', 'hielo', 'recuperado', 'boutique', '3003')..." autocomplete="off">
                <button type="button" id="clearFormulaSearchBtn" class="btn btn-outline-secondary" style="display: none;">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>
        </div>
        <div class="col-md-4 text-end">
            <span class="badge bg-dark border border-warning text-warning py-2 px-3 w-100 fs-6" id="formulasCounter">
                🧪 Mostrando {{ $formulas->count() }} de {{ $formulas->count() }} Fórmulas
            </span>
        </div>
    </div>
</div>

<!-- Listado de Fórmulas Activas -->
<div class="row g-4 mb-4" id="formulasContainer">
    @forelse($formulas as $formula)
        @php
            $v = $formula->currentVersion;
            $ingredientsText = '';
            if ($v && $v->items) {
                foreach ($v->items as $it) {
                    $ingredientsText .= ' ' . ($it->rawMaterial->name ?? '');
                }
            }
            $searchableData = $formula->name . ' ' . $formula->code . ' ' . ($formula->description ?? '') . $ingredientsText;
        @endphp
        <div class="col-lg-6 formula-card" data-search="{{ strtolower($searchableData) }}">
            <div class="card-custom h-100 border-start border-warning border-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <span class="badge bg-warning text-dark font-monospace mb-1">v{{ $v->version_number ?? 1 }} Activa</span>
                        <h4 class="fw-bold text-white mb-0">{{ $formula->name }}</h4>
                        <small class="text-info font-monospace">{{ $formula->code }}</small>
                    </div>
                    <div class="text-end">
                        <small class="text-white-50 d-block">Costo Promedio Mezcla</small>
                        <span class="fs-4 fw-bold text-success">${{ number_format($v->cost_per_kg ?? 0, 4) }} <small class="fs-6 text-white-50">/ KG</small></span>
                    </div>
                </div>

                <p class="text-white-50 small mb-3">{{ $formula->description ?? 'Sin descripción.' }}</p>

                <!-- Tabla de Ingredientes de la Versión Actual -->
                <div class="table-responsive mb-3 bg-dark rounded border border-secondary-subtle">
                    <table class="table table-sm table-custom mb-0 align-middle">
                        <thead>
                            <tr class="bg-black">
                                <th>Materia Prima</th>
                                <th class="text-end">Cant. (KG)</th>
                                <th class="text-end">Precio $/KG</th>
                                <th class="text-end">Subtotal ($)</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($v && $v->items)
                                @foreach($v->items as $item)
                                    <tr>
                                        <td>
                                            <strong class="text-white small">{{ $item->rawMaterial->name ?? 'Material' }}</strong>
                                        </td>
                                        <td class="text-end font-monospace text-info">{{ number_format($item->quantity_kg, 2) }} kg</td>
                                        <td class="text-end font-monospace text-warning">${{ number_format($item->price_applied, 4) }}</td>
                                        <td class="text-end font-monospace text-success fw-bold">${{ number_format($item->subtotal_cost, 2) }}</td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                        <tfoot class="border-top border-secondary">
                            <tr class="fw-bold">
                                <td class="text-white">TOTAL MEZCLA:</td>
                                <td class="text-end text-info font-monospace">{{ number_format($v->total_kg ?? 0, 2) }} kg</td>
                                <td></td>
                                <td class="text-end text-success font-monospace fs-6">${{ number_format($v->total_cost ?? 0, 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <!-- Footer de la Tarjeta con Acciones -->
                <div class="d-flex justify-content-between align-items-center pt-2 border-top border-secondary-subtle">
                    <small class="text-white-50">
                        Vigente desde: {{ $v && $v->valid_from ? $v->valid_from->format('d/m/Y h:i A') : 'Inicial' }}
                    </small>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-info btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#historyModal{{ $formula->id }}">
                            📜 Historial / Fechas
                        </button>
                        @if(Auth::user()->isSuperAdmin())
                            <button class="btn btn-warning btn-sm fw-bold" data-bs-toggle="modal" data-bs-target="#editRecipeModal{{ $formula->id }}">
                                🔄 Nueva Receta (v{{ ($v->version_number ?? 1) + 1 }})
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Auditoría Temporal / Historial de Versiones -->
        <div class="modal fade" id="historyModal{{ $formula->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-dark text-white border-secondary">
                    <div class="modal-header border-secondary">
                        <h5 class="modal-title fw-bold text-info">
                            <i class="bi bi-clock-history me-2"></i> Auditoría Temporal: {{ $formula->name }}
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-white-50 small mb-4">
                            Historial inmutable de versiones de esta fórmula. Cada versión mantiene intactos los ingredientes y costos vigentes en su momento.
                        </p>

                        <div class="accordion" id="accordionFormula{{ $formula->id }}">
                            @foreach($formula->versions as $ver)
                                <div class="accordion-item bg-dark border-secondary mb-2 rounded">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button bg-dark text-white {{ $ver->is_active ? '' : 'collapsed' }}" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVer{{ $ver->id }}">
                                            <div class="d-flex justify-content-between align-items-center w-100 me-3">
                                                <div>
                                                    <span class="badge {{ $ver->is_active ? 'bg-success' : 'bg-secondary' }} me-2">
                                                        v{{ $ver->version_number }} {{ $ver->is_active ? '(ACTIVA)' : '(HISTÓRICA)' }}
                                                    </span>
                                                    <strong class="text-white">{{ $ver->valid_from ? $ver->valid_from->format('d/m/Y') : '' }}</strong>
                                                    @if($ver->valid_to)
                                                        <small class="text-white-50"> hasta {{ $ver->valid_to->format('d/m/Y') }}</small>
                                                    @else
                                                        <small class="text-success"> hasta la actualidad</small>
                                                    @endif
                                                </div>
                                                <span class="badge bg-dark border border-success text-success fs-6">
                                                    ${{ number_format($ver->cost_per_kg, 4) }} / KG
                                                </span>
                                            </div>
                                        </button>
                                    </h2>
                                    <div id="collapseVer{{ $ver->id }}" class="accordion-collapse collapse {{ $ver->is_active ? 'show' : '' }}" data-bs-parent="#accordionFormula{{ $formula->id }}">
                                        <div class="accordion-body bg-black">
                                            @if($ver->notes)
                                                <p class="text-warning small mb-2"><i class="bi bi-info-circle me-1"></i> <strong>Motivo:</strong> {{ $ver->notes }}</p>
                                            @endif
                                            <table class="table table-sm table-custom mb-0">
                                                <thead>
                                                    <tr>
                                                        <th>Ingrediente</th>
                                                        <th class="text-end">Cantidad (KG)</th>
                                                        <th class="text-end">Precio $/KG</th>
                                                        <th class="text-end">Subtotal ($)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($ver->items as $vi)
                                                        <tr>
                                                            <td>{{ $vi->rawMaterial->name ?? 'Material' }}</td>
                                                            <td class="text-end font-monospace text-info">{{ number_format($vi->quantity_kg, 2) }} kg</td>
                                                            <td class="text-end font-monospace text-warning">${{ number_format($vi->price_applied, 4) }}</td>
                                                            <td class="text-end font-monospace text-success fw-bold">${{ number_format($vi->subtotal_cost, 2) }}</td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr class="fw-bold">
                                                        <td>TOTAL:</td>
                                                        <td class="text-end text-info font-monospace">{{ number_format($ver->total_kg, 2) }} kg</td>
                                                        <td></td>
                                                        <td class="text-end text-success font-monospace">${{ number_format($ver->total_cost, 2) }}</td>
                                                    </tr>
                                                </tfoot>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal para Crear Nueva Versión de Receta -->
        @if(Auth::user()->isSuperAdmin())
        <div class="modal fade" id="editRecipeModal{{ $formula->id }}" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content bg-dark text-white border-secondary">
                    <form action="{{ route('formulas.new_version', $formula->id) }}" method="POST">
                        @csrf
                        <div class="modal-header border-secondary">
                            <h5 class="modal-title fw-bold text-warning">
                                <i class="bi bi-arrow-repeat me-2"></i> Actualizar Receta: {{ $formula->name }} (v{{ ($v->version_number ?? 1) + 1 }})
                            </h5>
                            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="alert alert-info py-2 small mb-3">
                                <strong>Nota de Auditoría:</strong> Al guardar, la versión actual quedará archivada en el historial y la nueva versión pasará a ser la vigente para las producciones futuras.
                            </div>

                            <div class="mb-3">
                                <label class="form-label small text-white-50">Motivo del Ajuste de Fórmula</label>
                                <input type="text" name="notes" class="form-control" placeholder="Ej. Ajuste de proporción de recuperado por cambio de lote" required>
                            </div>

                            <label class="form-label small text-white-50 fw-bold mb-2">Ingredientes de la Mezcla (KG)</label>
                            <div id="recipeIngredientsContainer{{ $formula->id }}">
                                @if($v && $v->items)
                                    @foreach($v->items as $idx => $it)
                                        <div class="row g-2 mb-2 align-items-center ingredient-row">
                                            <div class="col-md-6">
                                                <select name="items[{{ $idx }}][raw_material_id]" class="form-select select-material" required>
                                                    @foreach($rawMaterials as $mat)
                                                        <option value="{{ $mat->id }}" data-price="{{ $mat->final_price }}" {{ $mat->id == $it->raw_material_id ? 'selected' : '' }}>
                                                            {{ $mat->name }} (${{ number_format($mat->final_price, 4) }}/kg)
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="input-group">
                                                    <input type="number" step="0.01" name="items[{{ $idx }}][quantity_kg]" class="form-control input-qty text-info fw-bold" value="{{ $it->quantity_kg }}" required>
                                                    <span class="input-group-text bg-dark border-secondary text-white-50">KG</span>
                                                </div>
                                            </div>
                                            <div class="col-md-2 text-end">
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-remove-row" onclick="this.closest('.ingredient-row').remove();">
                                                    🗑️
                                                </button>
                                            </div>
                                        </div>
                                    @endforeach
                                @endif
                            </div>

                            <button type="button" class="btn btn-outline-info btn-sm mt-2" onclick="addIngredientRow('{{ $formula->id }}')">
                                <i class="bi bi-plus me-1"></i> + Añadir Ingrediente
                            </button>
                        </div>
                        <div class="modal-footer border-secondary">
                            <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-warning btn-sm fw-bold">Guardar Versión v{{ ($v->version_number ?? 1) + 1 }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    @empty
        <div class="col-12">
            <div class="card-custom text-center py-5">
                <h5 class="text-white-50">No hay fórmulas de preparación registradas.</h5>
            </div>
        </div>
    @endforelse
</div>

<!-- Alerta de No Fórmulas Encontradas -->
<div id="noFormulasFoundAlert" class="card-custom p-5 text-center text-white-50 mb-4" style="display: none;">
    <i class="bi bi-search fs-1 d-block mb-2 text-warning"></i>
    <h5>No se encontraron recetas con ese criterio.</h5>
</div>

<!-- Modal para Crear Nueva Fórmula -->
@if(Auth::user()->isSuperAdmin())
<div class="modal fade" id="newFormulaModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content bg-dark text-white border-secondary">
            <form action="{{ route('formulas.store') }}" method="POST">
                @csrf
                <div class="modal-header border-secondary">
                    <h5 class="modal-title fw-bold text-primary">Crear Nueva Fórmula de Preparación</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label small text-white-50">Nombre de la Fórmula</label>
                            <input type="text" name="name" class="form-control" placeholder="Ej. Fórmula Panadería Cristal" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small text-white-50">Código Único</label>
                            <input type="text" name="code" class="form-control" placeholder="FOR-PAN-CRIS" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small text-white-50">Descripción / Uso</label>
                            <input type="text" name="description" class="form-control" placeholder="Mezcla para bolsa de panadería baja densidad transparente">
                        </div>
                    </div>

                    <label class="form-label small text-white-50 fw-bold mb-2">Ingredientes de la Mezcla (v1 Inicial)</label>
                    <div id="newFormulaIngredientsContainer">
                        <div class="row g-2 mb-2 align-items-center ingredient-row">
                            <div class="col-md-6">
                                <select name="items[0][raw_material_id]" class="form-select" required>
                                    <option value="" disabled selected>Seleccione Materia Prima...</option>
                                    @foreach($rawMaterials as $mat)
                                        <option value="{{ $mat->id }}">
                                            {{ $mat->name }} (${{ number_format($mat->final_price, 4) }}/kg)
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <div class="input-group">
                                    <input type="number" step="0.01" name="items[0][quantity_kg]" class="form-control text-info fw-bold" placeholder="Cantidad" required>
                                    <span class="input-group-text bg-dark border-secondary text-white-50">KG</span>
                                </div>
                            </div>
                            <div class="col-md-2 text-end">
                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.ingredient-row').remove();">🗑️</button>
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn btn-outline-info btn-sm mt-2" onclick="addNewFormulaIngredientRow()">
                        <i class="bi bi-plus me-1"></i> + Añadir Ingrediente
                    </button>
                </div>
                <div class="modal-footer border-secondary">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">Crear Fórmula y Versión v1</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

<script>
document.addEventListener('DOMContentLoaded', function () {
    const searchInput = document.getElementById('smartFormulaSearch');
    const clearBtn = document.getElementById('clearFormulaSearchBtn');
    const cards = Array.from(document.querySelectorAll('.formula-card'));
    const counterElem = document.getElementById('formulasCounter');
    const noResultsElem = document.getElementById('noFormulasFoundAlert');
    const totalCount = cards.length;

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

        cards.forEach(card => {
            const cardSearch = (card.getAttribute('data-search') || '').toLowerCase();

            if (!query) {
                card.style.display = '';
                visibleCount++;
                return;
            }

            const compactTarget = normalizeCompact(cardSearch);
            const targetTokens = normalizeText(cardSearch).split(/\s+/).filter(Boolean);

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
                card.style.display = '';
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });

        if (counterElem) {
            counterElem.innerText = `🧪 Mostrando ${visibleCount} de ${totalCount} Fórmulas`;
        }
        if (noResultsElem) {
            noResultsElem.style.display = visibleCount === 0 ? 'block' : 'none';
        }
        if (clearBtn) {
            clearBtn.style.display = query ? 'inline-block' : 'none';
        }
    }

    searchInput.addEventListener('input', executeFilter);
    clearBtn.addEventListener('click', function () {
        searchInput.value = '';
        executeFilter();
        searchInput.focus();
    });
});

let newRowIndex = 100;

function addIngredientRow(formulaId) {
    const container = document.getElementById('recipeIngredientsContainer' + formulaId);
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 align-items-center ingredient-row';
    row.innerHTML = `
        <div class="col-md-6">
            <select name="items[${newRowIndex}][raw_material_id]" class="form-select" required>
                @foreach($rawMaterials as $mat)
                    <option value="{{ $mat->id }}">{{ $mat->name }} (${{ number_format($mat->final_price, 4) }}/kg)</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <input type="number" step="0.01" name="items[${newRowIndex}][quantity_kg]" class="form-control text-info fw-bold" placeholder="KG" required>
                <span class="input-group-text bg-dark border-secondary text-white-50">KG</span>
            </div>
        </div>
        <div class="col-md-2 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.ingredient-row').remove();">🗑️</button>
        </div>
    `;
    container.appendChild(row);
    newRowIndex++;
}

function addNewFormulaIngredientRow() {
    const container = document.getElementById('newFormulaIngredientsContainer');
    const row = document.createElement('div');
    row.className = 'row g-2 mb-2 align-items-center ingredient-row';
    row.innerHTML = `
        <div class="col-md-6">
            <select name="items[${newRowIndex}][raw_material_id]" class="form-select" required>
                <option value="" disabled selected>Seleccione Materia Prima...</option>
                @foreach($rawMaterials as $mat)
                    <option value="{{ $mat->id }}">{{ $mat->name }} (${{ number_format($mat->final_price, 4) }}/kg)</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-4">
            <div class="input-group">
                <input type="number" step="0.01" name="items[${newRowIndex}][quantity_kg]" class="form-control text-info fw-bold" placeholder="Cantidad" required>
                <span class="input-group-text bg-dark border-secondary text-white-50">KG</span>
            </div>
        </div>
        <div class="col-md-2 text-end">
            <button type="button" class="btn btn-outline-danger btn-sm" onclick="this.closest('.ingredient-row').remove();">🗑️</button>
        </div>
    `;
    container.appendChild(row);
    newRowIndex++;
}
</script>
@endsection
