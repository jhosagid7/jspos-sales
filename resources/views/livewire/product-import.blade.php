<div class="card">
    <div class="card-header">
        <h3 class="card-title">Importador Inteligente de Productos (Excel)</h3>
    </div>
    <div class="card-body">
        
        {{-- Success Message --}}
        @if ($step === 3)
            <div class="alert alert-success text-center">
                <h2><i class="fas fa-check-circle"></i> ¡Importación Completada!</h2>
                <p class="lead">Se han importado <b>{{ $successCount }}</b> productos correctamente.</p>
                
                @if(count($importErrors) > 0)
                    <div class="alert alert-warning text-left mt-3">
                        <h5>Advertencias:</h5>
                        <ul>
                            @foreach($importErrors as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                <button wire:click="$set('step', 1)" class="btn btn-primary mt-3">Importar Otro Archivo</button>
                <a href="{{ route('products') }}" class="btn btn-secondary mt-3">Ir a Productos</a>
            </div>
        @endif

        {{-- Step 1: Upload --}}
        @if ($step === 1)
            <div 
                class="border-2 border-dashed p-5 text-center rounded d-flex flex-column align-items-center justify-content-center"
                style="border-style: dashed; border-color: #ccc; min-height: 200px; cursor: pointer;"
                onclick="document.getElementById('fileInput').click()"
            >
                @if ($file)
                    <h4 class="text-success"><i class="fas fa-file-excel"></i> {{ $file->getClientOriginalName() }}</h4>
                    <p>Archivo seleccionado. Procesando cabeceras...</p>
                @else
                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                    <h4>Arrastra tu archivo Excel aquí</h4>
                    <p class="text-muted">o haz clic para buscar (.xlsx, .csv)</p>
                @endif
                
                <input 
                    type="file" 
                    id="fileInput" 
                    wire:model="file" 
                    class="d-none" 
                    accept=".xlsx, .xls, .csv"
                >
                
                <div wire:loading wire:target="file">
                    <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    Subiendo y Leyendo...
                </div>
            </div>

            @error('file') <span class="text-danger d-block mt-2">{{ $message }}</span> @enderror
        @endif

        {{-- Step 2: Mapping --}}
        @if ($step === 2)
            <div class="row">
                <div class="col-md-12 mb-3">
                    <div class="alert alert-info">
                        <i class="fas fa-magic"></i> <b>Mapeo Inteligente:</b> Hemos intentado conectar tus columnas automáticamente. Por favor verifica que los campos sean correctos.
                    </div>
                </div>

                <div class="col-md-12">
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th style="width: 40%">Campo del Sistema</th>
                                <th style="width: 5%"></th>
                                <th style="width: 55%">Columna en tu Excel</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($systemFields as $field => $label)
                                <tr>
                                    <td class="align-middle">
                                        <label class="font-weight-bold mb-0">{{ $label }}</label>
                                        @if($field == 'name') <small class="text-danger">*</small> @endif
                                    </td>
                                    <td class="text-center align-middle"><i class="fas fa-arrow-right text-muted"></i></td>
                                    <td>
                                        <select wire:model="mapping.{{ $field }}" class="form-control @if(isset($mapping[$field])) is-valid @endif">
                                            <option value="">-- Ignorar este campo --</option>
                                            @foreach($headers as $header)
                                                <option value="{{ $header }}">{{ $header }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="col-md-12 text-center mt-4">
                    <button wire:click="$set('step', 1)" class="btn btn-secondary mr-2">Cancelar</button>
                    <button wire:click="import" class="btn btn-success btn-lg" wire:loading.attr="disabled">
                        <span wire:loading wire:target="import" class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                        <i class="fas fa-file-import"></i> Importar Productos
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
