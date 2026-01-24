<div>
    <div class="row g-3">
        {{-- Add New Item Form --}}
        <h6 class="border-bottom pb-2">Agregar Nueva Unidad/Bobina</h6>
        <div class="col-md-3">
            <label class="form-label">Peso (Kg/Cant)</label>
            <div class="input-group">
                <span class="input-group-text"><i class="fa fa-balance-scale"></i></span>
                <input wire:model="weight" type="number" step="0.01" class="form-control" placeholder="0.00">
            </div>
            @error('weight') <span class="text-danger small">{{ $message }}</span> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Color (Opcional)</label>
            <input wire:model="color" type="text" class="form-control" placeholder="Ej: Rojo, Azul">
            @error('color') <span class="text-danger small">{{ $message }}</span> @enderror
        </div>
        <div class="col-md-3">
            <label class="form-label">Lote/Batch (Opcional)</label>
            <input wire:model="batch" type="text" class="form-control" placeholder="Lote #">
        </div>
        <div class="col-md-3">
             <label class="form-label">Depósito</label>
             <select wire:model="warehouse_id" class="form-control form-select">
                 @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                 @endforeach
             </select>
             @error('warehouse_id') <span class="text-danger small">{{ $message }}</span> @enderror
        </div>
        <div class="col-12 text-end">
            <button wire:click="saveItem" class="btn btn-primary">
                <i class="fa fa-plus me-1"></i> Agregar Item
            </button>
        </div>
    </div>

    <hr>

    {{-- List Items --}}
    <h6 class="mb-3">Items Disponibles</h6>
    <div class="table-responsive">
        <table class="table table-bordered table-striped table-hover">
            <thead class="table-light">
                <tr>
                    <th>ID</th>
                    <th>Peso (Kg)</th>
                    <th>Original (Kg)</th>
                    <th>Color</th>
                    <th>Lote</th>
                    <th>Depósito</th>
                    <th>Estado</th>
                    <th>Fecha Ingreso</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($items as $item)
                    <tr>
                        <td>#{{ $item->id }}</td>
                        <td class="fw-bold">{{ floatval($item->quantity) }}</td>
                        <td class="text-muted">{{ floatval($item->original_quantity) }}</td>
                        <td>{{ $item->color ?? '-' }}</td>
                        <td>{{ $item->batch ?? '-' }}</td>
                        <td>{{ $item->warehouse->name ?? 'N/A' }}</td>
                        <td>
                            <span class="badge bg-success">Disponible</span>
                        </td>
                        <td>{{ $item->created_at->format('d/m/Y H:i') }}</td>
                        <td>
                            <button wire:click="deleteItem({{ $item->id }})" 
                                    wire:confirm="¿Estás seguro de eliminar este item? Esta acción es irreversible."
                                    class="btn btn-sm btn-danger" title="Eliminar">
                                <i class="fa fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            No hay items registrados para este producto.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
