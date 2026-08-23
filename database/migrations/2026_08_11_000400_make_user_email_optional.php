<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function verifyDatabase(): void
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
                'Refusing optional user email migration for environment %s on database %s.',
                $environment,
                $database,
            ));
        }

        if (! Schema::hasColumn('users', 'email')) {
            throw new RuntimeException('The users.email column does not exist.');
        }
    }

    public function up(): void
    {
        $this->verifyDatabase();

        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable()->change();
        });
    }

    public function down(): void
    {
        $this->verifyDatabase();

        if (DB::table('users')->whereNull('email')->exists()) {
            throw new RuntimeException(
                'Cannot require users.email while users without email exist.',
            );
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->string('email')->nullable(false)->change();
        });
    }
};
