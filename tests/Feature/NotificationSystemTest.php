<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\SendNotificationDelivery;
use App\Models\CalendarEvent;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\NotificationDelivery;
use App\Models\NotificationOccurrence;
use App\Models\NotificationPreference;
use App\Models\NotificationRule;
use App\Models\Permission;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\ReminderPlanner;
use App\Services\Notifications\TwilioMessageSender;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    public function test_due_followup_is_deduplicated_and_delivered_only_to_authorized_recipient(): void
    {
        config([
            'crm_notifications.enabled' => true,
            'crm_notifications.channels.database' => true,
            'crm_notifications.channels.push' => false,
            'crm_notifications.channels.mail' => false,
            'crm_notifications.channels.sms' => false,
            'crm_notifications.channels.whatsapp' => false,
        ]);
        $now = Carbon::parse('2026-08-16 09:00:00', 'UTC');
        Carbon::setTestNow($now);
        $recipient = $this->userWithPermissions(['tasks.view']);
        $otherUser = $this->userWithPermissions(['tasks.view']);
        $lead = $this->leadFor($recipient, $now->copy()->addMinutes(15));

        $planner = app(ReminderPlanner::class);
        $dispatcher = app(NotificationDispatcher::class);

        $this->assertSame(1, $planner->planScheduled($now));
        $this->assertSame(0, $planner->planScheduled($now));
        $this->assertSame(1, $dispatcher->dispatchDueOccurrences($now));
        $this->assertSame(0, $dispatcher->dispatchDueOccurrences($now));

        $occurrence = NotificationOccurrence::query()
            ->where('source_kind', 'lead_followup')
            ->where('source_id', $lead->getKey())
            ->where('event_key', NotificationRule::EVENT_FOLLOWUP_DUE)
            ->firstOrFail();
        $this->assertSame(NotificationOccurrence::STATUS_DISPATCHED, $occurrence->status);
        $this->assertDatabaseHas('notification_deliveries', [
            'notification_occurrence_id' => $occurrence->getKey(),
            'channel' => 'database',
            'status' => NotificationDelivery::STATUS_DELIVERED,
        ]);
        $this->assertDatabaseHas('notification_deliveries', [
            'notification_occurrence_id' => $occurrence->getKey(),
            'channel' => 'mail',
            'status' => NotificationDelivery::STATUS_SUPPRESSED,
        ]);
        $this->assertCount(1, $recipient->fresh()->notifications);
        $this->assertCount(0, $otherUser->fresh()->notifications);

        $notificationId = $recipient->fresh()->notifications->first()->id;
        $this->assertSame($notificationId, $occurrence->fresh()->notification_id);
        $this->actingAs($recipient)->getJson(route('v2.notifications.unread-count'))
            ->assertOk()->assertJson(['count' => 1]);
        $this->actingAs($otherUser)->patchJson(route('v2.notifications.read', $notificationId))
            ->assertNotFound();
        $this->actingAs($recipient)->postJson(route('v2.notifications.snooze', $notificationId), ['minutes' => 60])
            ->assertOk();
        $this->actingAs($recipient)->postJson(route('v2.notifications.snooze', $notificationId), ['minutes' => 15])
            ->assertOk();
        $this->assertDatabaseHas('notification_occurrences', [
            'notification_rule_id' => $occurrence->notification_rule_id,
            'recipient_user_id' => $recipient->getKey(),
            'trigger_at' => $now->copy()->addMinutes(15),
            'status' => NotificationOccurrence::STATUS_PENDING,
        ]);
        $this->assertDatabaseHas('notification_occurrences', [
            'notification_rule_id' => $occurrence->notification_rule_id,
            'recipient_user_id' => $recipient->getKey(),
            'trigger_at' => $now->copy()->addHour(),
            'status' => NotificationOccurrence::STATUS_CANCELED,
        ]);
        $this->assertSame(1, NotificationOccurrence::query()
            ->pending()
            ->where('notification_rule_id', $occurrence->notification_rule_id)
            ->where('source_id', $lead->getKey())
            ->where('recipient_user_id', $recipient->getKey())
            ->count());
    }

    public function test_quiet_hours_delay_external_delivery_and_queue_claim_is_idempotent(): void
    {
        Queue::fake();
        config([
            'crm_notifications.enabled' => true,
            'crm_notifications.channels.database' => true,
            'crm_notifications.channels.mail' => true,
        ]);
        $now = Carbon::parse('2026-08-16 23:30:00', 'UTC');
        Carbon::setTestNow($now);
        $user = $this->userWithPermissions(['tasks.view']);
        $lead = $this->leadFor($user, $now);
        $rule = NotificationRule::query()->where('event_key', NotificationRule::EVENT_FOLLOWUP_DUE)->firstOrFail();
        $rule->channels()->delete();
        $rule->channels()->createMany([['channel' => 'database'], ['channel' => 'mail']]);
        NotificationPreference::query()->create([
            'user_id' => $user->getKey(),
            'in_app_enabled' => true,
            'mail_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '07:00',
            'minimum_external_priority' => 'important',
        ]);
        NotificationOccurrence::query()->create([
            'notification_rule_id' => $rule->getKey(),
            'event_key' => $rule->event_key,
            'source_kind' => 'lead_followup',
            'source_id' => $lead->getKey(),
            'recipient_user_id' => $user->getKey(),
            'source_due_at' => $now,
            'trigger_at' => $now,
            'priority' => 'important',
            'status' => NotificationOccurrence::STATUS_PENDING,
            'payload' => ['title' => 'Due', 'body' => 'Follow up', 'action_url' => '/leads/'.$lead->getKey(), 'priority' => 'important'],
        ]);

        $dispatcher = app(NotificationDispatcher::class);
        $this->assertSame(1, $dispatcher->dispatchDueOccurrences($now));
        $mailDelivery = NotificationDelivery::query()->where('channel', 'mail')->firstOrFail();
        $this->assertSame(NotificationDelivery::STATUS_QUEUED, $mailDelivery->status);
        $this->assertSame('2026-08-17 07:00:00', $mailDelivery->scheduled_at->format('Y-m-d H:i:s'));
        $this->assertSame(0, $dispatcher->queueDueDeliveries($now));
        $this->assertSame(1, $dispatcher->queueDueDeliveries($now->copy()->addHours(8)));
        $this->assertSame(0, $dispatcher->queueDueDeliveries($now->copy()->addHours(8)));
        Queue::assertPushed(SendNotificationDelivery::class, 1);
    }

    public function test_users_manage_preferences_and_only_notification_admins_manage_rules(): void
    {
        $user = $this->userWithPermissions([]);
        $this->actingAs($user)->patch(route('v2.notifications.preferences.update'), [
            'locale' => 'en',
            'timezone' => 'Africa/Cairo',
            'mobile_phone' => '+201001234567',
            'in_app_enabled' => '1',
            'mail_enabled' => '1',
            'whatsapp_enabled' => '1',
            'whatsapp_opt_in' => '1',
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '07:00',
            'minimum_external_priority' => 'important',
            'daily_email_digest' => '1',
        ])->assertRedirect();
        $user->refresh();
        $this->assertSame('en', $user->locale);
        $this->assertSame('Africa/Cairo', $user->timezone);
        $this->assertNotNull($user->whatsapp_opt_in_at);
        $this->assertDatabaseHas('notification_preferences', [
            'user_id' => $user->getKey(),
            'mail_enabled' => true,
            'whatsapp_enabled' => true,
            'daily_email_digest' => true,
        ]);

        $settingsOnly = $this->userWithPermissions(['settings.access']);
        $this->actingAs($settingsOnly)->get(route('v2.settings.notifications.index'))->assertForbidden();
        $admin = $this->userWithPermissions(['settings.access', 'notifications.manage']);
        $this->actingAs($admin)->get(route('v2.settings.notifications.index'))
            ->assertOk()
            ->assertSee('Notification Rules')
            ->assertDontSee('crm.notification_event_');
        $this->actingAs($admin)->post(route('v2.settings.notifications.store'), [
            'name_ar' => 'متابعة مهمة',
            'name_en' => 'Important follow-up',
            'event_key' => NotificationRule::EVENT_FOLLOWUP_DUE,
            'enabled' => '1',
            'trigger_offset_minutes' => 30,
            'priority' => 'important',
            'channels' => ['database'],
            'recipient_types' => ['assigned_user'],
        ])->assertRedirect();
        $this->assertDatabaseHas('notification_rules', [
            'name_ar' => 'متابعة مهمة',
            'name_en' => 'Important follow-up',
            'event_key' => NotificationRule::EVENT_FOLLOWUP_DUE,
        ]);
    }

    public function test_channel_test_endpoint_is_rate_limited(): void
    {
        config([
            'crm_notifications.enabled' => true,
            'crm_notifications.channels.database' => true,
        ]);
        $user = $this->userWithPermissions([]);

        foreach (range(1, 5) as $attempt) {
            $this->actingAs($user)
                ->postJson(route('v2.notifications.preferences.test'), [
                    'channel' => 'database',
                ])
                ->assertOk();
        }

        $this->actingAs($user)
            ->postJson(route('v2.notifications.preferences.test'), [
                'channel' => 'database',
            ])
            ->assertTooManyRequests();
    }

    public function test_calendar_reminders_honor_long_offsets_and_completion_cancels_pending_delivery(): void
    {
        config(['crm_notifications.enabled' => true]);
        $now = Carbon::parse('2026-08-16 09:00:00', 'UTC');
        Carbon::setTestNow($now);
        $user = $this->userWithPermissions(['calendar.view']);
        $rule = NotificationRule::query()
            ->where('event_key', NotificationRule::EVENT_CALENDAR_DUE)
            ->firstOrFail();
        $rule->update(['trigger_offset_minutes' => 10080]);
        $event = CalendarEvent::query()->create([
            'user_id' => $user->getKey(),
            'title' => 'Long-range meeting',
            'start_time' => $now->copy()->addDays(6),
            'end_time' => $now->copy()->addDays(6)->addHour(),
            'type' => 'meeting',
            'status' => 'scheduled',
            'reminder_minutes_before' => null,
        ]);

        $this->assertSame(1, app(ReminderPlanner::class)->planScheduled($now));
        $this->assertDatabaseHas('notification_occurrences', [
            'notification_rule_id' => $rule->getKey(),
            'source_kind' => 'calendar_event',
            'source_id' => $event->getKey(),
            'recipient_user_id' => $user->getKey(),
            'status' => NotificationOccurrence::STATUS_PENDING,
        ]);

        $event->update(['status' => 'completed']);

        $this->assertDatabaseHas('notification_occurrences', [
            'notification_rule_id' => $rule->getKey(),
            'source_kind' => 'calendar_event',
            'source_id' => $event->getKey(),
            'recipient_user_id' => $user->getKey(),
            'status' => NotificationOccurrence::STATUS_CANCELED,
        ]);
    }

    public function test_overdue_planning_window_is_measured_from_the_delayed_trigger(): void
    {
        config([
            'crm_notifications.enabled' => true,
            'crm_notifications.planner_lookback_hours' => 24,
        ]);
        $now = Carbon::parse('2026-08-16 12:00:00', 'UTC');
        Carbon::setTestNow($now);
        $user = $this->userWithPermissions(['tasks.view']);
        $lead = $this->leadFor($user, $now->copy()->subHours(24)->subMinutes(30));
        $rule = NotificationRule::query()
            ->where('event_key', NotificationRule::EVENT_FOLLOWUP_OVERDUE)
            ->firstOrFail();
        $rule->update(['escalation_after_minutes' => 60]);

        $this->assertSame(1, app(ReminderPlanner::class)->planScheduled($now));
        $this->assertDatabaseHas('notification_occurrences', [
            'notification_rule_id' => $rule->getKey(),
            'source_id' => $lead->getKey(),
            'trigger_at' => $lead->next_follow_up_at->copy()->addHour(),
            'status' => NotificationOccurrence::STATUS_PENDING,
        ]);
    }

    public function test_source_completion_suppresses_external_delivery_before_it_is_queued(): void
    {
        config([
            'crm_notifications.enabled' => true,
            'crm_notifications.channels.mail' => true,
        ]);
        $now = Carbon::parse('2026-08-16 09:00:00', 'UTC');
        Carbon::setTestNow($now);
        $user = $this->userWithPermissions(['calendar.view']);
        NotificationPreference::query()->create([
            'user_id' => $user->getKey(),
            'mail_enabled' => true,
        ]);
        $rule = NotificationRule::query()
            ->where('event_key', NotificationRule::EVENT_CALENDAR_DUE)
            ->firstOrFail();
        $rule->channels()->delete();
        $rule->channels()->create(['channel' => 'mail']);
        $event = CalendarEvent::query()->create([
            'user_id' => $user->getKey(),
            'title' => 'Call customer',
            'start_time' => $now,
            'end_time' => $now->copy()->addHour(),
            'type' => 'call',
            'status' => 'scheduled',
            'reminder_minutes_before' => 15,
        ]);

        $this->assertSame(1, app(ReminderPlanner::class)->planScheduled($now));
        $this->assertSame(1, app(NotificationDispatcher::class)->dispatchDueOccurrences($now));
        $delivery = NotificationDelivery::query()->where('channel', 'mail')->firstOrFail();
        $this->assertSame(NotificationDelivery::STATUS_QUEUED, $delivery->status);

        $event->update(['status' => 'completed']);

        $this->assertSame(NotificationDelivery::STATUS_SUPPRESSED, $delivery->fresh()->status);
        $this->assertSame(
            NotificationOccurrence::STATUS_CANCELED,
            $delivery->occurrence->fresh()->status,
        );
    }

    public function test_rate_limit_counts_quiet_hour_delivery_reservations(): void
    {
        config([
            'crm_notifications.enabled' => true,
            'crm_notifications.channels.mail' => true,
            'crm_notifications.max_deliveries_per_user_per_hour' => 1,
        ]);
        $now = Carbon::parse('2026-08-16 23:30:00', 'UTC');
        Carbon::setTestNow($now);
        $user = $this->userWithPermissions(['tasks.view']);
        NotificationPreference::query()->create([
            'user_id' => $user->getKey(),
            'mail_enabled' => true,
            'quiet_hours_start' => '22:00',
            'quiet_hours_end' => '07:00',
        ]);
        $rule = NotificationRule::query()
            ->where('event_key', NotificationRule::EVENT_FOLLOWUP_DUE)
            ->firstOrFail();
        $rule->channels()->delete();
        $rule->channels()->create(['channel' => 'mail']);

        foreach (range(1, 2) as $index) {
            $lead = $this->leadFor($user, $now);
            NotificationOccurrence::query()->create([
                'notification_rule_id' => $rule->getKey(),
                'event_key' => $rule->event_key,
                'source_kind' => 'lead_followup',
                'source_id' => $lead->getKey(),
                'recipient_user_id' => $user->getKey(),
                'trigger_at' => $now,
                'priority' => 'important',
                'status' => NotificationOccurrence::STATUS_PENDING,
                'payload' => ['title' => 'Due '.$index],
            ]);
        }

        $this->assertSame(2, app(NotificationDispatcher::class)->dispatchDueOccurrences($now));
        $this->assertSame(1, NotificationDelivery::query()
            ->where('channel', 'mail')
            ->where('status', NotificationDelivery::STATUS_QUEUED)
            ->count());
        $this->assertSame(1, NotificationDelivery::query()
            ->where('channel', 'mail')
            ->where('status', NotificationDelivery::STATUS_SUPPRESSED)
            ->count());
    }

    public function test_delivery_job_rechecks_preferences_at_send_time(): void
    {
        Mail::fake();
        config(['crm_notifications.channels.mail' => true]);
        $user = $this->userWithPermissions(['tasks.view']);
        $lead = $this->leadFor($user, now());
        NotificationPreference::query()->create([
            'user_id' => $user->getKey(),
            'mail_enabled' => false,
        ]);
        $rule = NotificationRule::query()
            ->where('event_key', NotificationRule::EVENT_FOLLOWUP_DUE)
            ->firstOrFail();
        $occurrence = NotificationOccurrence::query()->create([
            'notification_rule_id' => $rule->getKey(),
            'event_key' => $rule->event_key,
            'source_kind' => 'lead_followup',
            'source_id' => $lead->getKey(),
            'recipient_user_id' => $user->getKey(),
            'trigger_at' => now()->startOfMinute(),
            'priority' => 'important',
            'status' => NotificationOccurrence::STATUS_DISPATCHED,
            'payload' => ['title' => 'Due', 'body' => 'Follow up'],
        ]);
        $delivery = NotificationDelivery::query()->create([
            'notification_occurrence_id' => $occurrence->getKey(),
            'channel' => 'mail',
            'status' => NotificationDelivery::STATUS_PROCESSING,
            'claim_token' => 'preference-claim',
            'claim_type' => 'delivery',
            'processing_started_at' => now(),
        ]);

        app()->call([new SendNotificationDelivery(
            (int) $delivery->getKey(),
            'preference-claim',
        ), 'handle']);

        Mail::assertNothingSent();
        $this->assertSame(NotificationDelivery::STATUS_SUPPRESSED, $delivery->fresh()->status);
    }

    public function test_stale_delivery_claim_is_recovered_with_a_new_token(): void
    {
        Queue::fake();
        $now = Carbon::parse('2026-08-16 09:00:00', 'UTC');
        Carbon::setTestNow($now);
        $user = $this->userWithPermissions(['tasks.view']);
        $lead = $this->leadFor($user, $now);
        $rule = NotificationRule::query()->firstOrFail();
        $occurrence = NotificationOccurrence::query()->create([
            'notification_rule_id' => $rule->getKey(),
            'event_key' => $rule->event_key,
            'source_kind' => 'lead_followup',
            'source_id' => $lead->getKey(),
            'recipient_user_id' => $user->getKey(),
            'trigger_at' => $now,
            'priority' => 'important',
            'status' => NotificationOccurrence::STATUS_DISPATCHED,
            'payload' => [],
        ]);
        $delivery = NotificationDelivery::query()->create([
            'notification_occurrence_id' => $occurrence->getKey(),
            'channel' => 'mail',
            'status' => NotificationDelivery::STATUS_PROCESSING,
            'scheduled_at' => $now,
            'claim_token' => 'stale-claim',
            'claim_type' => 'delivery',
            'processing_started_at' => $now->copy()->subMinutes(21),
        ]);

        $this->assertSame(1, app(NotificationDispatcher::class)->queueDueDeliveries($now));
        $delivery->refresh();
        $this->assertSame(NotificationDelivery::STATUS_PROCESSING, $delivery->status);
        $this->assertNotSame('stale-claim', $delivery->claim_token);
        Queue::assertPushed(
            SendNotificationDelivery::class,
            fn (SendNotificationDelivery $job): bool => $job->deliveryId === $delivery->getKey()
                && $job->claimToken === $delivery->claim_token,
        );
    }

    public function test_disabling_digest_releases_pending_mail_for_individual_delivery(): void
    {
        config(['crm_notifications.channels.mail' => true]);
        $user = $this->userWithPermissions([]);
        NotificationPreference::query()->create([
            'user_id' => $user->getKey(),
            'mail_enabled' => true,
            'daily_email_digest' => true,
        ]);
        $rule = NotificationRule::query()->firstOrFail();
        $occurrence = NotificationOccurrence::query()->create([
            'notification_rule_id' => $rule->getKey(),
            'event_key' => $rule->event_key,
            'source_kind' => 'user',
            'source_id' => $user->getKey(),
            'recipient_user_id' => $user->getKey(),
            'trigger_at' => now()->startOfMinute(),
            'priority' => 'important',
            'status' => NotificationOccurrence::STATUS_DISPATCHED,
            'payload' => [],
        ]);
        $delivery = NotificationDelivery::query()->create([
            'notification_occurrence_id' => $occurrence->getKey(),
            'channel' => 'mail',
            'status' => NotificationDelivery::STATUS_DIGEST_PENDING,
        ]);

        $this->actingAs($user)->patch(route('v2.notifications.preferences.update'), [
            'locale' => 'en',
            'timezone' => 'UTC',
            'mail_enabled' => '1',
            'minimum_external_priority' => 'important',
        ])->assertRedirect();

        $delivery->refresh();
        $this->assertSame(NotificationDelivery::STATUS_QUEUED, $delivery->status);
        $this->assertNotNull($delivery->scheduled_at);
    }

    public function test_push_subscription_rejects_unapproved_hosts_and_invalid_keys(): void
    {
        config([
            'crm_notifications.web_push.allowed_hosts' => ['fcm.googleapis.com'],
            'crm_notifications.web_push.public_key' => 'public-key',
        ]);
        $user = $this->userWithPermissions([]);
        $publicKey = rtrim(strtr(base64_encode(chr(4).str_repeat('p', 64)), '+/', '-_'), '=');
        $authToken = rtrim(strtr(base64_encode(str_repeat('a', 16)), '+/', '-_'), '=');
        $payload = [
            'endpoint' => 'https://attacker.example/push',
            'keys' => ['p256dh' => $publicKey, 'auth' => $authToken],
            'contentEncoding' => 'aes128gcm',
        ];

        $rejected = $this->actingAs($user)
            ->postJson(route('v2.notifications.push.store'), $payload);
        $this->assertSame(422, $rejected->getStatusCode(), $rejected->getContent());
        $rejected->assertJsonValidationErrors('endpoint');

        $payload['endpoint'] = 'https://fcm.googleapis.com/fcm/send/subscription-id';
        $this->actingAs($user)
            ->postJson(route('v2.notifications.push.store'), $payload)
            ->assertOk();
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->getKey(),
            'endpoint' => $payload['endpoint'],
        ]);
    }

    public function test_twilio_sender_issues_one_request_and_includes_delivery_correlation(): void
    {
        Http::fake([
            '*' => Http::response(['message' => 'provider unavailable'], 503),
        ]);
        config([
            'crm_notifications.twilio.account_sid' => 'AC123',
            'crm_notifications.twilio.auth_token' => 'token',
            'crm_notifications.twilio.sms_from' => '+15551234567',
            'crm_notifications.twilio.status_callback' => 'https://crm.example/webhooks/twilio',
        ]);

        $result = app(TwilioMessageSender::class)->sendSms(
            '+15557654321',
            'Reminder',
            42,
        );

        $this->assertSame(NotificationDelivery::STATUS_FAILED, $result->status);
        Http::assertSentCount(1);
        Http::assertSent(fn ($request): bool => $request['StatusCallback']
            === 'https://crm.example/webhooks/twilio?delivery=42');
    }

    public function test_canceled_pending_occurrence_can_be_replanned_for_the_same_trigger(): void
    {
        config(['crm_notifications.enabled' => true]);
        $now = Carbon::parse('2026-08-16 09:00:00', 'UTC');
        Carbon::setTestNow($now);
        $user = $this->userWithPermissions(['tasks.view']);
        $lead = $this->leadFor($user, $now->copy()->addMinutes(15));
        $planner = app(ReminderPlanner::class);

        $this->assertSame(1, $planner->planScheduled($now));
        $occurrence = NotificationOccurrence::query()
            ->where('source_id', $lead->getKey())
            ->where('event_key', NotificationRule::EVENT_FOLLOWUP_DUE)
            ->firstOrFail();
        $this->assertSame(1, $planner->cancelOutstanding('lead_followup', (int) $lead->getKey()));
        $this->assertSame(NotificationOccurrence::STATUS_CANCELED, $occurrence->fresh()->status);

        $this->assertSame(1, $planner->planScheduled($now));
        $occurrence->refresh();
        $this->assertSame(NotificationOccurrence::STATUS_PENDING, $occurrence->status);
        $this->assertNull($occurrence->canceled_at);
    }

    public function test_twilio_delivery_status_requires_a_valid_signature(): void
    {
        config(['crm_notifications.twilio.auth_token' => 'test-token']);
        $user = $this->userWithPermissions(['tasks.view']);
        $lead = $this->leadFor($user, now());
        $rule = NotificationRule::query()->firstOrFail();
        $occurrence = NotificationOccurrence::query()->create([
            'notification_rule_id' => $rule->getKey(),
            'event_key' => $rule->event_key,
            'source_kind' => 'lead_followup',
            'source_id' => $lead->getKey(),
            'recipient_user_id' => $user->getKey(),
            'trigger_at' => now()->startOfMinute(),
            'priority' => 'important',
            'status' => NotificationOccurrence::STATUS_DISPATCHED,
            'payload' => [],
        ]);
        $delivery = NotificationDelivery::query()->create([
            'notification_occurrence_id' => $occurrence->getKey(),
            'channel' => 'sms',
            'status' => NotificationDelivery::STATUS_SENT,
            'provider_message_id' => 'SM123',
        ]);
        $url = route('webhooks.twilio.notification-status');
        $parameters = ['MessageSid' => 'SM123', 'MessageStatus' => 'delivered'];
        ksort($parameters);
        $signedValue = $url;
        foreach ($parameters as $key => $value) {
            $signedValue .= $key.$value;
        }
        $signature = base64_encode(hash_hmac('sha1', $signedValue, 'test-token', true));

        $this->post($url, $parameters, ['X-Twilio-Signature' => 'invalid'])->assertForbidden();
        $this->post($url, $parameters, ['X-Twilio-Signature' => $signature])->assertNoContent();
        $this->assertSame(NotificationDelivery::STATUS_DELIVERED, $delivery->fresh()->status);
        $this->post($url, ['MessageSid' => 'SM123', 'MessageStatus' => 'sent'], [
            'X-Twilio-Signature' => $this->twilioSignature(
                $url,
                ['MessageSid' => 'SM123', 'MessageStatus' => 'sent'],
                'test-token',
            ),
        ])->assertNoContent();
        $this->assertSame(NotificationDelivery::STATUS_DELIVERED, $delivery->fresh()->status);
        $this->assertNotNull($delivery->fresh()->delivered_at);

        $earlyDelivery = NotificationDelivery::query()->create([
            'notification_occurrence_id' => $occurrence->getKey(),
            'channel' => 'whatsapp',
            'status' => NotificationDelivery::STATUS_PROCESSING,
            'claim_token' => 'callback-race',
            'claim_type' => 'delivery',
            'processing_started_at' => now(),
        ]);
        $earlyUrl = $url.'?delivery='.$earlyDelivery->getKey();
        $earlyParameters = ['MessageSid' => 'SM999', 'MessageStatus' => 'delivered'];
        $this->post($earlyUrl, $earlyParameters, [
            'X-Twilio-Signature' => $this->twilioSignature(
                $earlyUrl,
                $earlyParameters,
                'test-token',
            ),
        ])->assertNoContent();
        $earlyDelivery->refresh();
        $this->assertSame(NotificationDelivery::STATUS_DELIVERED, $earlyDelivery->status);
        $this->assertSame('SM999', $earlyDelivery->provider_message_id);
        $this->assertNull($earlyDelivery->claim_token);
    }

    /** @param array<string, string> $parameters */
    private function twilioSignature(string $url, array $parameters, string $token): string
    {
        ksort($parameters);
        foreach ($parameters as $key => $value) {
            $url .= $key.$value;
        }

        return base64_encode(hash_hmac('sha1', $url, $token, true));
    }

    /** @param list<string> $permissionCodes */
    private function userWithPermissions(array $permissionCodes): User
    {
        $group = Group::query()->create([
            'name' => fake()->unique()->word(),
            'code' => fake()->unique()->slug(),
            'is_system' => false,
        ]);
        foreach ($permissionCodes as $code) {
            $permission = Permission::query()->firstOrCreate(
                ['code' => $code],
                ['module' => explode('.', $code, 2)[0], 'name_ar' => $code],
            );
            $group->permissions()->syncWithoutDetaching($permission);
        }
        $user = User::factory()->create(['is_active' => true, 'timezone' => 'UTC', 'locale' => 'en']);
        $user->groups()->attach($group);

        return $user;
    }

    private function leadFor(User $user, Carbon $dueAt): Lead
    {
        $stage = PipelineStage::query()->firstOrCreate(
            ['code' => 'new'],
            ['name_ar' => 'جديد', 'position' => 1, 'color' => '#64748b', 'is_active' => true],
        );
        $status = LeadStatus::query()->firstOrCreate(
            ['code' => 'new'],
            ['pipeline_stage_id' => $stage->getKey(), 'name_ar' => 'جديد', 'position' => 1, 'color' => '#64748b', 'is_terminal' => false],
        );

        return Lead::query()->create([
            'lead_status_id' => $status->getKey(),
            'name' => fake()->name(),
            'assigned_user_id' => $user->getKey(),
            'created_by_user_id' => $user->getKey(),
            'next_follow_up_at' => $dueAt,
        ]);
    }
}
