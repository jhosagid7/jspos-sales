@php
    $userShortcuts = \App\Services\ShortcutService::getActiveShortcutsForUser();
    $activeKeys = array_column($userShortcuts, 'key');
    $availableGrouped = \App\Services\ShortcutService::getAvailableForUser();
    $defaultKeys = \App\Services\ShortcutService::getDefaultKeysForUser();
@endphp

<!-- Modal de Configuración de Accesos Directos -->
<div class="modal fade" id="shortcutsModal" tabindex="-1" role="dialog" aria-labelledby="shortcutsModalLabel" aria-hidden="true" data-backdrop="static">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title font-weight-bold d-flex align-items-center" id="shortcutsModalLabel">
                    <i class="fas fa-magic me-2"></i> Personalizar Cinta de Accesos Directos
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" style="outline: none; opacity: 0.9;">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            
            <div class="modal-body p-4" style="max-height: 72vh; overflow-y: auto;">
                <div class="row align-items-center mb-3 g-2">
                    <div class="col-md-7 col-12">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0">
                                <i class="fas fa-search text-muted"></i>
                            </span>
                            <input 
                                type="text" 
                                id="shortcut-search" 
                                class="form-control border-start-0" 
                                placeholder="Buscar menú, reporte o función..."
                                autocomplete="off"
                            >
                        </div>
                    </div>
                    <div class="col-md-5 col-12 text-md-end text-start">
                        <span class="badge badge-pill badge-primary px-3 py-2" style="font-size: 0.85rem;">
                            <span id="shortcuts-count">{{ count($activeKeys) }}</span> / 6 seleccionados
                        </span>
                    </div>
                </div>

                <div class="alert alert-light border p-2 mb-3 small text-muted d-flex align-items-center">
                    <i class="fas fa-info-circle text-info me-2 fs-5"></i>
                    <span>Selecciona hasta 6 accesos directos para tenerlos fijos en la barra superior. Se muestran todos los módulos a los que tu usuario tiene permiso.</span>
                </div>

                <form id="form-shortcuts">
                    @foreach($availableGrouped as $category => $items)
                        <div class="shortcut-category-group mb-4" data-category="{{ strtolower($category) }}">
                            <h6 class="text-primary font-weight-bold text-uppercase border-bottom pb-1 mb-2 d-flex align-items-center" style="font-size: 0.82rem; letter-spacing: 0.5px;">
                                <i class="fas fa-folder-open me-2 text-secondary"></i> {{ $category }}
                                <span class="badge badge-light text-muted ms-2">{{ count($items) }}</span>
                            </h6>
                            <div class="row g-2">
                                @foreach($items as $item)
                                    <div class="col-md-6 col-12 mb-2 shortcut-item-col" data-search="{{ strtolower($item['label'] . ' ' . $item['short_label'] . ' ' . $item['route'] . ' ' . $category) }}">
                                        <label class="shortcut-option-card d-flex align-items-center p-2 rounded border h-100 mb-0 cursor-pointer" for="chk_shortcut_{{ $item['key'] }}">
                                            <div class="custom-control custom-checkbox me-2">
                                                <input 
                                                    type="checkbox" 
                                                    class="custom-control-input shortcut-checkbox" 
                                                    id="chk_shortcut_{{ $item['key'] }}" 
                                                    value="{{ $item['key'] }}"
                                                    {{ in_array($item['key'], $activeKeys) ? 'checked' : '' }}
                                                >
                                                <label class="custom-control-label" for="chk_shortcut_{{ $item['key'] }}"></label>
                                            </div>
                                            <div class="shortcut-icon-preview rounded-circle d-flex align-items-center justify-content-center me-2 flex-shrink-0" style="width: 34px; height: 34px; background: rgba(0,0,0,0.04); color: {{ $item['color'] ?? '#007bff' }}; font-size: 1rem;">
                                                <i class="{{ $item['icon'] }}"></i>
                                            </div>
                                            <div class="flex-grow-1 overflow-hidden">
                                                <div class="font-weight-bold text-dark text-truncate" style="font-size: 0.88rem;">
                                                    {{ $item['label'] }}
                                                </div>
                                                <div class="text-muted small text-truncate" style="font-size: 0.73rem;">
                                                    {{ $item['route'] }}
                                                </div>
                                            </div>
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                    <div id="no-shortcuts-found" class="text-center py-4 text-muted d-none">
                        <i class="fas fa-search me-2"></i> No se encontraron menús que coincidan con la búsqueda.
                    </div>
                </form>
            </div>

            <div class="modal-footer bg-light d-flex justify-content-between py-2 px-4">
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-reset-shortcuts">
                    <i class="fas fa-undo me-1"></i> Valores por Defecto
                </button>
                <div>
                    <button type="button" class="btn btn-secondary btn-sm me-2" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm px-4" id="btn-save-shortcuts">
                        <i class="fas fa-save me-1"></i> Guardar Cambios
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .shortcut-option-card {
        background-color: #ffffff;
        transition: all 0.2s ease;
        cursor: pointer;
        user-select: none;
    }
    .shortcut-option-card:hover {
        background-color: #f8f9fa;
        border-color: #007bff !important;
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    .dark-mode .shortcut-option-card {
        background-color: #343a40;
        border-color: #495057 !important;
    }
    .dark-mode .shortcut-option-card:hover {
        background-color: #3f474e;
        border-color: #66a0d6 !important;
    }
    .dark-mode .shortcut-option-card .text-dark {
        color: #f8f9fa !important;
    }
    .dark-mode .shortcut-icon-preview {
        background: rgba(255,255,255,0.08) !important;
    }
    .dark-mode #shortcut-search {
        background-color: #343a40;
        color: #ffffff;
        border-color: #495057;
    }
    .dark-mode .input-group-text {
        background-color: #343a40 !important;
        border-color: #495057 !important;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const maxShortcuts = 6;
        const defaultKeys = @json($defaultKeys);
        
        function updateShortcutsCount() {
            const checked = document.querySelectorAll('.shortcut-checkbox:checked');
            const countEl = document.getElementById('shortcuts-count');
            if (countEl) {
                countEl.textContent = checked.length;
                if (checked.length >= maxShortcuts) {
                    countEl.parentElement.classList.remove('badge-primary');
                    countEl.parentElement.classList.add('badge-warning');
                } else {
                    countEl.parentElement.classList.remove('badge-warning');
                    countEl.parentElement.classList.add('badge-primary');
                }
            }
        }

        // Live search filter
        const searchInput = document.getElementById('shortcut-search');
        if (searchInput) {
            searchInput.addEventListener('input', function () {
                const term = this.value.toLowerCase().trim();
                let totalVisible = 0;

                document.querySelectorAll('.shortcut-category-group').forEach(group => {
                    let groupVisibleCount = 0;
                    group.querySelectorAll('.shortcut-item-col').forEach(col => {
                        const searchData = col.getAttribute('data-search') || '';
                        if (!term || searchData.includes(term)) {
                            col.classList.remove('d-none');
                            groupVisibleCount++;
                            totalVisible++;
                        } else {
                            col.classList.add('d-none');
                        }
                    });

                    if (groupVisibleCount === 0) {
                        group.classList.add('d-none');
                    } else {
                        group.classList.remove('d-none');
                    }
                });

                const noResults = document.getElementById('no-shortcuts-found');
                if (noResults) {
                    if (totalVisible === 0) {
                        noResults.classList.remove('d-none');
                    } else {
                        noResults.classList.add('d-none');
                    }
                }
            });
        }

        document.querySelectorAll('.shortcut-checkbox').forEach(cb => {
            cb.addEventListener('change', function () {
                const checked = document.querySelectorAll('.shortcut-checkbox:checked');
                if (checked.length > maxShortcuts) {
                    this.checked = false;
                    if (typeof swal === 'function') {
                        swal({
                            title: 'Límite alcanzado',
                            text: 'Puedes seleccionar un máximo de ' + maxShortcuts + ' accesos directos para mantener la barra limpia y rápida.',
                            icon: 'info',
                            button: 'Entendido'
                        });
                    } else {
                        alert('Puedes seleccionar un máximo de ' + maxShortcuts + ' accesos directos.');
                    }
                }
                updateShortcutsCount();
            });
        });

        // Reset to default button
        const btnReset = document.getElementById('btn-reset-shortcuts');
        if (btnReset) {
            btnReset.addEventListener('click', function () {
                document.querySelectorAll('.shortcut-checkbox').forEach(cb => {
                    cb.checked = defaultKeys.includes(cb.value);
                });
                updateShortcutsCount();
            });
        }

        // Save button
        const btnSave = document.getElementById('btn-save-shortcuts');
        if (btnSave) {
            btnSave.addEventListener('click', function () {
                const checkedBoxes = Array.from(document.querySelectorAll('.shortcut-checkbox:checked'));
                const selected = checkedBoxes.map(cb => cb.value);

                btnSave.disabled = true;
                btnSave.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i> Guardando...';

                fetch('{{ route("user.theme.update") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        key: 'shortcuts',
                        value: selected
                    })
                })
                .then(response => response.json())
                .then(data => {
                    btnSave.disabled = false;
                    btnSave.innerHTML = '<i class="fas fa-save me-1"></i> Guardar Cambios';
                    
                    if (data.success) {
                        $('#shortcutsModal').modal('hide');
                        if (typeof noty === 'function') {
                            noty('Accesos directos actualizados correctamente', 1);
                        }
                        // Recargar página para renderizar la nueva cinta de forma limpia
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        alert('No se pudieron guardar las preferencias.');
                    }
                })
                .catch(err => {
                    btnSave.disabled = false;
                    btnSave.innerHTML = '<i class="fas fa-save me-1"></i> Guardar Cambios';
                    console.error('Error al guardar shortcuts:', err);
                    alert('Error de conexión al guardar.');
                });
            });
        }
    });
</script>
