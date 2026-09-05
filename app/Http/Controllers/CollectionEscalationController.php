<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CollectionActivity;
use App\Models\CollectionCase;
use App\Models\CollectionEscalation;
use App\Models\NotificationRule;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\Notifications\ReminderPlanner;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CollectionEscalationController extends Controller
{
    public function escalate(Request $request, CollectionCase $collectionCase): RedirectResponse
    {
        $actor = $request->user();

        // Must be the assigned collector or super admin / manager
        abort_unless(
            $actor->isSuperAdmin()
            || ($actor->hasPermission(CrmPermission::COLLECTIONS_COLLECT) && (int) $collectionCase->assigned_collector_user_id === (int) $actor->id)
            || $actor->hasPermission(CrmPermission::COLLECTIONS_MANAGE),
            403,
        );

        if (! in_array($collectionCase->status, CollectionCase::OPEN_STATUSES, true)) {
            throw ValidationException::withMessages(['collection' => [__('crm.collection_closed')]]);
        }

        if ($collectionCase->status === CollectionCase::STATUS_AWAITING_CALL_CENTER) {
            throw ValidationException::withMessages(['escalation' => [__('crm.waiting_for_call_center')]]);
        }

        $validated = $request->validate([
            'collector_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $lead = $collectionCase->lead;
        $callCenterUserId = (int) ($lead?->assigned_user_id ?? $collectionCase->created_by_user_id ?? $actor->id);
        $collector = $collectionCase->assignedCollector ?? $actor;
        $collectionManagerId = $collector->manager_id ? (int) $collector->manager_id : null;

        $escalation = DB::transaction(function () use ($collectionCase, $actor, $collector, $callCenterUserId, $collectionManagerId, $validated): CollectionEscalation {
            $lockedCase = CollectionCase::query()->lockForUpdate()->findOrFail($collectionCase->id);
            $oldStatus = $lockedCase->status;

            $lockedCase->update([
                'status' => CollectionCase::STATUS_AWAITING_CALL_CENTER,
            ]);

            $esc = CollectionEscalation::query()->create([
                'collection_case_id' => $lockedCase->id,
                'collector_user_id' => $collector->id,
                'call_center_user_id' => $callCenterUserId,
                'collection_manager_user_id' => $collectionManagerId,
                'status' => CollectionEscalation::STATUS_PENDING,
                'collector_note' => trim((string) ($validated['collector_note'] ?? '')) ?: null,
                'requested_at' => now(),
            ]);

            CollectionActivity::query()->create([
                'collection_case_id' => $lockedCase->id,
                'actor_user_id' => $actor->id,
                'action' => 'escalated',
                'from_status' => $oldStatus,
                'to_status' => CollectionCase::STATUS_AWAITING_CALL_CENTER,
                'notes' => $esc->collector_note,
            ]);

            return $esc;
        });

        // Trigger notification for the urgent call center handoff
        try {
            app(ReminderPlanner::class)->planImmediate(
                NotificationRule::EVENT_COLLECTION_CALL_CENTER_ESCALATED,
                $collectionCase,
                $collectionCase->due_at,
            );
        } catch (\Throwable) {
            // Notification dispatch should not break transactional flow
        }

        return redirect()->route('v2.collections.show', $collectionCase)
            ->with('success', __('crm.escalation_sent_to_call_center'));
    }

    public function show(Request $request, CollectionEscalation $escalation): View
    {
        $actor = $request->user();
        $collectionCase = $escalation->collectionCase;

        abort_unless(
            $actor->isSuperAdmin()
            || (int) $escalation->call_center_user_id === (int) $actor->id
            || (int) $escalation->collector_user_id === (int) $actor->id
            || (int) $escalation->collection_manager_user_id === (int) $actor->id
            || $actor->hasPermission(CrmPermission::COLLECTIONS_MANAGE)
            || $actor->hasPermission(CrmPermission::COLLECTIONS_ESCALATIONS_RESPOND),
            403,
        );

        $escalation->load([
            'collectionCase.lead.governorate',
            'collectionCase.lead.subregion.governorate',
            'collectionCase.governorate',
            'collectionCase.subregion.governorate',
            'collectionCase.donationType',
            'collectionCase.branch',
            'collector.collectionSubregion.governorate',
            'callCenterUser',
            'collectionManager',
        ]);

        return view('collections.escalations.show', [
            'escalation' => $escalation,
            'collectionCase' => $escalation->collectionCase,
        ]);
    }

    public function resolve(Request $request, CollectionEscalation $escalation): RedirectResponse
    {
        $actor = $request->user();

        // Must be the assigned call center employee, super admin, or authorized manager
        abort_unless(
            $actor->isSuperAdmin()
            || (int) $escalation->call_center_user_id === (int) $actor->id
            || $actor->hasPermission(CrmPermission::COLLECTIONS_MANAGE)
            || $actor->hasPermission(CrmPermission::COLLECTIONS_ESCALATIONS_RESPOND),
            403,
        );

        if (! $escalation->isPending()) {
            throw ValidationException::withMessages(['escalation' => [__('crm.collection_closed')]]);
        }

        $validated = $request->validate([
            'decision' => ['required', 'string', Rule::in([CollectionEscalation::STATUS_COLLECT_NOW, CollectionEscalation::STATUS_RESCHEDULED])],
            'due_at' => [Rule::requiredIf($request->input('decision') === CollectionEscalation::STATUS_RESCHEDULED), 'nullable', 'date'],
            'response_note' => ['nullable', 'string', 'max:5000'],
        ]);

        $decision = $validated['decision'];
        $responseNote = trim((string) ($validated['response_note'] ?? '')) ?: null;
        $newDueAt = ! empty($validated['due_at']) ? Carbon::parse($validated['due_at']) : null;

        $collectionCase = $escalation->collectionCase;

        DB::transaction(function () use ($escalation, $collectionCase, $actor, $decision, $responseNote, $newDueAt): void {
            $lockedEscalation = CollectionEscalation::query()->lockForUpdate()->findOrFail($escalation->id);
            $lockedCase = CollectionCase::query()->lockForUpdate()->findOrFail($collectionCase->id);

            $oldStatus = $lockedCase->status;
            $oldDueAt = $lockedCase->due_at;

            $lockedEscalation->update([
                'status' => $decision,
                'response_note' => $responseNote,
                'new_due_at' => $newDueAt,
                'responded_at' => now(),
            ]);

            if ($decision === CollectionEscalation::STATUS_COLLECT_NOW) {
                $lockedCase->update([
                    'status' => CollectionCase::STATUS_ASSIGNED,
                ]);

                CollectionActivity::query()->create([
                    'collection_case_id' => $lockedCase->id,
                    'actor_user_id' => $actor->id,
                    'action' => 'call_center_collect_now',
                    'from_status' => $oldStatus,
                    'to_status' => CollectionCase::STATUS_ASSIGNED,
                    'notes' => $responseNote,
                ]);
            } else {
                $lockedCase->update([
                    'status' => CollectionCase::STATUS_SCHEDULED,
                    'due_at' => $newDueAt,
                ]);

                CollectionActivity::query()->create([
                    'collection_case_id' => $lockedCase->id,
                    'actor_user_id' => $actor->id,
                    'action' => 'call_center_rescheduled',
                    'from_status' => $oldStatus,
                    'to_status' => CollectionCase::STATUS_SCHEDULED,
                    'notes' => $responseNote,
                    'old_due_at' => $oldDueAt,
                    'new_due_at' => $newDueAt,
                ]);
            }
        });

        // Trigger notification for the resolved handoff
        try {
            app(ReminderPlanner::class)->planImmediate(
                NotificationRule::EVENT_COLLECTION_CALL_CENTER_RESOLVED,
                $collectionCase,
                $collectionCase->due_at,
            );
        } catch (\Throwable) {
            // Silence notification errors
        }

        return redirect()->route('v2.collections.show', $collectionCase)
            ->with('success', __('crm.escalation_resolved_success'));
    }
}
