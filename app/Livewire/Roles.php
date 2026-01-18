<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Livewire\Attributes\On;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class Roles extends Component
{
    public $search;
    public  $roleName, $roleId, $roleLevel = 0;
    public $permissionName, $permissionId;

    function mount()
    {
        // Strict protection: Only Admin can access this component
        // Strict protection: Only Admin can access this component
        // Also allow the specific super admin email just in case they lost the role
        if (!auth()->user()->hasRole('Admin') && auth()->user()->email !== 'jhosagid77@gmail.com') {
            abort(403, 'NO TIENES AUTORIZACIÓN PARA ACCEDER A ESTE MÓDULO');
        }

        session(['map' => '', 'child' => '', 'pos' => 'Control de Roles y Permisos']);
    }


    public function render()
    {
        return view('livewire.roles.roles', [
            'roles' => Role::orderBy('level', 'desc')->orderBy('name')->get(), // Order by level first
            'permisos' => Permission::when($this->search != null, function ($query) {
                $query->where('name', 'like', "%{$this->search}%");
            })->orderBy('name')->get(),
        ]);
    }

    //-------------------------------------------------------//
    //roles//
    function createRole()
    {
        $this->resetValidation();

        if (empty($this->roleName)) {
            $this->addError('roleName', 'Ingresa el nombre del Role');
            return;
        }

        // verificar si ya existe un role con el mismo nombre
        if (Role::where('name', $this->roleName)->exists()) {
            $this->addError('roleName', 'Ya existe un role con este nombre');
            return;
        }


        Role::create([
            'name' =>  $this->roleName,
            'level' => $this->roleLevel
        ]);
        $this->dispatch('noty', msg: 'Se ha creado con éxito el role ' . $this->roleName);
        $this->reset('roleName', 'roleLevel');
    }

    function Edit(Role $role)
    {
        $this->roleName = $role->name;
        $this->roleId = $role->id;
        $this->roleLevel = $role->level;
    }

    function cancelRoleEdit()
    {
        $this->reset('roleName', 'roleId', 'roleLevel');
    }

    function updateRole()
    {
        $this->resetValidation();

        if (empty($this->roleName)) {
            $this->addError('roleName', 'Ingresa el nombre del Role');
            return;
        }

        // verificar si existe un role con el mismo nombre en otro id
        if (Role::where('name', $this->roleName)->where('id', '!=', $this->roleId)->exists()) {
            $this->addError('roleName', 'Ya existe un role con este nombre');
            return;
        }

        Role::where('id', $this->roleId)->update([
            'name' =>  $this->roleName,
            'level' => $this->roleLevel
        ]);
        $this->dispatch('noty', msg: 'Se actualizó el role con éxito ');
        $this->reset('roleName', 'roleId', 'roleLevel');
    }

    #[On('destroyRole')]
    function destroyRole($id)
    {
        $role = Role::find($id);

        if (User::role($role->name)->count() > 0) {
            $this->dispatch('noty', msg: 'No se puede eliminar el role ' . $role->name . ' porque hay usuarios asignados a este role');
            return;
        }

        $role->delete();

        $this->dispatch('noty', msg: 'Se eliminó el role ' . $role->name . ' del sistema');
    }

    //--------------------------------------------------------------//
    //permisos//
    function createPermission()
    {
        $this->resetValidation();

        if (empty($this->permissionName)) {
            $this->addError('permissionName', 'Ingresa el nombre del Permiso');
            return;
        }

        // checar si existe un permison con el mismo nombre
        if (Permission::where('name', $this->permissionName)->exists()) {
            $this->addError('permissionName', 'Ya existe un permiso con este nombre');
            return;
        }


        Permission::create(['name' =>  $this->permissionName]);
        $this->dispatch('noty', msg: "Se creo el permiso  $this->permissionName  exitosamente");
        $this->permissionName = '';
    }


    function EditPermission(Permission $permission)
    {
        $this->permissionName = $permission->name;
        $this->permissionId = $permission->id;
    }


    function cancelPermissionEdit()
    {
        $this->reset('permissionName', 'permissionId');
    }

    function updatePermission()
    {
        $this->resetValidation();

        if (empty($this->permissionName)) {
            $this->addError('permissionName', 'Ingresa el nombre del Permiso');
            return;
        }

        // checar si existe un permison con el mismo nombre
        if (Permission::where('name', $this->permissionName)->where('id', '!=', $this->permissionId)->exists()) {
            $this->addError('permissionName', 'Ya existe un permiso con este nombre');
            return;
        }

        Permission::where('id', $this->permissionId)->update(['name' =>  $this->permissionName]);
        $this->dispatch('noty', msg: "Se actualizó el permiso  $this->permissionName  correctamente");
        $this->reset('permissionName', 'permissionId');
    }

    #[On('destroyPermission')]
    function destroyPermission($id)
    {
        $permission = Permission::find($id);

        // Verifica si hay roles asignados a este permiso
        if ($permission->roles()->count() > 0) {
            $this->dispatch('noty', msg: 'No se puede eliminar el permiso ' . $permission->name . ' porque hay roles asignados a él');
            return;
        }

        $permission->delete();

        $this->dispatch('noty', msg: 'Se eliminó el permiso ' . $permission->name . ' del sistema');
    }
}
