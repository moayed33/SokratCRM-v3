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
        $allowedDatabases = [
            'production' => 'sokrat_crm_v2',
            'testing' => 'sokrat_crm_v2_testing',
        ];
        $expectedDatabase = $allowedDatabases[$environment]
            ?? null;

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

        if (Schema::hasColumn('leads', 'quotation_sent')) {
            throw new RuntimeException(
                'leads.quotation_sent already exists.'
            );
        }

        Schema::table(
            'leads',
            function (Blueprint $table): void {
                $table
                    ->boolean('quotation_sent')
                    ->default(false)
                    ->after('source');

                $table->index(
                    'quotation_sent',
                    'leads_quotation_sent_index'
                );
            }
        );
    }

    public function down(): void
    {
        $this->verifyDatabase();

        if (! Schema::hasColumn('leads', 'quotation_sent')) {
            return;
        }

        Schema::table(
            'leads',
            function (Blueprint $table): void {
                $table->dropIndex(
                    'leads_quotation_sent_index'
                );

                $table->dropColumn('quotation_sent');
            }
        );
    }
};
