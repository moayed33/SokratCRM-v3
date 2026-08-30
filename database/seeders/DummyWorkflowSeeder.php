<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\CollectionActivity;
use App\Models\CollectionCase;
use App\Models\LeadStageFieldValue;
use App\Models\PushSubscription;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Fills the CRM tables that are empty on a fresh install so the whole
 * workflow (collections, stage fields, web-push) can be exercised in demo.
 *
 * Idempotent: running it twice will not duplicate rows.
 */
class DummyWorkflowSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedCollectionCases();
            $this->seedLeadStageFieldValues();
            $this->seedPushSubscriptions();
        });
    }

    private function seedCollectionCases(): void
    {
        if (CollectionCase::query()->exists()) {
            return;
        }

        $branchId = (int) (DB::table('branches')->where('is_active', true)->orderBy('id')->value('id') ?? 0);
        $donationTypes = DB::table('donation_types')->orderBy('id')->get(['id', 'name_ar']);
        $collectors = User::query()->whereIn('username', ['admin', 'demo.agent1', 'demo.agent2', 'demo.collector1'])
            ->orWhere('username', 'like', 'demo.%')
            ->orderBy('id')
            ->limit(8)
            ->get(['id']);
        $managerId = (int) (User::query()->where('username', 'admin')->value('id')
            ?? DB::table('users')->orderBy('id')->value('id')
            ?? 0);

        if ($collectors->isEmpty()) {
            $collectors = DB::table('users')->orderBy('id')->limit(4)->get(['id']);
        }

        // Leads with an existing follow-up are preferred so cases look organic.
        $leadRows = DB::table('leads as l')
            ->leftJoin('lead_followups as f', 'f.lead_id', '=', 'l.id')
            ->orderBy('l.id')
            ->groupBy('l.id', 'l.name', 'governorate', 'address')
            ->selectRaw('l.id, l.name, l.governorate, l.address, min(f.id) as followup_id')
            ->limit(16)
            ->get();

        if ($leadRows->isEmpty()) {
            return;
        }

        $statuses = [
            CollectionCase::STATUS_PENDING,
            CollectionCase::STATUS_ASSIGNED,
            CollectionCase::STATUS_SCHEDULED,
            CollectionCase::STATUS_COLLECTED,
            CollectionCase::STATUS_FAILED,
            CollectionCase::STATUS_CANCELLED,
        ];

        $cycles = ['monthly', 'quarterly', 'semi_annual', 'annual'];
        $notes = [
            'العميل طلب التواصل مساءً بعد العشاء.',
            'تم تحديد الموعد مسبقاً مع العميلة، يرجى الالتزام بالوقت.',
            'المبلغ متوقع نقداً، ويُفضل إحضار الإيصال الورقي.',
            'العميل يفضل التحصيل من مقر العمل في ساعات الدواء.',
            'بحاجة لتأكيد العنوان قبل الخروج للتحصيل.',
        ];
        $activityNotes = [
            'أنشئت الحالة تلقائياً بعد تسجيل المتابعة.',
            'تم إسناد الحالة إلى المحصل المختص.',
            'تم جدولة زيارة التحصيل حسب اتفاق العميل.',
            'تم استلام المبلغ وإصدار الإيصال بنجاح.',
            'لم يتمكن المحصل من الوصول للعميل، يُعاد الجدولة.',
        ];

        $createdCases = [];

        foreach ($leadRows->values() as $index => $lead) {
            $status = $statuses[$index % count($statuses)];
            $cycle = $cycles[$index % count($cycles)];
            $donationType = $donationTypes[$index % max($donationTypes->count(), 1)] ?? null;
            $collector = $collectors[$index % $collectors->count()];
            $isClosed = in_array($status, [CollectionCase::STATUS_COLLECTED, CollectionCase::STATUS_CANCELLED], true);
            $dueAt = Carbon::now()->addDays(($index % 9) - 3)->setTime(10 + ($index % 7), 30);
            $createdAt = Carbon::now()->subDays(6 + $index);

            $case = CollectionCase::query()->create([
                'lead_id' => (int) $lead->id,
                'source_followup_id' => $lead->followup_id !== null
                    && ! CollectionCase::query()->where('source_followup_id', $lead->followup_id)->exists()
                    ? (int) $lead->followup_id
                    : null,
                'branch_id' => $branchId ?: null,
                'donation_type_id' => $donationType?->id,
                'donation_type' => $donationType?->name_ar ?? 'عام / أخرى',
                'expected_amount' => [250, 500, 750, 1000, 1500, 2500][$index % 6] + ($index % 3) * 50,
                'cycle' => $cycle,
                'status' => $status,
                'due_at' => $dueAt,
                'collection_address' => trim((string) ($lead->address ?: '')) !== ''
                    ? (string) $lead->address
                    : 'شارع الجمهورية - '.(string) ($lead->governorate ?: 'القاهرة'),
                'notes' => $notes[$index % count($notes)],
                'assigned_collector_user_id' => $status === CollectionCase::STATUS_PENDING ? null : (int) $collector->id,
                'created_by_user_id' => $managerId ?: null,
                'assigned_by_user_id' => $status === CollectionCase::STATUS_PENDING ? null : ($managerId ?: null),
                'completed_by_user_id' => $isClosed ? $managerId ?: null : null,
                'completed_at' => $isClosed ? $dueAt->copy()->addHours(2) : null,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            $createdCases[] = [$case, $index];
        }

        $this->seedCollectionActivities($createdCases, $activityNotes);
    }

    /**
     * @param  array<int, array{0: CollectionCase, 1: int}>  $cases
     */
    private function seedCollectionActivities(array $cases, array $notes): void
    {
        foreach ($cases as [$case, $index]) {
            $actorId = $case->created_by_user_id;
            $createdAt = $case->created_at ?? Carbon::now();

            CollectionActivity::query()->create([
                'collection_case_id' => $case->id,
                'actor_user_id' => $actorId,
                'action' => 'created',
                'from_status' => null,
                'to_status' => CollectionCase::STATUS_PENDING,
                'notes' => $notes[0],
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            if ($case->status === CollectionCase::STATUS_PENDING) {
                continue;
            }

            CollectionActivity::query()->create([
                'collection_case_id' => $case->id,
                'actor_user_id' => $case->assigned_by_user_id,
                'action' => 'assigned',
                'from_status' => CollectionCase::STATUS_PENDING,
                'to_status' => CollectionCase::STATUS_ASSIGNED,
                'new_collector_user_id' => $case->assigned_collector_user_id,
                'notes' => $notes[1],
                'created_at' => $createdAt->copy()->addHours(3),
                'updated_at' => $createdAt->copy()->addHours(3),
            ]);

            if (in_array($case->status, [CollectionCase::STATUS_SCHEDULED, CollectionCase::STATUS_COLLECTED, CollectionCase::STATUS_FAILED], true)) {
                CollectionActivity::query()->create([
                    'collection_case_id' => $case->id,
                    'actor_user_id' => $case->assigned_collector_user_id,
                    'action' => 'scheduled',
                    'from_status' => CollectionCase::STATUS_ASSIGNED,
                    'to_status' => CollectionCase::STATUS_SCHEDULED,
                    'new_due_at' => $case->due_at,
                    'notes' => $notes[2],
                    'created_at' => $createdAt->copy()->addDay(),
                    'updated_at' => $createdAt->copy()->addDay(),
                ]);
            }

            if (in_array($case->status, [CollectionCase::STATUS_COLLECTED, CollectionCase::STATUS_FAILED, CollectionCase::STATUS_CANCELLED], true)) {
                CollectionActivity::query()->create([
                    'collection_case_id' => $case->id,
                    'actor_user_id' => $case->completed_by_user_id ?? $case->assigned_collector_user_id,
                    'action' => $case->status,
                    'from_status' => CollectionCase::STATUS_SCHEDULED,
                    'to_status' => $case->status,
                    'notes' => $case->status === CollectionCase::STATUS_COLLECTED ? $notes[3] : $notes[4],
                    'created_at' => $case->completed_at ?? $createdAt->copy()->addDays(2),
                    'updated_at' => $case->completed_at ?? $createdAt->copy()->addDays(2),
                ]);
            }
        }
    }

    private function seedLeadStageFieldValues(): void
    {
        if (LeadStageFieldValue::query()->exists()) {
            return;
        }

        $stageFields = DB::table('pipeline_stage_fields')
            ->whereNull('deleted_at')
            ->orderBy('pipeline_stage_id')
            ->orderBy('position')
            ->get();

        $creatorId = (int) (DB::table('users')->orderBy('id')->value('id') ?? 0);

        foreach ($stageFields as $field) {
            // Histories whose status belongs to this field's stage.
            $histories = DB::table('lead_status_histories as h')
                ->join('lead_statuses as s', 's.id', '=', 'h.to_status_id')
                ->where('s.pipeline_stage_id', $field->pipeline_stage_id)
                ->orderBy('h.id')
                ->limit(6)
                ->get(['h.id', 'h.lead_id']);

            foreach ($histories as $historyIndex => $history) {
                $exists = LeadStageFieldValue::query()
                    ->where('lead_id', $history->lead_id)
                    ->where('pipeline_stage_id', $field->pipeline_stage_id)
                    ->where('field_key', $field->key)
                    ->exists();

                if ($exists) {
                    continue;
                }

                LeadStageFieldValue::query()->create([
                    'lead_id' => (int) $history->lead_id,
                    'pipeline_stage_id' => (int) $field->pipeline_stage_id,
                    'pipeline_stage_field_id' => (int) $field->id,
                    'lead_status_history_id' => (int) $history->id,
                    'field_key' => (string) $field->key,
                    'field_type' => (string) $field->type,
                    'value' => $this->sampleValueFor((string) $field->type, (string) $field->key, $historyIndex),
                    'created_by_user_id' => $creatorId ?: null,
                    'created_at' => Carbon::now()->subDays($historyIndex + 1),
                    'updated_at' => Carbon::now()->subDays($historyIndex + 1),
                ]);
            }
        }
    }

    private function sampleValueFor(string $type, string $key, int $index): ?string
    {
        return match ($type) {
            'datetime', 'date' => Carbon::now()->addDays($index + 1)->setTime(11 + $index % 6, 0)->format('Y-m-d H:i:s'),
            'select' => $key === 'reason' ? 'غير متاح حالياً' : 'test',
            'textarea' => 'تم الاتصال بالعميل وتسجيل الملاحظات الخاصة بهذه المرحلة رقم '.($index + 1).'.',
            default => 'قيمة تجريبية '.($index + 1),
        };
    }

    private function seedPushSubscriptions(): void
    {
        if (PushSubscription::query()->exists()) {
            return;
        }

        $users = DB::table('users')->orderBy('id')->limit(3)->get(['id']);
        $agents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/126.0 Safari/537.36',
            'Mozilla/5.0 (Linux; Android 14; Pixel 8) Chrome/125.0 Mobile Safari/537.36',
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 14_5) Safari/17.5',
        ];

        foreach ($users as $index => $user) {
            $endpoint = sprintf(
                'https://fcm.googleapis.com/fcm/send/demo-subscription-%s-%d',
                bin2hex(random_bytes(8)),
                $index,
            );

            PushSubscription::query()->firstOrCreate(
                ['endpoint_hash' => hash('sha256', $endpoint)],
                [
                    'user_id' => (int) $user->id,
                    'endpoint' => $endpoint,
                    'public_key' => rtrim(strtr(base64_encode(random_bytes(65)), '+/', '-_'), '='),
                    'auth_token' => rtrim(strtr(base64_encode(random_bytes(16)), '+/', '-_'), '='),
                    'content_encoding' => 'aesgcm',
                    'user_agent' => $agents[$index % count($agents)],
                ],
            );
        }
    }
}
