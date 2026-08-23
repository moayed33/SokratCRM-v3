<?php

declare(strict_types=1);

namespace App\Services\Notifications;

final readonly class ChannelResult
{
    public function __construct(
        public string $status,
        public ?string $providerMessageId = null,
        public ?string $error = null,
    ) {}

    public static function sent(?string $providerMessageId = null): self
    {
        return new self('sent', $providerMessageId);
    }

    public static function failed(string $reason): self
    {
        return new self('failed', null, $reason);
    }

    public static function suppressed(string $reason): self
    {
        return new self('suppressed', null, $reason);
    }
}
