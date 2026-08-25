<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the Module Builder (custom modules) feature: generic entity
 * registry, record storage, its permissions, and any module-scoped
 * field definitions.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('custom_entity_records');
        Schema::dropIfExists('custom_entities');

        $permissionIds = DB::table('permissions')
            ->whereIn('code', ['records.view', 'records.manage'])
            ->pluck('id');

        if ($permissionIds->isNotEmpty()) {
            DB::table('group_permission')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        DB::table('lead_form_fields')
            ->where('entity', '!=', 'leads')
            ->delete();
    }

    public function down(): void
    {
        Schema::create('custom_entities', function (Blueprint $table): void {
            $table->id();
            $table->string('key', 50)->unique();
            $table->string('name_ar', 150);
            $table->string('name_en', 150)->nullable();
            $table->string('icon', 50)->default('bi-grid');
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('position')->default(0);
            $table->timestamps();
        });

        Schema::create('custom_entity_records', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('entity_id')->index();
            $table->string('title', 255)->nullable();
            $table->json('data')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('assigned_user_id')->nullable()->index();
            $table->unsignedBigInteger('created_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('entity_id')
                ->references('id')
                ->on('custom_entities')
                ->cascadeOnDelete();
        });
    }
};
