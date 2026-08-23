<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificationDelivery;
use App\Models\NotificationOccurrence;
use App\Models\NotificationPreference;
use App\Models\NotificationRule;
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

        return view('notifications.preferences', [
            'preference' => $preference,
            'vapidPublicKey' => (string) config('crm_notifications.web_push.public_key'),
            'systemChannels' => config('crm_notifications.channels'),
            'timezones' => timezone_identifiers_list(),
            'pushSubscriptionsCount' => $user->pushSubscriptions()->count(),
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

        if (! $occurrence->wasRecentlyCreated && $occurrence->status !== NotificationOccurrence::STATUS_PENDING) {
            $occurrence->update([
                'status' => NotificationOccurrence::STATUS_PENDING,
                'notification_id' => null,
                'dispatched_at' => null,
                'payload->test_channel' => $validated['channel'],
            ]);
            $occurrence->deliveries()->delete();
        }

        $dispatcher->dispatchDueOccurrences();
        $dispatcher->queueDueDeliveries();

        return response()->json([
            'success' => true,
            'message' => __('crm.notification_test_queued'),
        ]);
    }
}
