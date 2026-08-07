<div class="card shadow-sm border-0 mt-3">
    <div class="card-header bg-light border-0 py-3 d-flex justify-content-between align-items-center">
        <h5 class="card-title m-0 text-dark font-weight-bold">
            <i class="fas fa-trophy me-2 text-warning"></i> Configuración de Metas de Ventas y Comisiones
        </h5>
        <div class="btn-group btn-group-sm" role="group">
            <button type="button" wire:click="$set('activeSubTab', 'goals')" class="btn {{ $activeSubTab === 'goals' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="fas fa-list me-1"></i> Catálogo de Metas
            </button>
            <button type="button" wire:click="$set('activeSubTab', 'assignments')" class="btn {{ $activeSubTab === 'assignments' ? 'btn-primary' : 'btn-outline-primary' }}">
                <i class="fas fa-users-cog me-1"></i> Asignación a Vendedores
            </button>
        </div>
    </div>

    <div class="card-body">
        @if($activeSubTab === 'goals')
            {{-- Formulario para crear / editar metas --}}
            <div class="bg-light p-3 rounded mb-4 border">
                <h6 class="font-weight-bold text-dark mb-3">
                    <i class="fas {{ $isEditing ? 'fa-edit text-info' : 'fa-plus-circle text-success' }} me-2"></i>
                    {{ $isEditing ? 'Editar Meta de Ventas' : 'Crear Nueva Meta de Ventas' }}
                </h6>
                <form wire:submit.prevent="saveGoal">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label small font-weight-bold">Nombre de la Meta <span class="text-danger">*</span></label>
                            <input type="text" wire:model="name" class="form-control form-control-sm" placeholder="Ej: Mini meta, Maxi meta">
                            @error('name') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small font-weight-bold">Meta a Vender ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" wire:model="target_amount" class="form-control form-control-sm" placeholder="Ej: 375.00">
                            @error('target_amount') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small font-weight-bold">Premio / Comisión ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" wire:model="reward_amount" class="form-control form-control-sm" placeholder="Ej: 25.00">
                            @error('reward_amount') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-2">
                            <label class="form-label small font-weight-bold">Frecuencia / Corte <span class="text-danger">*</span></label>
                            <select wire:model="periodicity" class="form-control form-control-sm">
                                <option value="diaria">Diaria</option>
                                <option value="semanal">Semanal</option>
                                <option value="quincenal">Quincenal</option>
                                <option value="mensual">Mensual</option>
                                <option value="trimestral">Trimestral</option>
                                <option value="anual">Anual</option>
                            </select>
                            @error('periodicity') <span class="text-danger small d-block">{{ $message }}</span> @enderror
                        </div>

                        <div class="col-md-3 text-end">
                            <button type="submit" class="btn btn-sm btn-success px-3">
                                <i class="fas fa-save me-1"></i> {{ $isEditing ? 'Actualizar' : 'Guardar Meta' }}
                            </button>
                            @if($isEditing)
                                <button type="button" wire:click="resetForm" class="btn btn-sm btn-secondary px-3 ms-1">
                                    Cancelar
                                </button>
                            @endif
                        </div>
                    </div>
                </form>
            </div>

            {{-- Tabla de Metas registradas --}}
            <div class="table-responsive">
                <table class="table table-hover table-striped align-middle text-start">
                    <thead class="bg-light">
                        <tr>
                            <th>Nombre de la Meta</th>
                            <th>Meta Requerida ($)</th>
                            <th>Premio / Comisión ($)</th>
                            <th>Periodicidad / Corte</th>
                            <th>Estado</th>
                            <th class="text-end">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($goals as $goal)
                            <tr>
                                <td class="font-weight-bold text-dark">{{ $goal->name }}</td>
                                <td><span class="badge bg-info text-dark font-weight-bold">$ {{ number_format($goal->target_amount, 2) }}</span></td>
                                <td><span class="badge bg-success font-weight-bold">$ {{ number_format($goal->reward_amount, 2) }}</span></td>
                                <td>
                                    <span class="badge bg-primary text-uppercase">{{ $goal->periodicity }}</span>
                                </td>
                                <td>
                                    <button wire:click="toggleGoalActive({{ $goal->id }})" class="btn btn-sm {{ $goal->is_active ? 'btn-success' : 'btn-secondary' }} py-0 px-2">
                                        {{ $goal->is_active ? 'Activa' : 'Inactiva' }}
                                    </button>
                                </td>
                                <td class="text-end">
                                    <button wire:click="editGoal({{ $goal->id }})" class="btn btn-sm btn-outline-info me-1 py-0 px-2">
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    <button wire:click="deleteGoal({{ $goal->id }})" onclick="confirm('¿Desea eliminar esta meta?') || event.stopImmediatePropagation()" class="btn btn-sm btn-outline-danger py-0 px-2">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">No hay metas de ventas registradas aún.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        @elseif($activeSubTab === 'assignments')
            {{-- Asignación a Vendedores --}}
            <div class="row mb-3 align-items-center">
                <div class="col-md-6">
                    <p class="text-muted small mb-0">
                        Marca las casillas de las metas que aplican a cada usuario. Los usuarios con metas activas asignadas aparecerán automáticamente en el selector de vendedor en la caja/POS.
                    </p>
                </div>
                <div class="col-md-6 text-end">
                    <input type="text" wire:model.live="userSearch" class="form-control form-control-sm d-inline-block w-auto" placeholder="Buscar usuario por nombre o correo...">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover table-bordered align-middle text-start">
                    <thead class="bg-light">
                        <tr>
                            <th>Usuario / Vendedor</th>
                            <th>Correo / Rol</th>
                            @foreach($goals as $goal)
                                <th class="text-center">
                                    <span class="d-block font-weight-bold">{{ $goal->name }}</span>
                                    <small class="text-muted">${{ number_format($goal->target_amount, 0) }} ({{ $goal->periodicity }})</small>
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($users as $usr)
                            @php
                                $assignedGoalIds = $usr->commissionGoals->pluck('id')->toArray();
                            @endphp
                            <tr>
                                <td class="font-weight-bold text-dark">{{ $usr->name }}</td>
                                <td class="small text-muted">{{ $usr->email }}</td>
                                @foreach($goals as $goal)
                                    <td class="text-center">
                                        <div class="form-check d-flex justify-content-center m-0">
                                            <input type="checkbox" 
                                                   class="form-check-input"
                                                   style="cursor: pointer; width: 18px; height: 18px;"
                                                   @if(in_array($goal->id, $assignedGoalIds)) checked @endif
                                                   wire:change="toggleUserGoalAssignment({{ $usr->id }}, {{ $goal->id }})">
                                        </div>
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($goals) + 2 }}" class="text-center text-muted py-4">No se encontraron usuarios.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
