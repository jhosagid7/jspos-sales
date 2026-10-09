<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-6 rounded-xl shadow-sm border border-gray-100">
        <div>
            <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
                <svg class="w-7 h-7 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                Taller Automotriz - Fichas de Servicio
            </h1>
            <p class="text-sm text-gray-500 mt-1">Gestión integral de vehículos, diagnósticos, repuestos y liquidación de mano de obra</p>
        </div>
        <div class="flex items-center gap-3">
            <button wire:click="$set('showCreateModal', true)" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg shadow-sm transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Nueva Ficha / Orden
            </button>
        </div>
    </div>

    <!-- Filters & Search -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 bg-white p-4 rounded-xl shadow-sm border border-gray-100">
        <div class="md:col-span-2 relative">
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por placa, orden (OT-...), cliente o vehículo..." class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
            </div>
        </div>
        <div>
            <select wire:model.live="statusFilter" class="w-full py-2 px-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 text-sm">
                <option value="">Todos los Estados</option>
                <option value="reception">Recepción / Diagnóstico</option>
                <option value="budgeted">Presupuesto</option>
                <option value="approved">Aprobado</option>
                <option value="in_progress">En Reparación</option>
                <option value="ready">Listo para Entrega</option>
                <option value="invoiced">Facturado / Entregado</option>
            </select>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Orders List (2 Cols on lg) -->
        <div class="lg:col-span-2 space-y-4">
            <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200 text-xs font-semibold text-gray-600 uppercase tracking-wider">
                            <tr>
                                <th class="px-4 py-3">Orden</th>
                                <th class="px-4 py-3">Vehículo / Placa</th>
                                <th class="px-4 py-3">Cliente</th>
                                <th class="px-4 py-3">Estado</th>
                                <th class="px-4 py-3 text-right">Total</th>
                                <th class="px-4 py-3 text-center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @forelse($orders as $order)
                                <tr wire:key="order-{{ $order->id }}" class="hover:bg-indigo-50/40 transition cursor-pointer {{ $selectedOrderId === $order->id ? 'bg-indigo-50/80 font-medium' : '' }}" wire:click="selectOrder({{ $order->id }})">
                                    <td class="px-4 py-3 font-semibold text-indigo-600">
                                        {{ $order->order_number }}
                                        <div class="text-xs text-gray-400 font-normal">{{ $order->created_at->format('d/m/Y') }}</div>
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-block px-2 py-0.5 bg-gray-100 text-gray-800 rounded font-mono font-bold text-xs">{{ $order->vehicle?->plate }}</span>
                                        <div class="text-xs text-gray-500">{{ $order->vehicle?->brand }} {{ $order->vehicle?->model }}</div>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $order->customer?->name }}
                                    </td>
                                    <td class="px-4 py-3">
                                        @php
                                            $badges = [
                                                'reception' => 'bg-amber-100 text-amber-800',
                                                'budgeted' => 'bg-blue-100 text-blue-800',
                                                'approved' => 'bg-purple-100 text-purple-800',
                                                'in_progress' => 'bg-orange-100 text-orange-800',
                                                'ready' => 'bg-emerald-100 text-emerald-800',
                                                'invoiced' => 'bg-gray-100 text-gray-800',
                                            ];
                                            $labels = [
                                                'reception' => 'Recepción',
                                                'budgeted' => 'Presupuesto',
                                                'approved' => 'Aprobado',
                                                'in_progress' => 'En Taller',
                                                'ready' => 'Listo',
                                                'invoiced' => 'Facturado',
                                            ];
                                        @endphp
                                        <span class="px-2 py-1 rounded-full text-xs font-semibold {{ $badges[$order->status] ?? 'bg-gray-100 text-gray-600' }}">
                                            {{ $labels[$order->status] ?? $order->status }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-800">
                                        ${{ number_format($order->total, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button wire:click.stop="selectOrder({{ $order->id }})" class="p-1.5 text-gray-500 hover:text-indigo-600 rounded">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                        No se encontraron órdenes de trabajo registradas.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="p-3 border-t border-gray-100">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>

        <!-- Order Detail Panel (1 Col on lg) -->
        <div class="lg:col-span-1">
            @if($selectedOrder)
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 space-y-4">
                    <div class="flex items-center justify-between border-b pb-3">
                        <div>
                            <span class="text-xs uppercase font-bold text-gray-400">Detalle de Orden</span>
                            <h2 class="text-xl font-bold text-gray-800">{{ $selectedOrder->order_number }}</h2>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="{{ route('workshop.work-orders.pdf', $selectedOrder->id) }}" target="_blank" class="px-2.5 py-1.5 bg-gray-100 hover:bg-gray-200 text-gray-700 text-xs font-semibold rounded-lg transition inline-flex items-center gap-1" title="Ver / Descargar Ficha PDF">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                PDF
                            </a>
                            @if($selectedOrder->status !== 'invoiced')
                                <button wire:click="$set('showItemModal', true)" class="px-3 py-1.5 bg-indigo-50 hover:bg-indigo-100 text-indigo-700 text-xs font-semibold rounded-lg transition">
                                    + Agregar Ítem
                                </button>
                                <button wire:click="$set('showBillingModal', true)" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition shadow-sm">
                                    Cobrar / Facturar
                                </button>
                            @else
                                <span class="px-2.5 py-1 bg-gray-100 text-gray-700 font-semibold text-xs rounded-full">Orden Cobrada</span>
                            @endif
                        </div>
                    </div>

                    <!-- Vehicle & Client Info -->
                    <div class="bg-gray-50 p-3 rounded-lg text-xs space-y-1.5">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Cliente:</span>
                            <span class="font-semibold text-gray-800">{{ $selectedOrder->customer?->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Vehículo:</span>
                            <span class="font-semibold text-gray-800">{{ $selectedOrder->vehicle?->brand }} {{ $selectedOrder->vehicle?->model }} ({{ $selectedOrder->vehicle?->year }})</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Placa:</span>
                            <span class="font-mono font-bold text-gray-800">{{ $selectedOrder->vehicle?->plate }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Km Entrada:</span>
                            <span class="font-semibold text-gray-800">{{ number_format($selectedOrder->entry_mileage) }} Km</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Combustible:</span>
                            <span class="font-semibold text-gray-800">{{ $selectedOrder->fuel_level }}</span>
                        </div>
                    </div>

                    <!-- Items List -->
                    <div>
                        <h3 class="text-xs font-bold uppercase text-gray-500 mb-2">Servicios y Repuestos</h3>
                        <div class="divide-y divide-gray-100 max-h-60 overflow-y-auto pr-1">
                            @forelse($selectedOrder->items as $item)
                                <div class="py-2 text-xs flex justify-between items-start">
                                    <div>
                                        <div class="font-semibold text-gray-800 flex items-center gap-1.5">
                                            <span class="w-2 h-2 rounded-full {{ $item->type === 'part' ? 'bg-blue-500' : 'bg-amber-500' }}"></span>
                                            {{ $item->description }}
                                        </div>
                                        <div class="text-gray-400 text-[11px] mt-0.5">
                                            {{ $item->quantity }} x ${{ number_format($item->unit_price, 2) }}
                                            @if($item->mechanic)
                                                • Mec: {{ $item->mechanic->name }}
                                            @endif
                                        </div>
                                    </div>
                                    <div class="font-bold text-gray-800">
                                        ${{ number_format($item->subtotal, 2) }}
                                    </div>
                                </div>
                            @empty
                                <div class="py-4 text-center text-gray-400 text-xs">
                                    No hay ítems cargados en esta orden.
                                </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Financial Summary -->
                    <div class="border-t pt-3 space-y-1 text-xs">
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal Repuestos:</span>
                            <span>${{ number_format($selectedOrder->subtotal_parts, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-gray-600">
                            <span>Subtotal Mano de Obra:</span>
                            <span>${{ number_format($selectedOrder->subtotal_labor, 2) }}</span>
                        </div>
                        <div class="flex justify-between text-base font-bold text-gray-900 border-t pt-1">
                            <span>Total General:</span>
                            <span class="text-indigo-600">${{ number_format($selectedOrder->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-8 text-center text-gray-400 text-sm">
                    Selecciona una orden de la lista para ver el diagnóstico y cargar repuestos.
                </div>
            @endif
        </div>
    </div>
</div>
