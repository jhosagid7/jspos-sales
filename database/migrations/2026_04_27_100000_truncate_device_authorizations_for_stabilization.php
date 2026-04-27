<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\DeviceAuthorization;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * This migration is for administrative cleanup of old device identification mess.
     */
    public function up(): void
    {
        // Truncate the table to force all devices to re-authorize with the new stable UUID logic.
        // This is safe because we are moving from a fingerprint logic (volatile) to a stable Cookie/Session UUID logic.
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DeviceAuthorization::truncate();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        } catch (\Exception $e) {
            // If the model or table doesn't exist for some reason, we don't want to crash the update.
            \Illuminate\Support\Facades\Log::warning("Could not truncate device_authorizations: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Truncation cannot be reversed.
    }
};
