<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\NotificationOccurrence;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CrmReminderNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly NotificationOccurrence $occurrence,
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            ...$this->occurrence->payload,
            'occurrence_id' => $this->occurrence->getKey(),
        ];
    }
}
