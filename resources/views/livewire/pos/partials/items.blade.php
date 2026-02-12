<div>
    @if($salesViewMode === 'list')
        @include('livewire.pos.partials.items-list')
    @else
        @include('livewire.pos.partials.items-grid')
    @endif
</div>
