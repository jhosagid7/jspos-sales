<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;


class Users extends Component
{
    use WithPagination;

    public User $user;
    public $user_id, $editing, $search, $records, $pagination = 5, $pwd,  $temppwd;
    public  $role, $roleSelectedId,  $permissionId, $roles = [];

    protected $rules =
    [
        'user.name' => "required|max:85|unique:users,name",
        'user.email' => 'required|email|max:75',
        'user.password' => 'nullable',
        'user.status' => 'required|in:Active,Locked',
        'user.profile' => 'required', // agregar esta regla
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
    ];


    public function mount()
    {
        $this->user = new User();
        $this->user->status = 'Active';
        $this->user->profile = 0;
        $this->editing = false;

        session(['map' => 'Usuarios', 'child' => ' Componente ']);

        $this->roles = Role::with('permissions')->orderBy('name')->get();
        if (count($this->roles) > 0) {
            $this->role = Role::find($this->roles[0]->id);
            $this->roleSelectedId = $this->role->id;
        }
    }


    public function render()
    {
        $this->roles = Role::with('permissions')->orderBy('name')->get();
        if (count($this->roles) > 0) {
            $this->role = Role::find($this->roles[0]->id);
            $this->roleSelectedId = $this->role->id;
        }
        return view('livewire.users.users', [
            'roles' => $this->roles,
            'users' => $this->loadUsers(),
            'permisos' => Permission::when($this->search != null, function ($query) {
                $query->where('name', 'like', "%{$this->search}%");
            })->orderBy('name')->get(),
        ]);
    }


    public function loadUsers()
    {
        if (!empty($this->search)) {

            $this->resetPage();

            $query = User::with('sales', 'roles')->where('name', 'like', "%{$this->search}%")
                ->orWhere('email', 'like', "%{$this->search}%")
                ->orderBy('name', 'asc');
        } else {
            $query =  User::with('sales', 'roles')->orderBy('id', 'asc');
        }

        $this->records = $query->count();

        return $query->paginate($this->pagination);
    }


    public function Add()
    {
        $this->resetValidation();
        $this->resetExcept('user');
        $this->user = new User();
        $this->editing = false;
        $this->dispatch('init-new');
    }

    public function Edit(User $user)
    {
        $this->resetValidation();
        $this->user = $user;
        $this->editing = true;
        $this->temppwd = $user->password;
        $this->pwd = null;
    }

    public function cancelEdit()
    {
        $this->resetValidation();
        $this->user = new User();
        $this->editing = false;
        $this->search = null;
        $this->dispatch('init-new');
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

        // save model
        $this->user->save();


        $this->dispatch('noty', msg: $this->user->id != null ? 'USUARIO ACTUALIZADO CORRECTAMENTE' : 'USUARIO REGISTRADO CON ÉXITO');
        // dd($this->user->profile);
        $this->assignRole($this->user->id, $this->getRoleId($this->user->profile));

        $this->resetExcept('user');
        $this->user = new User();
        $this->user->status = 'Active';
        $this->user->profile = 0;
    }

    public function getRoleId($role)
    {
        if ($role) {
            $role = Role::where('name', $role)->first();
            return $role->id;
        }

        return 0;
    }
    public function Destroy(User $user)
    {
        if ($user->sales->count() > 0) {
            $this->dispatch('noty', msg: 'EL USUARIO TIENE VENTAS RELACIONADAS, NO ES POSIBLE ELIMINARLO');
            return;
        }
        if ($user->purchases()->count() > 0) {
            $this->dispatch('noty', msg: 'EL USUARIO TIENE COMPRAS RELACIONADAS, NO ES POSIBLE ELIMINARLO');
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
}
