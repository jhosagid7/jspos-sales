<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Livewire\Attributes\On;


class Users extends Component
{
    use WithPagination;

    public User $user;
    public $user_id, $editing, $search, $pagination = 5, $pwd,  $temppwd;
    public  $role, $roleSelectedId,  $permissionId, $roles = [];
    public $commission_percent = 0, $freight_percent = 0, $exchange_diff_percent = 0, $current_batch = '1';
    public $sellerCommission1Threshold, $sellerCommission1Percentage, $sellerCommission2Threshold, $sellerCommission2Percentage;

    protected $rules =
    [
        'user.name' => "required|max:85|unique:users,name",
        'user.email' => 'required|email|max:75',
        'user.password' => 'nullable',
        'user.status' => 'required|in:Active,Locked',
        'user.profile' => 'required', // agregar esta regla
        'user.commission_percentage' => 'nullable|numeric|min:0|max:100',
        'commission_percent' => 'nullable|numeric|min:0|max:100',
        'freight_percent' => 'nullable|numeric|min:0|max:100',
        'exchange_diff_percent' => 'nullable|numeric|min:0|max:1000',
        'sellerCommission1Threshold' => 'nullable|numeric',
        'sellerCommission1Percentage' => 'nullable|numeric',
        'sellerCommission2Threshold' => 'nullable|numeric',
        'sellerCommission2Percentage' => 'nullable|numeric',
        'user.printer_name' => 'nullable|string|max:55',
        'user.printer_width' => 'nullable|in:80mm,58mm',
    ];

    protected $messages = [
        'user.name.required' => 'Nombre requerido',
        'user.name.max' => 'Nombre debe tener máximo 85 caracteres',
        'user.name.unique' => 'El nombre ya existe',
        'user.email.required' => 'Email requerido',
        'user.email.email' => 'Email inválido',
        'user.email.max' => 'Email debe tener máximo 75 caracteres',
        'user.status.required' => 'Estatus requerido',
        'user.status.in' => 'Elige un estatus',
        'user.name.string' => 'Solo caracteres alfabeticos',
        'user.profile' => 'Selecciona un perfil', // agregar esta regla
        'user.commission_percentage.numeric' => 'El porcentaje debe ser numérico',
        'user.commission_percentage.min' => 'El porcentaje no puede ser negativo',
        'user.commission_percentage.max' => 'El porcentaje no puede ser mayor a 100',
    ];


    public function mount()
    {
        $this->user = new User();
        $this->user->status = 'Active';
        $this->user->profile = 0;
        $this->user->commission_percentage = 0;
        $this->commission_percent = 0;
        $this->freight_percent = 0;
        $this->exchange_diff_percent = 0;
        $this->exchange_diff_percent = 0;
        $this->current_batch = '1';
        $this->resetCommissionFields();
        $this->editing = false;

        session(['map' => 'Usuarios', 'child' => ' Componente ']);

        // Role Hierarchy Logic (Level-based)
        $currentUserRole = auth()->user()->roles->first(); // Get the role object to access 'level'
        
        if ($currentUserRole) {
            // Show only roles with level strictly lower than current user's level
            // Admin (100) sees < 100 (50, 30, 10)
            // Dueño (50) sees < 50 (30, 10)
            // Administrador (30) sees < 30 (10)
            
            // Special case: Admin (100) should be able to see all roles except maybe other Admins?
            // Or strictly follow the rule: Admin can assign anything <= 100?
            // Usually Admin can assign anything. Let's say Admin (100) can assign anything < 100.
            // But wait, Admin needs to be able to create other Admins?
            // User said: "el unico que puede crear roles es el admin" and "yo siempre tendre el control".
            // Let's assume Admin (100) can see ALL roles.
            
            if ($currentUserRole->level >= 100) {
                 $this->roles = Role::with('permissions')->orderBy('name')->get();
            } else {
                 $this->roles = Role::where('level', '<', $currentUserRole->level)->orderBy('name')->get();
            }
        } else {
            $this->roles = [];
        }

        if (count($this->roles) > 0) {
            $this->role = Role::find($this->roles[0]->id);
            $this->roleSelectedId = $this->role->id;
        }
    }

