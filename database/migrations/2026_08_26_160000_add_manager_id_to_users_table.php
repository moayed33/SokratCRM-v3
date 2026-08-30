<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'manager_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->foreignId('manager_id')
                    ->nullable()
                    ->after('branch_id')
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'manager_id')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('manager_id');
            });
        }
    }
};
