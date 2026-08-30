<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('instant_donation_methods') && ! Schema::hasColumn('instant_donation_methods', 'accounts')) {
            Schema::table('instant_donation_methods', function (Blueprint $table): void {
                $table->json('accounts')->nullable()->after('is_active');
            });
        }

        if (Schema::hasTable('donations') && ! Schema::hasColumn('donations', 'instant_donation_account')) {
            Schema::table('donations', function (Blueprint $table): void {
                $table->string('instant_donation_account', 255)->nullable()->after('instant_donation_method_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('donations') && Schema::hasColumn('donations', 'instant_donation_account')) {
            Schema::table('donations', function (Blueprint $table): void {
                $table->dropColumn('instant_donation_account');
            });
        }

        if (Schema::hasTable('instant_donation_methods') && Schema::hasColumn('instant_donation_methods', 'accounts')) {
            Schema::table('instant_donation_methods', function (Blueprint $table): void {
                $table->dropColumn('accounts');
            });
        }
    }
};
