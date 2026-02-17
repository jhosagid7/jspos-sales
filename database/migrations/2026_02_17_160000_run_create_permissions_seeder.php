<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'CreatePermissionsSeeder',
            '--force' => true // Force production run
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No down action needed for seeder run
    }
};
