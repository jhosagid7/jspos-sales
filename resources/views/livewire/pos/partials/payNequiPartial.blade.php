<div wire:ignore.self class="modal fade" id="modalNequiPartial" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-info">
                <h5 class="modal-title">Abono con Nequi</h5>
                <button class="btn-close" type="button" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="nequiPhoneNumber" class="form-label">Número de Teléfono</label>
                    <input type="text" class="form-control" wire:model="nequiPhoneNumber" id="nequiPhoneNumber">
                </div>
                <div class="mb-3">
                    <label for="nequiAmount" class="form-label">Monto</label>
                    <input type="number" class="form-control" wire:model="nequiAmount" id="nequiAmount">
                </div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
                <button class="btn btn-primary" wire:click="addNequiPayment" type="button">Agregar
                    Abono</button>
            </div>
        </div>
    </div>
</div>
</div>
