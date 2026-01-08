<div>
    <div wire:ignore.self class="modal fade" id="modalOrderDetailNote" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="p-1 modal-header bg-primary">
                    <h5 class="modal-title">Editar notas para la Orden #{{ $order_id }}</h5>
                    <button class="py-0 btn-close" type="button" data-dismiss="modal" aria-label="Close" onclick="$('#modalOrderDetailNote').modal('hide')"></button>
                </div>

                <div class="modal-body">
                    <div class="form-group">
                        <label for="sale_note">Nota de Orden:</label>
                        <textarea class="form-control" id="order_note" wire:model="order_note"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button wire:click.prevent="saveOrderNote" class="btn btn-primary">Guardar Nota</button>
                    <button class="btn btn-dark " type="button" data-dismiss="modal" onclick="$('#modalOrderDetailNote').modal('hide')">Cerrar</button>


                </div>

            </div>
        </div>
    </div>
</div>
