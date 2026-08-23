<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Security\CrmPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePermissionMatrixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            CrmPermission::GROUPS_ASSIGN_PERMISSIONS->value,
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['nullable', 'array'],
            'permissions.*.*' => [
                'string',
                Rule::exists('permissions', 'code'),
            ],
        ];
    }
}
