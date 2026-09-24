<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Warehouse;

class Warehouses extends Component
{
    use WithPagination;

    public $name, $address, $is_active = true, $is_partner_warehouse = false, $partner_name = '', $search, $selected_id, $pageTitle, $componentName;
    private $pagination = 5;

    public function mount()
    {
        $this->pageTitle = 'Listado';
        $this->componentName = 'Depósitos';
    }

    public function render()
    {
        if (strlen($this->search) > 0)
            $data = Warehouse::where('name', 'like', '%' . $this->search . '%')
                ->orWhere('partner_name', 'like', '%' . $this->search . '%')
                ->paginate($this->pagination);
        else
            $data = Warehouse::orderBy('id', 'desc')->paginate($this->pagination);

        return view('livewire.warehouses', ['data' => $data])
            ->extends('layouts.theme.app')
            ->section('content');
    }

    public function Edit($id)
    {
        $record = Warehouse::find($id, ['id', 'name', 'address', 'is_active', 'is_partner_warehouse', 'partner_name']);
        $this->name = $record->name;
        $this->address = $record->address;
        $this->is_active = (bool)$record->is_active;
        $this->is_partner_warehouse = (bool)$record->is_partner_warehouse;
        $this->partner_name = $record->partner_name ?? '';
        $this->selected_id = $record->id;

        $this->dispatch('show-modal', 'Show modal!');
    }

    public function Store()
    {
        if (!auth()->user()->can('warehouses.create')) {
            $this->dispatch('noty', msg: 'No tienes permiso para crear depósitos');
            return;
        }

        $rules = [
            'name' => 'required|min:3|unique:warehouses,name',
            'partner_name' => 'nullable|string|max:100',
        ];

        $messages = [
            'name.required' => 'Nombre del depósito es requerido',
            'name.min' => 'El nombre debe tener al menos 3 caracteres',
            'name.unique' => 'El nombre del depósito ya existe',
        ];

        $this->validate($rules, $messages);

        $warehouse = Warehouse::create([
            'name' => $this->name,
            'address' => $this->address,
            'is_active' => $this->is_active,
            'is_partner_warehouse' => (bool)$this->is_partner_warehouse,
            'partner_name' => $this->is_partner_warehouse ? $this->partner_name : null,
        ]);

        $this->resetUI();
        $this->dispatch('warehouse-added', 'Depósito Registrado');
    }

    public function Update()
    {
        if (!auth()->user()->can('warehouses.edit')) {
            $this->dispatch('noty', msg: 'No tienes permiso para editar depósitos');
            return;
        }

        $rules = [
            'name' => "required|min:3|unique:warehouses,name,{$this->selected_id}",
            'partner_name' => 'nullable|string|max:100',
        ];

        $messages = [
            'name.required' => 'Nombre del depósito es requerido',
            'name.min' => 'El nombre debe tener al menos 3 caracteres',
            'name.unique' => 'El nombre del depósito ya existe',
        ];

        $this->validate($rules, $messages);

        $warehouse = Warehouse::find($this->selected_id);
        $warehouse->update([
            'name' => $this->name,
            'address' => $this->address,
            'is_active' => $this->is_active,
            'is_partner_warehouse' => (bool)$this->is_partner_warehouse,
            'partner_name' => $this->is_partner_warehouse ? $this->partner_name : null,
        ]);

        $this->resetUI();
        $this->dispatch('warehouse-updated', 'Depósito Actualizado');
    }

    public function resetUI()
    {
        $this->name = '';
        $this->address = '';
        $this->is_active = true;
        $this->is_partner_warehouse = false;
        $this->partner_name = '';
        $this->search = '';
        $this->selected_id = 0;
    }

    protected $listeners = [
        'deleteRow' => 'Destroy'
    ];

    public function Destroy(Warehouse $warehouse)
    {
        if (!auth()->user()->can('warehouses.delete')) {
            $this->dispatch('noty', msg: 'No tienes permiso para eliminar depósitos');
            return;
        }

        $warehouse->delete();
        $this->resetUI();
        $this->dispatch('warehouse-deleted', 'Depósito Eliminado');
    }
}
