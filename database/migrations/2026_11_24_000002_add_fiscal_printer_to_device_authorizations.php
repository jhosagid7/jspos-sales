<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds fiscal printer assignment fields to device_authorizations and users tables.
 *
 * device_authorizations (per-cajero-PC fiscal printer):
 *   - fiscal_printer_driver     string   'intTfhka' | 'tfhkaNet' | 'tcp' | null
 *   - fiscal_printer_port       string   COM port (e.g. 'COM1') or IP:port (e.g. '192.168.1.100:9100')
 *   - fiscal_printer_model      string   'hka80' | 'pp9' | 'pp9plus'
 *   - fiscal_printer_enabled    boolean  Override: enable/disable fiscal for this device
 *
 * users (per-seller fiscal assignment override — same pattern as thermal printer):
 *   - fiscal_printer_device_id  unsignedBigInteger  FK to device_authorizations
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_authorizations', function (Blueprint $table) {
            if (!Schema::hasColumn('device_authorizations', 'fiscal_printer_driver')) {
                $table->string('fiscal_printer_driver', 20)->nullable()->after('printer_password')
                    ->comment('intTfhka | tfhkaNet | tcp — null = use global default');
            }
            if (!Schema::hasColumn('device_authorizations', 'fiscal_printer_port')) {
                $table->string('fiscal_printer_port', 60)->nullable()->after('fiscal_printer_driver')
                    ->comment('COM1 for local serial, or 192.168.1.x:9100 for network agent');
            }
            if (!Schema::hasColumn('device_authorizations', 'fiscal_printer_model')) {
                $table->string('fiscal_printer_model', 20)->nullable()->after('fiscal_printer_port')
                    ->comment('hka80 | pp9 | pp9plus — overrides global configuration model');
            }
            if (!Schema::hasColumn('device_authorizations', 'fiscal_printer_enabled')) {
                $table->boolean('fiscal_printer_enabled')->nullable()->after('fiscal_printer_model')
                    ->comment('null = inherit from global; true/false = device-level override');
            }
        });
    }

    public function down(): void
    {
        Schema::table('device_authorizations', function (Blueprint $table) {
            $cols = [
                'fiscal_printer_driver',
                'fiscal_printer_port',
                'fiscal_printer_model',
                'fiscal_printer_enabled',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('device_authorizations', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
