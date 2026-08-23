<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

final class CrmDatabaseGuard
{
    /**
     * Refuse CRM operations outside the exact production/testing database pair.
     */
    public static function ensureConnected(): void
    {
        $environment = app()->environment();
        $expectedDatabase = match ($environment) {
            'production' => 'sokrat_crm_v2',
            'testing' => 'sokrat_crm_v2_testing',
            default => null,
        };
        $connection = DB::connection();
        $database = (string) $connection->getDatabaseName();

        if (
            $connection->getDriverName() !== 'mysql'
            || $expectedDatabase === null
            || $database !== $expectedDatabase
        ) {
            throw new RuntimeException(sprintf(
                'CRM v2 database isolation failed for environment %s on database %s.',
                $environment,
                $database,
            ));
        }
    }
}
