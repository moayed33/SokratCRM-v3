<?php

declare(strict_types=1);

namespace App\Services;

use App\Support\VoipCredentials;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class VoipService
{
    private string $apiUrl;

    private string $clientSecret;

    private int $timeout;

    public function __construct(
        ?string $apiUrl = null,
        ?string $clientSecret = null,
        ?int $timeout = null
    ) {
        $credentials = VoipCredentials::all();
        $this->apiUrl = rtrim($apiUrl ?? ($credentials['api_url'] ?? (string) config('voip.api_url')), '/');
        $this->clientSecret = $clientSecret ?? ($credentials['client_secret'] ?? (string) config('voip.client_secret'));
        $this->timeout = $timeout ?? (int) config('voip.timeout', 10);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiUrl) && ! empty($this->clientSecret);
    }

    public function isConnected(): bool
    {
        if (! $this->isConfigured()) {
            return false;
        }

        return (bool) \Illuminate\Support\Facades\Cache::remember('voip.server.connected', 30, function (): bool {
            try {
                $response = Http::withToken($this->clientSecret)
                    ->timeout(min($this->timeout, 3))
                    ->get("{$this->apiUrl}/health");

                if (! $response->successful()) {
                    return false;
                }

                $json = $response->json();
                $status = strtolower((string) ($json['status'] ?? ''));

                return $status === 'ok' || $status === 'healthy' || ($json['success'] ?? false) === true;
            } catch (\Throwable) {
                return false;
            }
        });
    }

    public function health(): array
    {
        try {
            $response = Http::withToken($this->clientSecret)
                ->timeout(min($this->timeout, 3))
                ->get("{$this->apiUrl}/health");

            return $response->successful() ? ($response->json() ?? ['status' => 'ok']) : ['status' => 'error'];
        } catch (\Throwable) {
            return ['status' => 'error'];
        }
    }

    public function pair(
        string $pairingCode,
        string $name,
        string $origin,
        string $countryCode = '20'
    ): array {
        $response = Http::timeout($this->timeout)
            ->post("{$this->apiUrl}/pair", [
                'pairing_code' => $pairingCode,
                'name' => $name,
                'origin' => $origin,
                'default_country_code' => $countryCode,
            ]);

        if ($response->failed()) {
            Log::warning('VoIP pairing rejected by server', [
                'url' => "{$this->apiUrl}/pair",
                'status' => $response->status(),
                'body' => mb_substr($response->body(), 0, 500),
            ]);

            throw new RuntimeException(
                $response->json('error') ?? 'فشل الاتصال بسيرفر VoIP للاقتران.'
            );
        }

        return $response->json();
    }

    public function capabilities(): array
    {
        return $this->request('get', '/capabilities');
    }

    public function getExtensions(): array
    {
        return $this->request('get', '/extensions');
    }

    public function getCustomerCallHistory(string $phone, array $filters = []): array
    {
        if (isset($filters['limit']) && ! isset($filters['per_page'])) {
            $filters['per_page'] = $filters['limit'];
            unset($filters['limit']);
        }
        $query = array_merge(['phone' => $phone], $filters);

        return $this->request('get', '/calls', $query);
    }

    public function getExtensionStats(string $extension, array $filters = []): array
    {
        return $this->request('get', "/extensions/{$extension}/stats", $filters);
    }

    public function createEmbedTicket(
        int|string $crmUserId,
        string $crmUserName,
        ?string $supervisorExtension = null,
        array $requestedScopes = ['live:read']
    ): array {
        $payload = [
            'crm_user_id' => (string) $crmUserId,
            'crm_user_name' => $crmUserName,
            'requested_scopes' => $requestedScopes,
        ];

        if (! empty($supervisorExtension)) {
            $payload['supervisor_extension'] = (string) $supervisorExtension;
        }

        return $this->request('post', '/embed-tickets', $payload);
    }

    public function streamRecording(string $mediaId, ?string $range = null): Response
    {
        $request = Http::withToken($this->clientSecret)->timeout(30);
        if ($range !== null && preg_match('/^bytes=\d*-\d*$/', $range) === 1) {
            $request = $request->withHeaders(['Range' => $range]);
        }

        return $request->get("{$this->apiUrl}/recordings/{$mediaId}");
    }

    private function request(string $method, string $path, array $data = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('خدمة VoIP غير مهيأة بعد.');
        }

        $url = "{$this->apiUrl}".$path;
        $http = Http::withToken($this->clientSecret)->timeout($this->timeout);

        $response = $method === 'get'
            ? $http->get($url, $data)
            : $http->post($url, $data);

        if ($response->failed()) {
            $error = $response->json('error') ?? "VoIP API error: {$response->status()}";
            Log::error('VoIP Service Request Failed', [
                'url' => $url,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            throw new RuntimeException((string) $error);
        }

        return $response->json() ?? [];
    }
}
