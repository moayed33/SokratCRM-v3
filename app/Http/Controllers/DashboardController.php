<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\VoipService;
use App\Support\BranchContext;
use App\Support\CrmDatabaseGuard;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const DONATION_CYCLES = [
        'one_time' => 'مرة واحدة',
        'monthly' => 'شهري',
        'quarterly' => 'ربع سنوي',
        'semi_annual' => 'نصف سنوي',
        'annual' => 'سنوي',
    ];

    private const COMMUNICATION_LABELS = [
        'call' => 'اتصال',
        'whatsapp' => 'واتساب',
        'email' => 'بريد إلكتروني',
        'meeting' => 'مقابلة',
        'other' => 'أخرى',
    ];

    public function index(Request $request)
    {
        $this->assertCrmDatabase();
        $user = $request->user();

        $filters = $this->resolveFilters($request);

        $branchId = BranchContext::getCurrentBranchId($user);

        // 1. Dynamic active pipeline stages from database
        $pipelineStages = PipelineStage::query()
            ->with(['statuses'])
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $statuses = LeadStatus::query()
            ->with(['stage'])
            ->whereHas('stage', static fn (Builder $q) => $q->where('is_active', true))
            ->orderBy('position')
            ->get();

        // 2. Base query for accessible leads respecting active filters
        $leadBase = Lead::query()->accessibleTo($user);
        $this->applyLeadFilters($leadBase, $filters);

        $totalCustomersCount = (clone $leadBase)->count();
        $newCustomersCount = 0;
        $donorCustomersCount = 0;

        // 2b. Compute lead counts and percentages dynamically for each active PipelineStage
        $statusCounts = (clone $leadBase)
            ->whereNotNull('lead_status_id')
            ->groupBy('lead_status_id')
            ->selectRaw('lead_status_id, count(*) as total')
            ->pluck('total', 'lead_status_id')
            ->all();

        foreach ($pipelineStages as $stage) {
            $stageLeadsCount = 0;
            foreach ($stage->statuses as $st) {
                $stageLeadsCount += ($statusCounts[$st->id] ?? 0);
            }
            $stage->leads_count = $stageLeadsCount;
            $stage->percentage = $totalCustomersCount > 0
                ? round(($stageLeadsCount / $totalCustomersCount) * 100, 1)
                : 0.0;
        }

        $totalActiveStagesCount = $pipelineStages->count();
        $stages = $pipelineStages;
        // 3. Four Independent Canonical Status Cards
        $statusCards = [];
        foreach ($statuses as $status) {
            $statusCount = (clone $leadBase)
                ->where('lead_status_id', $status->id)
                ->count();

            $percentage = $totalCustomersCount > 0
                ? round(($statusCount / $totalCustomersCount) * 100, 1)
                : 0.0;

            if ($status->code === 'new') {
                $newCustomersCount = $statusCount;
            } elseif ($status->code === 'donor') {
                $donorCustomersCount = $statusCount;
            }

            $iconClass = match ($status->code) {
                'new' => 'bi-person-plus-fill',
                'no_answer', 'no-answer' => 'bi-telephone-x-fill',
                'not_interested', 'not-interested' => 'bi-x-circle-fill',
                'donor' => 'bi-heart-fill',
                default => 'bi-app-indicator',
            };

            $statusCards[] = [
                'id' => $status->id,
                'code' => $status->code,
                'name' => $status->localizedName(),
                'color' => (string) ($status->color ?: '#3478f6'),
                'icon' => $iconClass,
                'count' => $statusCount,
                'percentage' => $percentage,
                'stage_name' => $status->stage?->name_ar ?? '',
                'is_terminal' => (bool) $status->is_terminal,
            ];
        }

        $stageCards = $statusCards;

        // If specific stage counts were not found by code, default from position order
        if ($newCustomersCount === 0 && isset($stageCards[0])) {
            $newCustomersCount = $stageCards[0]['count'];
        }
        if ($donorCustomersCount === 0 && isset($stageCards[1])) {
            $donorCustomersCount = $stageCards[1]['count'];
        }

        // 4. KPI 1: Donor Conversion Rate = (Donors / Total Customers) * 100 (Section 7)
        $donorConversionRate = $totalCustomersCount > 0
            ? round(($donorCustomersCount / $totalCustomersCount) * 100, 1)
            : 0.0;

        // 5. KPI 2: Total Donation Value (Section 6)
        $totalDonationValue = (float) ((clone $leadBase)
            ->whereNotNull('donation_value')
            ->where('donation_value', '>', 0)
            ->sum('donation_value') ?? 0.0);

        // 6. KPI 3: Follow-up Counts (Section 6)
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $datedLeadBase = clone $leadBase;

        $followupCounts = [
            'today' => (clone $datedLeadBase)
                ->whereNotNull('next_follow_up_at')
                ->whereBetween('next_follow_up_at', [$todayStart, $todayEnd])
                ->count(),
            'overdue' => (clone $datedLeadBase)
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<', $todayStart)
                ->count(),
            'upcoming' => (clone $datedLeadBase)
                ->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '>', $todayEnd)
                ->count(),
            'no_date' => (clone $datedLeadBase)
                ->whereNull('next_follow_up_at')
                ->count(),
        ];

        // 7. Chart 1 — Customer Pipeline Stage Distribution (Donut / Pie Chart) (Section 11)
        $stageDistribution = [
            'labels' => $pipelineStages->map(fn (PipelineStage $s) => $s->localizedName())->all(),
            'data' => $pipelineStages->map(fn (PipelineStage $s) => (int) $s->leads_count)->all(),
            'colors' => $pipelineStages->map(fn (PipelineStage $s) => (string) ($s->color ?: '#3478f6'))->all(),
        ];
        // 8. Chart 2 — Customer / Donation Activity Over Time (Section 11)
        $monthsTrend = [];
        $arabicMonths = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر',
        ];

        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $mStart = (clone $monthDate)->startOfMonth();
            $mEnd = (clone $monthDate)->endOfMonth();

            $monthLabel = ($arabicMonths[$monthDate->month] ?? $monthDate->format('M')).' '.$monthDate->format('Y');

            $mNewCount = Lead::query()
                ->accessibleTo($user)
                ->whereBetween('created_at', [$mStart, $mEnd])
                ->count();

            $mDonorCount = Lead::query()
                ->accessibleTo($user)
                ->whereHas('status.stage', static fn ($sq) => $sq->where('code', 'donor'))
                ->whereBetween('created_at', [$mStart, $mEnd])
                ->count();

            $mDonationVal = (float) (Lead::query()
                ->accessibleTo($user)
                ->whereBetween('created_at', [$mStart, $mEnd])
                ->whereNotNull('donation_value')
                ->sum('donation_value') ?? 0.0);

            $monthsTrend[] = [
                'label' => $monthLabel,
                'new_count' => $mNewCount,
                'donor_count' => $mDonorCount,
                'donation_value' => $mDonationVal,
            ];
        }

        // 9. Latest Followups
        $latestFollowups = LeadFollowup::query()
            ->with([
                'lead.status.stage',
                'toStatus.stage',
                'user:id,name',
            ])
            ->whereHas('lead', function (Builder $query) use ($filters, $user): void {
                $query->accessibleTo($user);
                $this->applyLeadFilters($query, $filters);
            })
            ->orderByDesc('followed_up_at')
            ->orderByDesc('id')
            ->limit(8)
            ->get();

        // 10. Filter Lookups
        $visibleAssignedUserIds = Lead::query()
            ->accessibleTo($user)
            ->whereNotNull('assigned_user_id')
            ->distinct()
            ->pluck('assigned_user_id');

        $employees = User::query()
            ->whereIn('id', $visibleAssignedUserIds)
            ->orderBy('name')
            ->pluck('name')
            ->merge(
                Lead::query()
                    ->accessibleTo($user)
                    ->whereNull('assigned_user_id')
                    ->whereNotNull('assigned_employee')
                    ->where('assigned_employee', '<>', '')
                    ->pluck('assigned_employee')
            )
            ->filter()
            ->unique()
            ->sort()
            ->values();

        $donationTypes = DonationType::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $voipStatus = null;
        if ($user->hasPermission('voip.view')) {
            try {
                $voip = app(VoipService::class);
                if ($voip->isConfigured()) {
                    $voipStatus = $voip->health();
                }
            } catch (\Throwable $e) {
                // ignore
            }
        }

        return view('dashboard', [
            'stages' => $stages,
            'pipelineStages' => $pipelineStages,
            'totalActiveStagesCount' => $totalActiveStagesCount,
            'statuses' => $statuses,
            'statusCards' => $statusCards,
            'stageCards' => $stageCards,
            'totalCustomersCount' => $totalCustomersCount,
            'totalLeads' => $totalCustomersCount,
            'newCustomersCount' => $newCustomersCount,
            'donorCustomersCount' => $donorCustomersCount,
            'donorConversionRate' => $donorConversionRate,
            'totalDonationValue' => $totalDonationValue,
            'followupCounts' => $followupCounts,
            'stageDistribution' => $stageDistribution,
            'monthsTrend' => $monthsTrend,
            'latestFollowups' => $latestFollowups,
            'communicationLabels' => self::COMMUNICATION_LABELS,
            'employees' => $employees,
            'donationTypes' => $donationTypes,
            'donationCycles' => self::DONATION_CYCLES,
            'filters' => $filters,
            'voipStatus' => $voipStatus,
        ]);
    }

    public function kanban(Request $request)
    {
        $this->assertCrmDatabase();
        $user = $request->user();

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        // 1. Dynamic active pipeline stages ordered by configured position
        $pipelineStages = PipelineStage::query()
            ->with(['statuses'])
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $statuses = $pipelineStages;
        $kanbanColumns = [];
        $totalLeads = 0;

        foreach ($pipelineStages as $stage) {
            $statusIds = $stage->statuses->pluck('id')->all();
            $destinationStatus = $stage->statuses->first();
            $destinationStatusId = $destinationStatus?->id;
            $destinationStatusName = $destinationStatus?->name_ar ?? $stage->localizedName();

            if (! empty($statusIds)) {
                $leads = Lead::query()
                    ->accessibleTo($user)
                    ->with(['assignedUser:id,name', 'phones', 'status.stage'])
                    ->whereIn('lead_status_id', $statusIds)
                    ->orderByRaw('next_follow_up_at IS NULL')
                    ->orderBy('next_follow_up_at')
                    ->orderByDesc('updated_at')
                    ->get();
            } else {
                $leads = collect();
            }
            $totalCount = $leads->count();
            $totalLeads += $totalCount;

            $datedLeads = $leads->filter(static fn (Lead $lead): bool => $lead->next_follow_up_at !== null);

            $todayLeads = $datedLeads->filter(static function (Lead $lead) use ($todayStart, $todayEnd): bool {
                $at = $lead->next_follow_up_at;
                return $at !== null && $at->gte($todayStart) && $at->lte($todayEnd);
            })->sortBy(static fn (Lead $lead): int => $lead->next_follow_up_at?->getTimestamp() ?? 0)->values();

            $overdueLeads = $datedLeads->filter(static fn (Lead $lead): bool => $lead->next_follow_up_at?->lt($todayStart) ?? false)
                ->sortByDesc(static fn (Lead $lead): int => $lead->next_follow_up_at?->getTimestamp() ?? 0)->values();

            $upcomingLeads = $datedLeads->filter(static fn (Lead $lead): bool => $lead->next_follow_up_at?->gt($todayEnd) ?? false)
                ->sortBy(static fn (Lead $lead): int => $lead->next_follow_up_at?->getTimestamp() ?? 0)->values();

            $noDateLeads = $leads->filter(static fn (Lead $lead): bool => $lead->next_follow_up_at === null)->values();

            $kanbanColumns[] = [
                'id' => $stage->id,
                'stage_id' => $stage->id,
                'code' => $stage->code,
                'status_id' => $destinationStatusId,
                'destination_status_id' => $destinationStatusId,
                'name' => $stage->localizedName(),
                'stage_name' => $stage->localizedName(),
                'status_name' => $destinationStatusName,
                'color' => $stage->color ?: '#3478f6',
                'status_color' => $stage->color ?: '#3478f6',
                'stage_color' => $stage->color ?: '#3478f6',
                'icon' => $stage->icon,
                'class' => str_replace(['_', ' '], '-', (string) $stage->code),
                'total' => $totalCount,
                'total_count' => $totalCount,
                'scope_counts' => [
                    'today' => $todayLeads->count(),
                    'overdue' => $overdueLeads->count(),
                    'upcoming' => $upcomingLeads->count(),
                ],
                'scope_leads' => [
                    'today' => $todayLeads,
                    'overdue' => $overdueLeads,
                    'upcoming' => $upcomingLeads,
                ],
                'no_date_count' => $noDateLeads->count(),
                'leads' => $leads,
                'today_leads' => $todayLeads,
                'overdue_leads' => $overdueLeads,
                'upcoming_leads' => $upcomingLeads,
                'no_date_leads' => $noDateLeads,
                'has_destination_status' => $destinationStatusId !== null,
            ];
        }

        return view('kanban', [
            'kanbanColumns' => $kanbanColumns,
            'pipelineStages' => $pipelineStages,
            'totalLeads' => $totalLeads,
            'totalStagesCount' => count($kanbanColumns),
            'statuses' => $pipelineStages,
        ]);

    }

    private function resolveFilters(Request $request): array
    {
        $employee = mb_substr(trim((string) $request->query('employee', '')), 0, 150);
        $status = mb_substr(trim((string) $request->query('status', '')), 0, 50);
        $stage = mb_substr(trim((string) $request->query('stage', '')), 0, 50);
        $donationType = mb_substr(trim((string) $request->query('donation_type', '')), 0, 100);
        $donationCycle = mb_substr(trim((string) $request->query('donation_cycle', '')), 0, 50);
        $period = (string) $request->query('period', 'all');
        $fromInput = mb_substr(trim((string) $request->query('from', '')), 0, 10);
        $toInput = mb_substr(trim((string) $request->query('to', '')), 0, 10);

        $allowedPeriods = ['all', 'today', 'week', 'month', 'year', 'custom'];
        if (! in_array($period, $allowedPeriods, true)) {
            $period = 'all';
        }

        $now = now();
        $fromDate = null;
        $toDate = null;

        if ($period === 'custom' || ($fromInput !== '' || $toInput !== '')) {
            $period = 'custom';
            $fromDate = $this->parseDate($fromInput, false);
            $toDate = $this->parseDate($toInput, true);
        } else {
            if ($period === 'today') {
                $fromDate = $now->copy()->startOfDay();
                $toDate = $now->copy()->endOfDay();
            } elseif ($period === 'week') {
                $fromDate = $now->copy()->startOfWeek();
                $toDate = $now->copy()->endOfWeek();
            } elseif ($period === 'month') {
                $fromDate = $now->copy()->startOfMonth();
                $toDate = $now->copy()->endOfMonth();
            } elseif ($period === 'year') {
                $fromDate = $now->copy()->startOfYear();
                $toDate = $now->copy()->endOfYear();
            }
        }

        return [
            'employee' => $employee,
            'status' => $status,
            'stage' => $stage,
            'donation_type' => $donationType,
            'donation_cycle' => $donationCycle,
            'period' => $period,
            'from' => ($fromDate && $fromInput !== '' ? $fromInput : ''),
            'to' => ($toDate && $toInput !== '' ? $toInput : ''),
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    private function parseDate(string $value, bool $endOfDay): ?Carbon
    {
        if ($value === '' || ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value, (string) config('app.timezone', 'UTC'));
            return $endOfDay ? $date->endOfDay() : $date->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function applyLeadFilters(Builder $query, array $filters): void
    {
        if ($filters['employee'] !== '') {
            $query->where(function (Builder $employeeQuery) use ($filters): void {
                $employeeQuery
                    ->whereHas('assignedUser', function (Builder $userQuery) use ($filters): void {
                        $userQuery->where('name', $filters['employee']);
                    })
                    ->orWhere('assigned_employee', $filters['employee']);
            });
        }

        if (! empty($filters['status'])) {
            $statusVal = $filters['status'];
            $query->whereHas('status', function (Builder $statusQuery) use ($statusVal): void {
                if (is_numeric($statusVal)) {
                    $statusQuery->where('id', (int) $statusVal);
                } else {
                    $statusQuery->where('code', $statusVal);
                }
            });
        } elseif (! empty($filters['stage'])) {
            $stageVal = $filters['stage'];
            $query->whereHas('status', function (Builder $statusQuery) use ($stageVal): void {
                if (is_numeric($stageVal)) {
                    $statusQuery->where('pipeline_stage_id', (int) $stageVal);
                } else {
                    $statusQuery->whereHas('stage', static fn ($sq) => $sq->where('code', $stageVal));
                }
            });
        }

        if (! empty($filters['donation_type'])) {
            $dTypeVal = $filters['donation_type'];
            $query->where(function ($dq) use ($dTypeVal): void {
                $dq->where('donation_type', $dTypeVal)
                    ->orWhere('donation_type_id', $dTypeVal);
            });
        }

        if (! empty($filters['donation_cycle'])) {
            $query->where('donation_cycle', $filters['donation_cycle']);
        }

        if ($filters['from_date'] instanceof Carbon) {
            $query->where('created_at', '>=', $filters['from_date']);
        }

        if ($filters['to_date'] instanceof Carbon) {
            $query->where('created_at', '<=', $filters['to_date']);
        }
    }

    private function assertCrmDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}
