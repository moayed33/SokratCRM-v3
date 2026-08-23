<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table): void {
            $table->foreignId('assigned_user_id')
                ->nullable()
                ->after('assigned_employee')
                ->constrained('users')
                ->nullOnDelete();
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('lead_followups', function (Blueprint $table): void {
            $table->foreignId('user_id')
                ->nullable()
                ->after('employee_name')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('lead_status_histories', function (Blueprint $table): void {
            $table->foreignId('changed_by_user_id')
                ->nullable()
                ->after('changed_by')
                ->constrained('users')
                ->nullOnDelete();
        });

        Schema::table('quotations', function (Blueprint $table): void {
            $table->foreignId('created_by_user_id')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('created_by_user_id');
        });

        Schema::table('lead_status_histories', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('changed_by_user_id');
        });

        Schema::table('lead_followups', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('user_id');
        });

        Schema::table('leads', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('assigned_user_id');
            $table->dropConstrainedForeignId('created_by_user_id');
        });
    }
};
