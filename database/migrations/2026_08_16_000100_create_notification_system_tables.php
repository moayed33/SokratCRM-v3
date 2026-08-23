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
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });

        Schema::create('notification_rules', function (Blueprint $table): void {
            $table->id();
            $table->string('name', 150);
            $table->string('event_key', 80)->index();
            $table->boolean('enabled')->default(true)->index();
            $table->integer('trigger_offset_minutes')->default(15);
            $table->unsignedInteger('escalation_after_minutes')->nullable();
            $table->string('priority', 20)->default('important');
            $table->json('conditions')->nullable();
            $table->foreignId('created_by_user_id')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('notification_rule_channels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_rule_id')
                ->constrained()->cascadeOnDelete();
            $table->string('channel', 20);
            $table->timestamps();
            $table->unique(['notification_rule_id', 'channel']);
        });

        Schema::create('notification_rule_recipients', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_rule_id')
                ->constrained()->cascadeOnDelete();
            $table->string('recipient_type', 30);
            $table->unsignedBigInteger('recipient_id')->default(0);
            $table->timestamps();
            $table->index(['recipient_type', 'recipient_id']);
            $table->unique([
                'notification_rule_id',
                'recipient_type',
                'recipient_id',
            ], 'notification_rule_recipient_unique');
        });

        Schema::create('notification_occurrences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_rule_id')
                ->constrained()->cascadeOnDelete();
            $table->string('event_key', 80)->index();
            $table->string('source_kind', 30);
            $table->unsignedBigInteger('source_id');
            $table->foreignId('recipient_user_id')
                ->constrained('users')->cascadeOnDelete();
            $table->dateTime('source_due_at')->nullable();
            $table->dateTime('trigger_at')->index();
            $table->string('priority', 20)->default('important');
            $table->string('status', 20)->default('pending')->index();
            $table->json('payload');
            $table->uuid('notification_id')->nullable()->index();
            $table->dateTime('dispatched_at')->nullable();
            $table->dateTime('canceled_at')->nullable();
            $table->dateTime('dismissed_at')->nullable();
            $table->dateTime('snoozed_until')->nullable();
            $table->timestamps();
            $table->index(['source_kind', 'source_id']);
            $table->unique([
                'notification_rule_id',
                'source_kind',
                'source_id',
                'recipient_user_id',
                'trigger_at',
            ], 'notification_occurrence_dedupe');
        });

        Schema::create('notification_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('notification_occurrence_id')
                ->constrained()->cascadeOnDelete();
            $table->string('channel', 20);
            $table->string('destination', 500)->nullable();
            $table->string('status', 20)->default('queued')->index();
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->string('provider_message_id', 255)->nullable()->index();
            $table->text('last_error')->nullable();
            $table->dateTime('scheduled_at')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('delivered_at')->nullable();
            $table->timestamps();
            $table->unique([
                'notification_occurrence_id',
                'channel',
            ], 'notification_delivery_channel_unique');
        });

        $now = now();
        $defaults = [
            ['name' => 'Follow-up due soon', 'event_key' => 'followup.due', 'offset' => 15, 'escalation' => null, 'priority' => 'important', 'recipient' => 'assigned_user', 'channels' => ['database', 'push', 'mail', 'sms', 'whatsapp']],
            ['name' => 'Follow-up overdue', 'event_key' => 'followup.overdue', 'offset' => 0, 'escalation' => 30, 'priority' => 'urgent', 'recipient' => 'assigned_user', 'channels' => ['database', 'push', 'mail', 'sms', 'whatsapp']],
            ['name' => 'Follow-up rescheduled', 'event_key' => 'followup.rescheduled', 'offset' => 0, 'escalation' => null, 'priority' => 'normal', 'recipient' => 'assigned_user', 'channels' => ['database', 'push', 'mail']],
            ['name' => 'Lead reassigned', 'event_key' => 'followup.reassigned', 'offset' => 0, 'escalation' => null, 'priority' => 'important', 'recipient' => 'assigned_user', 'channels' => ['database', 'push', 'mail']],
            ['name' => 'Calendar event due', 'event_key' => 'calendar.due', 'offset' => 15, 'escalation' => null, 'priority' => 'important', 'recipient' => 'event_owner', 'channels' => ['database', 'push', 'mail', 'sms', 'whatsapp']],
            ['name' => 'Calendar event updated', 'event_key' => 'calendar.updated', 'offset' => 0, 'escalation' => null, 'priority' => 'normal', 'recipient' => 'event_owner', 'channels' => ['database', 'push', 'mail']],
            ['name' => 'Calendar event canceled', 'event_key' => 'calendar.canceled', 'offset' => 0, 'escalation' => null, 'priority' => 'important', 'recipient' => 'event_owner', 'channels' => ['database', 'push', 'mail']],
            ['name' => 'Notification channel test', 'event_key' => 'system.test', 'offset' => 0, 'escalation' => null, 'priority' => 'important', 'recipient' => 'explicit_user', 'channels' => ['database', 'push', 'mail', 'sms', 'whatsapp']],
        ];

        foreach ($defaults as $default) {
            $ruleId = DB::table('notification_rules')->insertGetId([
                'name' => $default['name'],
                'event_key' => $default['event_key'],
                'enabled' => true,
                'trigger_offset_minutes' => $default['offset'],
                'escalation_after_minutes' => $default['escalation'],
                'priority' => $default['priority'],
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('notification_rule_recipients')->insert([
                'notification_rule_id' => $ruleId,
                'recipient_type' => $default['recipient'],
                'recipient_id' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('notification_rule_channels')->insert(array_map(
                static fn (string $channel): array => [
                    'notification_rule_id' => $ruleId,
                    'channel' => $channel,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
                $default['channels'],
            ));
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_deliveries');
        Schema::dropIfExists('notification_occurrences');
        Schema::dropIfExists('notification_rule_recipients');
        Schema::dropIfExists('notification_rule_channels');
        Schema::dropIfExists('notification_rules');
        Schema::dropIfExists('notifications');
    }
};
