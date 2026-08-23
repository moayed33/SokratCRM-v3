<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\NotificationRule;
use App\Security\CrmPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveNotificationRuleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(CrmPermission::NOTIFICATIONS_MANAGE->value) ?? false;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'name_ar' => ['required', 'string', 'max:150'],
            'name_en' => ['required', 'string', 'max:150'],
            'event_key' => [
                'required',
                Rule::in(array_values(array_diff(
                    NotificationRule::EVENTS,
                    [NotificationRule::EVENT_SYSTEM_TEST],
                ))),
            ],
            'enabled' => ['nullable', 'boolean'],
            'trigger_offset_minutes' => ['required', 'integer', 'min:0', 'max:10080'],
            'escalation_after_minutes' => ['nullable', 'integer', 'min:0', 'max:10080'],
            'priority' => ['required', Rule::in(NotificationRule::PRIORITIES)],
            'channels' => ['required', 'array', 'min:1'],
            'channels.*' => ['required', 'distinct', Rule::in(NotificationRule::CHANNELS)],
            'recipient_types' => ['nullable', 'array'],
            'recipient_types.*' => ['required', 'distinct', Rule::in(['assigned_user', 'event_owner'])],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'group_ids' => ['nullable', 'array'],
            'group_ids.*' => ['integer', 'distinct', 'exists:groups,id'],
            'lead_status_ids' => ['nullable', 'array'],
            'lead_status_ids.*' => ['integer', 'distinct', 'exists:lead_statuses,id'],
            'calendar_types' => ['nullable', 'array'],
            'calendar_types.*' => ['string', 'distinct', Rule::in(['meeting', 'call', 'task', 'reminder'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $hasRecipients = count((array) $this->input('recipient_types', [])) > 0
                || count((array) $this->input('user_ids', [])) > 0
                || count((array) $this->input('group_ids', [])) > 0;

            if (! $hasRecipients) {
                $validator->errors()->add('recipients', __('crm.notification_rule_recipient_required'));
            }
        });
    }
}
