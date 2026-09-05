<?php

declare(strict_types=1);

return [
    'credentials_path' => env('VOIP_CREDENTIALS_PATH', storage_path('app/private/voip-credentials.json')),
    'api_url' => env('VOIP_API_URL', 'http://192.168.100.128:8090/api/integrations/crm/v1'),
    'client_id' => env('VOIP_CLIENT_ID', ''),
    'client_secret' => env('VOIP_CLIENT_SECRET', ''),
    'default_country_code' => env('VOIP_DEFAULT_COUNTRY_CODE', '20'),
    'timezone' => env('VOIP_TIMEZONE', 'Africa/Cairo'),
    'timeout' => (int) env('VOIP_TIMEOUT', 10),
];
