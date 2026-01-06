<div class="row layout-top-spacing">
    <div class="col-xl-12 col-lg-12 col-md-12 col-12 layout-spacing">
        <div class="widget widget-content-area br-4">
            <div class="widget-one">
                <div class="widget-content widget-content-area">
                    <div class="w-100">
                        <h3 class="mb-4">Apertura de Caja</h3>
                        
                        @if (session()->has('error'))
                            <div class="alert alert-danger">
                                {{ session('error') }}
                            </div>
                        @endif

                        @error('general')
                            <div class="alert alert-danger">
                                {{ $message }}
                            </div>
                        @enderror

                        <div class="row">
                            <div class="col-md-8">
                                <form wire:submit.prevent="openRegister">
                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <h5>Fondos Iniciales por Moneda</h5>
                                            <p class="text-muted">Ingrese la cantidad de efectivo con la que inicia el turno.</p>
                                        </div>
                                        
                                        @foreach($currencies as $currency)
                                        <div class="col-md-6 mb-3">
                                            <label>{{ $currency->label }} ({{ $currency->symbol }})</label>
                                            <div class="input-group">
                                                <span class="input-group-text">{{ $currency->symbol }}</span>
                                                <input type="number" step="0.01" class="form-control" 
                                                       wire:model="openingAmounts.{{ $currency->code }}">
                                            </div>
                                            @error("openingAmounts.{$currency->code}") 
                                                <span class="text-danger">{{ $message }}</span> 
                                            @enderror
                                        </div>
                                        @endforeach
                                    </div>

                                    <div class="row mb-4">
                                        <div class="col-12">
                                            <label>Notas de Apertura (Opcional)</label>
                                            <textarea class="form-control" rows="3" wire:model="notes" 
                                                      placeholder="Observaciones sobre el estado de la caja..."></textarea>
                                        </div>
                                    </div>

                                    @error('openingAmounts')
                                        <div class="alert alert-warning mb-3">
                                            {{ $message }}
                                        </div>
                                    @enderror

                                    <div class="row">
                                        <div class="col-12">
                                            <button type="submit" class="btn btn-primary btn-lg w-100">
                                                <i class="fas fa-cash-register me-2"></i> ABRIR CAJA
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Información</h5>
                                        <p class="card-text">
                                            Al abrir la caja, se registrará el inicio de su turno. 
                                            Asegúrese de contar bien el efectivo inicial.
                                        </p>
                                        <hr>
                                        <h6>Usuario:</h6>
                                        <p>{{ auth()->user()->name }}</p>
                                        <h6>Fecha:</h6>
                                        <p>{{ now()->format('d/m/Y H:i') }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
