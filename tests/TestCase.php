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
        $connection->statement('CREATE DATABASE IF NOT EXISTS `'.self::TEST_DATABASE.'`;');

        $activeDatabase = (string) $connection
            ->scalar('SELECT DATABASE()');

        if ($activeDatabase !== self::TEST_DATABASE) {
            throw new RuntimeException(sprintf(
                'Refusing tests: active database is %s.',
                $activeDatabase,
            ));
        }
        $connection->unprepared('SET FOREIGN_KEY_CHECKS=0;');
        $connection->unprepared('SET SESSION innodb_lock_wait_timeout = 50;');
        \Illuminate\Foundation\Testing\RefreshDatabaseState::$migrated = true;
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        \Illuminate\Support\Facades\DB::unprepared('SET FOREIGN_KEY_CHECKS=0;');
        \Illuminate\Support\Facades\DB::unprepared('SET SESSION innodb_lock_wait_timeout = 50;');
    }

    protected function beforeRefreshingDatabase()
    {
        $this->app->make(DatabaseManager::class)->connection()->unprepared('SET FOREIGN_KEY_CHECKS=0;');
    }

    protected function afterRefreshingDatabase()
    {
        $this->app->make(DatabaseManager::class)->connection()->unprepared('SET FOREIGN_KEY_CHECKS=0;');
    }
}