    public function render()
    {
        $users = $this->loadUsers();
        return view('livewire.users.users', [
            'users' => $users
        ]);
    }

    public function loadUsers()
    {
        if (strlen($this->search) > 0)
            return User::where('name', 'like', "%{$this->search}%")->paginate($this->pagination);
        else
            return User::orderBy('name', 'asc')->paginate($this->pagination);
    }

    public function Add()
    {
        $this->resetValidation();
        $this->resetExcept('user');
        $this->user = new User();
        $this->user->commission_percentage = 0;
        $this->commission_percent = 0;
        $this->freight_percent = 0;
        $this->exchange_diff_percent = 0;
        $this->resetCommissionFields();
        $this->editing = false;
        $this->dispatch('init-new');
    }

    public function Edit(User $user)
    {
        // Protect Super Admin
        if ($user->email === 'jhosagid77@gmail.com' && auth()->user()->email !== 'jhosagid77@gmail.com') {
            $this->dispatch('noty', msg: 'NO TIENES PERMISO PARA EDITAR AL SUPER ADMIN');
            return;
        }

        $this->user = $user;
        $this->editing = true;
        $this->pwd = '';
        $this->temppwd = $user->password;
        $this->roleSelectedId = $this->getRoleId($user->profile);
        $this->role = Role::find($this->roleSelectedId);
        
        $this->sellerCommission1Threshold = $user->seller_commission_1_threshold;
        $this->sellerCommission1Percentage = $user->seller_commission_1_percentage;
        $this->sellerCommission2Threshold = $user->seller_commission_2_threshold;
        $this->sellerCommission2Percentage = $user->seller_commission_2_percentage;

        $latestConfig = $user->latestSellerConfig;
        if($latestConfig) {
            $this->commission_percent = $latestConfig->commission_percent;
            $this->freight_percent = $latestConfig->freight_percent;
            $this->exchange_diff_percent = $latestConfig->exchange_diff_percent;
            $this->current_batch = $latestConfig->current_batch;
        } else {
            $this->commission_percent = 0;
            $this->freight_percent = 0;
            $this->exchange_diff_percent = 0;
            $this->current_batch = '1';
        }

        $this->dispatch('init-new');
    }

    public function cancelEdit()
    {
        $this->resetValidation();
        $this->resetExcept('user');
        $this->user = new User();
        $this->user->commission_percentage = 0;
        $this->commission_percent = 0;
        $this->freight_percent = 0;
        $this->exchange_diff_percent = 0;
        $this->resetCommissionFields();
        $this->editing = false;
    }

    public function Store()
    {
        $this->rules['user.name'] = $this->user->id > 0 ? "required|max:85|unique:users,name,{$this->user->id}" : 'required|max:85|unique:users,name';

        $this->validate($this->rules, $this->messages);

        if ($this->user->id == null) {
            if (empty($this->pwd)) {
                $this->addError('pwd', 'Ingresa el password');
                return;
            } else {
                $this->user->password = bcrypt($this->pwd);
            }
        } else {
            if (!empty($this->pwd))
                $this->user->password = bcrypt($this->pwd);
            else
                $this->user->password = $this->temppwd;
        }
        
        $this->user->seller_commission_1_threshold = $this->sellerCommission1Threshold;
        $this->user->seller_commission_1_percentage = $this->sellerCommission1Percentage;
        $this->user->seller_commission_2_threshold = $this->sellerCommission2Threshold;
        $this->user->seller_commission_2_percentage = $this->sellerCommission2Percentage;
        
        // Printer Name (Optional)
        // Ensure it's in fillable or manually assigned if not
        // Assuming User model is not strict or we assign it directly
        // $this->user->printer_name is already bound via wire:model if we add it
        // But we need to make sure it's safe.
        // Let's add it to rules first.

        // save model
        $this->user->save();

        if($this->user->profile == 'Vendedor') {
            \App\Models\SellerConfig::create([
                'user_id' => $this->user->id,
                'commission_percent' => $this->commission_percent ?? 0,
                'freight_percent' => $this->freight_percent ?? 0,
                'exchange_diff_percent' => $this->exchange_diff_percent ?? 0,
                'current_batch' => $this->current_batch ?? '1'
            ]);
        }


        $this->dispatch('noty', msg: $this->user->id != null ? 'USUARIO ACTUALIZADO CORRECTAMENTE' : 'USUARIO REGISTRADO CON ÉXITO');
        
        // Validate Role Assignment Permission (Level-based)
        $targetRoleName = $this->user->profile;
        $currentUserRole = auth()->user()->roles->first();
        $targetRole = Role::where('name', $targetRoleName)->first();

        $allowed = false;
        
        if ($currentUserRole && $targetRole) {
            if ($currentUserRole->level >= 100) {
                $allowed = true; // Admin can do anything
            } else {
                // Strictly lower level
                $allowed = $targetRole->level < $currentUserRole->level;
            }
        }

        if (!$allowed) {
             $this->dispatch('noty', msg: 'NO TIENES PERMISO PARA ASIGNAR ESTE ROL (NIVEL INSUFICIENTE)');
             return;
        }

        $this->assignRole($this->user->id, $this->getRoleId($this->user->profile));

        $this->resetExcept('user');
        $this->user = new User();
        $this->user->status = 'Active';
        $this->user->profile = 0;
        $this->user->commission_percentage = 0;
        $this->commission_percent = 0;
        $this->freight_percent = 0;
        $this->freight_percent = 0;
        $this->exchange_diff_percent = 0;
        $this->resetCommissionFields();
    }

