<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class LeadTransitionService
{
    /**
     * Perform a centralized Lead status transition inside an atomic database transaction.
     *
     * @param Lead $lead
     * @param LeadStatus $toStatus
     * @param User $actor
     * @param array<string, mixed> $context
     * @return array{lead: Lead, history: ?LeadStatusHistory, followup: ?LeadFollowup, changed: bool}
     *
     * @throws ValidationException
     */
    public function transition(
        Lead $lead,
        LeadStatus $toStatus,
        User $actor,
        array $context = []
    ): array {
        return DB::transaction(function () use ($lead, $toStatus, $actor, $context): array {
            /** @var Lead $lockedLead */
            $lockedLead = Lead::query()
                ->lockForUpdate()
                ->findOrFail($lead->getKey());

            $fromStatus = $lockedLead->status ?? LeadStatus::query()->find($lockedLead->lead_status_id);
            $fromStatusId = $fromStatus?->id ?? (int) $lockedLead->lead_status_id;
            $toStatusId = (int) $toStatus->id;
            $statusChanged = $fromStatusId !== $toStatusId;

            // 1. Business Rule Validation
            $this->validateTransitionRules($lockedLead, $fromStatus, $toStatus, $context);

            // 2. Resolve next_follow_up_at based on business rules
            $nextFollowUpAt = $this->resolveNextFollowUpAt($toStatus, $context, $lockedLead);

            // 3. Prepare Lead attributes for update
            $leadUpdateData = [
                'lead_status_id' => $toStatusId,
                'next_follow_up_at' => $nextFollowUpAt,
            ];

            if (array_key_exists('response_details', $context)) {
                $leadUpdateData['response_details'] = $context['response_details'];
            }
            if (array_key_exists('contact_date', $context)) {
                $leadUpdateData['contact_date'] = $context['contact_date'] !== null
                    ? ($context['contact_date'] instanceof Carbon ? $context['contact_date'] : Carbon::parse($context['contact_date']))
                    : now();
            }
            if (array_key_exists('responding_user_id', $context)) {
                $leadUpdateData['responding_user_id'] = $context['responding_user_id'];
            }
            if (array_key_exists('assigned_user_id', $context)) {
                $leadUpdateData['assigned_user_id'] = $context['assigned_user_id'];
            }
            if (array_key_exists('assigned_employee', $context)) {
                $leadUpdateData['assigned_employee'] = $context['assigned_employee'];
            }
            if (array_key_exists('disinterest_reason', $context)) {
                $leadUpdateData['disinterest_reason'] = $context['disinterest_reason'];
            }
            if (array_key_exists('notes', $context) && $context['notes'] !== null) {
                $leadUpdateData['notes'] = $context['notes'];
            }

            // Donation fields handling
            if (array_key_exists('donation_type', $context)) {
                $leadUpdateData['donation_type'] = $context['donation_type'];
            }
            if (array_key_exists('donation_type_id', $context)) {
                $leadUpdateData['donation_type_id'] = $context['donation_type_id'];
            }
            if (array_key_exists('donation_cycle', $context)) {
                $leadUpdateData['donation_cycle'] = $context['donation_cycle'];
            }
            if (array_key_exists('donation_value', $context)) {
                $leadUpdateData['donation_value'] = $context['donation_value'] !== null && $context['donation_value'] !== ''
                    ? (float) $context['donation_value']
                    : null;
            }
            if (array_key_exists('donation_purpose', $context)) {
                $leadUpdateData['donation_purpose'] = $context['donation_purpose'];
            }
            if (array_key_exists('donation_purpose_id', $context)) {
                $leadUpdateData['donation_purpose_id'] = $context['donation_purpose_id'];
            }

            // Additional lead attributes if passed in context (e.g. direct lead edit)
            if (! empty($context['lead_attributes']) && is_array($context['lead_attributes'])) {
                foreach ($context['lead_attributes'] as $attrKey => $attrVal) {
                    if (! in_array($attrKey, ['id', 'created_at', 'updated_at'], true)) {
                        $leadUpdateData[$attrKey] = $attrVal;
                    }
                }
                // Ensure lead_status_id and next_follow_up_at resolved take precedence
                $leadUpdateData['lead_status_id'] = $toStatusId;
                $leadUpdateData['next_follow_up_at'] = $nextFollowUpAt;
            }

            $lockedLead->update($leadUpdateData);

            // Handle campaign association if provided
            if (isset($context['campaign']) && $context['campaign'] instanceof Campaign) {
                $lockedLead->campaigns()->sync([$context['campaign']->id]);
            } elseif (! empty($context['campaign_id'])) {
                $lockedLead->campaigns()->sync([(int) $context['campaign_id']]);
            }

            // 4. Create LeadFollowup record if follow-up context is provided
            $followupRecord = null;
            $hasFollowupContext = ! empty($context['record_followup'])
                || ! empty($context['communication_type'])
                || ! empty($context['outcome']);

            if ($hasFollowupContext) {
                $communicationType = (string) ($context['communication_type'] ?? 'other');
                $outcome = isset($context['outcome']) ? (string) $context['outcome'] : null;
                $employeeName = ! empty($context['employee_name'])
                    ? (string) $context['employee_name']
                    : (trim((string) $actor->name) ?: 'System');

                $followupData = [
                    'lead_id' => $lockedLead->id,
                    'from_status_id' => $fromStatusId,
                    'to_status_id' => $toStatusId,
                    'user_id' => $actor->id ?? null,
                    'employee_name' => $employeeName,
                    'communication_type' => $communicationType,
                    'outcome' => $outcome,
                    'next_follow_up_at' => $nextFollowUpAt,
                    'followed_up_at' => ! empty($context['followed_up_at'])
                        ? ($context['followed_up_at'] instanceof Carbon ? $context['followed_up_at'] : Carbon::parse($context['followed_up_at']))
                        : now(),
                ];

                if (! empty($context['field_changes']) && is_array($context['field_changes'])) {
                    $followupData['field_changes'] = $context['field_changes'];
                }

                $followupRecord = LeadFollowup::query()->create($followupData);
            }

            // 5. Create LeadStatusHistory atomically if status changed or force_history requested
            $historyRecord = null;
            if ($statusChanged || ! empty($context['force_history'])) {
                $actorName = trim((string) $actor->name) ?: 'System';
                $defaultNote = $statusChanged
                    ? 'تغيير الحالة إلى '.$toStatus->localizedName()
                    : 'تحديث حالة العميل';

                $historyNote = $context['history_note'] ?? $defaultNote;

                $historyRecord = LeadStatusHistory::query()->create([
                    'lead_id' => $lockedLead->id,
                    'from_status_id' => $fromStatusId,
                    'to_status_id' => $toStatusId,
                    'changed_by' => $actorName,
                    'changed_by_user_id' => $actor->id ?? null,
                    'note' => $historyNote,
                    'changed_at' => now(),
                ]);
            }

            return [
                'lead' => $lockedLead->fresh(['status.stage', 'assignedUser']),
                'history' => $historyRecord,
                'followup' => $followupRecord,
                'changed' => $statusChanged,
            ];
        });
    }

    /**
     * Centralized transition validation rules.
     *
     * @throws ValidationException
     */
    protected function validateTransitionRules(
        Lead $lead,
        ?LeadStatus $fromStatus,
        LeadStatus $toStatus,
        array $context
    ): void {
        // Rule 1: no_answer requires a next_follow_up_at when in a follow-up or quick follow-up flow
        if ($toStatus->code === 'no_answer') {
            $hasFollowupDate = ! empty($context['next_follow_up_at']);
            $isFollowupFlow = ! empty($context['record_followup'])
                || ! empty($context['communication_type'])
                || ! empty($context['outcome']);

            if ($isFollowupFlow && ! $hasFollowupDate) {
                throw ValidationException::withMessages([
                    'next_follow_up_at' => ['موعد المتابعة القادمة إجباري لحالة لم يتم الرد.'],
                ]);
            }
        }
    }

    /**
     * Resolve the appropriate next_follow_up_at datetime.
     */
    protected function resolveNextFollowUpAt(
        LeadStatus $toStatus,
        array $context,
        Lead $lead
    ): ?Carbon {
        // Rule 1: not_interested always clears next_follow_up_at
        if ($toStatus->code === 'not_interested') {
            return null;
        }

        if (array_key_exists('next_follow_up_at', $context)) {
            if (empty($context['next_follow_up_at'])) {
                return null;
            }

            try {
                return $context['next_follow_up_at'] instanceof Carbon
                    ? $context['next_follow_up_at']
                    : Carbon::parse($context['next_follow_up_at']);
            } catch (\Throwable) {
                return null;
            }
        }

        return $lead->next_follow_up_at;
    }
}
