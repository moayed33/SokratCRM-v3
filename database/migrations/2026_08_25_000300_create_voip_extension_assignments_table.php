<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('voip_extension_assignments')) {
            Schema::create('voip_extension_assignments', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('extension', 50)->index();
                $table->timestamp('assigned_from');
                $table->timestamp('assigned_until')->nullable()->index();
                $table->timestamps();

                $table->index(['extension', 'assigned_from', 'assigned_until'], 'voip_extension_assignment_lookup');
            });

            DB::table('users')
                ->whereNotNull('voip_extension')
                ->where('voip_extension', '!=', '')
                ->orderBy('id')
                ->each(function (object $user): void {
                    DB::table('voip_extension_assignments')->insert([
                        'user_id' => $user->id,
                        'extension' => trim((string) $user->voip_extension),
                        'assigned_from' => $user->created_at ?? now(),
                        'assigned_until' => null,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropUnique('users_voip_extension_unique');
        });

        Schema::dropIfExists('voip_extension_assignments');
    }
};
