<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FcmService
{
    /**
     * Send a push notification to a single device token.
     */
    public function send(string $deviceToken, string $title, string $body, array $data = []): array
    {
        try {
            $accessToken = $this->getAccessToken();
            $projectId = config('fcm.project_id');

            $response = Http::withToken($accessToken)
                ->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send", [
                    'message' => [
                        'token' => $deviceToken,
                        'notification' => compact('title', 'body'),
                        // data payload: all values must be strings for FCM
                        'data' => array_map('strval', $data),
                        'android' => [
                            'priority' => 'high',
                            'notification' => [
                                'channel_id' => 'high_importance_channel',
                                'notification_priority' => 'PRIORITY_MAX',
                            ],
                        ],
                        'apns' => [
                            'headers' => ['apns-priority' => '10'],
                        ],
                    ],
                ]);

            if (!$response->successful()) {
                Log::warning('FCM send failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'token' => substr($deviceToken, 0, 15) . '...',
                ]);
            }

            return [
                'success' => $response->successful(),
                'invalid_token' => !$response->successful() && $this->isInvalidTokenError($response->body()),
            ];
        } catch (\Throwable $e) {
            Log::error('FcmService::send exception', [
                'message' => $e->getMessage(),
                'token' => substr($deviceToken, 0, 15) . '...',
            ]);

            return [
                'success' => false,
                'invalid_token' => false, // Don't assume invalid if there's a connection/config error!
            ];
        }
    }

    /**
     * Determine if an FCM error response indicates an invalid/expired token.
     */
    public function isInvalidTokenError(string $responseBody): bool
    {
        $body = json_decode($responseBody, true);
        $errorCode = $body['error']['details'][0]['errorCode'] ?? '';

        return in_array($errorCode, [
            'UNREGISTERED',
            'INVALID_ARGUMENT',
        ]);
    }

    // -------------------------------------------------------------------------
    // OAuth2 / JWT (FCM HTTP v1 API)
    // -------------------------------------------------------------------------

    /**
     * Get a valid OAuth2 access token for FCM HTTP v1.
     * Token is cached for 58 minutes (lifetime is 60 min).
     */
    private function getAccessToken(): string
    {
        return Cache::remember('fcm_access_token_v2', 3480, function () {
            $serviceAccount = $this->loadServiceAccount();
            $jwt = $this->buildJwt($serviceAccount);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (!$response->successful()) {
                throw new \RuntimeException(
                    'Failed to obtain FCM access token: ' . $response->body()
                );
            }

            return $response->json('access_token');
        });
    }

    /**
     * Build a signed JWT for Google OAuth2 token exchange.
     */
    private function buildJwt(array $serviceAccount): string
    {
        $now = time();

        $header = $this->base64UrlEncode(json_encode([
            'alg' => 'RS256',
            'typ' => 'JWT',
        ]));

        $payload = $this->base64UrlEncode(json_encode([
            'iss' => $serviceAccount['client_email'],
            'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
            'aud' => 'https://oauth2.googleapis.com/token',
            'iat' => $now,
            'exp' => $now + 3600,
        ]));

        $signingInput = "{$header}.{$payload}";

        openssl_sign($signingInput, $signature, $serviceAccount['private_key'], 'SHA256');

        return "{$signingInput}." . $this->base64UrlEncode($signature);
    }

    /**
     * Load and validate the Firebase service account JSON file.
     */
    private function loadServiceAccount(): array
    {
        $path = config('fcm.service_account_path');

        if (!file_exists($path)) {
            throw new \RuntimeException(
                "Firebase service account file not found at: {$path}"
            );
        }

        $serviceAccount = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($serviceAccount['private_key'])) {
            throw new \RuntimeException('Invalid Firebase service account JSON file.');
        }

        return $serviceAccount;
    }

    /**
     * Base64 URL-safe encode (RFC 4648 §5).
     */
    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
