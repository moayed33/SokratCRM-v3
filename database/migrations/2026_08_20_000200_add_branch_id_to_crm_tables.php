<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'branch_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('leads') && ! Schema::hasColumn('leads', 'branch_id')) {
            Schema::table('leads', function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('lead_status_id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('campaigns') && ! Schema::hasColumn('campaigns', 'branch_id')) {
            Schema::table('campaigns', function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('lead_followups') && ! Schema::hasColumn('lead_followups', 'branch_id')) {
            Schema::table('lead_followups', function (Blueprint $table): void {
                $table->foreignId('branch_id')
                    ->nullable()
                    ->after('lead_id')
                    ->constrained('branches')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('lead_followups', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('campaigns', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('branch_id');
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('branch_id');
        });
    }
};
