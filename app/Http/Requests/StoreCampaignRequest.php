<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Security\CrmPermission;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCampaignRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(
            CrmPermission::CAMPAIGNS_CREATE->value,
        ) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
            'cost' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'user_ids' => ['required', 'array', 'min:1'],
            'user_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where('is_active', true),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'اسم الحملة',
            'image' => 'صورة الحملة',
            'cost' => 'تكلفة الحملة',
            'starts_at' => 'وقت بداية الحملة',
            'ends_at' => 'وقت نهاية الحملة',
            'user_ids' => 'المستخدمون المشاركون',
            'user_ids.*' => 'المستخدم المختار',
        ];
    }
}
