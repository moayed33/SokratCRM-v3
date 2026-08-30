<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Lead;
use App\Models\User;
use App\Models\VoipExtensionAssignment;
use App\Security\CrmPermission;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\URL;

class VoipCallAnalytics
{
    public function __construct(private readonly VoipService $voip) {}

    public function forLead(Lead $lead, array $filters = []): array
    {
        $lead->loadMissing('additionalPhones');
        $phones = collect([$lead->phone])
            ->merge($lead->additionalPhones->pluck('phone'))
            ->map(static fn ($phone): string => trim((string) $phone))
            ->filter()
            ->unique()
            ->values();

        if (! $this->voip->isConfigured() || $phones->isEmpty()) {
            return $this->buildInsights(collect(), ['phones_queried' => $phones->all()]);
        }

        $rawCalls = collect();
        foreach ($phones as $phone) {
            try {
                $result = $this->voip->getCustomerCallHistory($phone, $filters);
                $calls = $this->extractCalls($result);

                if ($calls === []) {
                    $cleanPhone = preg_replace('/[^\d+]/', '', $phone);
                    if (is_string($cleanPhone) && $cleanPhone !== '' && $cleanPhone !== $phone) {
                        $calls = $this->extractCalls($this->voip->getCustomerCallHistory($cleanPhone, $filters));
                    }
                }

                $rawCalls = $rawCalls->concat($calls);
            } catch (\Throwable $e) {
                // Graceful fallback when VoIP is unreachable
            }
        }
        $calls = $this->normalizeCalls($rawCalls->all())
            ->unique(static fn (array $call): string => (string) ($call['id'] ?? sha1(json_encode([
                $call['call_date'],
                $call['customer_number'],
                $call['agent_extension'],
                $call['duration_seconds'],
            ]))))
            ->sortByDesc('call_timestamp')
            ->values();

        return $this->buildInsights($calls, ['phones_queried' => $phones->all()]);
    }

    public function forUser(User $user, array $filters = []): array
    {
        if (empty($user->voip_extension)) {
            return $this->buildInsights(collect(), ['extension' => null]);
        }

        $raw = $this->voip->getExtensionStats(
            (string) $user->voip_extension,
            $this->extensionServerFilters($filters),
        );
        $calls = $this->normalizeCalls($raw['recent_calls'] ?? $raw['calls'] ?? [])->values();
        if (! empty($filters['direction'])) {
            $direction = $this->normalizeDirection((string) $filters['direction']);
            $calls = $calls->where('direction', $direction)->values();
        }
        if (! empty($filters['status'])) {
            $status = $this->normalizeDisposition((string) $filters['status']);
            $calls = $calls->where('disposition', $status)->values();
        }

        $insights = $this->buildInsights($calls, ['extension' => (string) $user->voip_extension]);
        $externalSummary = is_array($raw['summary'] ?? null) ? $raw['summary'] : [];
        $canUseExternalAggregates = empty($filters['status'])
            && ($filters['direction'] ?? null) !== 'internal';

        if ($canUseExternalAggregates) {
            foreach ([
                'total_calls', 'answered_calls', 'missed_calls', 'inbound_calls',
                'outbound_calls', 'total_talk_seconds', 'answer_rate_percent',
            ] as $key) {
                if (isset($externalSummary[$key]) && is_numeric($externalSummary[$key])) {
                    $insights['summary'][$key] = (float) $externalSummary[$key];
                }
            }

            if (isset($externalSummary['avg_talk_seconds']) && is_numeric($externalSummary['avg_talk_seconds'])) {
                $insights['summary']['average_talk_seconds'] = (float) $externalSummary['avg_talk_seconds'];
            }

            $breakdown = $this->normalizeBreakdown($raw['disposition_breakdown'] ?? []);
            if ($breakdown !== []) {
                $insights['charts']['dispositions'] = $breakdown;
                $insights['summary']['missed_calls'] = collect($breakdown)
                    ->only(['missed', 'no_answer', 'busy', 'failed'])
                    ->sum();
            }

            foreach (['inbound', 'outbound', 'internal'] as $direction) {
                $key = $direction.'_calls';
                if (isset($externalSummary[$key]) && is_numeric($externalSummary[$key])) {
                    $insights['charts']['directions'][$direction] = (int) $externalSummary[$key];
                }
            }

            $daily = collect(is_array($raw['daily_breakdown'] ?? null) ? $raw['daily_breakdown'] : [])
                ->filter(static fn ($row): bool => is_array($row))
                ->values();
            if ($daily->isNotEmpty()) {
                $insights['charts']['daily'] = [
                    'labels' => $daily->pluck('date')->all(),
                    'calls' => $daily->map(static fn (array $row): int => (int) ($row['total_calls'] ?? $row['total'] ?? 0))->all(),
                ];
            }
        }

        return $insights;
    }

