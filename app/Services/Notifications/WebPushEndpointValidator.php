<?php

declare(strict_types=1);

namespace App\Services\Notifications;

final class WebPushEndpointValidator
{
    public function isAllowed(string $endpoint): bool
    {
        $parts = parse_url($endpoint);
        if (! is_array($parts)
            || strtolower((string) ($parts['scheme'] ?? '')) !== 'https'
            || isset($parts['user'], $parts['pass'])
            || (isset($parts['port']) && (int) $parts['port'] !== 443)) {
            return false;
        }

        $host = strtolower(rtrim((string) ($parts['host'] ?? ''), '.'));
        $allowedHosts = config('crm_notifications.web_push.allowed_hosts', []);

        return $host !== '' && in_array($host, $allowedHosts, true);
    }

    public function hasValidKeys(string $publicKey, string $authToken): bool
    {
        $decodedPublicKey = $this->decodeBase64Url($publicKey);
        $decodedAuthToken = $this->decodeBase64Url($authToken);

        return $decodedPublicKey !== null
            && strlen($decodedPublicKey) === 65
            && ord($decodedPublicKey[0]) === 4
            && $decodedAuthToken !== null
            && strlen($decodedAuthToken) === 16;
    }

    private function decodeBase64Url(string $value): ?string
    {
        if ($value === '' || preg_match('/^[A-Za-z0-9_-]+$/', $value) !== 1) {
            return null;
        }

        $normalized = strtr($value, '-_', '+/');
        $padding = strlen($normalized) % 4;
        if ($padding !== 0) {
            $normalized .= str_repeat('=', 4 - $padding);
        }

        $decoded = base64_decode($normalized, true);

        return $decoded === false ? null : $decoded;
    }
}
