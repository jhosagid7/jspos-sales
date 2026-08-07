<?php

namespace App\Livewire\Settings;

use Livewire\Component;
use App\Models\CommissionGoal;
use App\Models\User;

class CommissionGoalsManager extends Component
{
    public $activeSubTab = 'goals'; // 'goals' or 'assignments'

    // Form fields for Goal CRUD
    public $goalId;
    public $name = '';
    public $target_amount = '';
    public $reward_amount = '';
    public $periodicity = 'semanal';
    public $start_day_of_week = 'lunes';
    public $end_day_of_week = 'domingo';
    public $is_active = true;
    public $sort_order = 0;
    public $isEditing = false;

    // Filter for assignment search
    public $userSearch = '';

    protected $rules = [
        'name' => 'required|string|max:100',
        'target_amount' => 'required|numeric|min:0.01',
        'reward_amount' => 'required|numeric|min:0.01',
        'periodicity' => 'required|in:diaria,semanal,quincenal,mensual,trimestral,anual',
        'start_day_of_week' => 'nullable|string',
        'end_day_of_week' => 'nullable|string',
        'is_active' => 'boolean',
        'sort_order' => 'nullable|integer',
    ];

    protected $messages = [
        'name.required' => 'El nombre de la meta es obligatorio.',
        'target_amount.required' => 'El monto meta es obligatorio.',
        'target_amount.numeric' => 'El monto meta debe ser un número.',
        'reward_amount.required' => 'El premio/comisión es obligatorio.',
        'reward_amount.numeric' => 'El premio/comisión debe ser un número.',
        'periodicity.required' => 'La periodicidad es obligatoria.',
    ];

    public function render()
    {
        $goals = CommissionGoal::orderBy('sort_order')->orderBy('target_amount')->get();
        
        $users = User::when(strlen($this->userSearch) > 0, function($q) {
            $q->where('name', 'like', '%' . $this->userSearch . '%')
              ->orWhere('email', 'like', '%' . $this->userSearch . '%');
        })
        ->with('commissionGoals')
        ->orderBy('name')
        ->get();

        return view('livewire.settings.commission-goals-manager', [
            'goals' => $goals,
            'users' => $users,
        ]);
    }

    public function resetForm()
    {
        $this->reset(['goalId', 'name', 'target_amount', 'reward_amount', 'periodicity', 'start_day_of_week', 'end_day_of_week', 'is_active', 'sort_order', 'isEditing']);
        $this->periodicity = 'semanal';
        $this->start_day_of_week = 'lunes';
        $this->end_day_of_week = 'domingo';
        $this->is_active = true;
        $this->resetErrorBag();
    }

    public function saveGoal()
    {
        $this->validate();

        CommissionGoal::updateOrCreate(
            ['id' => $this->goalId],
            [
                'name' => trim($this->name),
                'target_amount' => floatval($this->target_amount),
                'reward_amount' => floatval($this->reward_amount),
                'periodicity' => $this->periodicity,
                'start_day_of_week' => $this->start_day_of_week ?? 'lunes',
                'end_day_of_week' => $this->end_day_of_week ?? 'domingo',
                'is_active' => $this->is_active ? 1 : 0,
                'sort_order' => intval($this->sort_order),
            ]
        );

        $msg = $this->isEditing ? 'Meta actualizada con éxito.' : 'Meta creada con éxito.';
        $this->dispatch('noty', msg: $msg);
        $this->resetForm();
    }

    public function editGoal($id)
    {
        $goal = CommissionGoal::findOrFail($id);
        $this->goalId = $goal->id;
        $this->name = $goal->name;
        $this->target_amount = $goal->target_amount;
        $this->reward_amount = $goal->reward_amount;
        $this->periodicity = $goal->periodicity;
        $this->start_day_of_week = $goal->start_day_of_week ?? 'lunes';
        $this->end_day_of_week = $goal->end_day_of_week ?? 'domingo';
        $this->is_active = $goal->is_active;
        $this->sort_order = $goal->sort_order;
        $this->isEditing = true;
    }

    public function toggleGoalActive($id)
    {
        $goal = CommissionGoal::findOrFail($id);
        $goal->is_active = !$goal->is_active;
        $goal->save();
        $this->dispatch('noty', msg: 'Estado de la meta actualizado.');
    }

    public function deleteGoal($id)
    {
        $goal = CommissionGoal::findOrFail($id);
        $goal->delete();
        $this->dispatch('noty', msg: 'Meta eliminada con éxito.');
    }

    public function toggleUserGoalAssignment($userId, $goalId)
    {
        $user = User::findOrFail($userId);
        if ($user->commissionGoals()->where('commission_goal_id', $goalId)->exists()) {
            $user->commissionGoals()->detach($goalId);
            $this->dispatch('noty', msg: 'Meta desasignada del usuario.');
        } else {
            $user->commissionGoals()->attach($goalId);
            $this->dispatch('noty', msg: 'Meta asignada exitosamente al usuario.');
        }
    }
}
