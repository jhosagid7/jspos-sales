<div class="row sales layout-top-spacing">
    <div class="col-sm-12">
        <div class="widget widget-chart-one">
            <div class="widget-heading">
                <h4 class="card-title">
                    <b>Licencias SaaS | Generador Maestro</b>
                </h4>
            </div>

            <div class="widget-content">
                <div class="row">
                    <!-- Left Column: Inputs -->
                    <div class="col-sm-12 col-md-4">
                        <div class="form-group">
                            <label>Hardware ID del Cliente (Código de Instalación) *</label>
                            <input type="text" wire:model.defer="clientId" class="form-control" placeholder="Ej: JSPOS-ABC-123">
                            @error('clientId') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Días de Validez *</label>
                            <input type="number" wire:model.defer="days" class="form-control" placeholder="Ej: 30">
                            @error('days') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>

                        <div class="form-group">
                            <label>Cantidad Máxima de Computadoras *</label>
                            <input type="number" wire:model.defer="maxDevices" class="form-control" placeholder="Ej: 1">
                            <small class="text-muted">Pon 999 para ilimitado</small>
                            @error('maxDevices') <span class="text-danger er">{{ $message }}</span> @enderror
                        </div>
                        
                        <hr>
                        <h5>Preajustes (Combos Comerciales)</h5>
                        <div class="btn-group w-100 mt-2" role="group">
                            <button wire:click="setPreset('BASIC')" type="button" class="btn btn-outline-secondary">Limpiar (Básico)</button>
                            <button wire:click="setPreset('PRO')" type="button" class="btn btn-outline-primary">Plan Pro</button>
                            <button wire:click="setPreset('PREMIUM')" type="button" class="btn btn-outline-success">Premium</button>
                        </div>
                    </div>

                    <!-- Right Column: Modules -->
                    <div class="col-sm-12 col-md-8">
                        <h5 class="mb-3">Configuración de Módulos Opcionales</h5>
                        <div class="row">
                            @foreach($availableModules as $key => $name)
                            <div class="col-md-6 mb-2">
                                <div class="custom-control custom-switch">
                                    <input type="checkbox" wire:model="selectedModules" value="{{ $key }}" class="custom-control-input" id="switch_{{ $key }}">
                                    <label class="custom-control-label" for="switch_{{ $key }}">
                                        {{ $name }}
                                    </label>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        
                        <div class="alert alert-warning mt-3">
                            <strong>Atención:</strong> El módulo de <b>Producción</b> consumirá inventarios, por lo cual es estrictamente necesario habilitar el módulo de <b>Múltiples Depósitos</b> para activarlo.
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-sm-12 text-center">
                        <button wire:click.prevent="generate()" class="btn btn-dark btn-lg px-5">
                            <i class="fas fa-key"></i> Generar Licencia Encriptada
                        </button>
                    </div>
                </div>

                @if($generatedKey)
                <div class="row mt-4">
                    <div class="col-sm-12">
                        <div class="form-group">
                            <label class="d-flex justify-content-between align-items-center">
                                <span><strong>Llave de Licencia Generada:</strong> (Copia este texto y envíalo al cliente)</span>
                                <button type="button" class="btn btn-sm btn-outline-dark" onclick="copyGeneratedLicense()" title="Copiar Licencia">
                                    <i class="far fa-copy" id="genCopyIcon"></i> Copiar
                                </button>
                            </label>
                            <textarea id="generatedLicenseText" class="form-control font-weight-bold text-success" rows="6" readonly style="font-family: monospace; background-color: #f8f9fa;">{{ $generatedKey }}</textarea>
                        </div>
                    </div>
                </div>

                @endif

                <script>
                    function copyGeneratedLicense() {
                        var textElement = document.getElementById("generatedLicenseText");
                        if (!textElement) return; // safeguard
                        var text = textElement.value;
                        
                        var textArea = document.createElement("textarea");
                        textArea.value = text;
                        textArea.style.top = "0";
                        textArea.style.left = "0";
                        textArea.style.position = "fixed";
                        textArea.style.opacity = "0";
                        document.body.appendChild(textArea);
                        
                        textArea.focus();
                        textArea.select();

                        try {
                            var successful = document.execCommand('copy');
                            if (successful) {
                                var icon = document.getElementById('genCopyIcon');
                                if (icon) {
                                    icon.classList.remove('fa-copy', 'far');
                                    icon.classList.add('fa-check', 'fas', 'text-success');
                                    
                                    setTimeout(function() {
                                        icon.classList.remove('fa-check', 'fas', 'text-success');
                                        icon.classList.add('fa-copy', 'far');
                                    }, 2000);
                                }
                                
                                if (typeof toastr !== 'undefined') {
                                    toastr.success('Licencia copiada al portapapeles');
                                } else if (typeof Noty !== 'undefined') {
                                    new Noty({
                                        type: 'success',
                                        text: 'Licencia Copiada',
                                        timeout: 2000
                                    }).show();
                                }
                            }
                        } catch (err) {
                            console.error('Fallback: Oops, unable to copy', err);
                        }

                        document.body.removeChild(textArea);
                    }
                </script>
                
            </div>
        </div>
    </div>
</div>
