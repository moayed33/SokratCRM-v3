<?php

declare(strict_types=1);

return [
    'database' => env('DB_DATABASE', 'sokrat_crm_v3'),

    'bootstrap_admin' => [
        'name' => env('CRM_V2_ADMIN_NAME', 'مدير النظام'),
        'username' => env('CRM_V2_ADMIN_USER', 'admin'),
        'email' => env('CRM_V2_ADMIN_EMAIL', 'admin@localhost.invalid'),
        'password' => env('CRM_V2_ADMIN_PASSWORD'),
    ],
];
