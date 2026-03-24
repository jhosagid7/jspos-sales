<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\WhatsappTemplate;

class DescargoTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        WhatsappTemplate::updateOrCreate(
            ['event_type' => 'descargo_created'],
            [
                'subject' => 'Nuevo Descargo Registrado',
                'body' => "*- NUEVO DESCARGO -*\n\nSe ha registrado un ajuste de salida de inventario.\n\n*ID:* #[DESCARGO_ID]\n*MOTIVO:* [MOTIVO]\n*AUTORIZADO POR:* [AUTORIZADO]\n*RESPONSABLE:* [USUARIO]\n*FECHA:* [FECHA]\n\nSe adjunta el detalle en formato PDF para su aprobación.",
                'is_active' => true
            ]
        );
    }
}
