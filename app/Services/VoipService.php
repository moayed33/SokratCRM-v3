<?php

declare(strict_types=1);

namespace App\Services;

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
        $this->apiUrl = rtrim($apiUrl ?? (string) config('voip.api_url'), '/');
        $this->clientSecret = $clientSecret ?? (string) config('voip.client_secret');
        $this->timeout = $timeout ?? (int) config('voip.timeout', 10);
    }

    public function isConfigured(): bool
    {
        return ! empty($this->apiUrl) && ! empty($this->clientSecret);
    }

    public function health(): array
    {
        $response = Http::timeout($this->timeout)
            ->get("{$this->apiUrl}/health");

        return $response->successful() ? $response->json() : ['status' => 'error'];
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

    public function streamRecording(string $mediaId): Response
    {
        return Http::withToken($this->clientSecret)
            ->timeout(30)
            ->get("{$this->apiUrl}/recordings/{$mediaId}");
    }

    private function request(string $method, string $path, array $data = []): array
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException('خدمة VoIP غير مهيأة بعد.');
        }

        $url = "{$this->apiUrl}" . $path;
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
