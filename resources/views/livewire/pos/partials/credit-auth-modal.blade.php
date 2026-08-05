<!-- Modal para Autorización de Crédito -->
<div wire:ignore.self class="modal fade" id="creditAuthModal" tabindex="-1" role="dialog" aria-labelledby="creditAuthModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold" id="creditAuthModalLabel">
                    <i class="fas fa-lock me-2"></i> Autorización de Crédito Requerida
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close" wire:click="$set('showCreditAuthModal', false)"></button>
            </div>
            
            <div class="modal-body p-4 text-center">
                <div class="mb-4">
                    <i class="fas fa-exclamation-triangle text-warning fa-3x mb-3"></i>
                    <h5 class="text-dark">{{ $creditAuthStatusMessage }}</h5>
                    <p class="text-muted">El crédito ha sido bloqueado por el sistema. Puede solicitar una autorización especial para esta factura.</p>
                </div>

                @if(!$pendingCreditAuthId)
                    <button type="button" class="btn btn-warning btn-lg w-100 fw-bold" wire:click="requestCreditAuthorization" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="requestCreditAuthorization">
                            <i class="fas fa-paper-plane me-2"></i> Solicitar Autorización a Supervisores
                        </span>
                        <span wire:loading wire:target="requestCreditAuthorization">
                            <i class="fas fa-spinner fa-spin me-2"></i> Enviando...
                        </span>
                    </button>
                @else
                    <div class="bg-light p-3 rounded border border-warning mb-3">
                        <p class="mb-2 font-weight-bold text-success"><i class="fas fa-check-circle me-1"></i> Solicitud Enviada</p>
                        <p class="small text-muted mb-3">Se ha enviado un PIN de 6 caracteres a los supervisores por correo/WhatsApp.</p>
                        
                        <div class="form-group mb-0">
                            <label class="form-label font-weight-bold">Ingrese el PIN de Autorización Recibido</label>
                            <input type="text" wire:model.defer="creditAuthPin" class="form-control form-control-lg text-center font-weight-bold ls-2 mb-2" placeholder="Ej: A1B2C3" maxlength="6" style="letter-spacing: 0.5rem; text-transform: uppercase;">
                            <small class="text-muted d-block mb-2"><i class="fas fa-info-circle me-1"></i> El sistema identificará automáticamente al supervisor que emitió este PIN.</small>
                            @error('creditAuthPin') <span class="text-danger small d-block mb-2">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <button type="button" class="btn btn-success btn-lg w-100 fw-bold" wire:click="validateCreditPIN" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="validateCreditPIN">
                            <i class="fas fa-unlock me-2"></i> Validar PIN y Aprobar
                        </span>
                        <span wire:loading wire:target="validateCreditPIN">
                            <i class="fas fa-spinner fa-spin me-2"></i> Validando...
                        </span>
                    </button>
                    
                    <button type="button" class="btn btn-link text-muted mt-2" wire:click="requestCreditAuthorization" wire:loading.attr="disabled">
                        Reenviar Código
                    </button>
                @endif
            </div>
            
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="$set('showCreditAuthModal', false)">Cancelar</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('open-credit-auth-modal', () => {
            $('#creditAuthModal').modal('show');
        });
        
        Livewire.on('close-credit-auth-modal', () => {
            $('#creditAuthModal').modal('hide');
        });
    });
</script>
