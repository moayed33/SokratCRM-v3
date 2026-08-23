<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Quotation;
use App\Models\User;
use App\Security\CrmPermission;

class QuotationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(CrmPermission::QUOTATIONS_VIEW);
    }

    public function view(User $user, Quotation $quotation): bool
    {
        return $user->hasPermission(CrmPermission::QUOTATIONS_VIEW);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(CrmPermission::QUOTATIONS_CREATE);
    }
}
