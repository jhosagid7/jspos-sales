<div wire:ignore.self class="modal fade" id="modalUnitSelection" tabindex="-1" role="dialog" aria-labelledby="modalUnitSelectionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalUnitSelectionLabel">
                    <i class="fas fa-box-open mr-2"></i> Seleccionar Presentación
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                @if($selectedProductForUnits)
                    <h5 class="text-center mb-4">{{ $selectedProductForUnits->name }}</h5>
                    
                    <div class="list-group">
                        <!-- Default Unit -->
                        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                            wire:click="addProductWithUnit({{ $selectedProductForUnits->id }})">
                            <div>
                                <span class="font-weight-bold">Unidad (Default)</span>
                                <small class="d-block text-muted">Factor: 1</small>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-primary badge-pill">${{ number_format($selectedProductForUnits->price, 2) }}</span>
                                <small class="d-block mt-1">Stock: {{ $selectedProductForUnits->stock_qty }}</small>
                            </div>
                        </button>

                        <!-- Other Units -->
                        @foreach($selectedProductForUnits->units as $unit)
                            <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                                wire:click="addProductWithUnit({{ $selectedProductForUnits->id }}, {{ $unit->id }})">
                                <div>
                                    <span class="font-weight-bold">{{ $unit->unit_name }}</span>
                                    <small class="d-block text-muted">Factor: {{ $unit->conversion_factor }}</small>
                                </div>
                                <div class="text-right">
                                    <span class="badge badge-info badge-pill">${{ number_format($unit->price, 2) }}</span>
                                    @php
                                        $unitStock = floor($selectedProductForUnits->stock_qty / $unit->conversion_factor);
                                    @endphp
                                    <small class="d-block mt-1">Disp: {{ $unitStock }}</small>
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('show-unit-modal', (event) => {
            $('#modalUnitSelection').modal('show');
        });
        Livewire.on('close-unit-modal', (event) => {
            $('#modalUnitSelection').modal('hide');
        });
    });
</script>
