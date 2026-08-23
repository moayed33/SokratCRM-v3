<?php

declare(strict_types=1);

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class CrmNotificationDigestMail extends Mailable
{
    use Queueable, SerializesModels;

    /** @param list<array<string, mixed>> $notifications */
    public function __construct(
        public readonly array $notifications,
        public readonly string $locale,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(subject: trans('crm.notification_daily_digest', [], $this->locale));
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.crm-notification-digest',
            with: [
                'notifications' => $this->notifications,
                'locale' => $this->locale,
            ],
        );
    }
}
