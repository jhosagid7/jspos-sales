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
                        <h3 class="text-danger">{{ $daysRemaining }} Días Restantes</h3>
                        <p class="text-muted">Su licencia está por expirar. Ingrese una nueva clave para continuar disfrutando del servicio sin interrupciones.</p>
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
    </script>
</div>
