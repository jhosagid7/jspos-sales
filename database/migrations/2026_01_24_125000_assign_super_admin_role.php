<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Ensure Role Exists
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web'], ['level' => 1000]);

        // Find User
        $user = User::where('email', 'jhosagid77@gmail.com')->first();

        if ($user) {
            // Assign Role
            $user->assignRole($role);
            // Update Profile Field for UI
            $user->profile = 'Super Admin';
            $user->save();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Optional: Revert changes
    }
};
