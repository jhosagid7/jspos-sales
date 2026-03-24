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
        // Ensure Adjustments permissions exist
        $permissions = [
            'adjustments.approve_cargo',
            'adjustments.reject_cargo',
            'adjustments.delete_cargo'
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p);
        }

        // Give them to Admin by default
        $admin = Role::findByName('Admin');
        if ($admin) {
            $admin->givePermissionTo($permissions);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
