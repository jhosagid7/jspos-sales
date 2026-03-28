<div>
    <div class="row sales layout-top-spacing">
        <div class="col-sm-12">
            <div class="card height-equal">
                <div class="card-header border-l-primary border-2">
                    <div class="row">
                        <div class="col-sm-12 col-md-8">
                            <h4 class="text-primary"><i class="fas fa-inbox"></i> Bandeja de Salida: Correo Electrónico</h4>
                            <p class="text-muted">Gestione los correos enviados o pendientes de revisión.</p>
                        </div>
                        <div class="col-sm-12 col-md-4 text-end">
                            <select wire:model.live="statusFilter" class="form-select form-select-sm d-inline-block w-auto">
                                <option value="">Todos los Estados</option>
                                <option value="pending">Pendientes</option>
                                <option value="sent">Enviados</option>
                                <option value="failed">Fallidos</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row align-items-center mb-3">
                        <div class="col-12 col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" wire:model.live="search" placeholder="Buscar por cliente o email..." class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-hover table-striped mt-1">
                            <thead class="table-primary">
                                <tr>
                                    <th>ID</th>
                                    <th class="text-center">FECHA</th>
                                    <th class="text-center">CLIENTE</th>
                                    <th class="text-center">EMAIL</th>
                                    <th class="text-center">TIPO</th>
                                    <th class="text-center">ASUNTO</th>
                                    <th class="text-center">ESTADO</th>
                                    <th class="text-center">ERRORES</th>
                                    <th class="text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($messages as $msg)
                                <tr>
                                    <td>{{ $msg->id }}</td>
                                    <td class="text-center">{{ $msg->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="text-center">{{ $msg->customer ? $msg->customer->name : 'N/A' }}</td>
                                    <td class="text-center">{{ $msg->email_address }}</td>
                                    <td class="text-center">
                                        @if($msg->related_model_type == \App\Models\Sale::class || str_contains($msg->attachment_path ?? '', 'factura'))
                                            <span class="badge badge-light-primary">Factura</span>
                                        @elseif($msg->related_model_type == \App\Models\Payment::class || str_contains($msg->attachment_path ?? '', 'recibo'))
                                            <span class="badge badge-light-info">Abono</span>
                                        @else
                                            <span class="badge badge-light-secondary">Mensaje</span>
                                        @endif
                                    </td>
                                    <td class="text-left" style="max-width: 250px;">
                                        <small>{{ $msg->subject }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if($msg->status == 'pending')
                                            <span class="badge badge-light-warning">En Cola</span>
                                        @elseif($msg->status == 'sent')
                                            <span class="badge badge-light-success">Enviado</span>
                                        @elseif($msg->status == 'failed')
                                            <span class="badge badge-light-danger">Fallido</span>
                                        @endif
                                    </td>
                                    <td class="text-center" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $msg->error_message }}">
                                        <small class="text-danger">{{ $msg->error_message }}</small>
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group" role="group">
                                            @if($msg->attachment_path && file_exists($msg->attachment_path))
                                                <a href="{{ route('email.download-pdf', $msg->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Ver PDF Adjunto">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            @endif
                                            
                                            <button wire:click="retryMessage({{ $msg->id }})" class="btn btn-sm btn-primary" title="Reenviar Correo">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center">No hay correos en la bandeja</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="d-flex justify-content-end mt-3">
                        {{ $messages->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
