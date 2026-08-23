<?php

declare(strict_types=1);

return [
    'enabled' => env('NOTIFICATIONS_ENABLED', true),
    'poll_seconds' => (int) env('NOTIFICATIONS_POLL_SECONDS', 60),
    'planner_lookback_hours' => (int) env('NOTIFICATIONS_LOOKBACK_HOURS', 24),
    'max_deliveries_per_user_per_hour' => (int) env('NOTIFICATIONS_RATE_LIMIT', 30),

    'channels' => [
        'database' => true,
        'mail' => env('NOTIFICATIONS_MAIL_ENABLED', false),
        'push' => env('NOTIFICATIONS_PUSH_ENABLED', false),
        'sms' => env('NOTIFICATIONS_SMS_ENABLED', false),
        'whatsapp' => env('NOTIFICATIONS_WHATSAPP_ENABLED', false),
    ],

    'web_push' => [
        'subject' => env('WEB_PUSH_SUBJECT', env('MAIL_FROM_ADDRESS')),
        'public_key' => env('WEB_PUSH_PUBLIC_KEY'),
        'private_key' => env('WEB_PUSH_PRIVATE_KEY'),
        'allowed_hosts' => array_values(array_filter(array_map(
            static fn (string $host): string => strtolower(trim($host)),
            explode(',', (string) env(
                'WEB_PUSH_ALLOWED_HOSTS',
                'fcm.googleapis.com,updates.push.services.mozilla.com,web.push.apple.com',
            )),
        ))),
    ],

    'twilio' => [
        'account_sid' => env('TWILIO_ACCOUNT_SID'),
        'auth_token' => env('TWILIO_AUTH_TOKEN'),
        'sms_from' => env('TWILIO_SMS_FROM'),
        'messaging_service_sid' => env('TWILIO_MESSAGING_SERVICE_SID'),
        'whatsapp_from' => env('TWILIO_WHATSAPP_FROM'),
        'whatsapp_content_sid_ar' => env('TWILIO_WHATSAPP_CONTENT_SID_AR'),
        'whatsapp_content_sid_en' => env('TWILIO_WHATSAPP_CONTENT_SID_EN'),
        'status_callback' => env('TWILIO_STATUS_CALLBACK'),
    ],
];
