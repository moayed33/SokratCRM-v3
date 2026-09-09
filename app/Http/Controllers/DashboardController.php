<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CollectionCase;
use App\Models\Donation;
use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\VoipService;
use App\Support\BranchContext;
use App\Support\CrmDatabaseGuard;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const DONATION_CYCLES = [
        'one_time' => 'مرة واحدة',
        'monthly' => 'شهري',
        'quarterly' => 'ربع سنوي',
        'semi_annual' => 'نصف سنوي',
        'annual' => 'سنوي',
        'other' => 'أخرى',
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
        $donationLeadFilters = array_replace($filters, [
            'donation_type' => '',
            'donation_cycle' => '',
            'from_date' => null,
            'to_date' => null,
        ]);
        $recordedDonations = Donation::query()
            ->whereHas('lead', function (Builder $query) use ($donationLeadFilters, $user): void {
                $query->accessibleTo($user);
                $this->applyLeadFilters($query, $donationLeadFilters);
            });

        if ($filters['donation_type'] !== '') {
            $recordedDonations->where(function (Builder $query) use ($filters): void {
                $query->where('donation_type', $filters['donation_type'])
                    ->orWhere('donation_type_id', $filters['donation_type']);
            });
        }
        if ($filters['donation_cycle'] !== '') {
            $recordedDonations->where('cycle', $filters['donation_cycle']);
        }
        if ($filters['from_date'] instanceof Carbon) {
            $recordedDonations->where('donated_at', '>=', $filters['from_date']);
        }
        if ($filters['to_date'] instanceof Carbon) {
            $recordedDonations->where('donated_at', '<=', $filters['to_date']);
        }

        $recordedDonationValue = (float) $recordedDonations->sum('amount');
        $legacyDonationValue = (float) ((clone $leadBase)
            ->whereDoesntHave('donations')
            ->whereNotNull('donation_value')
            ->where('donation_value', '>', 0)
            ->sum('donation_value') ?? 0.0);
        $totalDonationValue = $recordedDonationValue + $legacyDonationValue;

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
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = now()->subMonths($i);
            $mStart = (clone $monthDate)->startOfMonth();
            $mEnd = (clone $monthDate)->endOfMonth();

            $monthLabel = $monthDate
                ->locale(app()->getLocale())
                ->translatedFormat('F Y');

            $mNewCount = Lead::query()
                ->accessibleTo($user)
                ->whereBetween('created_at', [$mStart, $mEnd])
                ->count();

            $mDonorCount = Lead::query()
                ->accessibleTo($user)
                ->whereHas('status.stage', static fn ($sq) => $sq->where('code', 'donor'))
                ->whereBetween('created_at', [$mStart, $mEnd])
                ->count();

            $mDonationVal = (float) Donation::query()
                ->whereHas('lead', static fn (Builder $query) => $query->accessibleTo($user))
                ->whereBetween('donated_at', [$mStart, $mEnd])
                ->sum('amount');
            $mDonationVal += (float) (Lead::query()
                ->accessibleTo($user)
                ->whereDoesntHave('donations')
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

        $collectionSummary = null;
        $collectionStatusDistribution = null;
        $collectorPerformance = collect();
        if ($user->hasPermission(CrmPermission::COLLECTIONS_VIEW)) {
            $collectionBase = CollectionCase::query()->accessibleTo($user);
            $collectionSummary = [
                'open' => (clone $collectionBase)->open()->count(),
                'overdue' => (clone $collectionBase)->open()->where('due_at', '<', now())->count(),
                'expected_open' => (float) (clone $collectionBase)->open()->sum('expected_amount'),
                'received' => (float) Donation::query()
                    ->whereHas('collectionCase', static fn (Builder $query) => $query->accessibleTo($user))
                    ->sum('amount'),
            ];

            if ($user->isSuperAdmin()) {
                $collectionStatuses = [
                    'pending',
                    'assigned',
                    'scheduled',
                    'failed',
                    'collected',
                    'cancelled',
                ];
                $collectionStatusCounts = (clone $collectionBase)
                    ->selectRaw('status, count(*) as total')
                    ->groupBy('status')
                    ->pluck('total', 'status');
                $collectionStatusDistribution = [
                    'labels' => collect($collectionStatuses)
                        ->map(static fn (string $status): string => __('crm.collection_status_'.$status))
                        ->all(),
                    'data' => collect($collectionStatuses)
                        ->map(static fn (string $status): int => (int) ($collectionStatusCounts[$status] ?? 0))
                        ->all(),
                ];

                $collectorPerformance = (clone $collectionBase)
                    ->whereNotNull('assigned_collector_user_id')
                    ->with('assignedCollector:id,name')
                    ->get()
                    ->groupBy('assigned_collector_user_id')
                    ->map(static function ($cases): array {
                        return [
                            'name' => $cases->first()?->assignedCollector?->name ?? __('crm.unassigned'),
                            'total' => $cases->count(),
                            'collected' => $cases->where('status', CollectionCase::STATUS_COLLECTED)->count(),
                            'open_value' => (float) $cases->whereIn('status', CollectionCase::OPEN_STATUSES)->sum('expected_amount'),
                        ];
                    })
                    ->sortByDesc('collected')
                    ->take(8)
                    ->values();
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
            'communicationLabels' => collect(array_keys(self::COMMUNICATION_LABELS))
                ->mapWithKeys(static fn (string $type): array => [
                    $type => __('crm.communication_'.$type),
                ])
                ->all(),
            'employees' => $employees,
            'donationTypes' => $donationTypes,
            'donationCycles' => self::DONATION_CYCLES,
            'filters' => $filters,
            'voipStatus' => $voipStatus,
            'collectionSummary' => $collectionSummary,
            'collectionStatusDistribution' => $collectionStatusDistribution,
            'collectorPerformance' => $collectorPerformance,
        ]);
    }

    public function kanban(Request $request)
    {
        $this->assertCrmDatabase();
        $user = $request->user();

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $perPage = (int) $request->query('limit', 10);
        if ($perPage < 1 || $perPage > 100) {
            $perPage = 10;
        }

        // 1. Dynamic active pipeline stages ordered by configured position
        $pipelineStages = PipelineStage::query()
            ->with(['statuses'])
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $statuses = $pipelineStages;
        $allStatusIds = $pipelineStages->flatMap(static fn ($s) => $s->statuses->pluck('id'))->all();

        // 2. High-performance SQL aggregation for stage & scope counts across all active stages
        $countsMap = [];
        if (! empty($allStatusIds)) {
            $rawCounts = Lead::query()
                ->accessibleTo($user)
                ->whereIn('lead_status_id', $allStatusIds)
                ->selectRaw('lead_status_id,
                    COUNT(*) as total,
                    SUM(CASE WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at >= ? AND next_follow_up_at <= ? THEN 1 ELSE 0 END) as today_count,
                    SUM(CASE WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at < ? THEN 1 ELSE 0 END) as overdue_count,
                    SUM(CASE WHEN next_follow_up_at IS NOT NULL AND next_follow_up_at > ? THEN 1 ELSE 0 END) as upcoming_count,
                    SUM(CASE WHEN next_follow_up_at IS NULL THEN 1 ELSE 0 END) as no_date_count',
                    [$todayStart, $todayEnd, $todayStart, $todayEnd]
                )
                ->groupBy('lead_status_id')
                ->get();

            foreach ($rawCounts as $rc) {
                $countsMap[$rc->lead_status_id] = [
                    'total' => (int) $rc->total,
                    'today' => (int) $rc->today_count,
                    'overdue' => (int) $rc->overdue_count,
                    'upcoming' => (int) $rc->upcoming_count,
                    'no_date' => (int) $rc->no_date_count,
                ];
            }
        }

        $kanbanColumns = [];
        $totalLeads = 0;

        foreach ($pipelineStages as $stage) {
            $statusIds = $stage->statuses->pluck('id')->all();
            $destinationStatus = $stage->statuses->first();
            $destinationStatusId = $destinationStatus?->id;
            $destinationStatusName = $destinationStatus?->name_ar ?? $stage->localizedName();

            $stageTotal = 0;
            $stageToday = 0;
            $stageOverdue = 0;
            $stageUpcoming = 0;
            $stageNoDate = 0;
            foreach ($statusIds as $sId) {
                if (isset($countsMap[$sId])) {
                    $stageTotal += $countsMap[$sId]['total'];
                    $stageToday += $countsMap[$sId]['today'];
                    $stageOverdue += $countsMap[$sId]['overdue'];
                    $stageUpcoming += $countsMap[$sId]['upcoming'];
                    $stageNoDate += $countsMap[$sId]['no_date'];
                }
            }
            $totalLeads += $stageTotal;

            $isDirectStatus = in_array($stage->code, ['new', 'not_interested'], true);

            if (! empty($statusIds)) {
                if ($isDirectStatus) {
                    $todayLeads = Lead::query()
                        ->accessibleTo($user)
                        ->with(['assignedUser:id,name', 'phones', 'status.stage'])
                        ->whereIn('lead_status_id', $statusIds)
                        ->orderByRaw('next_follow_up_at IS NULL')
                        ->orderBy('next_follow_up_at')
                        ->orderByDesc('updated_at')
                        ->limit($perPage)
                        ->get();
                    $overdueLeads = collect();
                    $upcomingLeads = collect();
                } else {
                    $todayLeads = $stageToday > 0 ? Lead::query()
                        ->accessibleTo($user)
                        ->with(['assignedUser:id,name', 'phones', 'status.stage'])
                        ->whereIn('lead_status_id', $statusIds)
                        ->whereNotNull('next_follow_up_at')
                        ->whereBetween('next_follow_up_at', [$todayStart, $todayEnd])
                        ->orderBy('next_follow_up_at')
                        ->orderByDesc('updated_at')
                        ->limit($perPage)
                        ->get() : collect();

                    $overdueLeads = $stageOverdue > 0 ? Lead::query()
                        ->accessibleTo($user)
                        ->with(['assignedUser:id,name', 'phones', 'status.stage'])
                        ->whereIn('lead_status_id', $statusIds)
                        ->whereNotNull('next_follow_up_at')
                        ->where('next_follow_up_at', '<', $todayStart)
                        ->orderByDesc('next_follow_up_at')
                        ->orderByDesc('updated_at')
                        ->limit($perPage)
                        ->get() : collect();

                    $upcomingLeads = $stageUpcoming > 0 ? Lead::query()
                        ->accessibleTo($user)
                        ->with(['assignedUser:id,name', 'phones', 'status.stage'])
                        ->whereIn('lead_status_id', $statusIds)
                        ->whereNotNull('next_follow_up_at')
                        ->where('next_follow_up_at', '>', $todayEnd)
                        ->orderBy('next_follow_up_at')
                        ->orderByDesc('updated_at')
                        ->limit($perPage)
                        ->get() : collect();
                }
            } else {
                $todayLeads = collect();
                $overdueLeads = collect();
                $upcomingLeads = collect();
            }

            $activeScopeTotal = $isDirectStatus ? $stageTotal : $stageToday;
            $totalPages = max(1, (int) ceil($activeScopeTotal / $perPage));

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
                'total' => $stageTotal,
                'total_count' => $stageTotal,
                'scope_counts' => [
                    'today' => $stageToday,
                    'overdue' => $stageOverdue,
                    'upcoming' => $stageUpcoming,
                ],
                'scope_leads' => [
                    'today' => $todayLeads,
                    'overdue' => $overdueLeads,
                    'upcoming' => $upcomingLeads,
                ],
                'no_date_count' => $stageNoDate,
                'leads' => $todayLeads,
                'today_leads' => $todayLeads,
                'overdue_leads' => $overdueLeads,
                'upcoming_leads' => $upcomingLeads,
                'no_date_leads' => collect(),
                'position' => $stage->position,
                'has_destination_status' => $destinationStatusId !== null,
                'per_page' => $perPage,
                'current_page' => 1,
                'total_pages' => $totalPages,
            ];
        }
        $employees = User::query()
            ->select(['id', 'name'])
            ->orderBy('name')
            ->get();

        return view('kanban', [
            'kanbanColumns' => $kanbanColumns,
            'pipelineStages' => $pipelineStages,
            'totalLeads' => $totalLeads,
            'totalStagesCount' => count($kanbanColumns),
            'statuses' => $pipelineStages,
            'employees' => $employees,
            'perPage' => $perPage,
        ]);
    }

    public function kanbanCards(Request $request): JsonResponse
    {
        $this->assertCrmDatabase();
        $user = $request->user();

        $stageId = $request->query('stage_id');
        $stageCode = $request->query('stage_code');

        $stage = PipelineStage::query()
            ->with('statuses')
            ->where('is_active', true)
            ->when($stageId, static fn ($q) => $q->where('id', (int) $stageId))
            ->when(! $stageId && $stageCode, static fn ($q) => $q->where('code', $stageCode))
            ->first();

        if (! $stage) {
            return response()->json(['success' => false, 'error' => 'Stage not found'], 404);
        }

        $statusIds = $stage->statuses->pluck('id')->all();
        if (empty($statusIds)) {
            return response()->json([
                'success' => true,
                'stage_id' => $stage->id,
                'stage_code' => $stage->code,
                'scope' => 'today',
                'page' => 1,
                'per_page' => 10,
                'total' => 0,
                'total_pages' => 1,
                'from' => 0,
                'to' => 0,
                'count' => 0,
                'html' => '',
            ]);
        }

        $scope = (string) $request->query('scope', 'today');
        $page = max(1, (int) $request->query('page', 1));
        $limit = (int) $request->query('limit', 10);
        if ($limit < 1 || $limit > 100) {
            $limit = 10;
        }

        $search = mb_substr(trim((string) $request->query('search', '')), 0, 150);
        $employeeId = $request->query('employee_id');

        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $query = Lead::query()
            ->accessibleTo($user)
            ->with(['assignedUser:id,name', 'phones', 'status.stage'])
            ->whereIn('lead_status_id', $statusIds);

        $isDirectStatus = in_array($stage->code, ['new', 'not_interested'], true);
        if (! $isDirectStatus) {
            if ($scope === 'today') {
                $query->whereNotNull('next_follow_up_at')
                    ->whereBetween('next_follow_up_at', [$todayStart, $todayEnd])
                    ->orderBy('next_follow_up_at')
                    ->orderByDesc('updated_at');
            } elseif ($scope === 'overdue') {
                $query->whereNotNull('next_follow_up_at')
                    ->where('next_follow_up_at', '<', $todayStart)
                    ->orderByDesc('next_follow_up_at')
                    ->orderByDesc('updated_at');
            } elseif ($scope === 'upcoming') {
                $query->whereNotNull('next_follow_up_at')
                    ->where('next_follow_up_at', '>', $todayEnd)
                    ->orderBy('next_follow_up_at')
                    ->orderByDesc('updated_at');
            } elseif ($scope === 'no_date') {
                $query->whereNull('next_follow_up_at')
                    ->orderByDesc('updated_at');
            } else {
                $query->orderByRaw('next_follow_up_at IS NULL')
                    ->orderBy('next_follow_up_at')
                    ->orderByDesc('updated_at');
            }
        } else {
            if ($scope === 'no_date') {
                $query->whereNull('next_follow_up_at');
            } elseif ($scope === 'today') {
                $query->whereNotNull('next_follow_up_at')->whereBetween('next_follow_up_at', [$todayStart, $todayEnd]);
            } elseif ($scope === 'overdue') {
                $query->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '<', $todayStart);
            } elseif ($scope === 'upcoming') {
                $query->whereNotNull('next_follow_up_at')->where('next_follow_up_at', '>', $todayEnd);
            }
            $query->orderByRaw('next_follow_up_at IS NULL')
                ->orderBy('next_follow_up_at')
                ->orderByDesc('updated_at');
        }

        if ($employeeId !== null && $employeeId !== '') {
            $query->where('assigned_user_id', (int) $employeeId);
        }

        if ($search !== '') {
            $query->where(static function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('source', 'like', "%{$search}%")
                    ->orWhereHas('assignedUser', static fn ($uq) => $uq->where('name', 'like', "%{$search}%"));
            });
        }

        $paginated = $query->paginate($limit, ['*'], 'page', $page);

        $allStages = PipelineStage::query()
            ->with('statuses')
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $kanbanColumns = [];
        $currentColumn = null;
        foreach ($allStages as $st) {
            $destStatus = $st->statuses->first();
            $colData = [
                'id' => $st->id,
                'stage_id' => $st->id,
                'code' => $st->code,
                'status_id' => $destStatus?->id,
                'destination_status_id' => $destStatus?->id,
                'name' => $st->localizedName(),
                'stage_name' => $st->localizedName(),
                'status_name' => $destStatus?->name_ar ?? $st->localizedName(),
                'color' => $st->color ?: '#3478f6',
                'status_color' => $st->color ?: '#3478f6',
                'stage_color' => $st->color ?: '#3478f6',
                'position' => $st->position,
                'class' => str_replace(['_', ' '], '-', (string) $st->code),
            ];
            $kanbanColumns[] = $colData;
            if ($st->id === $stage->id) {
                $currentColumn = $colData;
            }
        }

        $html = '';
        foreach ($paginated->items() as $lead) {
            $html .= view('partials.kanban-card', [
                'lead' => $lead,
                'column' => $currentColumn,
                'kanbanColumns' => $kanbanColumns,
                'scope' => $scope,
            ])->render();
        }

        $total = $paginated->total();
        $totalPages = max(1, (int) ceil($total / $limit));
        $from = $total > 0 ? ($page - 1) * $limit + 1 : 0;
        $to = min($page * $limit, $total);

        return response()->json([
            'success' => true,
            'stage_id' => $stage->id,
            'stage_code' => $stage->code,
            'scope' => $scope,
            'page' => $page,
            'per_page' => $limit,
            'total' => $total,
            'total_pages' => $totalPages,
            'from' => $from,
            'to' => $to,
            'count' => count($paginated->items()),
            'html' => $html,
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