    /** @param Collection<int, User> $users */
    public function forTeam(Collection $users, array $filters = []): array
    {
        $rows = collect();
        $calls = collect();
        $errors = [];

        foreach ($users as $user) {
            try {
                $insights = $this->forUser($user, $filters);
                $summary = $insights['summary'];
                $rows->push([
                    'user' => $user,
                    'extension' => $user->voip_extension,
                    'total_calls' => (int) ($summary['total_calls'] ?? 0),
                    'answered_calls' => (int) ($summary['answered_calls'] ?? 0),
                    'missed_calls' => (int) ($summary['missed_calls'] ?? 0),
                    'outbound_calls' => (int) ($summary['outbound_calls'] ?? 0),
                    'answer_rate_percent' => (float) ($summary['answer_rate_percent'] ?? 0),
                    'total_talk_seconds' => (int) ($summary['total_talk_seconds'] ?? 0),
                ]);
                $calls = $calls->concat($insights['calls']);
            } catch (\Throwable $exception) {
                $errors[$user->id] = $exception->getMessage();
            }
        }

        $summary = [
            'total_calls' => $rows->sum('total_calls'),
            'answered_calls' => $rows->sum('answered_calls'),
            'missed_calls' => $rows->sum('missed_calls'),
            'outbound_calls' => $rows->sum('outbound_calls'),
            'total_talk_seconds' => $rows->sum('total_talk_seconds'),
        ];
        $summary['answer_rate_percent'] = $summary['total_calls'] > 0
            ? round(($summary['answered_calls'] / $summary['total_calls']) * 100, 1)
            : 0;

        return [
            'summary' => $summary,
            'users' => $rows->sortByDesc('total_calls')->values()->all(),
            'calls' => $calls->sortByDesc('call_timestamp')->take(30)->values()->all(),
            'errors' => $errors,
            'charts' => [
                'employees' => [
                    'labels' => $rows->pluck('user.name')->all(),
                    'calls' => $rows->pluck('total_calls')->all(),
                    'answered' => $rows->pluck('answered_calls')->all(),
                    'talk_minutes' => $rows->map(static fn (array $row): float => round($row['total_talk_seconds'] / 60, 1))->all(),
                ],
            ],
        ];
    }

