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
     */
    public function up(): void
    {
        // Find duplicates (same IP, same UserAgent, status Approved)
        $duplicates = DB::table('device_authorizations')
            ->where('status', 'approved')
            ->select('ip_address', 'user_agent', DB::raw('count(*) as count'))
            ->groupBy('ip_address', 'user_agent')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $duplicate) {
            // Get all records for this fingerprint ordered by last_accessed_at desc
            $records = DeviceAuthorization::where('ip_address', $duplicate->ip_address)
                ->where('user_agent', $duplicate->user_agent)
                ->where('status', 'approved')
                ->orderBy('last_accessed_at', 'desc')
                ->get();
            
            // Keep the first one (most recent), delete the rest
            $records->shift();
            
            foreach ($records as $record) {
                // Only delete if it doesn't have a printer configured (to avoid losing config)
                if (empty($record->printer_name)) {
                    $record->delete();
                }
            }
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
