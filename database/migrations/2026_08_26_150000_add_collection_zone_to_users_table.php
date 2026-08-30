<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && ! Schema::hasColumn('users', 'collection_zone')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->string('collection_zone', 255)->nullable()->after('mobile_phone');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'collection_zone')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->dropColumn('collection_zone');
            });
        }
    }
};
