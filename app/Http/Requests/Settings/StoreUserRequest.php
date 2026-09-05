<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Security\CrmPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            CrmPermission::USERS_CREATE->value,
        ) ?? false;
    }

    public function rules(): array
    {
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
                'unique:users,username',
            ],
            'email' => [
                'nullable',
                'email',
                'max:255',
                'unique:users,email',
            ],
            'voip_extension' => ['nullable', 'string', 'max:50', Rule::unique('users', 'voip_extension')],
            'collection_zone' => ['nullable', 'string', 'max:255'],
            'collection_subregion_id' => ['nullable', 'integer', Rule::exists('governorate_subregions', 'id')->where('is_active', true)],
            'password' => [
                'required',
                'string',
                'confirmed',
            ],
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
