<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">Grupos de Precio</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{ route('welcome') }}">Inicio</a></li>
                        <li class="breadcrumb-item active">Grupos de Precio</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                {{-- Form --}}
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-layer-group mr-1"></i>
                                {{ $editingId ? 'Editar Grupo' : 'Nuevo Grupo' }}
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group">
                                <label>Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                                       wire:model.live="name" placeholder="Ej: Botellones">
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="form-group">
                                <label>Descripción</label>
                                <input type="text" class="form-control @error('description') is-invalid @enderror"
                                       wire:model.live="description" placeholder="Descripción opcional">
                                @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <button class="btn btn-primary" wire:click="save">
                                <i class="fas fa-save mr-1"></i>
                                {{ $editingId ? 'Actualizar' : 'Crear Grupo' }}
                            </button>
                            @if($editingId)
                                <button class="btn btn-secondary" wire:click="cancelEdit">
                                    <i class="fas fa-times mr-1"></i> Cancelar
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- List --}}
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list mr-1"></i> Grupos Registrados
                            </h5>
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-hover mb-0">
                                <thead class="thead-light">
                                    <tr>
                                        <th>Nombre</th>
                                        <th>Descripción</th>
                                        <th class="text-center">Productos</th>
                                        <th class="text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($groups as $group)
                                        <tr>
                                            <td><strong>{{ $group->name }}</strong></td>
                                            <td class="text-muted">{{ $group->description ?: '—' }}</td>
                                            <td class="text-center">
                                                <span class="badge badge-info">{{ $group->products_count }}</span>
                                            </td>
                                            <td class="text-right">
                                                <button class="btn btn-sm btn-warning" wire:click="edit({{ $group->id }})">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger"
                                                        wire:click="delete({{ $group->id }})"
                                                        wire:confirm="¿Eliminar '{{ $group->name }}'? Los productos vinculados quedarán sin grupo.">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-2x mb-2 d-block opacity-50"></i>
                                                No hay grupos creados aún.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
