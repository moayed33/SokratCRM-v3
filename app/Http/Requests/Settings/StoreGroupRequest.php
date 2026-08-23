<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\Group;
use App\Security\CrmPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            CrmPermission::GROUPS_CREATE->value,
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:100',
                'regex:/\A[a-z0-9]+(?:-[a-z0-9]+)*\z/',
                Rule::notIn([Group::SUPER_ADMIN_CODE]),
                'unique:groups,code',
            ],
            'description' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
