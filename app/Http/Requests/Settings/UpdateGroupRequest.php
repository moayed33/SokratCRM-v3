<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\Group;
use App\Security\CrmPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            CrmPermission::GROUPS_UPDATE->value,
        ) ?? false;
    }

    public function rules(): array
    {
        /** @var Group $group */
        $group = $this->route('group');

        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'nullable',
                'string',
                'max:100',
                'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::unique('groups', 'code')->ignore($group),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
