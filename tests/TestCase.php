<?php

namespace Tests;

use Illuminate\Database\DatabaseManager;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    private const TEST_DATABASE = 'sokrat_crm_v3_testing';

    public function createApplication(): Application
    {
        $app = parent::createApplication();
        $connection = $app
            ->make(DatabaseManager::class)
            ->connection();
        $environment = $app->environment();
        $driver = $connection->getDriverName();
        $configuredDatabase = (string) $connection
            ->getDatabaseName();

        if (
            $environment !== 'testing'
            || $driver !== 'mysql'
            || $configuredDatabase !== self::TEST_DATABASE
        ) {
            throw new RuntimeException(sprintf(
                'Refusing tests: environment=%s, driver=%s, database=%s.',
                $environment,
                $driver,
                $configuredDatabase,
            ));
        }

        $activeDatabase = (string) $connection
            ->scalar('SELECT DATABASE()');

        if ($activeDatabase !== self::TEST_DATABASE) {
            throw new RuntimeException(sprintf(
                'Refusing tests: active database is %s.',
                $activeDatabase,
            ));
        }

        return $app;
    }
}