    /** @param list<array<string, mixed>> $rawCalls */
    private function normalizeCalls(array $rawCalls): Collection
    {
        $extensions = collect($rawCalls)
            ->pluck('agent_extension')
            ->map(static fn ($extension): string => trim((string) $extension))
            ->filter()
            ->unique();
        $assignments = VoipExtensionAssignment::query()
            ->with('user:id,name,username,voip_extension')
            ->whereIn('extension', $extensions)
            ->orderByDesc('assigned_from')
            ->get()
            ->groupBy('extension');
        $currentUsers = User::query()
            ->whereIn('voip_extension', $extensions)
            ->get(['id', 'name', 'username', 'voip_extension'])
            ->keyBy('voip_extension');
        $canPlayRecordings = request()->user()?->hasPermission(CrmPermission::VOIP_RECORDINGS) ?? false;

        return collect($rawCalls)->map(function ($raw) use ($assignments, $currentUsers, $canPlayRecordings): array {
            $raw = is_array($raw) ? $raw : [];
            $direction = $this->normalizeDirection((string) ($raw['direction'] ?? 'unknown'));
            $callDate = (string) ($raw['call_date'] ?? $raw['started_at'] ?? '');
            $callAt = $this->parseDate($callDate);
            $extension = trim((string) ($raw['agent_extension'] ?? ''));
            $crmUser = $this->resolveUser($extension, $callAt, $assignments, $currentUsers);
            $durationSeconds = max(0, (int) ($raw['duration_seconds'] ?? $raw['billsec'] ?? 0));
            $disposition = $this->normalizeDisposition((string) ($raw['disposition'] ?? 'unknown'));
            $billableSeconds = max(0, (int) ($raw['billable_seconds']
                ?? $raw['billsec']
                ?? ($disposition === 'answered' ? $durationSeconds : 0)));
            $mediaId = $raw['media_id'] ?? data_get($raw, 'recording.media_id');
            $recordingExplicitlyUnavailable = array_key_exists('has_recording', $raw) && ! $raw['has_recording'];
            $hasRecording = ! empty($mediaId)
                && ! $recordingExplicitlyUnavailable
                && data_get($raw, 'recording.available', true) !== false;
            $agentName = $crmUser?->name ?: (string) ($raw['agent_name'] ?? '');
            $agentInfo = $extension !== ''
                ? ($agentName !== '' ? "{$extension} ({$agentName})" : $extension)
                : ($agentName !== '' ? $agentName : '—');
            $customerNumber = (string) ($raw['customer_number'] ?? '');

            return [
                'id' => $raw['id'] ?? null,
                'call_date' => $callAt?->format('Y-m-d H:i:s') ?? ($callDate !== '' ? $callDate : '—'),
                'call_timestamp' => $callAt?->timestamp ?? 0,
                'direction' => $direction,
                'src' => $direction === 'inbound' ? ($customerNumber ?: '—') : $agentInfo,
                'dst' => $direction === 'outbound' ? ($customerNumber ?: '—') : $agentInfo,
                'customer_number' => $customerNumber ?: null,
                'agent_extension' => $extension ?: null,
                'external_agent_name' => $raw['agent_name'] ?? null,
                'crm_user' => $crmUser ? [
                    'id' => $crmUser->id,
                    'name' => $crmUser->name,
                    'username' => $crmUser->username,
                ] : null,
                'agent_name' => $agentName ?: null,
                'duration_seconds' => $durationSeconds,
                'billable_seconds' => $billableSeconds,
                'duration_formatted' => $this->formatDuration($durationSeconds),
                'disposition' => $disposition,
                'has_recording' => $hasRecording,
                'recording_url' => $hasRecording && $canPlayRecordings
                    ? URL::temporarySignedRoute(
                        'v2.voip.recordings.stream',
                        now()->addHours(2),
                        ['mediaId' => (string) $mediaId],
                    )
                    : null,
            ];
        });
    }

    private function buildInsights(Collection $calls, array $context = []): array
    {
        $summary = $this->summarize($calls);
        $daily = $calls
            ->filter(static fn (array $call): bool => $call['call_timestamp'] > 0)
            ->groupBy(static fn (array $call): string => date('Y-m-d', $call['call_timestamp']))
            ->sortKeys();

        return [
            ...$context,
            'summary' => $summary,
            'calls' => $calls->all(),
            'charts' => [
                'directions' => [
                    'inbound' => $calls->where('direction', 'inbound')->count(),
                    'outbound' => $calls->where('direction', 'outbound')->count(),
                    'internal' => $calls->where('direction', 'internal')->count(),
                ],
                'dispositions' => $calls->countBy('disposition')->all(),
                'daily' => [
                    'labels' => $daily->keys()->all(),
                    'calls' => $daily->map->count()->values()->all(),
                ],
            ],
        ];
    }

