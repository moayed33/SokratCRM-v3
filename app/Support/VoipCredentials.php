<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

final class VoipCredentials
{
    private const KEYS = ['api_url', 'client_id', 'client_secret'];

    /** @return array<string, string> */
    public static function all(): array
    {
        $path = self::path();
        if (! is_file($path) || ! is_readable($path)) {
            return [];
        }

        $contents = file_get_contents($path);
        $decoded = is_string($contents) ? json_decode($contents, true) : null;
        if (! is_array($decoded)) {
            return [];
        }

        return collect(self::KEYS)
            ->mapWithKeys(static fn (string $key): array => [$key => trim((string) ($decoded[$key] ?? ''))])
            ->all();
    }

    /** @param array<string, string> $values */
    public static function update(array $values): void
    {
        $credentials = self::all();
        foreach (self::KEYS as $key) {
            if (array_key_exists($key, $values)) {
                $credentials[$key] = trim((string) $values[$key]);
            }
        }

        $path = self::path();
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0770, true) && ! is_dir($directory)) {
            throw new RuntimeException('تعذر إنشاء مجلد بيانات اعتماد VoIP.');
        }

        $temporaryPath = is_writable($directory)
            ? @tempnam($directory, '.voip-credentials-')
            : false;

        $targetPath = $temporaryPath !== false ? $temporaryPath : $path;

        try {
            $json = json_encode($credentials, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
            if (file_put_contents($targetPath, $json."\n", LOCK_EX) === false) {
                throw new RuntimeException('تعذر حفظ بيانات اعتماد VoIP.');
            }

            @chmod($targetPath, 0600);
            if ($temporaryPath !== false && $temporaryPath !== $path && ! @rename($temporaryPath, $path)) {
                throw new RuntimeException('تعذر تثبيت ملف بيانات اعتماد VoIP.');
            }
        } finally {
            if ($temporaryPath !== false && $temporaryPath !== $path && is_file($temporaryPath)) {
                @unlink($temporaryPath);
            }
        }
    }

    private static function path(): string
    {
        return (string) config('voip.credentials_path', storage_path('app/private/voip-credentials.json'));
    }
}
