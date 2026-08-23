<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Jobs\SendNotificationDigest;
use App\Models\NotificationDelivery;
use App\Models\NotificationPreference;
use Carbon\CarbonInterface;
use Illuminate\Support\Str;
use Throwable;

class NotificationDigestDispatcher
{
    public function queueDueDigests(?CarbonInterface $at = null): int
    {
        if (! config('crm_notifications.channels.mail')) {
            return 0;
        }

        $now = ($at ?? now())->copy();
        $queued = 0;
        NotificationDelivery::query()
            ->where('status', NotificationDelivery::STATUS_PROCESSING)
            ->where('claim_type', 'digest')
            ->where('processing_started_at', '<=', $now->copy()->subMinutes(20))
            ->update([
                'status' => NotificationDelivery::STATUS_DIGEST_PENDING,
                'claim_token' => null,
                'claim_type' => null,
                'processing_started_at' => null,
            ]);

        NotificationPreference::query()
            ->with('user')
            ->where('daily_email_digest', true)
            ->where('mail_enabled', true)
            ->orderBy('id')
            ->chunkById(100, function ($preferences) use ($now, &$queued): void {
                foreach ($preferences as $preference) {
                    $user = $preference->user;
                    if (! $user?->is_active || ! $user->email) {
                        continue;
                    }

                    $localNow = $now->copy()->setTimezone($user->timezone ?: 'UTC');
                    if ((int) $localNow->format('G') < 8) {
                        continue;
                    }

                    $lastDigestDate = $preference->last_digest_at?->copy()
                        ->setTimezone($user->timezone ?: 'UTC')->toDateString();
                    if ($lastDigestDate === $localNow->toDateString()) {
                        continue;
                    }

                    $deliveryIds = NotificationDelivery::query()
                        ->where('status', NotificationDelivery::STATUS_DIGEST_PENDING)
                        ->whereHas('occurrence', fn ($query) => $query
                            ->where('recipient_user_id', $user->getKey()))
                        ->orderBy('id')
                        ->limit(100)
                        ->pluck('id')
                        ->map(static fn ($id): int => (int) $id)
                        ->all();

                    if ($deliveryIds === []) {
                        continue;
                    }

                    $claimToken = (string) Str::uuid();
                    NotificationDelivery::query()
                        ->whereKey($deliveryIds)
                        ->where('status', NotificationDelivery::STATUS_DIGEST_PENDING)
                        ->update([
                            'status' => NotificationDelivery::STATUS_PROCESSING,
                            'claim_token' => $claimToken,
                            'claim_type' => 'digest',
                            'processing_started_at' => $now,
                        ]);
                    $claimedIds = NotificationDelivery::query()
                        ->where('claim_token', $claimToken)
                        ->pluck('id')
                        ->map(static fn ($id): int => (int) $id)
                        ->all();
                    if ($claimedIds === []) {
                        continue;
                    }

                    try {
                        SendNotificationDigest::dispatch(
                            $user->getKey(),
                            $claimedIds,
                            $claimToken,
                        );
                    } catch (Throwable $exception) {
                        NotificationDelivery::query()
                            ->where('claim_token', $claimToken)
                            ->update([
                                'status' => NotificationDelivery::STATUS_DIGEST_PENDING,
                                'claim_token' => null,
                                'claim_type' => null,
                                'processing_started_at' => null,
                            ]);

                        throw $exception;
                    }

                    $queued++;
                }
            });

        return $queued;
    }
}
