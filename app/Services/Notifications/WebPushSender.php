<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\User;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use RuntimeException;

class WebPushSender
{
    public function __construct(
        private readonly WebPushEndpointValidator $endpointValidator,
    ) {}

    /** @param array<string, mixed> $payload */
    public function send(User $user, array $payload): ChannelResult
    {
        $subject = trim((string) config('crm_notifications.web_push.subject'));
        $publicKey = trim((string) config('crm_notifications.web_push.public_key'));
        $privateKey = trim((string) config('crm_notifications.web_push.private_key'));

        if ($subject === '' || $publicKey === '' || $privateKey === '') {
            return ChannelResult::suppressed('Web Push VAPID credentials are not configured.');
        }

        $subscriptions = $user->pushSubscriptions()->get();
        if ($subscriptions->isEmpty()) {
            return ChannelResult::suppressed('The user has no active browser push subscription.');
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $subject,
                'publicKey' => $publicKey,
                'privateKey' => $privateKey,
            ],
        ], [
            'TTL' => 300,
            'urgency' => ($payload['priority'] ?? null) === 'urgent' ? 'high' : 'normal',
        ]);

        $encoded = json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        $sent = 0;
        $errors = [];

        foreach ($subscriptions as $stored) {
            if (! $this->endpointValidator->isAllowed($stored->endpoint)
                || ! $this->endpointValidator->hasValidKeys(
                    $stored->public_key,
                    $stored->auth_token,
                )) {
                $stored->delete();

                continue;
            }
            $report = $webPush->sendOneNotification(
                new Subscription(
                    $stored->endpoint,
                    $stored->public_key,
                    $stored->auth_token,
                    $stored->content_encoding,
                ),
                $encoded,
            );

            if ($report->isSuccess()) {
                $sent++;

                continue;
            }

            if ($report->isSubscriptionExpired()) {
                $stored->delete();

                continue;
            }

            $errors[] = $report->getReason();
        }

        if ($sent > 0) {
            return ChannelResult::sent();
        }

        if ($subscriptions->count() > 0 && $errors === []) {
            return ChannelResult::suppressed('All browser push subscriptions have expired.');
        }

        throw new RuntimeException(implode('; ', array_unique($errors)) ?: 'Web Push delivery failed.');
    }
}
