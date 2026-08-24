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
            'testing' => 'sokrat_crm_v3_testing',
            default => 'sokrat_crm_v3',
        };
        $connection = DB::connection();
        $database = (string) $connection->getDatabaseName();

        if (
            $connection->getDriverName() !== 'mysql'
            || $expectedDatabase === null
            || $database !== $expectedDatabase
        ) {
            throw new RuntimeException(sprintf(
                'CRM v3 database isolation failed for environment %s on database %s.',
                $environment,
                $database,
            ));
        }
    }
}
