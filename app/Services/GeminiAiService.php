<?php

namespace App\Services;

use App\Models\Configuration;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiAiService
{
    protected string $model;
    protected int $timeout;

    public function __construct(?string $model = null, int $timeout = 60)
    {
        if ($model) {
            $this->model = $model;
        } else {
            $config = Configuration::first();
            $this->model = $config->ai_settings['model'] ?? 'gemini-flash-latest';
        }
        $this->timeout = $timeout;
    }

    /**
     * Get the active Gemini API Key from database or .env
     */
    public function getApiKey(): ?string
    {
        $config = Configuration::first();
        if ($config && !empty($config->gemini_api_key)) {
            return trim($config->gemini_api_key);
        }

        return env('GEMINI_API_KEY') ?: config('services.gemini.key');
    }

    /**
     * Check if Gemini AI is configured with a valid key.
     */
    public function isConfigured(): bool
    {
        return !empty($this->getApiKey());
    }

    /**
     * List all models supported by the provided API key that support generateContent.
     */
    public function listAvailableModels(?string $overrideKey = null): array
    {
        $key = $overrideKey ? trim($overrideKey) : $this->getApiKey();
        if (empty($key)) {
            return [];
        }

        try {
            $response = Http::timeout(10)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$key}");
            if ($response->successful()) {
                $data = $response->json();
                $models = [];
                foreach ($data['models'] ?? [] as $m) {
                    $methods = $m['supportedGenerationMethods'] ?? [];
                    if (in_array('generateContent', $methods)) {
                        $modelName = str_replace('models/', '', $m['name']);
                        $models[] = [
                            'id' => $modelName,
                            'name' => $m['displayName'] ?? $modelName,
                            'description' => $m['description'] ?? '',
                        ];
                    }
                }
                return $models;
            }
        } catch (\Throwable $e) {
            Log::warning('GeminiAiService::listAvailableModels failed: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Test connection to Gemini API with a minimal ping.
     */
    public function testConnection(?string $overrideKey = null): array
    {
        $key = $overrideKey ? trim($overrideKey) : $this->getApiKey();

        if (empty($key)) {
            return [
                'success' => false,
                'message' => 'No se ha configurado ninguna API Key de Google Gemini.',
            ];
        }

        try {
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent?key={$key}";

            $payload = [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => 'Responde únicamente con la palabra OK.']
                        ]
                    ]
                ],
                'generationConfig' => [
                    'maxOutputTokens' => 500,
                    'temperature' => 0.1,
                ]
            ];

            $response = Http::timeout($this->timeout)
                ->withHeaders(['Content-Type' => 'application/json'])
                ->post($url, $payload);

            if ($response->successful()) {
                return [
                    'success' => true,
                    'model' => $this->model,
                    'message' => '¡Conexión exitosa con Google Gemini AI (Modelo: ' . $this->model . ')!',
                ];
            }

            $errorData = $response->json();
            $errorMsg = $errorData['error']['message'] ?? ('Error HTTP ' . $response->status() . ': ' . $response->body());

            $isModelIssue = $response->status() === 404 
                || str_contains(strtolower($errorMsg), 'not found') 
                || str_contains(strtolower($errorMsg), 'not supported')
                || str_contains(strtolower($errorMsg), 'no longer available')
                || str_contains(strtolower($errorMsg), 'update your code');

            if ($isModelIssue) {
                $candidatesToTry = [];

                // 1. Check if Google recommended a specific model in the error message
                if (preg_match('/models\/([a-zA-Z0-9\.\-_]+)/', $errorMsg, $matches)) {
                    $googleRecommended = $matches[1];
                    if ($googleRecommended !== $this->model) {
                        $candidatesToTry[] = $googleRecommended;
                    }
                }

                // 2. Add evergreen gemini-flash-latest
                if (!in_array('gemini-flash-latest', $candidatesToTry) && $this->model !== 'gemini-flash-latest') {
                    $candidatesToTry[] = 'gemini-flash-latest';
                }

                // 3. Fetch models from user's account and add other flash models
                $available = $this->listAvailableModels($key);
                $modelIds = !empty($available) ? array_column($available, 'id') : [];
                foreach ($modelIds as $mId) {
                    if (str_contains($mId, 'flash') && !in_array($mId, $candidatesToTry) && $mId !== $this->model) {
                        $candidatesToTry[] = $mId;
                    }
                }

                foreach ($candidatesToTry as $suggested) {
                    $retryUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$suggested}:generateContent?key={$key}";
                    $retryResponse = Http::timeout($this->timeout)
                        ->withHeaders(['Content-Type' => 'application/json'])
                        ->post($retryUrl, $payload);

                    if ($retryResponse->successful()) {
                        $this->model = $suggested;
                        return [
                            'success' => true,
                            'model' => $suggested,
                            'message' => "¡Conexión exitosa! El modelo anterior no estaba disponible, se conectó usando '{$suggested}'.",
                            'available_models' => $available,
                            'suggested_model' => $suggested,
                        ];
                    }
                }

                return [
                    'success' => false,
                    'message' => "Google AI: {$errorMsg}",
                    'available_models' => $available,
                ];
            }

            return [
                'success' => false,
                'message' => 'Google AI rechazó la conexión: ' . $errorMsg,
            ];
        } catch (\Throwable $th) {
            Log::error('GeminiAiService::testConnection failed: ' . $th->getMessage());
            return [
                'success' => false,
                'message' => 'No se pudo conectar con los servidores de Google: ' . $th->getMessage(),
            ];
        }
    }

    /**
     * Analyze a purchase invoice document (Image or PDF) using Gemini Vision OCR.
     * Returns structured JSON data with supplier info, invoice details, and item rows.
     */
    public function analyzePurchaseInvoice(string $base64Data, string $mimeType = 'image/jpeg'): array
    {
        $key = $this->getApiKey();
        if (empty($key)) {
            return [
                'success' => false,
                'error' => 'La API Key de Google Gemini no está configurada.',
            ];
        }

        $systemPrompt = <<<PROMPT
Eres un asistente experto en contabilidad y auditoría de facturas comerciales de compras y proveedores.
Analiza la imagen o documento de la factura proporcionada y extrae los datos con la máxima precisión posible.
Debes responder ÚNICAMENTE con un objeto JSON válido, sin bloques de código markdown (sin ```json ni ```), sin texto antes ni después.

El formato JSON debe cumplir estrictamente esta estructura:
{
  "supplier_name": "Nombre o Razón Social del proveedor o null si no es legible",
  "supplier_tax_id": "RIF, RUT, RFC o número de identificación fiscal o null",
  "invoice_number": "Número de factura o control legible o null",
  "invoice_date": "Fecha de la factura en formato YYYY-MM-DD o null si no se identifica",
  "currency": "USD, VES, COP u otra moneda identificada (default: USD)",
  "items": [
    {
      "description": "Nombre o descripción completa del producto en mayúsculas",
      "name": "Nombre o descripción completa del producto en mayúsculas",
      "sku": "Código de barra o SKU si aparece, o null",
      "quantity": 1.0,
      "unit_price": 10.0,
      "unit_cost": 10.0,
      "total_price": 10.0,
      "total_cost": 10.0
    }
  ],
  "subtotal": 0.0,
  "tax_amount": 0.0,
  "total_amount": 0.0
}

Reglas críticas:
1. Convierte todos los nombres de productos a MAYÚSCULAS.
2. Si una cantidad o costo tiene decimales, represéntalo como número de punto flotante.
3. No inventes productos. Extrae solo lo que esté visible en las filas de la factura.
4. Si el subtotal o total no está explícito, calcúlalo a partir de la suma de los items.
PROMPT;

        $payload = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $systemPrompt],
                        [
                            'inline_data' => [
                                'mime_type' => $mimeType,
                                'data' => $base64Data,
                            ]
                        ]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.1,
                'responseMimeType' => 'application/json',
            ]
        ];

        $candidates = array_unique([
            $this->model,
            'gemini-flash-lite-latest',
            'gemini-flash-latest',
            'gemini-2.0-flash',
            'gemini-2.0-flash-lite',
        ]);

        $lastError = 'No se pudo procesar la factura';

        foreach ($candidates as $candidateModel) {
            try {
                $url = "https://generativelanguage.googleapis.com/v1beta/models/{$candidateModel}:generateContent?key={$key}";

                $response = Http::timeout($this->timeout)
                    ->withHeaders(['Content-Type' => 'application/json'])
                    ->post($url, $payload);

                if (!$response->successful()) {
                    $errorData = $response->json();
                    $errorMsg = $errorData['error']['message'] ?? 'Error ' . $response->status() . ': ' . $response->body();
                    $lastError = $errorMsg;
                    Log::warning("GeminiAiService::analyzePurchaseInvoice model '{$candidateModel}' error: {$errorMsg}, trying fallback model...");
                    continue;
                }

                $responseData = $response->json();
                $rawText = $responseData['candidates'][0]['content']['parts'][0]['text'] ?? '';

                // Clean any possible markdown wrappers
                $cleanJson = trim(preg_replace('/^```(?:json)?|```$/m', '', trim($rawText)));

                $parsed = json_decode($cleanJson, true);

                if (!is_array($parsed) || !isset($parsed['items'])) {
                    $lastError = 'La respuesta de la IA no pudo ser interpretada como una factura válida.';
                    Log::warning("GeminiAiService: Invalid JSON from '{$candidateModel}', trying next model...", ['raw' => $rawText]);
                    continue;
                }

                // Normalize items defensively so any key (name/description, unit_cost/unit_price) is always populated
                if (is_array($parsed['items'])) {
                    foreach ($parsed['items'] as &$it) {
                        if (!is_array($it)) continue;
                        $desc = trim($it['description'] ?? $it['name'] ?? $it['producto'] ?? $it['articulo'] ?? '');
                        $cost = floatval($it['unit_price'] ?? $it['unit_cost'] ?? $it['costo'] ?? $it['precio'] ?? $it['cost'] ?? $it['price'] ?? 0);
                        $qty = floatval($it['quantity'] ?? $it['qty'] ?? $it['cantidad'] ?? $it['cant'] ?? 1);
                        $it['name'] = $desc;
                        $it['description'] = $desc;
                        $it['unit_cost'] = $cost;
                        $it['unit_price'] = $cost;
                        $it['quantity'] = $qty;
                    }
                    unset($it);
                }

                return [
                    'success' => true,
                    'data' => $parsed,
                    'model_used' => $candidateModel,
                ];
            } catch (\Throwable $th) {
                $lastError = $th->getMessage();
                Log::warning("GeminiAiService::analyzePurchaseInvoice model '{$candidateModel}' exception: {$lastError}, trying fallback model...");
                continue;
            }
        }

        return [
            'success' => false,
            'error' => "Google Gemini: {$lastError}",
        ];
    }
}
