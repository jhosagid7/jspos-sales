<?php

namespace App\Livewire;

use App\Models\User;
use Livewire\Component;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AsignarPermisos extends Component
{
    public $search;
    public  $role, $roleSelectedId,  $permissionId;
    public $users = [], $roles = [];

    function mount()
    {
        // Strict protection: Only Admin can access this component
        if (!auth()->user()->hasRole('Admin') && auth()->user()->email !== 'jhosagid77@gmail.com') {
            abort(403, 'NO TIENES AUTORIZACIÓN PARA ACCEDER A ESTE MÓDULO');
        }

        session(['map' => '', 'child' => '', 'pos' => 'Asignación de Roles y Permisos']);

        $this->users = User::orderBy('id')->get();
        $this->roles = Role::with('permissions')->orderBy('name')->get();
        if (count($this->roles) > 0) {
            $this->role = Role::find($this->roles[0]->id);
            $this->roleSelectedId = $this->role->id;
        }
    }


    public function render()
    {
        $permisos = Permission::when($this->search != null, function ($query) {
            $query->where('name', 'like', "%{$this->search}%");
        })->orderBy('name')->get();

        // Group and Translate Permissions
        $groupedPermissions = $permisos->groupBy(function ($item) {
            return explode('.', $item->name)[0];
        })->map(function ($group, $key) {
            $groupName = $this->translateGroup($key);
            return [
                'name' => $groupName,
                'permissions' => $group->map(function ($perm) {
                    $perm->display_name = $this->translatePermission($perm->name);
                    return $perm;
                })
            ];
        })->sortBy('name');

        return view('livewire.roles.asignar-permisos', [
            'permisos' => $permisos, // Keep for compatibility if needed, but we'll use groupedPermissions
            'groupedPermissions' => $groupedPermissions
        ]);
    }

    private function translateGroup($key)
    {
        $map = [
            'sales' => 'Ventas',
            'warehouses' => 'Depósitos',
            'cash_register' => 'Caja',
            'settings' => 'Configuración',
            'products' => 'Productos',
            'categories' => 'Categorías',
            'users' => 'Usuarios',
            'roles' => 'Roles',
            'reports' => 'Reportes',
            'customers' => 'Clientes',
            'suppliers' => 'Proveedores',
            'purchases' => 'Compras',
        ];
        return $map[$key] ?? ucfirst($key);
    }

    private function translatePermission($name)
    {
        $parts = explode('.', $name);
        $action = $parts[1] ?? $name;

        $map = [
            'create' => 'Crear',
            'edit' => 'Editar',
            'delete' => 'Eliminar',
            'view' => 'Ver',
            'view_all' => 'Ver Todos',
            'view_own' => 'Ver Propios',
            'switch_warehouse' => 'Cambiar Depósito',
            'mix_warehouses' => 'Mezclar Depósitos',
            'close' => 'Cerrar',
            'open' => 'Abrir',
            'print' => 'Imprimir',
            'download' => 'Descargar',
            'assign' => 'Asignar',
            'revoke' => 'Revocar',
        ];

        return $map[$action] ?? ucfirst(str_replace('_', ' ', $action));
    }

    function updatedRoleSelectedId()
    {
        $this->role = Role::find($this->roleSelectedId);
    }

    public function assignRole($userId, $roleId)
    {

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

            $this->UpdateProfileRoleUser($userId, $this->getRoleName($roleId));
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar asignar el role: {$th->getMessage()} ");
        }
    }

    function assignPermission($permissionId, $checkState)
    {
        try {
            if ($this->roleSelectedId == null) {
                $this->dispatch('noty', msg: "Selecciona el role para asignar el permiso");
                return;
            }

            $role = Role::find($this->roleSelectedId);
            $permission = Permission::find($permissionId);

            if ($checkState) {
                // asignar el permiso al role
                $role->givePermissionTo($permission);
                $message = 'Se asignó';
            } else {
                // eliminar el permiso del role
                $role->revokePermissionTo($permission);
                $message = 'Se eliminó';
            }


            // feedback
            $this->dispatch('noty', msg: "$message el permiso  $permission->name  al rol  $role->name");
            //
        } catch (\Exception $th) {
            $this->dispatch('noty', msg: "Error al intentar asignar el permiso al role : {$th->getMessage()} ");
        }
    }

    function assignRevokeAllPermissions($checkState)
    {
        $role = Role::find($this->roleSelectedId);
        $permissions = Permission::all();

        if ($role) {
            if ($checkState) {
                $role->syncPermissions($permissions);
                $message = "Se asignaron todos los permisos al role  $role->name";
            } else {
                $role->revokePermissionTo($permissions);
                $message = "Se revocaron todos los permisos al role  $role->name";
            }
            $this->dispatch('noty', msg: $message);
        } else {
            $this->dispatch('noty', msg: 'No se encuentra en sistema el role seleccionado');
        }
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


    public function getRoleName($roleId)
    {
        $role = Role::where('id', $roleId)->first();
        return $role->name;
    }
}
