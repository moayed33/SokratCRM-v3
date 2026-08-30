<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Campaign;
use App\Models\CollectionActivity;
use App\Models\CollectionCase;
use App\Models\Donation;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\LeadStatusHistory;
use App\Models\User;
use App\Models\PipelineStage;
use App\Support\StageFieldSchema;
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

            $donationData = isset($context['donation']) && is_array($context['donation'])
                ? $context['donation']
                : null;
            $collectionData = isset($context['collection']) && is_array($context['collection'])
                ? $context['collection']
                : null;
            $isEnteringDonor = $statusChanged && $toStatus->code === 'donor';

            if ($isEnteringDonor && $donationData === null && $collectionData === null) {
                $legacyDonation = array_merge(
                    is_array($context['lead_attributes'] ?? null) ? $context['lead_attributes'] : [],
                    $context,
                );
                $amount = $legacyDonation['donation_value'] ?? null;

                if (is_numeric($amount) && (float) $amount > 0) {
                    $donationData = [
                        'donation_type_id' => $legacyDonation['donation_type_id'] ?? null,
                        'donation_type' => trim((string) ($legacyDonation['donation_type'] ?? '')) ?: 'تبرع غير مصنف',
                        'amount' => (string) $amount,
                        'cycle' => trim((string) ($legacyDonation['donation_cycle'] ?? '')) ?: 'one_time',
                        'donated_at' => $legacyDonation['contact_date'] ?? now(),
                    ];
                }
            }

            if ($isEnteringDonor && $donationData === null && $collectionData === null) {
                throw ValidationException::withMessages([
                    'donation_value' => ['يجب تسجيل بيانات التبرع عند تحويل العميل إلى متبرع.'],
                ]);
            }

            if ($donationData !== null && $collectionData !== null) {
                throw ValidationException::withMessages([
                    'donation_way' => ['اختر طريقة تبرع واحدة فقط.'],
                ]);
            }

            if (
                ($donationData !== null || $collectionData !== null)
                && ($context['donation_intent'] ?? null) === 'conversion'
                && ! $isEnteringDonor
            ) {
                throw ValidationException::withMessages([
                    'lead_status_id' => ['تم تحويل العميل إلى متبرع بالفعل. حدّث الصفحة قبل تسجيل تبرع جديد.'],
                ]);
            }
            // 1. Destination stage resolution
            $toStage = $toStatus->stage ?? PipelineStage::query()->find($toStatus->pipeline_stage_id);

            // 2. Validate stage field schema and extract normalized stage values
            $normalizedStageValues = [];
            if ($toStage !== null) {
                $rawStageInputs = isset($context['stage_fields']) && is_array($context['stage_fields'])
                    ? $context['stage_fields']
                    : [];
                if (! empty($context['stage_fields_custom']) && is_array($context['stage_fields_custom'])) {
                    $rawStageInputs['stage_fields_custom'] = $context['stage_fields_custom'];
                }
                // Backward compatibility mapping for canonical rules if field exists on stage
                $stageFields = StageFieldSchema::getFieldsForStage($toStage, true);
                $stageFieldKeys = $stageFields->pluck('key')->all();
                if (in_array('callback_at', $stageFieldKeys, true) && ! isset($rawStageInputs['callback_at']) && ! empty($context['next_follow_up_at'])) {
                    $rawStageInputs['callback_at'] = $context['next_follow_up_at'] instanceof Carbon
                        ? $context['next_follow_up_at']->toDateTimeString()
                        : (string) $context['next_follow_up_at'];
                }
                if (in_array('reason', $stageFieldKeys, true) && ! isset($rawStageInputs['reason'])) {
                    $disReason = (string) ($context['disinterest_reason'] ?? $context['outcome'] ?? '');
                    if ($disReason !== '') {
                        $reasonField = $stageFields->firstWhere('key', 'reason');
                        $opts = $reasonField ? $reasonField->normalizedOptions() : [];
                        $optVals = array_column($opts, 'value');
                        $optLabelsAr = array_column($opts, 'label_ar');
                        $optLabelsEn = array_column($opts, 'label_en');
                        $allAllowed = array_merge($optVals, $optLabelsAr, $optLabelsEn);
                        if (in_array($disReason, $allAllowed, true) || empty($optVals)) {
                            $rawStageInputs['reason'] = $disReason;
                        } elseif (in_array('other', $optVals, true)) {
                            $rawStageInputs['reason'] = 'other';
                            $rawStageInputs['stage_fields_custom'] = array_merge(
                                (array) ($rawStageInputs['stage_fields_custom'] ?? []),
                                ['reason' => $disReason],
                            );
                        } else {
                            $rawStageInputs['reason'] = $optVals[0] ?? $disReason;
                        }
                    }
                }
                try {
                    $normalizedStageValues = StageFieldSchema::validateAndExtract($toStage, $rawStageInputs, $actor);
                } catch (ValidationException $e) {
                    $errors = $e->errors();
                    if (isset($errors['callback_at']) && ! isset($errors['next_follow_up_at'])) {
                        $errors['next_follow_up_at'] = $errors['callback_at'];
                    }
                    if (isset($errors['reason']) && ! isset($errors['disinterest_reason'])) {
                        $errors['disinterest_reason'] = $errors['reason'];
                    }
                    throw ValidationException::withMessages($errors);
                }
            }

            // 3. Business Rule Validation
            $this->validateTransitionRules($lockedLead, $fromStatus, $toStatus, $context);

            // 4. Resolve next_follow_up_at based on business rules and stage fields
            $nextFollowUpAt = $this->resolveNextFollowUpAt($toStatus, $context, $lockedLead, $normalizedStageValues);
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
            } elseif (! empty($normalizedStageValues['reason'])) {
                $leadUpdateData['disinterest_reason'] = (string) $normalizedStageValues['reason'];
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

            if ($donationData !== null) {
                $leadUpdateData['donation_type'] = $donationData['donation_type'];
                $leadUpdateData['donation_type_id'] = $donationData['donation_type_id'];
                $leadUpdateData['donation_cycle'] = $donationData['cycle'];
                $leadUpdateData['donation_value'] = $donationData['amount'];
            }

            if ($collectionData !== null) {
                $leadUpdateData['donation_type'] = $collectionData['donation_type'];
                $leadUpdateData['donation_type_id'] = $collectionData['donation_type_id'];
                $leadUpdateData['donation_cycle'] = $collectionData['cycle'];
                $leadUpdateData['donation_value'] = $collectionData['expected_amount'];
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

            $donationRecord = null;
            if ($donationData !== null) {
                $donationRecord = Donation::query()->create([
                    'lead_id' => $lockedLead->id,
                    'lead_followup_id' => $followupRecord?->id,
                    'donation_type_id' => $donationData['donation_type_id'] ?? null,
                    'donation_type' => $donationData['donation_type'],
                    'amount' => $donationData['amount'],
                    'cycle' => $donationData['cycle'],
                    'donation_way' => $donationData['donation_way'] ?? 'legacy',
                    'instant_donation_method_id' => $donationData['instant_donation_method_id'] ?? null,
                    'instant_donation_account' => $donationData['instant_donation_account'] ?? null,
                    'receipt_path' => $donationData['receipt_path'] ?? null,
                    'receipt_original_name' => $donationData['receipt_original_name'] ?? null,
                    'donated_at' => ($donationData['donated_at'] ?? null) instanceof Carbon
                        ? $donationData['donated_at']
                        : Carbon::parse($donationData['donated_at'] ?? now()),
                    'recorded_by_user_id' => $actor->id,
                ]);
            }

            $collectionRecord = null;
            if ($collectionData !== null) {
                $assignedCollectorId = $collectionData['assigned_collector_user_id'] ?? null;
                $collectionRecord = CollectionCase::query()->create([
                    'lead_id' => $lockedLead->id,
                    'source_followup_id' => $followupRecord?->id,
                    'branch_id' => $lockedLead->branch_id,
                    'donation_type_id' => $collectionData['donation_type_id'] ?? null,
                    'donation_type' => $collectionData['donation_type'],
                    'expected_amount' => $collectionData['expected_amount'],
                    'cycle' => $collectionData['cycle'],
                    'status' => $assignedCollectorId !== null
                        ? CollectionCase::STATUS_ASSIGNED
                        : CollectionCase::STATUS_PENDING,
                    'due_at' => Carbon::parse($collectionData['due_at']),
                    'collection_address' => $collectionData['collection_address'],
                    'notes' => $collectionData['notes'] ?? null,
                    'assigned_collector_user_id' => $assignedCollectorId,
                    'created_by_user_id' => $actor->id,
                    'assigned_by_user_id' => $assignedCollectorId !== null ? $actor->id : null,
                ]);

                CollectionActivity::query()->create([
                    'collection_case_id' => $collectionRecord->id,
                    'actor_user_id' => $actor->id,
                    'action' => 'created',
                    'to_status' => $collectionRecord->status,
                    'notes' => $collectionRecord->notes,
                    'new_due_at' => $collectionRecord->due_at,
                    'new_collector_user_id' => $assignedCollectorId,
                ]);
            }

            // 5. Create LeadStatusHistory atomically if status changed or force_history requested
            $historyRecord = null;
            if ($statusChanged || ! empty($context['force_history'])) {
                $actorName = trim((string) $actor->name) ?: 'System';
                $defaultNote = $statusChanged
                    ? 'تغيير الحالة إلى '.$toStatus->localizedName()
                    : 'تحديث حالة العميل';

                // Detect backward transitions (moving to an earlier pipeline position)
                $fromPosition = $fromStatus?->stage?->position;
                $toPosition = $toStage?->position;
                $isBackward = $statusChanged
                    && $fromPosition !== null
                    && $toPosition !== null
                    && $toPosition < $fromPosition;

                if ($isBackward) {
                    $defaultNote = '⚠ تراجع: '.$defaultNote
                        .' (من '.$fromStatus->localizedName().')';
                }

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

            // 6. Persist LeadStageFieldValue records atomically within the same transaction
            $savedStageValues = collect();
            if ($toStage !== null && ! empty($normalizedStageValues)) {
                $savedStageValues = StageFieldSchema::persistValues(
                    $lockedLead,
                    $toStage,
                    $normalizedStageValues,
                    $historyRecord,
                    $actor
                );
            }

            return [
                'lead' => $lockedLead->fresh(['status.stage', 'assignedUser', 'stageFieldValues']),
                'history' => $historyRecord,
                'followup' => $followupRecord,
                'donation' => $donationRecord,
                'collection' => $collectionRecord,
                'stage_field_values' => $savedStageValues,
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
        // Rule 1: no_answer requires a next_follow_up_at in follow-up flows
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

        // Rule 2: not_interested requires a disinterest_reason
        if ($toStatus->code === 'not_interested') {
            $reason = trim((string) ($context['disinterest_reason'] ?? ''));
            if ($reason === '' && empty($context['lead_attributes']['disinterest_reason'])) {
                throw ValidationException::withMessages([
                    'disinterest_reason' => ['سبب عدم الاهتمام مطلوب.'],
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
        Lead $lead,
        array $normalizedStageValues = []
    ): ?Carbon {
        // not_interested always clears next_follow_up_at
        if ($toStatus->code === 'not_interested') {
            return null;
        }
        // callback_at or scheduling date from stage fields takes precedence
        if (! empty($normalizedStageValues['callback_at'])) {
            try {
                return Carbon::parse((string) $normalizedStageValues['callback_at']);
            } catch (\Throwable) {
                // fallback to context value
            }
        }

        foreach (['scheduled_donation_date', 'next_donation_date', 'next_donation', 'scheduled_date', 'appointment_at'] as $schedKey) {
            if (! empty($normalizedStageValues[$schedKey])) {
                $rawSched = (string) $normalizedStageValues[$schedKey];
                $relativeDate = self::resolveRelativeDate($rawSched);
                if ($relativeDate !== null) {
                    return $relativeDate;
                }
                try {
                    return Carbon::parse($rawSched);
                } catch (\Throwable) {
                }
            }
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

    /**
     * Resolve predefined relative period tokens into concrete future dates.
     */
    public static function resolveRelativeDate(string $token): ?Carbon
    {
        $token = strtolower(trim($token));
        return match ($token) {
            '1_day', 'tomorrow' => now()->addDay(),
            '2_days' => now()->addDays(2),
            '3_days' => now()->addDays(3),
            '1_week', 'after_1_week', 'week' => now()->addWeek(),
            '2_weeks', 'after_2_weeks' => now()->addWeeks(2),
            '3_weeks' => now()->addWeeks(3),
            '1_month', 'after_1_month', 'monthly', 'month' => now()->addMonthsNoOverflow(1),
            '2_months' => now()->addMonthsNoOverflow(2),
            '3_months', 'after_3_months', 'quarterly' => now()->addMonthsNoOverflow(3),
            '6_months', 'after_6_months', 'semi_annual' => now()->addMonthsNoOverflow(6),
            '1_year', 'after_1_year', 'annual', 'year' => now()->addYears(1),
            default => null,
        };
    }
}
