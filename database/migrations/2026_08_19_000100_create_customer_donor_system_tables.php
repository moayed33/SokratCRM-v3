<?php

declare(strict_types=1);

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Extend pipeline_stages with is_primary and icon
        if (! Schema::hasColumn('pipeline_stages', 'is_primary')) {
            Schema::table('pipeline_stages', function (Blueprint $table): void {
                $table->boolean('is_primary')->default(false)->after('position');
                $table->string('icon', 50)->nullable()->after('color');
            });
        }

        // 2. Create donation_types lookup table
        if (! Schema::hasTable('donation_types')) {
            Schema::create('donation_types', function (Blueprint $table): void {
                $table->id();
                $table->string('name_ar', 100);
                $table->string('name_en', 100)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedTinyInteger('position')->default(1);
                $table->timestamps();

                $table->index(['is_active', 'position']);
            });
        }

        // 3. Create donation_purposes lookup table
        if (! Schema::hasTable('donation_purposes')) {
            Schema::create('donation_purposes', function (Blueprint $table): void {
                $table->id();
                $table->string('name_ar', 150);
                $table->string('name_en', 150)->nullable();
                $table->boolean('is_active')->default(true);
                $table->unsignedTinyInteger('position')->default(1);
                $table->timestamps();

                $table->index(['is_active', 'position']);
            });
        }

        // 4. Extend leads table with donor fields
        if (Schema::hasTable('leads')) {
            Schema::table('leads', function (Blueprint $table): void {
                if (! Schema::hasColumn('leads', 'donation_type')) {
                    $table->string('donation_type', 100)->nullable()->after('quotation_file_path');
                }
                if (! Schema::hasColumn('leads', 'donation_type_id')) {
                    $table->foreignId('donation_type_id')
                        ->nullable()
                        ->after('donation_type')
                        ->constrained('donation_types')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('leads', 'donation_cycle')) {
                    $table->string('donation_cycle', 50)->nullable()->after('donation_type_id');
                }
                if (! Schema::hasColumn('leads', 'donation_value')) {
                    $table->decimal('donation_value', 14, 2)->nullable()->after('donation_cycle');
                }
                if (! Schema::hasColumn('leads', 'donation_purpose')) {
                    $table->string('donation_purpose', 150)->nullable()->after('donation_value');
                }
                if (! Schema::hasColumn('leads', 'donation_purpose_id')) {
                    $table->foreignId('donation_purpose_id')
                        ->nullable()
                        ->after('donation_purpose')
                        ->constrained('donation_purposes')
                        ->nullOnDelete();
                }
                if (! Schema::hasColumn('leads', 'response_details')) {
                    $table->text('response_details')->nullable()->after('donation_purpose_id');
                }
                if (! Schema::hasColumn('leads', 'contact_date')) {
                    $table->dateTime('contact_date')->nullable()->after('response_details');
                }
                if (! Schema::hasColumn('leads', 'responding_user_id')) {
                    $table->foreignId('responding_user_id')
                        ->nullable()
                        ->after('assigned_user_id')
                        ->constrained('users')
                        ->nullOnDelete();
                }
            });
        }
        // 5. Create lead_phones table
        if (! Schema::hasTable('lead_phones')) {
            Schema::create('lead_phones', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('lead_id')
                    ->constrained('leads')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->string('phone', 50)->index();
                $table->boolean('is_primary')->default(false);
                $table->string('label', 50)->nullable();
                $table->timestamps();

                $table->index(['lead_id', 'is_primary']);
            });
        }

        // 6. Create lead_related_people table
        if (! Schema::hasTable('lead_related_people')) {
            Schema::create('lead_related_people', function (Blueprint $table): void {
                $table->id();
                $table->foreignId('lead_id')
                    ->constrained('leads')
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->string('name', 150);
                $table->string('phone', 50)->nullable();
                $table->string('relationship_type', 100);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['lead_id']);
            });
        }

    }

    public function down(): void
    {
        Schema::dropIfExists('lead_related_people');
        Schema::dropIfExists('lead_phones');

        Schema::table('leads', function (Blueprint $table): void {
            if (Schema::hasColumn('leads', 'responding_user_id')) {
                $table->dropConstrainedForeignId('responding_user_id');
            }
            if (Schema::hasColumn('leads', 'donation_purpose_id')) {
                $table->dropConstrainedForeignId('donation_purpose_id');
            }
            if (Schema::hasColumn('leads', 'donation_type_id')) {
                $table->dropConstrainedForeignId('donation_type_id');
            }

            $columns = [
                'donation_type',
                'donation_cycle',
                'donation_value',
                'donation_purpose',
                'response_details',
                'contact_date',
            ];
            $toDrop = array_filter($columns, static fn ($col) => Schema::hasColumn('leads', $col));
            if ($toDrop !== []) {
                $table->dropColumn(array_values($toDrop));
            }
        });

        Schema::dropIfExists('donation_purposes');
        Schema::dropIfExists('donation_types');

        if (Schema::hasTable('pipeline_stages')) {
            Schema::table('pipeline_stages', function (Blueprint $table): void {
                if (Schema::hasColumn('pipeline_stages', 'icon')) {
                    $table->dropColumn('icon');
                }
                if (Schema::hasColumn('pipeline_stages', 'is_primary')) {
                    $table->dropColumn('is_primary');
                }
            });
        }
    }
};
