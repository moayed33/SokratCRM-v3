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
        if (! Schema::hasTable('donations')) {
            Schema::create('donations', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('lead_id')->constrained('leads')->cascadeOnDelete();
                $table->foreignId('lead_followup_id')->nullable()->unique()->constrained('lead_followups')->nullOnDelete();
                $table->foreignId('donation_type_id')->nullable()->constrained('donation_types')->nullOnDelete();
                $table->string('donation_type', 100);
                $table->decimal('amount', 14, 2);
                $table->string('cycle', 50);
                $table->string('receipt_path')->nullable();
                $table->string('receipt_original_name')->nullable();
                $table->dateTime('donated_at')->index();
                $table->foreignId('recorded_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->index(['lead_id', 'donated_at']);
                $table->index(['recorded_by_user_id', 'donated_at']);
            });
        }
        if (Schema::hasTable('leads') && Schema::hasColumn('leads', 'donation_value')) {
            DB::table('leads')
            ->whereNotNull('donation_value')
            ->where('donation_value', '>', 0)
            ->orderBy('id')
            ->eachById(function (object $lead): void {
                $cycle = match (trim((string) ($lead->donation_cycle ?? ''))) {
                    'monthly', 'شهري' => 'monthly',
                    'quarterly', 'ربع سنوي' => 'quarterly',
                    'semi_annual', 'نصف سنوي' => 'semi_annual',
                    'annual', 'سنوي' => 'annual',
                    default => 'one_time',
                };

                DB::table('donations')->insert([
                        'lead_id' => $lead->id,
                        'lead_followup_id' => null,
                        'donation_type_id' => $lead->donation_type_id,
                    'donation_type' => trim((string) ($lead->donation_type ?? '')) ?: 'تبرع غير مصنف',
                    'amount' => $lead->donation_value,
                    'cycle' => $cycle,
                        'receipt_path' => null,
                        'receipt_original_name' => null,
                    'donated_at' => $lead->contact_date ?? $lead->updated_at ?? $lead->created_at ?? now(),
                        'recorded_by_user_id' => $lead->responding_user_id ?? $lead->created_by_user_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('donations');
    }
};
