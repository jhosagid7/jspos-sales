<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos de Caja
        $permissions = [
            'cash_register.open',     // Abrir caja
            'cash_register.close',    // Cerrar caja
            'cash_register.view_own', // Ver solo sus propias cajas
            'cash_register.view_all', // Ver todas las cajas (Admin)
            
            // Permisos de Ventas
            'sales.create',           // Crear ventas
            'sales.view_own',         // Ver solo sus propias ventas
            'sales.view_all',         // Ver todas las ventas
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Asignar permisos al rol ADMIN (si existe o crearlo)
        $adminRole = Role::firstOrCreate(['name' => 'ADMIN']);
        $adminRole->givePermissionTo(Permission::all());

        // Crear rol CAJERO (si no existe) y asignar permisos básicos
        $cashierRole = Role::firstOrCreate(['name' => 'CAJERO']);
        $cashierRole->givePermissionTo([
            'cash_register.open',
            'cash_register.close',
            'cash_register.view_own',
            'sales.create',
            'sales.view_own',
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Opcional: Eliminar los permisos creados
        $permissions = [
            'cash_register.open',
            'cash_register.close',
            'cash_register.view_own',
            'cash_register.view_all',
            'sales.create',
            'sales.view_own',
            'sales.view_all',
        ];

        foreach ($permissions as $permission) {
            $p = Permission::where('name', $permission)->first();
            if ($p) {
                $p->delete();
            }
        }
    }
};
