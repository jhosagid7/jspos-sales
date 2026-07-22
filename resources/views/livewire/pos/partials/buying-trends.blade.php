@if($customer && count($trends) > 0)
<div x-data="{ expanded: false }" class="card card-outline card-success shadow-sm mt-3 animate__animated animate__fadeIn" :class="{ 'collapsed-card': !expanded }" style="border-radius: 15px; overflow: hidden;" wire:ignore.self>
    <div class="card-header bg-white border-bottom-0">
        <h3 class="card-title text-success font-weight-bold">
            <i class="fas fa-fire mr-2"></i> Sugerencias para {{ explode(' ', $customer['name'])[0] }}
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool text-success" @click="expanded = !expanded" style="transition: transform 0.3s ease;">
                <i class="fas" :class="expanded ? 'fa-minus' : 'fa-plus'"></i>
            </button>
        </div>
    </div>
    <div class="card-body p-2" x-show="expanded" style="max-height: 450px; overflow-y: auto; display: none;">
        <div class="list-group list-group-flush">
            @foreach($trends as $trend)
                <div class="list-group-item list-group-item-action p-2 border-0 mb-2 trend-item" 
                     style="border-radius: 12px; cursor: pointer; transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1); background: #f8fdf8;"
                     wire:click="addToCartFromTrend({{ $trend->id }})"
                     wire:loading.attr="disabled"
                     wire:key="trend-{{ $trend->id }}">
                    <div class="d-flex align-items-center">
                        <div class="product-thumb-container mr-3 position-relative" style="width: 55px; height: 55px; flex-shrink: 0;">
                            @if($trend->photo)
                                <img src="{{ asset($trend->photo) }}" class="img-fluid rounded shadow-sm h-100 w-100" style="object-fit: cover;" alt="img" 
                                     onerror="this.src='{{ asset('noimage.jpg') }}'; this.onerror=null;">
                            @else
                                <div class="bg-light d-flex align-items-center justify-content-center rounded shadow-sm h-100 w-100">
                                    <i class="fas fa-image text-muted opacity-50"></i>
                                </div>
                            @endif
                            <div class="trend-rank position-absolute" style="top: -5px; left: -5px;">
                                <span class="badge badge-success badge-pill shadow-sm" style="font-size: 0.6rem;">#{{ $loop->iteration }}</span>
                            </div>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="font-weight-bold text-dark text-truncate" style="font-size: 0.85rem;" title="{{ $trend->name }}">
                                {{ $trend->name }}
                            </div>
                            <div class="d-flex justify-content-between align-items-center mt-1">
                                <span class="badge badge-pill {{ $trend->stock_qty > 0 ? 'badge-success-soft text-success' : 'badge-danger-soft text-danger' }}" style="font-size: 0.7rem;">
                                    {{ $trend->stock_qty }} en stock
                                </span>
                                <div class="text-primary font-weight-bold" style="font-size: 0.9rem;">
                                    {{ $primaryCurrency->symbol ?? '$' }}{{ number_format($trend->price, 2) }}
                                </div>
                            </div>
                        </div>
                        <div class="ml-2 trend-action">
                            <div class="btn btn-xs btn-success rounded-circle shadow-sm p-0 d-flex align-items-center justify-content-center" style="width: 28px; height: 28px;">
                                <i class="fas fa-plus" style="font-size: 0.7rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
    <div class="card-footer bg-light p-2 text-center" x-show="expanded" style="display: none;">
        <small class="text-muted"><i class="fas fa-magic mr-1"></i> Basado en historial reciente</small>
    </div>
</div>

<style>
    .badge-success-soft {
        background-color: #e8f5e9;
        color: #2e7d32;
        border: 1px solid rgba(46, 125, 50, 0.1);
    }
    .badge-danger-soft {
        background-color: #ffebee;
        color: #c62828;
        border: 1px solid rgba(198, 40, 40, 0.1);
    }
    .trend-item:hover {
        background-color: #ffffff !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(40, 167, 69, 0.15);
        border: 1px solid rgba(40, 167, 69, 0.2) !important;
    }
    .trend-item:hover .trend-action .btn {
        transform: scale(1.1);
        background-color: #218838;
    }
    .trend-item:active {
        transform: scale(0.98);
    }
    /* Custom scrollbar for trends */
    .card-body::-webkit-scrollbar {
        width: 4px;
    }
    .card-body::-webkit-scrollbar-track {
        background: transparent;
    }
    .card-body::-webkit-scrollbar-thumb {
        background: rgba(40, 167, 69, 0.2);
        border-radius: 10px;
    }
</style>
@endif
