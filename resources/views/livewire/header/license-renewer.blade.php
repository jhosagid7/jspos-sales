<div>
    {{-- Component hidden, only modal --}}

    {{-- Renewal Modal --}}
    <div wire:ignore.self class="modal fade" id="licenseRenewalModal" tabindex="-1" role="dialog" aria-labelledby="licenseRenewalModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <div class="modal-header bg-warning">
                    <h5 class="modal-title text-dark" id="licenseRenewalModalLabel">
                        <i class="fas fa-key"></i> Renovar Licencia
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <h4 class="text-secondary mb-1">Licencia de: <strong>{{ $businessName }}</strong></h4>
                        
                        @if((int)$daysRemaining <= 5)
                            <h3 class="text-danger mt-2">{{ $daysRemaining }} Días Restantes</h3>
                            <p class="text-danger font-weight-bold">¡Alerta! Su licencia está por expirar muy pronto. Ingrese una nueva clave para evitar la suspensión del servicio.</p>
                        @elseif((int)$daysRemaining <= 15)
                            <h3 class="text-warning mt-2">{{ $daysRemaining }} Días Restantes</h3>
                            <p class="text-muted">Su licencia expira en un par de semanas. Puede ir solicitando su renovación con anticipación.</p>
                        @else
                            <h3 class="text-success mt-2">{{ $daysRemaining }} Días Restantes</h3>
                            <p class="text-muted">Su sistema se encuentra operando con normalidad y su licencia está completamente activa.</p>
                        @endif
                    </div>

                    <div class="row mb-3">
                        <div class="col-12 text-center">
                            <span class="badge badge-primary text-uppercase px-3 py-2" style="font-size: 14px;">
                                <i class="fas fa-star text-warning"></i> PLAN ACTUAL: {{ $licenseType }}
                            </span>
                        </div>
                    </div>

                    <div class="alert alert-secondary py-3 px-3 mb-4 rounded d-flex justify-content-between align-items-center" style="border: 2px dashed #ccc;">
                        <div>
                            <span class="d-block font-weight-bold text-dark mb-1"><i class="fas fa-id-card"></i> ID de Cliente:</span>
                            <span class="text-monospace text-dark font-weight-bold" id="clientIdText" style="word-break: break-all; font-size: 1.1rem;">{{ $clientId }}</span>
                        </div>
                        <button type="button" class="btn btn-outline-dark ml-3" onclick="copyClientId()" title="Copiar ID" style="min-width: 45px;">
                            <i class="far fa-copy" id="copyIcon"></i>
                        </button>
                    </div>

                    <div class="form-group">
                        <label for="licenseKey">Clave de Licencia</label>
                        <textarea wire:model="licenseKey" class="form-control" id="licenseKey" rows="4" placeholder="Pegue aquí su licencia..."></textarea>
                        @error('licenseKey') <span class="text-danger small">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between">
                    <button type="button" class="btn btn-outline-secondary" wire:click="requestRenewal" wire:loading.attr="disabled">
                        <i class="fas fa-envelope"></i> Solicitar Renovación
                    </button>
                    <button type="button" class="btn btn-primary" wire:click="renew" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="renew">Activar Licencia</span>
                        <span wire:loading wire:target="renew">Verificando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:initialized', () => {
            @this.on('show-license-modal', () => {
                $('#licenseRenewalModal').modal('show');
            });

            @this.on('hide-license-modal', () => {
                $('#licenseRenewalModal').modal('hide');
            });
            
            @this.on('reload-page', () => {
                setTimeout(() => {
                    window.location.reload();
                }, 1500);
            });
        });

        function copyClientId() {
            var text = document.getElementById("clientIdText").innerText.trim();
            var modal = document.getElementById('licenseRenewalModal');
            
            // Fallback for older browsers or HTTP (non-secure) contexts
            var textArea = document.createElement("textarea");
            textArea.value = text;
            
            // Make it invisible but still part of the DOM flow for selection
            textArea.style.top = "0";
            textArea.style.left = "0";
            textArea.style.position = "fixed";
            textArea.style.opacity = "0";

            // Append to the modal to prevent Bootstrap Focus Trap from breaking selection
            if(modal) {
                modal.appendChild(textArea);
            } else {
                document.body.appendChild(textArea);
            }
            
            textArea.focus();
            textArea.select();

            try {
                var successful = document.execCommand('copy');
                if (successful) {
                    var icon = document.getElementById('copyIcon');
                    icon.classList.remove('fa-copy', 'far');
                    icon.classList.add('fa-check', 'fas', 'text-success');
                    
                    if (typeof toastr !== 'undefined') {
                        toastr.success('ID de Cliente copiado exitosamente');
                    }
                    
                    setTimeout(function() {
                        icon.classList.remove('fa-check', 'fas', 'text-success');
                        icon.classList.add('fa-copy', 'far');
                    }, 2000);
                }
            } catch (err) {
                console.error('Fallback: Oops, unable to copy', err);
            }

            if(modal) {
                modal.removeChild(textArea);
            } else {
                document.body.removeChild(textArea);
            }
        }
    </script>
</div>
