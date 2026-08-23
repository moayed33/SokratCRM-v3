<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('locale', 5)->default((string) config('app.locale', 'ar'))->after('voip_extension');
            $table->string('timezone', 64)->default((string) config('app.timezone', 'UTC'))->after('locale');
            $table->string('mobile_phone', 20)->nullable()->after('timezone');
            $table->timestamp('whatsapp_opt_in_at')->nullable()->after('mobile_phone');
        });

        Schema::create('notification_preferences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->unique()
                ->constrained()->cascadeOnDelete();
            $table->boolean('in_app_enabled')->default(true);
            $table->boolean('mail_enabled')->default(false);
            $table->boolean('push_enabled')->default(false);
            $table->boolean('sms_enabled')->default(false);
            $table->boolean('whatsapp_enabled')->default(false);
            $table->time('quiet_hours_start')->nullable();
            $table->time('quiet_hours_end')->nullable();
            $table->string('minimum_external_priority', 20)->default('important');
            $table->boolean('daily_email_digest')->default(false);
            $table->dateTime('last_digest_at')->nullable();
            $table->timestamps();
        });

        Schema::create('push_subscriptions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()->cascadeOnDelete();
            $table->char('endpoint_hash', 64)->unique();
            $table->text('endpoint');
            $table->string('public_key', 255);
            $table->string('auth_token', 255);
            $table->string('content_encoding', 30)->default('aesgcm');
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('push_subscriptions');
        Schema::dropIfExists('notification_preferences');

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'locale',
                'timezone',
                'mobile_phone',
                'whatsapp_opt_in_at',
            ]);
        });
    }
};
