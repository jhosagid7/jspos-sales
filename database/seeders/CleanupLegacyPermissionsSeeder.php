<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;

class CleanupLegacyPermissionsSeeder extends Seeder
{
    public function run()
    {
        $this->command->info('Iniciando limpieza y migración de permisos...');

        // 1. Permisos a ELIMINAR (Confirmados como no usados / duplicados)
        $toDelete = [
            'compras',
            'clientes',
            'proveedores',
            'productos',
            'ventas',
            'usuarios'
        ];

        foreach ($toDelete as $name) {
            $perm = Permission::where('name', $name)->first();
            if ($perm) {
                // Revoke from all first to avoid constraint errors (though cascade usually handles this)
                DB::table('role_has_permissions')->where('permission_id', $perm->id)->delete();
                DB::table('model_has_permissions')->where('permission_id', $perm->id)->delete();
                
                $perm->delete();
                $this->command->info("Eliminado: $name");
            } else {
                $this->command->warn("No encontrado para eliminar: $name");
            }
        }

        // 2. Permisos a MIGRAR (Renombrar y reasignar)
        // Map: Old Name => New Name
        $migrations = [
            'aprobar_cargos' => 'adjustments.approve_cargo',
            'aprobar_descargos' => 'adjustments.approve_descargo',
            'guardar ordenes de ventas' => 'orders.save',
            'metodos de pago' => 'payments.methods',
            'pago con efectivo/nequi' => 'payments.method_cash',
            'pago con credito' => 'payments.method_credit',
            'pago con Banco' => 'payments.method_bank',
            'pago con Nequi' => 'payments.method_nequi', // Assuming this exists or mapping to something else
        ];

        foreach ($migrations as $oldName => $newName) {
            $oldPerm = Permission::where('name', $oldName)->first();
            
            // Create New Permission if not exists
            $newPerm = Permission::firstOrCreate(['name' => $newName, 'guard_name' => 'web']);
            
            if ($oldPerm) {
                // Find all roles that have the old permission
                $roles = Role::permission($oldName)->get();
                
                foreach ($roles as $role) {
                    if (!$role->hasPermissionTo($newName)) {
                        $role->givePermissionTo($newPerm);
                        $this->command->info("Migrado '$newName' al rol '{$role->name}'");
                    }
                }

                // Delete old permission
                $oldPerm->delete();
                $this->command->info("Eliminado antiguo: $oldName");
            } else {
                $this->command->warn("Permiso antiguo '$oldName' no encontrado. Se creó el nuevo '$newName' por si acaso.");
            }
        }
    }
}
