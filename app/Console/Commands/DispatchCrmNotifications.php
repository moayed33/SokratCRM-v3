<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Notifications\NotificationDigestDispatcher;
use App\Services\Notifications\NotificationDispatcher;
use App\Services\Notifications\ReminderPlanner;
use Illuminate\Console\Command;

class DispatchCrmNotifications extends Command
{
    protected $signature = 'crm:notifications:dispatch';

    protected $description = 'Plan and dispatch due CRM notifications';

    public function handle(
        ReminderPlanner $planner,
        NotificationDispatcher $dispatcher,
        NotificationDigestDispatcher $digestDispatcher,
    ): int {
        if (! config('crm_notifications.enabled')) {
            $this->components->info('CRM notifications are disabled.');

            return self::SUCCESS;
        }

        $planned = $planner->planScheduled();
        $occurrences = $dispatcher->dispatchDueOccurrences();
        $deliveries = $dispatcher->queueDueDeliveries();
        $digests = $digestDispatcher->queueDueDigests();

        $this->components->info(sprintf(
            'Planned %d, dispatched %d, queued %d, digests %d.',
            $planned,
            $occurrences,
            $deliveries,
            $digests,
        ));

        return self::SUCCESS;
    }
}
