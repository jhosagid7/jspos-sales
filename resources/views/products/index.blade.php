@extends('layouts.app')
@section('title', 'Catálogo de Bolsas')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h3 class="fw-bold mb-1">🛍️ Catálogo de Bolsas de Fábrica</h3>
        <p class="text-white-50 mb-0">Productos y medidas disponibles para pesaje y registro de producción</p>
    </div>
    <button class="btn btn-primary fw-bold" data-bs-toggle="modal" data-bs-target="#createProductModal">
        <i class="bi bi-plus-circle-fill me-1"></i> Registrar Nueva Bolsa
    </button>
</div>

<div class="card-custom">
    <div class="table-responsive">
        <table class="table table-custom mb-0">
            <thead>
                <tr>
                    <th>Nombre de la Bolsa / Medida</th>
                    <th>Código / SKU</th>
                    <th>Costo Estimado</th>
                    <th>Precio Base</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $p)
                    <tr>
                        <td class="fw-bold text-white">{{ $p->name }}</td>
                        <td><span class="badge bg-secondary">{{ $p->sku }}</span></td>
                        <td>${{ number_format($p->cost, 2) }}</td>
                        <td class="text-success fw-bold">${{ number_format($p->price, 2) }}</td>
                        <td>
                            @if($p->is_active)
                                <span class="badge bg-success">Activo</span>
                            @else
                                <span class="badge bg-danger">Inactivo</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <button class="btn btn-outline-info btn-sm me-1" data-bs-toggle="modal" data-bs-target="#editProductModal{{ $p->id }}">
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <form action="{{ route('products.destroy', $p->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>

                    <!-- Edit Modal -->
                    <div class="modal fade" id="editProductModal{{ $p->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('products.update', $p->id) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header border-secondary-subtle">
                                        <h5 class="modal-title fw-bold">Editar Bolsa</h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label small text-white-50">Descripción / Nombre</label>
                                            <input type="text" name="name" class="form-control" value="{{ $p->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label small text-white-50">Código / SKU</label>
                                            <input type="text" name="sku" class="form-control" value="{{ $p->sku }}" required>
                                        </div>
                                        <div class="row g-2 mb-3">
                                            <div class="col">
                                                <label class="form-label small text-white-50">Costo ($)</label>
                                                <input type="number" step="0.01" name="cost" class="form-control" value="{{ $p->cost }}" required>
                                            </div>
                                            <div class="col">
                                                <label class="form-label small text-white-50">Precio ($)</label>
                                                <input type="number" step="0.01" name="price" class="form-control" value="{{ $p->price }}" required>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer border-secondary-subtle">
                                        <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-primary btn-sm fw-bold">Guardar Cambios</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

<!-- Create Modal -->
<div class="modal fade" id="createProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('products.store') }}" method="POST">
                @csrf
                <div class="modal-header border-secondary-subtle">
                    <h5 class="modal-title fw-bold">Registrar Nueva Bolsa</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small text-white-50">Nombre / Medida de la Bolsa</label>
                        <input type="text" name="name" class="form-control" placeholder="Ej. Bolsa Negra 70x100 Pesada" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small text-white-50">Código / SKU</label>
                        <input type="text" name="sku" class="form-control" placeholder="Ej. BN-70100" required>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col">
                            <label class="form-label small text-white-50">Costo Estimado ($)</label>
                            <input type="number" step="0.01" name="cost" class="form-control" value="0.00" required>
                        </div>
                        <div class="col">
                            <label class="form-label small text-white-50">Precio Base ($)</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="0.00" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer border-secondary-subtle">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm fw-bold">Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
