<div>
    <div class="row sales layout-top-spacing">
        <div class="col-sm-12">
            <div class="widget widget-chart-one">
                <div class="widget-heading">
                    <h4 class="card-title">
                        <b>Bandeja de WhatsApp</b>
                    </h4>
                    <ul class="tabs tab-pills">
                        <li>
                            <select wire:model.live="statusFilter" class="form-control form-control-sm mr-2">
                                <option value="">Todos los Estados</option>
                                <option value="pending">Pendientes</option>
                                <option value="sent">Enviados</option>
                                <option value="failed">Fallidos</option>
                            </select>
                        </li>
                    </ul>
                </div>

                <div class="widget-content">
                    <div class="row align-items-center mb-3">
                        <div class="col-12 col-md-4">
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                </div>
                                <input type="text" wire:model.live="search" placeholder="Buscar por cliente o teléfono..." class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped mt-1">
                            <thead class="text-white" style="background: #3B3F5C">
                                <tr>
                                    <th class="table-th text-white">ID</th>
                                    <th class="table-th text-white text-center">FECHA</th>
                                    <th class="table-th text-white text-center">CLIENTE</th>
                                    <th class="table-th text-white text-center">TELÉFONO</th>
                                    <th class="table-th text-white text-center">TIPO</th>
                                    <th class="table-th text-white text-center">MENSAJE</th>
                                    <th class="table-th text-white text-center">ESTADO</th>
                                    <th class="table-th text-white text-center">ERRORES</th>
                                    <th class="table-th text-white text-center">ACCIONES</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($messages as $msg)
                                <tr>
                                    <td><h6>{{ $msg->id }}</h6></td>
                                    <td class="text-center"><h6>{{ $msg->created_at->format('d/m/Y H:i') }}</h6></td>
                                    <td class="text-center"><h6>{{ $msg->customer ? $msg->customer->name : 'N/A' }}</h6></td>
                                    <td class="text-center"><h6>{{ $msg->phone_number }}</h6></td>
                                    <td class="text-center">
                                        <h6>
                                            @if($msg->related_model_type == \App\Models\Sale::class || str_contains($msg->attachment_path ?? '', 'factura'))
                                                <span class="badge badge-primary">Factura</span>
                                            @elseif($msg->related_model_type == \App\Models\Payment::class || str_contains($msg->attachment_path ?? '', 'recibo'))
                                                <span class="badge badge-info">Abono</span>
                                            @else
                                                <span class="badge badge-secondary">Mensaje</span>
                                            @endif
                                        </h6>
                                    </td>
                                    <td class="text-left" style="max-width: 300px; padding: 10px;">
                                        <div x-data="{ expanded: false }">
                                            <div x-show="!expanded" style="white-space: normal; line-height: 1.2;">
                                                <h6 style="margin-bottom:0;"><small>{{ mb_strimwidth($msg->message_body, 0, 75, '...') }} 
                                                    @if(strlen($msg->message_body) > 75)
                                                        <a href="#" @click.prevent="expanded = true" class="text-primary font-weight-bold">ver más</a>
                                                    @endif
                                                </small></h6>
                                            </div>
                                            <div x-show="expanded" style="display: none; white-space: normal; line-height: 1.2;">
                                                <h6 style="margin-bottom:0;"><small>{{ $msg->message_body }} <a href="#" @click.prevent="expanded = false" class="text-danger font-weight-bold">ver menos</a></small></h6>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <h6>
                                            @if($msg->status == 'pending')
                                                <span class="badge badge-warning">En Cola</span>
                                            @elseif($msg->status == 'sent')
                                                <span class="badge badge-success">Enviado</span>
                                            @elseif($msg->status == 'failed')
                                                <span class="badge badge-danger">Fallido</span>
                                            @endif
                                        </h6>
                                    </td>
                                    <td class="text-center" style="max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $msg->error_message }}">
                                        <h6><small class="text-danger">{{ $msg->error_message }}</small></h6>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center align-items-center" style="gap: 5px;">
                                            @if($msg->attachment_path && file_exists($msg->attachment_path))
                                                <a href="{{ route('whatsapp.download-pdf', $msg->id) }}" target="_blank" class="btn btn-sm btn-outline-danger" title="Ver PDF Adjunto">
                                                    <i class="fas fa-file-pdf"></i>
                                                </a>
                                            @endif
                                            
                                            <button wire:click="retryMessage({{ $msg->id }})" class="btn btn-sm btn-dark" title="Reenviar Mensaje">
                                                <i class="fas fa-paper-plane"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
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
