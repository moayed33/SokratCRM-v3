<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\SaveNotificationRuleRequest;
use App\Models\Group;
use App\Models\LeadStatus;
use App\Models\NotificationDelivery;
use App\Models\NotificationRule;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class NotificationRuleController extends Controller
{
    public function index(): View
    {
        return view('settings.notifications.index', [
            'rules' => NotificationRule::query()
                ->with(['channels', 'recipients'])
                ->where('event_key', '!=', NotificationRule::EVENT_SYSTEM_TEST)
                ->orderBy('event_key')
                ->orderBy('id')
                ->get(),
            'deliveryStats' => NotificationDelivery::query()
                ->where('created_at', '>=', now()->subDay())
                ->selectRaw('status, count(*) as aggregate')
                ->groupBy('status')
                ->pluck('aggregate', 'status'),
            'systemChannels' => config('crm_notifications.channels'),
        ]);
    }

    public function create(): View
    {
        return $this->formView(new NotificationRule([
            'enabled' => true,
            'trigger_offset_minutes' => 15,
            'priority' => 'important',
        ]));
    }

    public function store(SaveNotificationRuleRequest $request): RedirectResponse
    {
        $rule = DB::transaction(function () use ($request): NotificationRule {
            $rule = NotificationRule::query()->create([
                ...$request->safe()->only([
                    'name_ar',
                    'name_en',
                    'event_key',
                    'trigger_offset_minutes',
                    'escalation_after_minutes',
                    'priority',
                ]),
                'enabled' => $request->boolean('enabled'),
                'conditions' => $this->conditions($request->validated()),
                'created_by_user_id' => $request->user()->getKey(),
            ]);
            $this->replaceRelations($rule, $request->validated());

            return $rule;
        });

        return redirect()->route('v2.settings.notifications.edit', $rule)
            ->with('success', __('crm.notification_rule_created'));
    }

    public function edit(NotificationRule $notificationRule): View
    {
        abort_if($notificationRule->event_key === NotificationRule::EVENT_SYSTEM_TEST, 404);
        $notificationRule->load(['channels', 'recipients']);

        return $this->formView($notificationRule);
    }

    public function update(
        SaveNotificationRuleRequest $request,
        NotificationRule $notificationRule,
    ): RedirectResponse {
        abort_if($notificationRule->event_key === NotificationRule::EVENT_SYSTEM_TEST, 404);

        DB::transaction(function () use ($request, $notificationRule): void {
            $notificationRule->occurrences()->pending()->delete();
            $notificationRule->update([
                ...$request->safe()->only([
                    'name_ar',
                    'name_en',
                    'event_key',
                    'trigger_offset_minutes',
                    'escalation_after_minutes',
                    'priority',
                ]),
                'enabled' => $request->boolean('enabled'),
                'conditions' => $this->conditions($request->validated()),
            ]);
            $this->replaceRelations($notificationRule, $request->validated());
        });

        return back()->with('success', __('crm.notification_rule_updated'));
    }

    public function duplicate(NotificationRule $notificationRule): RedirectResponse
    {
        abort_if($notificationRule->event_key === NotificationRule::EVENT_SYSTEM_TEST, 404);

        $copy = DB::transaction(function () use ($notificationRule): NotificationRule {
            $notificationRule->load(['channels', 'recipients']);
            $copy = $notificationRule->replicate();
            $copy->name_ar = trans(
                'crm.notification_rule_copy_name',
                ['name' => $notificationRule->name_ar],
                'ar',
            );
            $copy->name_en = trans(
                'crm.notification_rule_copy_name',
                ['name' => $notificationRule->name_en],
                'en',
            );
            $copy->enabled = false;
            $copy->created_by_user_id = request()->user()->getKey();
            $copy->save();
            $copy->channels()->createMany($notificationRule->channels->map(
                static fn ($channel): array => ['channel' => $channel->channel],
            )->all());
            $copy->recipients()->createMany($notificationRule->recipients->map(
                static fn ($recipient): array => [
                    'recipient_type' => $recipient->recipient_type,
                    'recipient_id' => $recipient->recipient_id,
                ],
            )->all());

            return $copy;
        });

        return redirect()->route('v2.settings.notifications.edit', $copy)
            ->with('success', __('crm.notification_rule_duplicated'));
    }

    public function toggle(NotificationRule $notificationRule): RedirectResponse
    {
        abort_if($notificationRule->event_key === NotificationRule::EVENT_SYSTEM_TEST, 404);

        DB::transaction(function () use ($notificationRule): void {
            $notificationRule->update(['enabled' => ! $notificationRule->enabled]);
            if (! $notificationRule->enabled) {
                $notificationRule->occurrences()->pending()->delete();
            }
        });

        return back()->with('success', __('crm.notification_rule_updated'));
    }

    public function destroy(NotificationRule $notificationRule): RedirectResponse
    {
        abort_if($notificationRule->event_key === NotificationRule::EVENT_SYSTEM_TEST, 404);

        DB::transaction(function () use ($notificationRule): void {
            $notificationRule->occurrences()->pending()->delete();
            $notificationRule->delete();
        });

        return redirect()->route('v2.settings.notifications.index')
            ->with('success', __('crm.notification_rule_deleted'));
    }

    private function formView(NotificationRule $rule): View
    {
        return view('settings.notifications.form', [
            'rule' => $rule,
            'events' => array_values(array_diff(
                NotificationRule::EVENTS,
                [NotificationRule::EVENT_SYSTEM_TEST],
            )),
            'channels' => NotificationRule::CHANNELS,
            'priorities' => NotificationRule::PRIORITIES,
            'users' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
            'groups' => Group::query()->orderBy('name')->get(['id', 'name']),
            'leadStatuses' => LeadStatus::query()->orderBy('position')->get(['id', 'name_ar']),
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, list<mixed>>|null
     */
    private function conditions(array $validated): ?array
    {
        $conditions = [
            'lead_status_ids' => array_map('intval', $validated['lead_status_ids'] ?? []),
            'calendar_types' => array_values($validated['calendar_types'] ?? []),
        ];

        return $conditions['lead_status_ids'] === [] && $conditions['calendar_types'] === []
            ? null
            : $conditions;
    }

    /** @param array<string, mixed> $validated */
    private function replaceRelations(NotificationRule $rule, array $validated): void
    {
        $rule->channels()->delete();
        $rule->channels()->createMany(array_map(
            static fn (string $channel): array => ['channel' => $channel],
            $validated['channels'],
        ));

        $recipients = [];
        foreach ($validated['recipient_types'] ?? [] as $type) {
            $recipients[] = ['recipient_type' => $type, 'recipient_id' => 0];
        }
        foreach ($validated['user_ids'] ?? [] as $userId) {
            $recipients[] = ['recipient_type' => 'explicit_user', 'recipient_id' => (int) $userId];
        }
        foreach ($validated['group_ids'] ?? [] as $groupId) {
            $recipients[] = ['recipient_type' => 'group', 'recipient_id' => (int) $groupId];
        }

        $rule->recipients()->delete();
        $rule->recipients()->createMany($recipients);
    }
}
