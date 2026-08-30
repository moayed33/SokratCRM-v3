<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificationDelivery;
use App\Models\NotificationOccurrence;
use App\Models\NotificationPreference;
use App\Models\NotificationRule;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class NotificationPreferenceController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $preference = NotificationPreference::query()->firstOrCreate([
            'user_id' => $user->getKey(),
        ])->refresh();

        $recentDeliveries = NotificationDelivery::query()
            ->with('occurrence')
            ->whereHas('occurrence', fn ($query) => $query
                ->where('recipient_user_id', $user->getKey()))
            ->orderByDesc('id')
            ->limit(8)
            ->get()
            ->map(fn (NotificationDelivery $delivery): array => $this->presentDelivery($delivery, $user));

        return view('notifications.preferences', [
            'preference' => $preference,
            'vapidPublicKey' => (string) config('crm_notifications.web_push.public_key'),
            'systemChannels' => config('crm_notifications.channels'),
            'timezones' => timezone_identifiers_list(),
            'pushSubscriptionsCount' => $user->pushSubscriptions()->count(),
            'recentDeliveries' => $recentDeliveries,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'locale' => ['required', Rule::in(['ar', 'en'])],
            'timezone' => ['required', 'timezone:all'],
            'mobile_phone' => ['nullable', 'string', 'regex:/^\+[1-9][0-9]{7,14}$/'],
            'in_app_enabled' => ['nullable', 'boolean'],
            'mail_enabled' => ['nullable', 'boolean'],
            'push_enabled' => ['nullable', 'boolean'],
            'sms_enabled' => ['nullable', 'boolean'],
            'whatsapp_enabled' => ['nullable', 'boolean'],
            'whatsapp_opt_in' => ['nullable', 'boolean'],
            'quiet_hours_start' => ['nullable', 'date_format:H:i', 'required_with:quiet_hours_end'],
            'quiet_hours_end' => ['nullable', 'date_format:H:i', 'required_with:quiet_hours_start'],
            'minimum_external_priority' => ['required', Rule::in(NotificationRule::PRIORITIES)],
            'daily_email_digest' => ['nullable', 'boolean'],
        ], [
            'mobile_phone.regex' => __('crm.notification_phone_e164_error'),
        ]);
        $user = $request->user();
        $mobilePhone = $validated['mobile_phone'] ?? null;
        $whatsappOptIn = $request->boolean('whatsapp_opt_in') && $mobilePhone;

        $user->update([
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'],
            'mobile_phone' => $mobilePhone,
            'whatsapp_opt_in_at' => $whatsappOptIn
                ? ($user->whatsapp_opt_in_at ?? now())
                : null,
        ]);

        NotificationPreference::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            [
                'in_app_enabled' => $request->boolean('in_app_enabled'),
                'mail_enabled' => $request->boolean('mail_enabled'),
                'push_enabled' => $request->boolean('push_enabled'),
                'sms_enabled' => $request->boolean('sms_enabled'),
                'whatsapp_enabled' => $request->boolean('whatsapp_enabled'),
                'quiet_hours_start' => $validated['quiet_hours_start'] ?? null,
                'quiet_hours_end' => $validated['quiet_hours_end'] ?? null,
                'minimum_external_priority' => $validated['minimum_external_priority'],
                'daily_email_digest' => $request->boolean('daily_email_digest'),
            ],
        );

        if (! $request->boolean('daily_email_digest')) {
            $mailAvailable = $request->boolean('mail_enabled')
                && (bool) config('crm_notifications.channels.mail');
            NotificationDelivery::query()
                ->where('status', NotificationDelivery::STATUS_DIGEST_PENDING)
                ->whereHas('occurrence', fn ($query) => $query
                    ->where('recipient_user_id', $user->getKey()))
                ->update([
                    'status' => $mailAvailable
                        ? NotificationDelivery::STATUS_QUEUED
                        : NotificationDelivery::STATUS_SUPPRESSED,
                    'scheduled_at' => $mailAvailable ? now() : null,
                    'last_error' => $mailAvailable
                        ? null
                        : 'Email or daily digest delivery was disabled.',
                ]);
        }

        session(['locale' => $validated['locale']]);

        return back()->with('success', __('crm.notification_preferences_saved'));
    }

    public function test(
        Request $request,
        NotificationDispatcher $dispatcher,
    ): JsonResponse {
        $validated = $request->validate([
            'channel' => ['required', Rule::in(NotificationRule::CHANNELS)],
        ]);
        abort_unless(
            config('crm_notifications.enabled')
                && (bool) config('crm_notifications.channels.'.$validated['channel'], false),
            422,
            __('crm.notification_channel_unavailable_hint'),
        );
        $user = $request->user();
        $rule = NotificationRule::withTrashed()
            ->where('event_key', NotificationRule::EVENT_SYSTEM_TEST)
            ->firstOrFail();
        $now = now()->startOfMinute();
        $locale = in_array($user->locale, ['ar', 'en'], true) ? $user->locale : 'ar';

        $occurrence = NotificationOccurrence::query()->firstOrCreate([
            'notification_rule_id' => $rule->getKey(),
            'source_kind' => 'user',
            'source_id' => $user->getKey(),
            'recipient_user_id' => $user->getKey(),
            'trigger_at' => $now,
        ], [
            'event_key' => NotificationRule::EVENT_SYSTEM_TEST,
            'priority' => 'important',
            'status' => NotificationOccurrence::STATUS_PENDING,
            'payload' => [
                'event_key' => NotificationRule::EVENT_SYSTEM_TEST,
                'priority' => 'important',
                'title' => trans('crm.notification_test_title', [], $locale),
                'body' => trans('crm.notification_test_body', [], $locale),
                'source_kind' => 'user',
                'source_id' => $user->getKey(),
                'source_name' => $user->name,
                'action_url' => route('v2.notifications.preferences.edit'),
                'locale' => $locale,
                'test_channel' => $validated['channel'],
            ],
        ]);

        if (! $occurrence->wasRecentlyCreated) {
            $payload = $occurrence->payload;
            $payload['test_channel'] = $validated['channel'];
            $updates = ['payload' => $payload];

            if ($occurrence->status !== NotificationOccurrence::STATUS_PENDING) {
                $updates += [
                    'status' => NotificationOccurrence::STATUS_PENDING,
                    'notification_id' => null,
                    'dispatched_at' => null,
                ];
                $occurrence->deliveries()->delete();
            }

            $occurrence->update($updates);
        }

        $dispatcher->dispatchDueOccurrences($now, (int) $occurrence->getKey());
        $dispatcher->queueDueDeliveries($now, (int) $occurrence->getKey());

        $delivery = $occurrence->deliveries()
            ->where('channel', $validated['channel'])
            ->latest('id')
            ->firstOrFail();
        $presentedDelivery = $this->presentDelivery($delivery, $user);

        return response()->json([
            'success' => true,
            'message' => __('crm.notification_test_completed', [
                'status' => $presentedDelivery['status_label'],
            ]),
            'delivery' => $presentedDelivery,
        ]);
    }

    /** @return array<string, mixed> */
    private function presentDelivery(NotificationDelivery $delivery, User $user): array
    {
        $delivery->loadMissing('occurrence');
        $statusLabel = match ($delivery->status) {
            NotificationDelivery::STATUS_QUEUED => __('crm.notification_status_queued'),
            NotificationDelivery::STATUS_PROCESSING => __('crm.notification_status_processing'),
            NotificationDelivery::STATUS_DIGEST_PENDING => __('crm.notification_status_digest_pending'),
            NotificationDelivery::STATUS_SENT => __('crm.notification_status_sent'),
            NotificationDelivery::STATUS_DELIVERED => __('crm.notification_status_delivered'),
            NotificationDelivery::STATUS_FAILED => __('crm.notification_status_failed'),
            NotificationDelivery::STATUS_SUPPRESSED => __('crm.notification_status_suppressed'),
            default => $delivery->status,
        };
        $channelLabel = match ($delivery->channel) {
            'database' => __('crm.notification_channel_in_app'),
            'mail' => __('crm.email'),
            'push' => __('crm.notification_channel_push'),
            'sms' => __('crm.notification_channel_sms'),
            'whatsapp' => __('crm.whatsapp'),
            default => $delivery->channel,
        };
        $reason = match ($delivery->last_error) {
            'Channel is disabled by system configuration.' => __('crm.notification_suppressed_system_disabled'),
            'Channel is disabled in user preferences.' => __('crm.notification_suppressed_preference_disabled'),
            'Notification priority is below the user threshold.' => __('crm.notification_suppressed_priority'),
            'The user has no email address.' => __('crm.notification_suppressed_missing_email'),
            'The user has no E.164 mobile number.' => __('crm.notification_suppressed_missing_mobile'),
            'The user has not opted in to WhatsApp notifications.' => __('crm.notification_suppressed_whatsapp_consent'),
            'Per-user channel rate limit reached.' => __('crm.notification_suppressed_rate_limit'),
            null, '' => null,
            default => __('crm.notification_delivery_error_generic'),
        };
        $occurredAt = $delivery->delivered_at
            ?? $delivery->sent_at
            ?? $delivery->scheduled_at
            ?? $delivery->created_at;
        $timezone = $user->timezone ?: 'UTC';
        $payload = $delivery->occurrence?->payload ?? [];

        return [
            'id' => $delivery->getKey(),
            'channel' => $delivery->channel,
            'channel_label' => $channelLabel,
            'status' => $delivery->status,
            'status_label' => $statusLabel,
            'title' => (string) ($payload['title'] ?? $delivery->occurrence?->event_key ?? ''),
            'reason' => $reason,
            'occurred_at' => $occurredAt?->toIso8601String(),
            'occurred_at_label' => $occurredAt?->copy()->setTimezone($timezone)->format('Y-m-d H:i'),
        ];
    }
}