    private function summarize(Collection $calls): array
    {
        $total = $calls->count();
        $answered = $calls->where('disposition', 'answered')->count();
        $talkSeconds = $calls->sum('billable_seconds');

        return [
            'total_calls' => $total,
            'answered_calls' => $answered,
            'missed_calls' => $calls->whereIn('disposition', ['missed', 'no_answer', 'busy', 'failed'])->count(),
            'inbound_calls' => $calls->where('direction', 'inbound')->count(),
            'outbound_calls' => $calls->where('direction', 'outbound')->count(),
            'total_talk_seconds' => $talkSeconds,
            'average_talk_seconds' => $answered > 0 ? (int) round($talkSeconds / $answered) : 0,
            'answer_rate_percent' => $total > 0 ? round(($answered / $total) * 100, 1) : 0,
        ];
    }

    private function resolveUser(string $extension, ?CarbonImmutable $callAt, Collection $assignments, Collection $currentUsers): ?User
    {
        if ($extension === '') {
            return null;
        }

        if ($callAt !== null) {
            $assignment = $assignments->get($extension, collect())->first(
                static fn (VoipExtensionAssignment $assignment): bool => $assignment->assigned_from <= $callAt
                    && ($assignment->assigned_until === null || $assignment->assigned_until > $callAt),
            );

            if ($assignment?->user) {
                return $assignment->user;
            }
        }

        return $currentUsers->get($extension);
    }

    private function extractCalls(array $response): array
    {
        $calls = $response['calls'] ?? $response['data'] ?? (array_is_list($response) ? $response : []);

        return is_array($calls) ? array_values(array_filter($calls, 'is_array')) : [];
    }

    private function normalizeDirection(string $direction): string
    {
        return match (mb_strtolower(trim($direction))) {
            'incoming', 'inbound', 'in' => 'inbound',
            'outgoing', 'outbound', 'out' => 'outbound',
            'internal', 'local' => 'internal',
            default => 'unknown',
        };
    }

    private function normalizeDisposition(string $disposition): string
    {
        return match (mb_strtoupper(str_replace(['-', ' '], '_', trim($disposition)))) {
            'ANSWERED', 'CONNECTED', 'COMPLETED' => 'answered',
            'MISSED' => 'missed',
            'NO_ANSWER', 'NOANSWER' => 'no_answer',
            'BUSY' => 'busy',
            'FAILED', 'CONGESTION' => 'failed',
            default => mb_strtolower(trim($disposition)) ?: 'unknown',
        };
    }

    private function normalizeBreakdown(mixed $breakdown): array
    {
        if (! is_array($breakdown)) {
            return [];
        }

        $normalized = [];
        foreach ($breakdown as $disposition => $count) {
            if (! is_numeric($count)) {
                continue;
            }

            $key = $this->normalizeDisposition((string) $disposition);
            $normalized[$key] = ($normalized[$key] ?? 0) + (int) $count;
        }

        return $normalized;
    }

    private function extensionServerFilters(array $filters): array
    {
        $serverFilters = [];
        $timezone = (string) config('voip.timezone', 'Africa/Cairo');
        if (! empty($filters['from_date'])) {
            $serverFilters['from'] = CarbonImmutable::parse((string) $filters['from_date'], $timezone)
                ->startOfDay()
                ->toIso8601String();
        }
        if (! empty($filters['to_date'])) {
            $serverFilters['to'] = CarbonImmutable::parse((string) $filters['to_date'], $timezone)
                ->endOfDay()
                ->toIso8601String();
        }

        $direction = $this->normalizeDirection((string) ($filters['direction'] ?? ''));
        if (in_array($direction, ['inbound', 'outbound'], true)) {
            $serverFilters['direction'] = $direction;
        }

        return $serverFilters;
    }

    private function parseDate(string $date): ?CarbonImmutable
    {
        if (trim($date) === '') {
            return null;
        }

        try {
            return CarbonImmutable::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }

    private function formatDuration(int $seconds): string
    {
        $minutes = intdiv($seconds, 60);
        $remaining = $seconds % 60;

        return $minutes > 0 ? sprintf('%d:%02d', $minutes, $remaining) : $remaining.'s';
    }
}
