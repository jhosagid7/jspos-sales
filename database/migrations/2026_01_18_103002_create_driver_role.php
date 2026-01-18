<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

return new class extends Migration
{
    public function up(): void
    {
        // Ensure Spatie Permission tables exist before running
        if (Schema::hasTable('roles')) {
            // Create Driver Role if it doesn't exist
            if (!Role::where('name', 'Driver')->exists()) {
                Role::create(['name' => 'Driver', 'guard_name' => 'web']);
            }
            
            // Create specific permission for delivery if needed, or just use role check
            // For now, we'll just rely on the Role 'Driver'
        }
    }

    public function down(): void
    {
        // We generally don't delete roles in down() to avoid data loss if rolled back accidentally,
        // but for strict reversibility:
        // Role::where('name', 'Driver')->delete();
    }
};
