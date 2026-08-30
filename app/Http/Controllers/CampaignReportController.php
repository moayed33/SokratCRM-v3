<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\Lead;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\BranchContext;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampaignReportController extends Controller
{
    public function __invoke(Request $request): View
    {
        $validated = $request->validate([
            'campaign_id' => ['nullable', 'integer', 'min:1'],
            'employee_id' => ['nullable', 'integer', 'min:1'],
            'period' => ['nullable', Rule::in(['all', 'today', 'week', 'month', 'year', 'custom'])],
            'from' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_if:period,custom', 'date_format:Y-m-d', 'after_or_equal:from'],
        ]);

        $user = $request->user();
        $period = (string) ($validated['period'] ?? 'year');
        [$from, $to] = $this->resolvePeriod(
            $period,
            $validated['from'] ?? null,
            $validated['to'] ?? null,
        );

        if ($period === 'custom' && $from?->addYears(20)->lessThan($to)) {
            throw ValidationException::withMessages([
                'to' => __('crm.campaign_report_range_too_large'),
            ]);
        }

        $campaigns = $this->visibleCampaigns($user)
            ->orderByDesc('starts_at')
            ->get(['id', 'name', 'image_path', 'starts_at', 'ends_at']);

        $selectedCampaignId = isset($validated['campaign_id'])
            ? (int) $validated['campaign_id']
            : null;

        if ($selectedCampaignId !== null && ! $campaigns->contains('id', $selectedCampaignId)) {
            abort(404);
        }

        $campaignIds = $selectedCampaignId !== null
            ? [$selectedCampaignId]
            : $campaigns->pluck('id')->all();

        $employees = User::query()
            ->whereHas('campaigns', static function (Builder $query) use ($campaignIds): void {
                $query->whereKey($campaignIds);
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $selectedEmployeeId = isset($validated['employee_id'])
            ? (int) $validated['employee_id']
            : null;

        if ($selectedEmployeeId !== null && ! $employees->contains('id', $selectedEmployeeId)) {
            abort(404);
        }

        $selectedEmployee = $selectedEmployeeId !== null
            ? $employees->firstWhere('id', $selectedEmployeeId)
            : null;

        if ($selectedEmployeeId !== null) {
            $campaignIds = Campaign::query()
                ->whereKey($campaignIds)
                ->whereHas(
                    'users',
                    static fn (Builder $query): Builder => $query->whereKey($selectedEmployeeId),
                )
                ->pluck('id')
                ->all();
        }

        $campaignQuery = Campaign::query()->whereKey($campaignIds);
        $createdCampaigns = $this->withinRange(
            clone $campaignQuery,
            'created_at',
            $from,
            $to,
        )->count();
        $endedCampaigns = $this->withinRange(
            (clone $campaignQuery)->where('ends_at', '<', now()),
            'ends_at',
            $from,
            $to,
        )->count();
        $totalCampaignCost = (float) (clone $campaignQuery)->sum('cost');

        $leadQuery = Lead::query()
            ->accessibleTo($user)
            ->whereHas('campaigns', static function (Builder $query) use ($campaignIds): void {
                $query->whereKey($campaignIds);
            });

        if ($selectedEmployeeId !== null) {
            $leadQuery->where('assigned_user_id', $selectedEmployeeId);
        }

        $totalLeads = (clone $leadQuery)->count();
        $donorLeads = (clone $leadQuery)
            ->whereHas('status.stage', static fn (Builder $query) => $query->where('code', 'donor'))
            ->count();

        $recordedDonationValue = (float) Donation::query()
            ->whereIn('lead_id', (clone $leadQuery)->select('leads.id'))
            ->sum('amount');
        $legacyDonationValue = (float) ((clone $leadQuery)
            ->whereDoesntHave('donations')
            ->whereNotNull('donation_value')
            ->where('donation_value', '>', 0)
            ->sum('donation_value') ?? 0.0);

        $stageCounts = (clone $leadQuery)
            ->join('lead_statuses', 'lead_statuses.id', '=', 'leads.lead_status_id')
            ->selectRaw('lead_statuses.pipeline_stage_id, COUNT(DISTINCT leads.id) as aggregate')
            ->groupBy('lead_statuses.pipeline_stage_id')
            ->pluck('aggregate', 'lead_statuses.pipeline_stage_id');

        $stageDistribution = PipelineStage::query()
            ->orderBy('position')
            ->orderBy('id')
            ->get()
            ->filter(static fn (PipelineStage $stage) => (int) ($stageCounts[$stage->id] ?? 0) > 0
                || (bool) $stage->is_active)
            ->map(static function (PipelineStage $stage) use ($stageCounts, $totalLeads): array {
                $count = (int) ($stageCounts[$stage->id] ?? 0);

                return [
                    'code' => $stage->code,
                    'label' => $stage->localizedName(),
                    'count' => $count,
                    'percentage' => $totalLeads > 0
                        ? round(($count / $totalLeads) * 100, 1)
                        : 0.0,
                    'color' => $stage->color ?: '#64748b',
                ];
            })
            ->values();

        $metrics = [
            'created_campaigns' => $createdCampaigns,
            'ended_campaigns' => $endedCampaigns,
            'current_leads' => $totalLeads,
            'donor_leads' => $donorLeads,
            'total_donation_value' => round($recordedDonationValue + $legacyDonationValue, 2),
            'total_campaign_cost' => round($totalCampaignCost, 2),
            'lead_cost' => $totalLeads > 0
                ? round($totalCampaignCost / $totalLeads, 2)
                : 0.0,
            'conversion_rate' => $totalLeads > 0
                ? round(($donorLeads / $totalLeads) * 100, 1)
                : 0.0,
        ];

        $filters = [
            'campaign_id' => $selectedCampaignId,
            'employee_id' => $selectedEmployeeId,
            'period' => $period,
            'from' => $from?->format('Y-m-d'),
            'to' => $to?->format('Y-m-d'),
        ];

        return view('campaigns.reports', [
            'campaigns' => $campaigns,
            'employees' => $employees,
            'filters' => $filters,
            'metrics' => $metrics,
            'stageDistribution' => $stageDistribution,
            'timeline' => $selectedCampaignId === null
                ? $this->buildTimeline($campaignIds, $from, $to)
                : [],
            'selectedCampaign' => $selectedCampaignId !== null
                ? $campaigns->firstWhere('id', $selectedCampaignId)
                : null,
            'selectedEmployee' => $selectedEmployee,
        ]);
    }

    private function visibleCampaigns(User $user): Builder
    {
        $query = Campaign::query()->forBranch(BranchContext::getCurrentBranchId($user));

        if (! $user->isSuperAdmin()) {
            $query->where(function (Builder $query) use ($user): void {
                $query->whereHas(
                    'users',
                    static fn (Builder $memberQuery) => $memberQuery->whereKey($user->id),
                );

                if ($user->hasPermission(CrmPermission::CAMPAIGNS_CREATE)) {
                    $query->orWhere('created_by_user_id', $user->id);
                }
            });
        }

        return $query;
    }

    private function withinRange(
        Builder $query,
        string $column,
        ?CarbonImmutable $from,
        ?CarbonImmutable $to,
    ): Builder {
        if ($from !== null) {
            $query->where($column, '>=', $from);
        }

        if ($to !== null) {
            $query->where($column, '<=', $to);
        }

        return $query;
    }

    private function resolvePeriod(
        string $period,
        ?string $customFrom,
        ?string $customTo,
    ): array {
        $now = CarbonImmutable::now();

        return match ($period) {
            'all' => [null, null],
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'week' => [$now->startOfWeek(), $now->endOfWeek()],
            'month' => [$now->startOfMonth(), $now->endOfMonth()],
            'custom' => [
                CarbonImmutable::parse($customFrom)->startOfDay(),
                CarbonImmutable::parse($customTo)->endOfDay(),
            ],
            default => [$now->startOfYear(), $now->endOfYear()],
        };
    }

    private function buildTimeline(
        array $campaignIds,
        ?CarbonImmutable $from,
        ?CarbonImmutable $to,
    ): array {
        $query = Campaign::query()->whereKey($campaignIds);
        $start = $from;
        $end = $to;

        if ($start === null) {
            $firstCreatedAt = (clone $query)->min('created_at');
            $firstEndedAt = (clone $query)
                ->where('ends_at', '<', now())
                ->min('ends_at');
            $firstEventAt = collect([$firstCreatedAt, $firstEndedAt])
                ->filter()
                ->sort()
                ->first();
            $start = $firstEventAt
                ? CarbonImmutable::parse($firstEventAt)->startOfDay()
                : CarbonImmutable::now()->startOfYear();
        }

        $end ??= CarbonImmutable::now()->endOfDay();

        if ($start->greaterThan($end)) {
            $start = $end->startOfDay();
        }

        $days = $start->diffInDays($end);
        $interval = match (true) {
            $days <= 14 => 'day',
            $days <= 120 => 'week',
            $days <= 730 => 'month',
            default => 'year',
        };

        $timeline = [];
        $cursor = $start;

        while ($cursor->lessThanOrEqualTo($end)) {
            $bucketEnd = match ($interval) {
                'day' => $cursor->endOfDay(),
                'week' => $cursor->addDays(6)->endOfDay(),
                'month' => $cursor->endOfMonth(),
                default => $cursor->endOfYear(),
            };
            $bucketEnd = $bucketEnd->greaterThan($end) ? $end : $bucketEnd;

            $label = match ($interval) {
                'day' => $cursor->translatedFormat('d M'),
                'week' => $cursor->translatedFormat('d M').' - '.$bucketEnd->translatedFormat('d M'),
                'month' => $cursor->translatedFormat('M Y'),
                default => $cursor->format('Y'),
            };

            $timeline[] = [
                'label' => $label,
                'created' => (clone $query)
                    ->whereBetween('created_at', [$cursor, $bucketEnd])
                    ->count(),
                'ended' => (clone $query)
                    ->where('ends_at', '<', now())
                    ->whereBetween('ends_at', [$cursor, $bucketEnd])
                    ->count(),
            ];

            $cursor = $bucketEnd->addMicrosecond()->startOfDay();
        }

        return $timeline;
    }
}
