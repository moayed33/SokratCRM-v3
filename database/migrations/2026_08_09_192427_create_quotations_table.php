<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'quotations',
            function (
                Blueprint $table
            ): void {
                $table->id();

                $table
                    ->string(
                        'quotation_no',
                        100
                    )
                    ->index();

                $table
                    ->string(
                        'client_name'
                    )
                    ->index();

                $table
                    ->string(
                        'location'
                    )
                    ->nullable();

                $table
                    ->string(
                        'prepared_by'
                    )
                    ->nullable();

                $table
                    ->date(
                        'quote_date'
                    )
                    ->nullable()
                    ->index();

                $table
                    ->string(
                        'system_title'
                    )
                    ->nullable();

                $table
                    ->decimal(
                        'grand_total',
                        15,
                        2
                    )
                    ->default(0);

                $table->longText(
                    'payload'
                );

                $table
                    ->string(
                        'created_by'
                    )
                    ->nullable();

                $table->timestamps();
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'quotations'
        );
    }
};
