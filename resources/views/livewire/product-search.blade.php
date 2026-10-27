<div class="position-relative" 
    x-data="{
        selectedIndex: @entangle('selectedIndex'),
        itemCount: 0,

        init() {
            Livewire.on('clear-search', () => {
                this.reset();
                const el = document.getElementById('inputSearchProduct');
                if (el) el.value = '';
            });

            Livewire.on('focus-search-input', () => {
                this.focusInput();
            });

            $watch('itemCount', value => {
                if(value === 0) this.selectedIndex = -1;
            });
        },

        focusInput() {
            const el = document.getElementById('inputSearchProduct');
            if (el) {
                el.focus();
                el.select();
            }
        },

        checkScroll() {
            this.$nextTick(() => {
                if (!this.$refs.resultsList) return;
                const activeItem = this.$refs.resultsList.children[this.selectedIndex];
                if (activeItem) {
                    activeItem.scrollIntoView({ block: 'nearest' });
                }
            });
        },

        navigate(direction) {
            if (this.$refs.resultsList) {
                this.itemCount = this.$refs.resultsList.children.length;
            } else {
                this.itemCount = 0;
            }

            if (this.itemCount === 0) return;

            if (direction === 'down') {
                this.selectedIndex = this.selectedIndex < this.itemCount - 1 ? this.selectedIndex + 1 : this.selectedIndex;
            } else {
                this.selectedIndex = this.selectedIndex > 0 ? this.selectedIndex - 1 : 0;
            }
            this.checkScroll();
        },

        selectItem() {
            if (this.selectedIndex >= 0 && this.$refs.resultsList) {
                const activeItem = this.$refs.resultsList.children[this.selectedIndex];
                if (activeItem && activeItem.dataset.id) {
                    $wire.selectProduct(activeItem.dataset.id);
                    this.reset();
                }
            } else if (this.itemCount === 1 && this.$refs.resultsList) {
                const firstItem = this.$refs.resultsList.children[0];
                if (firstItem && firstItem.dataset.id) {
                    $wire.selectProduct(firstItem.dataset.id);
                    this.reset();
                }
            }
        },

        reset() {
            this.selectedIndex = -1;
        }
    }" 
    @click.away="reset(); $wire.set('products', [])">

    <div class="faq-form position-relative">
        <input class="form-control form-control-lg" 
            type="text" 
            wire:model.live.debounce.400ms="search"
            placeholder="Ingresa Código o Descripción [F2]"
            id="inputSearchProduct" 
            autocomplete="off"
            @keydown.escape="reset(); $wire.set('products', [])"
            @keydown.arrow-down.prevent="navigate('down')"
            @keydown.arrow-up.prevent="navigate('up')"
            @keydown.enter.prevent="selectItem()">
        <i class="search-icon" data-feather="search"></i>
    </div>

    @if (!empty($products) && count($products) > 0)
        <div x-init="itemCount = {{ count($products) }}"></div>

        <ul x-ref="resultsList" 
            class="mt-1 bg-white border list-group position-absolute w-100 shadow-lg rounded"
            style="z-index: 2000; max-height: 350px; overflow-y: auto; left: 0; top: 100%;">
            @foreach ($products as $index => $product)
                <li class="p-2 list-group-item list-group-item-action d-flex justify-content-between align-items-center"
                    data-id="{{ $product->id }}"
                    :class="{ 'bg-primary text-white': selectedIndex === {{ $index }} }"
                    wire:click="selectProduct({{ $product->id }})"
                    style="cursor: pointer; border-bottom: 1px solid #f0f0f0;"
                    @mouseenter="selectedIndex = {{ $index }}">
                    <div class="d-flex align-items-center w-100">
                        <img src="{{ asset($product->photo) }}" alt="img" class="rounded mr-2 border" 
                            onerror="this.src='{{ asset('noimage.jpg') }}'; this.onerror=null;" 
                            style="width: 42px; height: 42px; object-fit: cover;">
                        <div class="d-flex flex-column flex-grow-1 overflow-hidden pr-2">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="font-weight-bold" :class="{ 'text-white': selectedIndex === {{ $index }}, 'text-dark': selectedIndex !== {{ $index }} }" style="font-size: 0.95rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                                    {{ $product->name }}
                                </span>
                            </div>
                            <div class="small" :class="{ 'text-white-50': selectedIndex === {{ $index }}, 'text-muted': selectedIndex !== {{ $index }} }">
                                @if($product->sku)
                                    <span class="badge" :class="{ 'badge-light text-dark': selectedIndex === {{ $index }}, 'badge-light border': selectedIndex !== {{ $index }} }">
                                        {{ $product->sku }}
                                    </span>
                                @endif
                                <span class="ml-1">Cód: #{{ $product->id }}</span>
                                @if($product->category)
                                    <span class="ml-1 d-none d-sm-inline">• {{ $product->category->name }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="text-right flex-shrink-0 ml-2">
                            <span class="font-weight-bold" :class="{ 'text-white': selectedIndex === {{ $index }}, 'text-primary': selectedIndex !== {{ $index }} }" style="font-size: 1.05rem;">
                                ${{ number_format($product->cost, 2) }}
                            </span>
                            <div class="small" :class="{ 'text-white-50': selectedIndex === {{ $index }}, 'text-muted': selectedIndex !== {{ $index }} }" style="font-size: 0.75rem;">
                                Costo Base
                            </div>
                        </div>
                    </div>
                </li>
            @endforeach
        </ul>
    @endif
</div>

@push('my-scripts')
<script>
    document.addEventListener('livewire:init', function() {
        Livewire.on('focus-cost', function(event) {
            setTimeout(function() {
                var uid = event.uid || event.element;
                var input = document.querySelector('#tblProducts #c' + uid);
                if (input) {
                    input.focus();
                    input.select();
                }
            }, 300);
        });
    });
</script>
@endpush
