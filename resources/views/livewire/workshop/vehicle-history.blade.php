<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800 uppercase tracking-wider">
                    Taller Mecánico
                </span>
                <span class="text-xs text-gray-400">• Trazabilidad y Mantenimiento</span>
            </div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight mt-1">Historial Clínico del Vehículo</h1>
            <p class="text-xs text-gray-500">Consulte todas las intervenciones, reparaciones, repuestos instalados y kilometraje por placa.</p>
        </div>
    </div>

    <!-- Search Box -->
    <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100">
        <label class="block text-xs font-bold uppercase text-gray-600 mb-2">Buscar Vehículo por Placa, Marca o Modelo</label>
        <div class="flex gap-2">
            <input type="text" wire:model.live.debounce.300ms="searchPlate" wire:keydown.enter="searchVehicle" placeholder="Ej: ABC-123, Hilux, Fortaleza..." class="flex-1 text-sm rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm uppercase">
            <button wire:click="searchVehicle" class="px-5 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold text-xs rounded-lg transition shadow-sm">
                Buscar
            </button>
        </div>

        @if(!empty($vehicles) && !$selectedVehicleId)
            <div class="mt-3 divide-y divide-gray-100 border border-gray-100 rounded-lg overflow-hidden bg-gray-50">
                @foreach($vehicles as $v)
                    <div wire:click="selectVehicle({{ $v->id }})" class="p-3 hover:bg-indigo-50 cursor-pointer flex justify-between items-center text-xs">
                        <div>
                            <span class="font-black text-indigo-700 text-sm">{{ $v->plate }}</span>
                            <span class="font-semibold text-gray-800 ml-2">{{ $v->brand }} {{ $v->model }} ({{ $v->year }})</span>
                            <span class="text-gray-400 ml-2">• Dueño: {{ $v->customer->name ?? 'N/A' }}</span>
                        </div>
                        <span class="text-xs font-bold text-indigo-600">Ver Ficha &rarr;</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    @if($history && $history['vehicle'])
        <!-- Vehicle Card -->
        <div class="bg-white p-5 rounded-xl shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="border-r border-gray-100 pr-4">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Placa Identificadora</span>
                <div class="text-2xl font-black text-indigo-700 mt-0.5">{{ $history['vehicle']->plate }}</div>
                <div class="text-xs font-semibold text-gray-800 mt-1">{{ $history['vehicle']->brand }} {{ $history['vehicle']->model }} {{ $history['vehicle']->year }}</div>
                <div class="text-[11px] text-gray-500">Color: {{ $history['vehicle']->color ?? 'No registrado' }}</div>
            </div>
            <div class="border-r border-gray-100 pr-4">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Propietario / Empresa</span>
                <div class="text-sm font-bold text-gray-900 mt-1">{{ $history['vehicle']->customer->name ?? 'N/A' }}</div>
                <div class="text-xs text-gray-500">{{ $history['vehicle']->customer->taxpayer_id ?? '' }}</div>
                <div class="text-[11px] text-gray-400">{{ $history['vehicle']->customer->phone ?? '' }}</div>
            </div>
            <div class="border-r border-gray-100 pr-4">
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Kilometraje Actual</span>
                <div class="text-xl font-black text-gray-800 mt-1">{{ number_format($history['vehicle']->current_mileage ?? 0) }} km</div>
                <div class="text-[11px] text-gray-400 mt-0.5">Total Visitas al Taller: {{ $history['total_visits'] }}</div>
            </div>
            <div>
                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Inversión Total en Taller</span>
                <div class="text-2xl font-black text-emerald-600 mt-1">${{ number_format($history['total_spent'], 2) }}</div>
                <div class="text-[11px] text-gray-500">Repuestos: ${{ number_format($history['total_parts_spent'], 2) }} | MO: ${{ number_format($history['total_labor_spent'], 2) }}</div>
            </div>
        </div>

        <!-- History Timeline / Orders -->
        <div class="space-y-4">
            <h2 class="text-base font-bold text-gray-800">Historial Cronológico de Órdenes ({{ $history['orders']->count() }})</h2>

            @forelse($history['orders'] as $ord)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-3">
                    <div class="flex flex-col md:flex-row md:items-center justify-between pb-3 border-b border-gray-100 gap-2">
                        <div class="flex items-center gap-3">
                            <span class="text-base font-black text-indigo-700">{{ $ord->order_number }}</span>
                            <span class="text-xs text-gray-400">{{ $ord->created_at->format('d/m/Y H:i') }}</span>
                            @php
                                $badges = [
                                    'reception' => 'bg-amber-100 text-amber-800',
                                    'in_progress' => 'bg-blue-100 text-blue-800',
                                    'completed' => 'bg-emerald-100 text-emerald-800',
                                    'delivered' => 'bg-purple-100 text-purple-800',
                                    'invoiced' => 'bg-green-100 text-green-800',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 text-[11px] font-bold rounded-full {{ $badges[$ord->status] ?? 'bg-gray-100' }}">
                                {{ ucfirst($ord->status) }}
                            </span>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="text-xs text-right">
                                <span class="text-gray-400">Total:</span>
                                <span class="font-black text-gray-900">${{ number_format($ord->total, 2) }}</span>
                            </div>
                            <a href="{{ route('workshop.work-orders.pdf', $ord->id) }}" target="_blank" class="px-2.5 py-1 bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold text-xs rounded-lg transition inline-flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                Ficha PDF
                            </a>
                        </div>
                    </div>

                    <div class="text-xs text-gray-700 bg-gray-50/70 p-3 rounded-lg">
                        <strong class="text-gray-900">Diagnóstico / Motivo de Ingreso:</strong> {{ $ord->initial_diagnosis }}
                        @if($ord->entry_mileage)
                            <span class="ml-2 text-gray-400">• Km Entrada: {{ number_format($ord->entry_mileage) }} km</span>
                        @endif
                    </div>

                    <!-- Items Breakdown -->
                    <div class="divide-y divide-gray-100 text-xs">
                        @foreach($ord->items as $item)
                            <div class="py-2 flex justify-between items-center">
                                <div>
                                    <span class="inline-block w-2 h-2 rounded-full mr-1.5 {{ $item->type === 'part' ? 'bg-blue-500' : 'bg-amber-500' }}"></span>
                                    <span class="font-medium text-gray-800">{{ $item->description }}</span>
                                    <span class="text-gray-400 text-[11px] ml-1">({{ $item->quantity }} x ${{ number_format($item->unit_price, 2) }})</span>
                                    @if($item->mechanic)
                                        <span class="text-indigo-600 text-[11px] ml-2">Mecánico: {{ $item->mechanic->name }}</span>
                                    @endif
                                </div>
                                <div class="font-bold text-gray-800">
                                    ${{ number_format($item->subtotal, 2) }}
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @empty
                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-100 text-center text-gray-400 text-sm">
                    Este vehículo no tiene intervenciones u órdenes registradas hasta el momento.
                </div>
            @endforelse
        </div>
    @elseif(!empty($searchPlate))
        <div class="bg-white p-10 rounded-xl shadow-sm border border-gray-100 text-center text-gray-400 text-sm">
            No se encontró ningún vehículo con la placa ingresada.
        </div>
    @endif
</div>