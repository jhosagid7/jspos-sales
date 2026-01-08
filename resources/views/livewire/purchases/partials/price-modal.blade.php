<div class="modal fade" id="priceModal" tabindex="-1" role="dialog" wire:ignore.self>
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Gestión de Precios</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Precio</th>
                                <th>Margen %</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($editingProductPrices as $index => $priceRow)
                            <tr>
                                <td>
                                    @if($index === 0)
                                        <span class="badge badge-primary">Principal</span>
                                    @else
                                        <span class="badge badge-secondary">Secundario</span>
                                    @endif
                                </td>
                                <td>
                                    <input type="number" class="form-control" 
                                           wire:model="editingProductPrices.{{ $index }}.price" 
                                           wire:change="updateModalMargin({{ $index }})">
                                </td>
                                <td>
                                    {{ $priceRow['margin'] }}%
                                </td>
                                <td>
                                    @if($index > 0)
                                    <button class="btn btn-danger btn-sm" wire:click="removePriceRow({{ $index }})">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                    @endif
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <button class="btn btn-primary mt-2" wire:click="addPriceRow">
                    <i class="fa fa-plus"></i> Agregar Precio
                </button>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-success" wire:click="savePrices">Guardar Cambios</button>
            </div>
        </div>
    </div>
</div>
