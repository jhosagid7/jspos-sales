<?php

namespace App\Services;

use App\Models\License;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class LicenseService
{
    /**
     * Get the unique Client ID for this installation.
     * Generates one if it doesn't exist.
     */
    public function getClientId()
    {
        // Check if we have a stored client ID in a file (persistent across DB wipes)
        $path = storage_path('app/client_id.txt');
        
        if (file_exists($path)) {
            return trim(file_get_contents($path));
        }

        // Generate a new UUID
        $clientId = (string) \Illuminate\Support\Str::uuid();
        file_put_contents($path, $clientId);

        return $clientId;
    }

    /**
     * Validate a license key string.
     * Returns true if valid and saves to DB, false otherwise.
     */
    public function activateLicense($licenseKey)
    {
        try {
            // 1. Decode the base64 license
            $decoded = base64_decode($licenseKey);
            if (!$decoded) return false;

            // 2. Extract data and signature
            // Format: JSON_DATA . "||" . SIGNATURE
            $parts = explode('||', $decoded);
            if (count($parts) !== 2) return false;

            $jsonData = $parts[0];
            $signature = base64_decode($parts[1]);

            // 3. Verify Signature using Public Key
            $publicKey = file_get_contents(base_path('public_key.pem'));
            if (!$publicKey) throw new \Exception("Public key not found");

            $valid = openssl_verify($jsonData, $signature, $publicKey, OPENSSL_ALGO_SHA512);

            if ($valid !== 1) {
                return false; // Invalid signature
            }

            // 4. Parse JSON Data
            $data = json_decode($jsonData, true);
            if (!$data || !isset($data['client_id']) || !isset($data['expires_at'])) {
                return false;
            }

            // 5. Verify Client ID matches this system
            if ($data['client_id'] !== $this->getClientId()) {
                return false; // License belongs to another machine
            }

            // 6. Save to Database
            License::create([
                'license_key' => $licenseKey,
                'client_id' => $data['client_id'],
                'expires_at' => Carbon::parse($data['expires_at']),
            ]);

            return true;

        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("License activation error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if the system has a valid active license.
     */
    public function checkLicense()
    {
        // Get the latest license
        $license = License::latest('created_at')->first();

        if (!$license) {
            return [
                'status' => 'invalid',
                'message' => 'No license found',
                'days_remaining' => 0
            ];
        }

        $expiresAt = $license->expires_at;
        $now = Carbon::now();

        if ($now->gt($expiresAt)) {
            return [
                'status' => 'expired',
                'message' => 'License expired on ' . $expiresAt->format('d/m/Y'),
                'days_remaining' => 0
            ];
        }

        $daysRemaining = $now->diffInDays($expiresAt, false);

        return [
            'status' => 'active',
            'message' => 'License active',
            'days_remaining' => (int) $daysRemaining,
            'expires_at' => $expiresAt
        ];
    }
}
