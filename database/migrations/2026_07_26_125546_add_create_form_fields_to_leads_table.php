<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private function verifyDatabase(): void
    {
        $database = (string) DB::connection()
            ->getDatabaseName();
        $environment = app()->environment();
        $expectedDatabase = match ($environment) {
            'testing' => 'sokrat_crm_v3_testing',
            default => 'sokrat_crm_v3',
        };

        if ($database !== $expectedDatabase) {
            throw new RuntimeException(sprintf(
                'Refusing migration for environment %s on database %s.',
                $environment,
                $database,
            ));
        }
    }

    public function up(): void
    {
        $this->verifyDatabase();

        $columns = [
            'first_name',
            'last_name',
            'activity',
            'governorate',
            'address',
            'users_count',
            'branches_count',
            'job_title',
            'disinterest_reason',
            'solution_type',
            'lines_count',
            'extensions',
            'departments',
            'quotation_file_path',
        ];

        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'first_name')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->string('first_name', 75)->nullable()->after('name');
                $table->string('last_name', 75)->nullable()->after('name');
                $table->string('activity', 150)->nullable()->after('company_name');
                $table->string('governorate', 100)->nullable()->after('company_name');
                $table->string('address', 255)->nullable()->after('company_name');
                $table->unsignedInteger('users_count')->nullable();
                $table->unsignedInteger('branches_count')->nullable();
                $table->string('job_title', 150)->nullable();
                $table->text('disinterest_reason')->nullable();
                $table->string('solution_type', 20)->nullable();
                $table->unsignedInteger('lines_count')->nullable();
                $table->text('extensions')->nullable();
                $table->text('departments')->nullable();
                $table->string('quotation_file_path', 500)->nullable();
            });
        }
    }

    public function down(): void
    {
        $this->verifyDatabase();

        $columns = [
            'quotation_file_path',
            'departments',
            'extensions',
            'lines_count',
            'solution_type',
            'disinterest_reason',
            'job_title',
            'branches_count',
            'users_count',
            'address',
            'governorate',
            'activity',
            'last_name',
            'first_name',
        ];

        if (! Schema::hasTable('leads')) {
            return;
        }

        $existing = array_values(
            array_filter(
                $columns,
                static fn (string $column): bool => Schema::hasColumn('leads', $column)
            )
        );

        if ($existing === []) {
            return;
        }

        Schema::table(
            'leads',
            function (Blueprint $table) use ($existing): void {
                $table->dropColumn($existing);
            }
        );
    }
};
