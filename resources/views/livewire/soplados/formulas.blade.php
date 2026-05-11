<div>
    <div class="row sales layout-top-spacing">
        <div class="col-sm-12 col-md-4">
            <div class="widget widget-chart-one">
                <div class="widget-heading">
                    <h4 class="card-title">
                        <b>Configurar Receta</b>
                    </h4>
                </div>
                <div class="widget-content">
                    <div class="form-group">
                        <label>Producto Terminado (Botellón)</label>
                        <select wire:model.live="product_id" class="form-control">
                            <option value="">Seleccione...</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Insumo (Preforma / Tapa)</label>
                        <select wire:model="ingredient_id" class="form-control">
                            <option value="">Seleccione...</option>
                            @foreach($products as $p)
                                <option value="{{ $p->id }}">{{ $p->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Cantidad necesaria (por 1 unidad)</label>
                        <input type="number" step="0.0001" wire:model="quantity" class="form-control">
                    </div>

                    <button wire:click="store" class="btn btn-primary btn-block">Guardar Ingrediente</button>
                </div>
            </div>
        </div>

        <div class="col-sm-12 col-md-8">
            <div class="widget widget-chart-one">
                <div class="widget-heading">
                    <h4 class="card-title">
                        <b>Listado de Recetas (Fórmulas)</b>
                    </h4>
                </div>
                <div class="row mb-3 mt-3">
                    <div class="col-sm-12">
                        <input type="text" wire:model.live="search" class="form-control" placeholder="Buscar por producto...">
                    </div>
                </div>
                <div class="widget-content">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="text-white" style="background: #3B3F5C">
                                <tr>
                                    <th class="table-th text-white">PRODUCTO FINAL</th>
                                    <th class="table-th text-white text-center">INSUMO</th>
                                    <th class="table-th text-white text-center">CANTIDAD</th>
                                    <th class="table-th text-white text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($formulas as $f)
                                <tr>
                                    <td><h6>{{ $f->product->name }}</h6></td>
                                    <td class="text-center"><h6>{{ $f->ingredient->name }}</h6></td>
                                    <td class="text-center"><h6>{{ number_format($f->quantity, 2) }}</h6></td>
                                    <td class="text-center">
                                        <button wire:click="delete({{ $f->id }})" class="btn btn-danger btn-sm">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        {{ $formulas->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
