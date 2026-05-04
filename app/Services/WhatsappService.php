<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsappService
{
    protected string $apiUrl;

    public function __construct()
    {
        // By default, assuming the node script runs on localhost:3000
        $this->apiUrl = config('services.whatsapp.url', 'http://localhost:3000');
    }

    /**
     * Comprueba si la API de Node.js está respondiendo y autenticada.
     */
    public function checkStatus(): bool
    {
        try {
            $response = Http::timeout(5)->get("{$this->apiUrl}/status");
            if ($response->successful()) {
                return $response->json('isReady', false);
            }
        } catch (\Exception $e) {
            Log::error('Error verificando estado de WhatsApp API: ' . $e->getMessage());
        }

        return false;
    }

    /**
     * Envía un mensaje a la API de Node.js.
     * Retorna un array ['success' => bool, 'error' => string|null]
     * 
     * @param string $phone
     * @param string $message
     * @param string|null $attachmentPath Ruta absoluta local al archivo (PDF)
     */
    public function sendMessage(string $phone, string $message, ?string $attachmentPath = null): array
    {
        // Basic Venezuela local format to international conversion
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) == 11 && str_starts_with($phone, '0')) {
            $phone = '58' . substr($phone, 1);
        } elseif (strlen($phone) == 10 && (str_starts_with($phone, '4') || str_starts_with($phone, '2'))) {
            $phone = '58' . $phone;
        }

        try {
            $request = Http::timeout(30);

            if ($attachmentPath && file_exists($attachmentPath)) {
                $filename = basename($attachmentPath);
                $request = $request->attach('attachment', file_get_contents($attachmentPath), $filename);
                
                $response = $request->post("{$this->apiUrl}/send", [
                    'phone' => $phone,
                    'message' => $message,
                    'filename' => $filename
                ]);
            } else {
                $response = $request->post("{$this->apiUrl}/send", [
                    'phone' => $phone,
                    'message' => $message,
                ]);
            }

            if ($response->successful() && $response->json('success') === true) {
                return ['success' => true, 'error' => null];
            }

            return [
                'success' => false, 
                'error' => $response->json('error', 'Error desconocido de la API')
            ];

        } catch (\Exception $e) {
            Log::error('Error comunicando con WhatsApp API: ' . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