    public function resetCommissionFields()
    {
        $this->sellerCommission1Threshold = null;
        $this->sellerCommission1Percentage = null;
        $this->sellerCommission2Threshold = null;
        $this->sellerCommission2Percentage = null;
    }

    public function getRoleId($role)
    {
        if ($role) {
            $role = Role::where('name', $role)->first();
            return $role->id;
        }

        return 0;
    }
    #[On('destroyUser')]
    public function Destroy($id)
    {
        $user = User::find($id);
        if ($user->sales->count() > 0) {
            $this->dispatch('noty', msg: 'EL USUARIO TIENE VENTAS RELACIONADAS, NO ES POSIBLE ELIMINARLO');
            return;
        }
        if ($user->purchases()->count() > 0) {
            $this->dispatch('noty', msg: 'EL USUARIO TIENE COMPRAS RELACIONADAS, NO ES POSIBLE ELIMINARLO');
            return;
        }

        // Protect Super Admin
        if ($user->email === 'jhosagid77@gmail.com') {
            $this->dispatch('noty', msg: 'NO ES POSIBLE ELIMINAR AL SUPER ADMIN');
            return;
        }

        $user->delete();

        $this->resetPage();


        $this->dispatch('noty', msg: 'USUARIO ELIMINADO CON ÉXITO');
    }

    public function assignRole($userId, $roleId)
    {
        // dd($this->editing);
        try {

            $user = User::find($userId);
            $role = Role::find($roleId);

            // Asigna el rol al usuario
            if ($roleId == 0) {
                $user->syncRoles([]); //eliminar roles
            } else {
                $user->syncRoles([$role]); //asignar role
            }

            if ($roleId == 0) {
                $this->dispatch('noty', msg: "Se eliminaron los roles al usuario $user->name");
            } else {
                $this->dispatch('noty', msg: 'Se asignó el rol ' . $role->name . ' al usuario ' . $user->name);
            }

            // $this->UpdateProfileRoleUser($userId, $role->name);

            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar asignar el role: {$th->getMessage()} ");
        }
    }
    function updatedRoleSelectedId()
    {
        $this->role = Role::find($this->roleSelectedId);
    }

    public function UpdateProfileRoleUser($userId = null, $role)
    {
        try {
            DB::beginTransaction();

            $order = User::findOrFail($userId);
            $order->update([
                'profile' => $role,

            ]);

            DB::commit();
        } catch (\Exception $th) {
            DB::rollBack();
            $this->dispatch('noty', msg: "Error al intentar actualizar el role $role del usuario \n {$th->getMessage()}");
        }
    }
    public $history = [];
    public $viewingUserId;

    public function viewHistory($userId)
    {
        $this->viewingUserId = $userId;
        $this->history = \App\Models\SellerConfig::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->get();
        
        $this->dispatch('show-history-modal');
    }

    public function closeHistory()
    {
        $this->history = [];
        $this->dispatch('close-history-modal');
    }
}
