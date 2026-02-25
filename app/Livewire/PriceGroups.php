<?php

namespace App\Livewire;

use App\Models\PriceGroup;
use Livewire\Component;

class PriceGroups extends Component
{
    public $name = '';
    public $description = '';
    public $editingId = null;

    public $rules = [
        'name'        => 'required|string|max:100',
        'description' => 'nullable|string|max:255',
    ];

    public function render()
    {
        $groups = PriceGroup::withCount('products')->orderBy('name')->get();
        return view('livewire.price-groups', compact('groups'));
    }

    public function save()
    {
        $this->validate();

        PriceGroup::updateOrCreate(
            ['id' => $this->editingId],
            ['name' => $this->name, 'description' => $this->description]
        );

        $this->reset(['name', 'description', 'editingId']);
        $this->dispatch('noty', msg: $this->editingId ? 'Grupo actualizado.' : 'Grupo creado.');
    }

    public function edit(PriceGroup $group)
    {
        $this->editingId    = $group->id;
        $this->name         = $group->name;
        $this->description  = $group->description;
    }

    public function delete(PriceGroup $group)
    {
        // Unlink products first
        $group->products()->update(['price_group_id' => null]);
        $group->delete();
        $this->dispatch('noty', msg: 'Grupo eliminado.');
    }

    public function cancelEdit()
    {
        $this->reset(['name', 'description', 'editingId']);
    }
}
