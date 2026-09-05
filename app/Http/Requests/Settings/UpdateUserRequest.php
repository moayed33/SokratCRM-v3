<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\User;
use App\Security\CrmPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            CrmPermission::USERS_UPDATE->value,
        ) ?? false;
    }

    public function rules(): array
    {
        /** @var User $managedUser */
        $managedUser = $this->route('user');

        return [
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')->where('is_active', true)],
            'manager_id' => ['nullable', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'mobile_phone' => ['nullable', 'string', 'max:50'],
            'name' => ['required', 'string', 'max:150'],
            'username' => [
                'nullable',
                'string',
                'max:100',
                'alpha_dash:ascii',
                Rule::unique('users', 'username')->ignore($managedUser),
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($managedUser),
            ],
            'voip_extension' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('users', 'voip_extension')->ignore($managedUser),
            ],
            'collection_zone' => ['nullable', 'string', 'max:255'],
            'collection_subregion_id' => ['nullable', 'integer', 'exists:governorate_subregions,id'],
            'group_ids' => ['required', 'array', 'min:1'],
            'group_ids.*' => [
                'integer',
                'distinct',
                'exists:groups,id',
            ],
            'subordinate_ids' => ['nullable', 'array'],
            'subordinate_ids.*' => ['integer', 'distinct', 'exists:users,id'],
            'subordinates_section_rendered' => ['nullable', 'boolean'],
        ];
    }
}
