<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use App\Models\CalendarEvent;
use App\Models\Lead;
use App\Models\NotificationRule;

class NotificationRuleMatcher
{
    public function matches(NotificationRule $rule, Lead|CalendarEvent $source): bool
    {
        $conditions = $rule->conditions ?? [];

        if ($source instanceof Lead) {
            $statusIds = array_map('intval', $conditions['lead_status_ids'] ?? []);

            return $statusIds === []
                || in_array((int) $source->lead_status_id, $statusIds, true);
        }

        $types = array_values(array_filter(
            $conditions['calendar_types'] ?? [],
            'is_string',
        ));

        return $types === [] || in_array($source->type, $types, true);
    }
}
