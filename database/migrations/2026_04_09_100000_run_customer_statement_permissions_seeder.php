<?php
/**
 * Migration to automatically run the Customer Statement permissions seeder.
 * This ensures that when users update to version v1.9.79, the new permissions
 * are automatically created and assigned to the correct roles via the AutoMigrate middleware.
 */

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'AddCustomerStatementPermissionSeeder',
            '--force' => true
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down action as permissions are usually additive or handled via admin panel
    }
};
