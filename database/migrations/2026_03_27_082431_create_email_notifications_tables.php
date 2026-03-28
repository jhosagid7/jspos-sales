<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Create email_templates
        Schema::create('email_templates', function (Blueprint $table) {
            $table->id();
            $table->string('event_type')->unique(); 
            $table->string('subject')->nullable();
            $table->text('body')->nullable();
            $table->boolean('is_active')->default(false);
            $table->enum('dispatch_mode', ['auto', 'manual'])->default('auto');
            $table->timestamps();
        });

        // Seed some initial email templates
        DB::table('email_templates')->insert([
            ['event_type' => 'sale_created', 'subject' => 'Factura de Compra #[FACTURA]', 'body' => 'Hola [CLIENTE], adjunto encontrarás el recibo de tu compra. Gracias por tu preferencia!', 'is_active' => true, 'dispatch_mode' => 'auto', 'created_at' => now(), 'updated_at' => now()],
            ['event_type' => 'payment_received', 'subject' => 'Recibo de Pago de Factura #[FACTURA_PAGADA]', 'body' => 'Hola [CLIENTE], hemos recibido tu abono por [MONTO_PAGADO]. Tu saldo restante es de [SALDO_RESTANTE].', 'is_active' => true, 'dispatch_mode' => 'auto', 'created_at' => now(), 'updated_at' => now()],
            ['event_type' => 'cargo_created', 'subject' => 'Nuevo Cargo Registrado', 'body' => 'Hola, se ha registrado un nuevo Cargo #[CARGO_ID] por el motivo: [MOTIVO]. Responsable: [USUARIO]. Por favor revisa el panel para su aprobación.', 'is_active' => true, 'dispatch_mode' => 'auto', 'created_at' => now(), 'updated_at' => now()],
            ['event_type' => 'descargo_created', 'subject' => 'Nuevo Descargo Registrado', 'body' => 'Se ha registrado un ajuste de salida de inventario. ID #[DESCARGO_ID] por motivo: [MOTIVO].', 'is_active' => true, 'dispatch_mode' => 'auto', 'created_at' => now(), 'updated_at' => now()],
        ]);

        // 2. Create email_messages
        Schema::create('email_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('related_model_id')->nullable();
            $table->string('related_model_type')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->string('email_address')->nullable();
            $table->string('subject')->nullable();
            $table->text('message_body')->nullable();
            $table->string('attachment_path')->nullable();
            $table->enum('status', ['pending', 'sent', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        // 3. Alter whatsapp_templates
        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->enum('dispatch_mode', ['auto', 'manual'])->default('auto')->after('is_active');
        });

        // 4. Alter customers
        Schema::table('customers', function (Blueprint $table) {
            $table->boolean('email_notify_sales')->default(false)->after('whatsapp_notify_payments');
            $table->boolean('email_notify_payments')->default(false)->after('email_notify_sales');
            $table->enum('wa_dispatch_mode', ['auto', 'manual'])->default('auto')->after('email_notify_payments');
            $table->enum('email_dispatch_mode', ['auto', 'manual'])->default('auto')->after('wa_dispatch_mode');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['email_notify_sales', 'email_notify_payments', 'wa_dispatch_mode', 'email_dispatch_mode']);
        });

        Schema::table('whatsapp_templates', function (Blueprint $table) {
            $table->dropColumn('dispatch_mode');
        });

        Schema::dropIfExists('email_messages');
        Schema::dropIfExists('email_templates');
    }
};
