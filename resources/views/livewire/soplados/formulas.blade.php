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
                    <div class="form-group position-relative">
                        <label>Producto Terminado (Botellón)</label>
                        <div class="input-group">
                            <input type="text" wire:model.live.debounce.300ms="search_product" class="form-control" placeholder="Buscar producto...">
                            @if($product_id)
                                <button class="btn btn-outline-danger" wire:click="$set('product_id', null); $set('search_product', '')">
                                    <i class="fa fa-times"></i>
                                </button>
                            @endif
                        </div>
                        @if(!empty($product_results))
                            <ul class="list-group w-100 shadow" style="position: absolute; z-index: 999;">
                                @foreach($product_results as $p)
                                    <li wire:click="selectProduct({{ $p->id }}, '{{ $p->name }}')" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" style="cursor: pointer;">
                                        <span>{{ $p->name }}</span>
                                        <span class="badge badge-primary">Elegir</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>

                    <div class="form-group position-relative">
                        <label>Insumo (Preforma / Tapa)</label>
                        <div class="input-group">
                            <input type="text" wire:model.live.debounce.300ms="search_ingredient" class="form-control" placeholder="Buscar insumo...">
                            @if($ingredient_id)
                                <button class="btn btn-outline-danger" wire:click="$set('ingredient_id', null); $set('search_ingredient', '')">
                                    <i class="fa fa-times"></i>
                                </button>
                            @endif
                        </div>
                        @if(!empty($ingredient_results))
                            <ul class="list-group w-100 shadow" style="position: absolute; z-index: 999;">
                                @foreach($ingredient_results as $p)
                                    <li wire:click="selectIngredient({{ $p->id }}, '{{ $p->name }}')" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center" style="cursor: pointer;">
                                        <span>{{ $p->name }}</span>
                                        <span class="badge badge-primary">Elegir</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
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
                                    <td><h6>{{ $f->product->name ?? 'Producto Eliminado' }}</h6></td>
                                    <td class="text-center"><h6>{{ $f->ingredient->name ?? 'Insumo Eliminado' }}</h6></td>
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
