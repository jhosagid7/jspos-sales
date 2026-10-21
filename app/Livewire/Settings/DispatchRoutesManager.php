<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\DispatchRoute;

class DispatchRoutesManager extends Component
{
    public $routeId;
    public $name = '';
    public $route_type = 'fixed_route';
    public $dispatch_day = 'thursday';
    public $cutoff_day = 'wednesday';
    public $cutoff_time = '12:00';
    public $description = '';
    public $is_active = true;

    public $isEditing = false;
    public $showModal = false;

    protected $rules = [
        'name' => 'required|string|max:150',
        'route_type' => 'required|in:fixed_route,local,pickup',
        'dispatch_day' => 'nullable|string',
        'cutoff_day' => 'nullable|string',
        'cutoff_time' => 'required|string',
        'description' => 'nullable|string|max:255',
        'is_active' => 'boolean',
    ];

    protected $messages = [
        'name.required' => 'El nombre de la ruta es obligatorio.',
        'route_type.required' => 'El tipo de ruta es obligatorio.',
        'cutoff_time.required' => 'La hora de corte es obligatoria.',
    ];

    public function render()
    {
        $routes = DispatchRoute::withCount('customers')->orderBy('name')->get();

        return view('livewire.settings.dispatch-routes-manager', [
            'routes' => $routes,
        ])->layout('layouts.theme.app');
    }

    public function openCreateModal()
    {
        $this->reset(['routeId', 'name', 'description']);
        $this->route_type = 'fixed_route';
        $this->dispatch_day = 'thursday';
        $this->cutoff_day = 'wednesday';
        $this->cutoff_time = '12:00';
        $this->is_active = true;
        $this->isEditing = false;
        $this->showModal = true;
    }

    public function edit($id)
    {
        $route = DispatchRoute::findOrFail($id);
        $this->routeId = $route->id;
        $this->name = $route->name;
        $this->route_type = $route->route_type ?? 'fixed_route';
        $this->dispatch_day = $route->dispatch_day ?? 'thursday';
        $this->cutoff_day = $route->cutoff_day ?? 'wednesday';
        $this->cutoff_time = substr($route->cutoff_time ?? '12:00:00', 0, 5);
        $this->description = $route->description ?? '';
        $this->is_active = (bool) $route->is_active;

        $this->isEditing = true;
        $this->showModal = true;
    }

    public function save()
    {
        $this->validate();

        $data = [
            'name' => $this->name,
            'route_type' => $this->route_type,
            'dispatch_day' => $this->route_type === 'pickup' ? null : $this->dispatch_day,
            'cutoff_day' => $this->route_type === 'pickup' ? null : $this->cutoff_day,
            'cutoff_time' => $this->cutoff_time . ':00',
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];

        if ($this->isEditing && $this->routeId) {
            $route = DispatchRoute::findOrFail($this->routeId);
            $route->update($data);
            $this->dispatch('noty', msg: 'Ruta de despacho actualizada exitosamente.');
        } else {
            DispatchRoute::create($data);
            $this->dispatch('noty', msg: 'Ruta de despacho creada exitosamente.');
        }

        $this->showModal = false;
    }

    public function toggleActive($id)
    {
        $route = DispatchRoute::findOrFail($id);
        $route->is_active = !$route->is_active;
        $route->save();

        $this->dispatch('noty', msg: 'Estado de la ruta actualizado.');
    }

    public function delete($id)
    {
        $route = DispatchRoute::findOrFail($id);
        if ($route->customers()->count() > 0) {
            $this->dispatch('noty-error', msg: 'No se puede eliminar la ruta porque tiene clientes asignados.');
            return;
        }

        $route->delete();
        $this->dispatch('noty', msg: 'Ruta eliminada exitosamente.');
    }
}
