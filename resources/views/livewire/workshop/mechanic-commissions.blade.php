<div class="p-6 max-w-7xl mx-auto space-y-6">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-2xl shadow-sm border border-gray-100">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 uppercase tracking-wider">
                    Taller Mecánico
                </span>
                <span class="text-xs text-gray-400">• Liquidación de Técnicos</span>
            </div>
            <h1 class="text-2xl font-black text-gray-900 tracking-tight mt-1">Liquidación de Comisiones de Mecánicos</h1>
            <p class="text-xs text-gray-500">Mano de obra devengada y porcentaje liquidable por especialista técnico.</p>
        </div>
    </div>

    <!-- Filters Bar -->
    <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Mecánico / Especialista</label>
            <select wire:model.live="mechanicId" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                <option value="0">Todos los mecánicos</option>
                @foreach($mechanics as $m)
                    <option value="{{ $m->id }}">{{ $m->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Desde</label>
            <input type="date" wire:model.live="dateFrom" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Hasta</label>
            <input type="date" wire:model.live="dateTo" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
        </div>
        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Estado de Orden</label>
            <select wire:model.live="statusFilter" class="w-full text-xs rounded-lg border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 shadow-sm">
                <option value="all">Todas las órdenes</option>
                <option value="invoiced">Solo Facturadas / Cobradas</option>
                <option value="delivered">Entregadas</option>
                <option value="completed">Completadas</option>
                <option value="in_progress">En Progreso</option>
            </select>
        </div>
    </div>

    <!-- Metrics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Mano de Obra Realizada</span>
            <div class="text-2xl font-black text-gray-800 mt-1">${{ number_format($report['total_labor_charged'], 2) }}</div>
            <span class="text-[11px] text-gray-400 mt-0.5 block">{{ $report['total_items_count'] }} servicios registrados</span>
        </div>
        <div class="bg-emerald-50 p-5 rounded-xl border border-emerald-100 shadow-sm">
            <span class="text-xs font-semibold text-emerald-700 uppercase tracking-wider">Total Comisiones a Liquidar</span>
            <div class="text-2xl font-black text-emerald-800 mt-1">${{ number_format($report['total_commission_earned'], 2) }}</div>
            <span class="text-[11px] text-emerald-600 mt-0.5 block">Liquidables según porcentaje pactado</span>
        </div>
        <div class="bg-indigo-50 p-5 rounded-xl border border-indigo-100 shadow-sm">
            <span class="text-xs font-semibold text-indigo-700 uppercase tracking-wider">Técnicos Activos en el Período</span>
            <div class="text-2xl font-black text-indigo-800 mt-1">{{ count($report['by_mechanic']) }}</div>
            <span class="text-[11px] text-indigo-600 mt-0.5 block">Con mano de obra registrada</span>
        </div>
    </div>

    <!-- Summary by Mechanic -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
            <h3 class="font-bold text-sm text-gray-800">Resumen por Mecánico</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-500 font-semibold uppercase text-[10px] tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3">Mecánico</th>
                        <th class="px-4 py-3 text-center">Órdenes</th>
                        <th class="px-4 py-3 text-center">Servicios</th>
                        <th class="px-4 py-3 text-right">Mano de Obra Facturada</th>
                        <th class="px-4 py-3 text-right">Comisión Devengada</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($report['by_mechanic'] as $mSummary)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3 font-semibold text-gray-900">{{ $mSummary['mechanic_name'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $mSummary['orders_count'] }}</td>
                            <td class="px-4 py-3 text-center">{{ $mSummary['items_count'] }}</td>
                            <td class="px-4 py-3 text-right font-medium">${{ number_format($mSummary['total_labor'], 2) }}</td>
                            <td class="px-4 py-3 text-right font-black text-emerald-600">${{ number_format($mSummary['total_commission'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-400">No hay registros de comisiones para los filtros seleccionados.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Detailed Labor Items -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 border-b border-gray-100">
            <h3 class="font-bold text-sm text-gray-800">Detalle de Trabajos y Mano de Obra</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-gray-50 text-gray-500 font-semibold uppercase text-[10px] tracking-wider border-b border-gray-100">
                    <tr>
                        <th class="px-4 py-3">Fecha / OT</th>
                        <th class="px-4 py-3">Vehículo / Cliente</th>
                        <th class="px-4 py-3">Mecánico</th>
                        <th class="px-4 py-3">Servicio / Tarea</th>
                        <th class="px-4 py-3 text-right">Precio</th>
                        <th class="px-4 py-3 text-center">% Com.</th>
                        <th class="px-4 py-3 text-right">Monto Com.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($report['items'] as $it)
                        <tr class="hover:bg-gray-50/50">
                            <td class="px-4 py-3">
                                <span class="font-bold text-indigo-600">{{ $it->workOrder->order_number ?? 'N/A' }}</span>
                                <div class="text-[10px] text-gray-400">{{ $it->created_at->format('d/m/Y') }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span class="font-semibold text-gray-900">{{ $it->workOrder->vehicle->full_name ?? 'N/A' }}</span>
                                <div class="text-[11px] text-gray-400">{{ $it->workOrder->customer->name ?? 'N/A' }}</div>
                            </td>
                            <td class="px-4 py-3 font-medium text-gray-800">{{ $it->mechanic->name ?? 'N/A' }}</td>
                            <td class="px-4 py-3">{{ $it->description }}</td>
                            <td class="px-4 py-3 text-right font-medium">${{ number_format($it->subtotal, 2) }}</td>
                            <td class="px-4 py-3 text-center font-bold text-gray-600">{{ number_format($it->mechanic_commission_percentage, 0) }}%</td>
                            <td class="px-4 py-3 text-right font-black text-emerald-600">${{ number_format($it->mechanic_commission_amount, 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-6 text-center text-gray-400">No hay detalles de mano de obra en este período.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>