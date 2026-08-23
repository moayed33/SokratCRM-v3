<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Mail\CrmReminderMail;
use App\Models\CalendarEvent;
use App\Models\Lead;
use App\Models\NotificationDelivery;
use App\Models\NotificationPreference;
use App\Models\User;
use App\Services\Notifications\ChannelResult;
use App\Services\Notifications\NotificationRecipientResolver;
use App\Services\Notifications\TwilioMessageSender;
use App\Services\Notifications\WebPushSender;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendNotificationDelivery implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    /** @var list<int> */
    public array $backoff = [60, 300, 900];

    public function __construct(
        public readonly int $deliveryId,
        public readonly string $claimToken,
    ) {}

    public function handle(
        TwilioMessageSender $twilio,
        WebPushSender $webPush,
        NotificationRecipientResolver $recipientResolver,
    ): void {
        $delivery = NotificationDelivery::query()
            ->with(['occurrence.recipient'])
            ->whereKey($this->deliveryId)
            ->where('status', NotificationDelivery::STATUS_PROCESSING)
            ->where('claim_token', $this->claimToken)
            ->first();

        if (! $delivery) {
            return;
        }

        $occurrence = $delivery->occurrence;
        $recipient = $occurrence?->recipient;
        $source = $occurrence ? $this->resolveSource($occurrence->source_kind, $occurrence->source_id) : null;
        $preference = $recipient
            ? NotificationPreference::query()->where('user_id', $recipient->getKey())->first()
            : null;

        if (! $occurrence || $occurrence->status !== 'dispatched'
            || ! $recipient?->is_active || ! $source
            || ! $recipientResolver->canReceive($recipient, $source)) {
            $this->suppress('Recipient or source access is no longer available.');

            return;
        }

        if (! (bool) config("crm_notifications.channels.{$delivery->channel}", false)
            || ! $preference?->channelEnabled($delivery->channel)
            || ! $preference->allowsPriority($occurrence->priority)) {
            $this->suppress('The delivery channel is no longer enabled for this recipient.');

            return;
        }

        if ($delivery->channel === 'mail' && ! $recipient->email) {
            $this->suppress('The user has no email address.');

            return;
        }

        if (in_array($delivery->channel, ['sms', 'whatsapp'], true) && ! $recipient->mobile_phone) {
            $this->suppress('The user has no E.164 mobile number.');

            return;
        }

        if ($delivery->channel === 'whatsapp' && $recipient->whatsapp_opt_in_at === null) {
            $this->suppress('The user has not opted in to WhatsApp notifications.');

            return;
        }

        $delivery->increment('attempts');
        $payload = $occurrence->payload;

        try {
            $result = match ($delivery->channel) {
                'mail' => $this->sendMail($recipient->email, $payload),
                'push' => $webPush->send($recipient, $this->pushPayload($payload)),
                'sms' => $twilio->sendSms(
                    (string) $recipient->mobile_phone,
                    $this->plainText($payload),
                    $delivery->getKey(),
                ),
                'whatsapp' => $twilio->sendWhatsApp(
                    (string) $recipient->mobile_phone,
                    (string) ($payload['locale'] ?? 'ar'),
                    [
                        '1' => (string) ($payload['title'] ?? ''),
                        '2' => (string) ($payload['body'] ?? ''),
                        '3' => (string) ($payload['action_url'] ?? ''),
                    ],
                    $delivery->getKey(),
                ),
                default => ChannelResult::suppressed('Unsupported notification channel.'),
            };

            NotificationDelivery::query()
                ->whereKey($delivery->getKey())
                ->where('status', NotificationDelivery::STATUS_PROCESSING)
                ->where('claim_token', $this->claimToken)
                ->update([
                    'status' => $result->status,
                    'provider_message_id' => $result->providerMessageId,
                    'last_error' => $result->error,
                    'sent_at' => $result->status === NotificationDelivery::STATUS_SENT ? now() : null,
                    'delivered_at' => $delivery->channel === 'push'
                        && $result->status === NotificationDelivery::STATUS_SENT ? now() : null,
                    'claim_token' => null,
                    'claim_type' => null,
                    'processing_started_at' => null,
                ]);
        } catch (Throwable $exception) {
            NotificationDelivery::query()
                ->whereKey($this->deliveryId)
                ->where('claim_token', $this->claimToken)
                ->update(['last_error' => mb_substr($exception->getMessage(), 0, 4000)]);
            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        NotificationDelivery::query()
            ->whereKey($this->deliveryId)
            ->where('claim_token', $this->claimToken)
            ->update([
                'status' => NotificationDelivery::STATUS_FAILED,
                'last_error' => mb_substr($exception?->getMessage() ?? 'Delivery job failed.', 0, 4000),
                'claim_token' => null,
                'claim_type' => null,
                'processing_started_at' => null,
            ]);
    }

    private function suppress(string $reason): void
    {
        NotificationDelivery::query()
            ->whereKey($this->deliveryId)
            ->where('claim_token', $this->claimToken)
            ->update([
                'status' => NotificationDelivery::STATUS_SUPPRESSED,
                'last_error' => $reason,
                'claim_token' => null,
                'claim_type' => null,
                'processing_started_at' => null,
            ]);
    }

    private function resolveSource(string $kind, int $id): Lead|CalendarEvent|User|null
    {
        return match ($kind) {
            'lead_followup' => Lead::query()->find($id),
            'calendar_event' => CalendarEvent::query()->find($id),
            'user' => User::query()->find($id),
            default => null,
        };
    }

    /** @param array<string, mixed> $payload */
    private function sendMail(?string $email, array $payload): ChannelResult
    {
        if (! $email) {
            return ChannelResult::suppressed('The user has no email address.');
        }

        Mail::to($email)->send(new CrmReminderMail($payload));

        return ChannelResult::sent();
    }

    /** @param array<string, mixed> $payload */
    private function plainText(array $payload): string
    {
        return implode("\n", array_filter([
            $payload['title'] ?? null,
            $payload['body'] ?? null,
            $payload['action_url'] ?? null,
        ], static fn (mixed $value): bool => is_string($value) && $value !== ''));
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    private function pushPayload(array $payload): array
    {
        return [
            'title' => (string) ($payload['title'] ?? 'SokratCRM'),
            'body' => (string) ($payload['body'] ?? ''),
            'url' => (string) ($payload['action_url'] ?? '/'),
            'priority' => (string) ($payload['priority'] ?? 'normal'),
            'tag' => sprintf(
                'crm-%s-%s',
                $payload['source_kind'] ?? 'notification',
                $payload['source_id'] ?? '0',
            ),
        ];
    }
}
