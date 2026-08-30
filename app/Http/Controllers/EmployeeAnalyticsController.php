<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\CollectionCase;
use App\Models\Donation;
use App\Models\Group;
use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\User;
use App\Services\VoipService;
use App\Security\CrmPermission;
use App\Support\CrmDatabaseGuard;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Throwable;

class EmployeeAnalyticsController extends Controller
{
    public function index(Request $request, VoipService $voip): View|RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        /** @var User|null $viewer */
        $viewer = Auth::user();
        if (! $viewer) {
            return redirect()->route('login');
        }

        abort_unless($viewer->hasPermission(CrmPermission::REPORTS_EMPLOYEES_VIEW), 403, 'Unauthorized. Employee analytics is restricted.');

        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:all,today,week,month,year,custom'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'branch_id' => ['nullable', 'string'],
            'group_id' => ['nullable', 'string'],
            'status' => ['nullable', 'string', 'in:all,online,incall,offline'],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);

        $period = $validated['period'] ?? 'month';
        $now = now();
        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;

            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                break;

            case 'month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;

            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                break;

            case 'custom':
                if (! empty($validated['from'])) {
                    $startDate = Carbon::parse($validated['from'])->startOfDay();
                }
                if (! empty($validated['to'])) {
                    $endDate = Carbon::parse($validated['to'])->endOfDay();
                }
                break;

            case 'all':
            default:
                break;
        }

        // Scoped employees for current viewer (Super Admin, Team Leader, or Collection Manager)
        $employeesQuery = $viewer->managedEmployeesQuery()
            ->with(['branch:id,name_ar,name_en,code', 'groups:id,name,code', 'manager:id,name,username']);

        // Super Admin branch filtering
        $selectedBranchId = $validated['branch_id'] ?? null;
        if ($viewer->isSuperAdmin() && ! empty($selectedBranchId) && $selectedBranchId !== 'all') {
            $employeesQuery->where('users.branch_id', (int) $selectedBranchId);
        }

        // Group / Role filtering
        $selectedGroupId = $validated['group_id'] ?? null;
        if (! empty($selectedGroupId) && $selectedGroupId !== 'all') {
            $employeesQuery->whereHas('groups', static fn ($gq) => $gq->where('groups.id', (int) $selectedGroupId));
        }

        // Search query
        $search = trim((string) ($validated['q'] ?? ''));
        if ($search !== '') {
            $employeesQuery->where(function ($sq) use ($search): void {
                $term = '%'.$search.'%';
                $sq->where('users.name', 'like', $term)
                    ->orWhere('users.username', 'like', $term)
                    ->orWhere('users.voip_extension', 'like', $term)
                    ->orWhere('users.email', 'like', $term);
            });
        }

        $allManagedEmployees = $employeesQuery->orderBy('users.name')->get();
        $employeeIds = $allManagedEmployees->pluck('id')->all();

        // 1. Follow-ups Aggregations
        $followupsCounts = [];
        $followupsCalls = [];
        $followupsMeetings = [];
        $followupsWhatsapp = [];

        if ($employeeIds !== []) {
            $fuQuery = LeadFollowup::query()
                ->whereIn('user_id', $employeeIds);
            if ($startDate) {
                $fuQuery->where('followed_up_at', '>=', $startDate);
            }
            if ($endDate) {
                $fuQuery->where('followed_up_at', '<=', $endDate);
            }

            $fuResults = $fuQuery->select([
                'user_id',
                'communication_type',
                DB::raw('COUNT(*) as total'),
            ])
                ->groupBy('user_id', 'communication_type')
                ->get();

            foreach ($fuResults as $row) {
                $uid = (int) $row->user_id;
                $followupsCounts[$uid] = ($followupsCounts[$uid] ?? 0) + (int) $row->total;
                if ($row->communication_type === 'call') {
                    $followupsCalls[$uid] = (int) $row->total;
                } elseif ($row->communication_type === 'meeting') {
                    $followupsMeetings[$uid] = (int) $row->total;
                } elseif ($row->communication_type === 'whatsapp') {
                    $followupsWhatsapp[$uid] = (int) $row->total;
                }
            }
        }

        // 2. Assigned Leads and Converted Donors
        $leadsCount = [];
        $donorsCount = [];
        if ($employeeIds !== []) {
            $leadsResults = Lead::query()
                ->whereIn('assigned_user_id', $employeeIds)
                ->select([
                    'assigned_user_id',
                    DB::raw('COUNT(*) as total_leads'),
                ])
                ->groupBy('assigned_user_id')
                ->get();

            foreach ($leadsResults as $row) {
                $leadsCount[(int) $row->assigned_user_id] = (int) $row->total_leads;
            }

            $donorsResults = Lead::query()
                ->whereIn('assigned_user_id', $employeeIds)
                ->whereHas('status.stage', static fn ($sq) => $sq->where('code', 'donor'))
                ->select([
                    'assigned_user_id',
                    DB::raw('COUNT(*) as total_donors'),
                ])
                ->groupBy('assigned_user_id')
                ->get();

            foreach ($donorsResults as $row) {
                $donorsCount[(int) $row->assigned_user_id] = (int) $row->total_donors;
            }
        }

        // 3. Recorded Donations
        $donationsCount = [];
        $donationsAmount = [];
        if ($employeeIds !== []) {
            $donQuery = Donation::query()
                ->whereIn('recorded_by_user_id', $employeeIds);
            if ($startDate) {
                $donQuery->where('donated_at', '>=', $startDate);
            }
            if ($endDate) {
                $donQuery->where('donated_at', '<=', $endDate);
            }

            $donResults = $donQuery->select([
                'recorded_by_user_id',
                DB::raw('COUNT(*) as count_donations'),
                DB::raw('SUM(amount) as total_amount'),
            ])
                ->groupBy('recorded_by_user_id')
                ->get();

            foreach ($donResults as $row) {
                $uid = (int) $row->recorded_by_user_id;
                $donationsCount[$uid] = (int) $row->count_donations;
                $donationsAmount[$uid] = (float) $row->total_amount;
            }
        }

        // 4. Completed Collections
        $collectionsCount = [];
        $collectionsAmount = [];
        if ($employeeIds !== []) {
            $colQuery = CollectionCase::query()
                ->whereIn('assigned_collector_user_id', $employeeIds)
                ->where('status', 'completed');
            if ($startDate) {
                $colQuery->where('completed_at', '>=', $startDate);
            }
            if ($endDate) {
                $colQuery->where('completed_at', '<=', $endDate);
            }

            $colResults = $colQuery->select([
                'assigned_collector_user_id',
                DB::raw('COUNT(*) as count_cases'),
                DB::raw('SUM(expected_amount) as total_collected'),
            ])
                ->groupBy('assigned_collector_user_id')
                ->get();

            foreach ($colResults as $row) {
                $uid = (int) $row->assigned_collector_user_id;
                $collectionsCount[$uid] = (int) $row->count_cases;
                $collectionsAmount[$uid] = (float) $row->total_collected;
            }
        }

        // 5. VoIP Live Status (if configured)
        $voipState = [];
        if ($voip->isConfigured()) {
            try {
                $rawExts = $voip->getExtensions();
                $extList = $rawExts['extensions'] ?? $rawExts['data'] ?? $rawExts;
                if (is_array($extList)) {
                    foreach ($extList as $e) {
                        $extNum = (string) ($e['extension'] ?? $e['id'] ?? '');
                        if ($extNum !== '') {
                            $voipState[$extNum] = [
                                'online' => ! empty($e['online']),
                                'in_call' => ! empty($e['in_call']),
                                'status' => $e['status'] ?? 'offline',
                            ];
                        }
                    }
                }
            } catch (Throwable) {
                // Silently ignore PBX unreachable
            }
        }

        // Assemble metrics per employee
        $allStaffStats = $allManagedEmployees->map(static function (User $emp) use (
            $followupsCounts,
            $followupsCalls,
            $followupsMeetings,
            $followupsWhatsapp,
            $leadsCount,
            $donorsCount,
            $donationsCount,
            $donationsAmount,
            $collectionsCount,
            $collectionsAmount,
            $voipState
        ): array {
            $id = $emp->id;
            $ext = (string) ($emp->voip_extension ?? '');
            $voipInfo = $ext !== '' ? ($voipState[$ext] ?? null) : null;
            $isOnline = ! empty($voipInfo['online']);
            $isInCall = ! empty($voipInfo['in_call']);
            $telephonyStatus = $isInCall ? 'incall' : ($isOnline ? 'online' : 'offline');

            return [
                'user' => $emp,
                'id' => $id,
                'name' => $emp->name,
                'username' => $emp->username,
                'manager' => $emp->manager,
                'branch' => $emp->branch,
                'groups' => $emp->groups,
                'extension' => $ext,
                'voip_info' => $voipInfo,
                'telephony_status' => $telephonyStatus,
                'assigned_leads' => $leadsCount[$id] ?? 0,
                'converted_donors' => $donorsCount[$id] ?? 0,
                'total_followups' => $followupsCounts[$id] ?? 0,
                'calls_count' => $followupsCalls[$id] ?? 0,
                'meetings_count' => $followupsMeetings[$id] ?? 0,
                'whatsapp_count' => $followupsWhatsapp[$id] ?? 0,
                'donations_count' => $donationsCount[$id] ?? 0,
                'donations_amount' => $donationsAmount[$id] ?? 0.0,
                'collections_count' => $collectionsCount[$id] ?? 0,
                'collections_amount' => $collectionsAmount[$id] ?? 0.0,
                'total_value' => ($donationsAmount[$id] ?? 0.0) + ($collectionsAmount[$id] ?? 0.0),
            ];
        });

        // Filter by telephony status if requested
        $selectedStatus = $validated['status'] ?? 'all';
        $filteredStaffStats = $allStaffStats;
        if ($selectedStatus !== 'all') {
            $filteredStaffStats = $allStaffStats->filter(static fn ($s) => $s['telephony_status'] === $selectedStatus)->values();
        }

        // Totals Strip
        $summary = [
            'total_employees' => $allStaffStats->count(),
            'online_extensions' => $allStaffStats->filter(static fn ($s) => ! empty($s['voip_info']['online']))->count(),
            'total_leads' => $allStaffStats->sum('assigned_leads'),
            'total_followups' => $allStaffStats->sum('total_followups'),
            'total_donors' => $allStaffStats->sum('converted_donors'),
            'total_donations_value' => $allStaffStats->sum('donations_amount'),
            'total_collected_value' => $allStaffStats->sum('collections_amount'),
            'grand_total_value' => $allStaffStats->sum('total_value'),
        ];

        // Prepare chart payload (Top 10 staff by activity)
        $topStaff = $allStaffStats->sortByDesc('total_followups')->take(10)->values();
        $chartData = [
            'labels' => $topStaff->pluck('name')->all(),
            'followups' => $topStaff->pluck('total_followups')->all(),
            'donors' => $topStaff->pluck('converted_donors')->all(),
            'donations' => $topStaff->pluck('donations_count')->all(),
            'collections' => $topStaff->pluck('collections_count')->all(),
        ];
        $topFinancialStaff = $allStaffStats->sortByDesc('total_value')->take(10)->values();
        $financialChartData = [
            'labels' => $topFinancialStaff->pluck('name')->all(),
            'donations' => $topFinancialStaff->pluck('donations_amount')->all(),
            'collections' => $topFinancialStaff->pluck('collections_amount')->all(),
            'total_value' => $topFinancialStaff->pluck('total_value')->all(),
        ];

        $channelsData = [
            'calls' => $allStaffStats->sum('calls_count'),
            'meetings' => $allStaffStats->sum('meetings_count'),
            'whatsapp' => $allStaffStats->sum('whatsapp_count'),
        ];

        // Pagination for employee table
        $perPage = (int) ($validated['per_page'] ?? 10);
        $page = (int) $request->input('page', 1);
        $offset = ($page - 1) * $perPage;
        $paginatedStaff = new LengthAwarePaginator(
            $filteredStaffStats->slice($offset, $perPage)->values(),
            $filteredStaffStats->count(),
            $perPage,
            $page,
            ['path' => route('v2.reports.employees'), 'query' => $request->query()]
        );

        $branches = $viewer->isSuperAdmin() ? Branch::query()->where('is_active', true)->orderBy('name_ar')->get() : collect();
        $availableGroups = Group::query()->orderBy('name')->get(['id', 'name', 'code']);

        return view('reports.employees', [
            'viewer' => $viewer,
            'staffStats' => $paginatedStaff,
            'summary' => $summary,
            'chartData' => $chartData,
            'financialChartData' => $financialChartData,
            'channelsData' => $channelsData,
            'branches' => $branches,
            'groups' => $availableGroups,
            'filters' => [
                'period' => $period,
                'from' => $startDate?->format('Y-m-d') ?? ($validated['from'] ?? ''),
                'to' => $endDate?->format('Y-m-d') ?? ($validated['to'] ?? ''),
                'branch_id' => $selectedBranchId ?? '',
                'group_id' => $selectedGroupId ?? '',
                'status' => $selectedStatus,
                'q' => $search,
                'per_page' => $perPage,
            ],
        ]);
    }

    public function show(Request $request, User $user, VoipService $voip): View|RedirectResponse
    {
        CrmDatabaseGuard::ensureConnected();

        /** @var User|null $viewer */
        $viewer = Auth::user();
        if (! $viewer) {
            return redirect()->route('login');
        }

        abort_unless($viewer->hasPermission(CrmPermission::REPORTS_EMPLOYEES_VIEW), 403, 'Unauthorized.');

        // Authorize that target employee is in viewer's management scope
        $isAccessible = $viewer->managedEmployeesQuery()->where('users.id', $user->id)->exists();
        abort_unless($isAccessible, 403, 'Unauthorized access to this employee profile.');

        $validated = $request->validate([
            'period' => ['nullable', 'string', 'in:all,today,week,month,year,custom'],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'tab' => ['nullable', 'string', 'in:overview,leads,followups,donations,collections,voip'],
        ]);

        $period = $validated['period'] ?? 'month';
        $activeTab = $validated['tab'] ?? 'overview';
        $now = now();
        $startDate = null;
        $endDate = null;

        switch ($period) {
            case 'today':
                $startDate = $now->copy()->startOfDay();
                $endDate = $now->copy()->endOfDay();
                break;

            case 'week':
                $startDate = $now->copy()->startOfWeek();
                $endDate = $now->copy()->endOfWeek();
                break;

            case 'month':
                $startDate = $now->copy()->startOfMonth();
                $endDate = $now->copy()->endOfMonth();
                break;

            case 'year':
                $startDate = $now->copy()->startOfYear();
                $endDate = $now->copy()->endOfYear();
                break;

            case 'custom':
                if (! empty($validated['from'])) {
                    $startDate = Carbon::parse($validated['from'])->startOfDay();
                }
                if (! empty($validated['to'])) {
                    $endDate = Carbon::parse($validated['to'])->endOfDay();
                }
                break;

            case 'all':
            default:
                break;
        }

        $user->loadMissing(['branch', 'groups', 'manager', 'subordinates']);

        // 1. Followups Summary
        $fuQuery = LeadFollowup::query()->where('user_id', $user->id);
        if ($startDate) {
            $fuQuery->where('followed_up_at', '>=', $startDate);
        }
        if ($endDate) {
            $fuQuery->where('followed_up_at', '<=', $endDate);
        }
        $totalFollowups = (int) $fuQuery->count();
        $callsCount = (int) (clone $fuQuery)->where('communication_type', 'call')->count();
        $meetingsCount = (int) (clone $fuQuery)->where('communication_type', 'meeting')->count();
        $whatsappCount = (int) (clone $fuQuery)->where('communication_type', 'whatsapp')->count();

        // 2. Assigned Leads
        $assignedLeadsCount = Lead::query()->where('assigned_user_id', $user->id)->count();
        $convertedDonorsCount = Lead::query()
            ->where('assigned_user_id', $user->id)
            ->whereHas('status.stage', static fn ($sq) => $sq->where('code', 'donor'))
            ->count();
        $conversionRate = $assignedLeadsCount > 0 ? round(($convertedDonorsCount / $assignedLeadsCount) * 100, 1) : 0.0;

        // 3. Recorded Donations
        $donQuery = Donation::query()->where('recorded_by_user_id', $user->id);
        if ($startDate) {
            $donQuery->where('donated_at', '>=', $startDate);
        }
        if ($endDate) {
            $donQuery->where('donated_at', '<=', $endDate);
        }
        $donationsCount = (int) (clone $donQuery)->count();
        $donationsAmount = (float) (clone $donQuery)->sum('amount');

        // 4. Collections
        $colQuery = CollectionCase::query()->where('assigned_collector_user_id', $user->id);
        if ($startDate) {
            $colQuery->where('due_at', '>=', $startDate);
        }
        if ($endDate) {
            $colQuery->where('due_at', '<=', $endDate);
        }
        $assignedCasesCount = (int) (clone $colQuery)->count();
        $completedCasesCount = (int) (clone $colQuery)->where('status', 'completed')->count();
        $collectedAmount = (float) (clone $colQuery)->where('status', 'completed')->sum('expected_amount');

        // 5. VoIP Stats (if extension assigned)
        $voipInfo = null;
        if (! empty($user->voip_extension) && $voip->isConfigured()) {
            try {
                $rawExts = $voip->getExtensions();
                $extList = $rawExts['extensions'] ?? $rawExts['data'] ?? $rawExts;
                if (is_array($extList)) {
                    foreach ($extList as $e) {
                        if ((string) ($e['extension'] ?? $e['id'] ?? '') === (string) $user->voip_extension) {
                            $voipInfo = [
                                'online' => ! empty($e['online']),
                                'in_call' => ! empty($e['in_call']),
                                'status' => $e['status'] ?? 'offline',
                            ];
                            break;
                        }
                    }
                }
            } catch (Throwable) {
                // Ignore PBX error
            }
        }

        // Tab Data Queries
        $assignedLeads = Lead::query()
            ->where('assigned_user_id', $user->id)
            ->with(['status.stage'])
            ->orderByDesc('updated_at')
            ->paginate(15, ['*'], 'leads_page')
            ->withQueryString();

        $recentFollowups = LeadFollowup::query()
            ->where('user_id', $user->id)
            ->with(['lead', 'toStatus.stage'])
            ->orderByDesc('followed_up_at')
            ->paginate(15, ['*'], 'followups_page')
            ->withQueryString();

        $recentDonations = Donation::query()
            ->where('recorded_by_user_id', $user->id)
            ->with(['lead', 'donationType'])
            ->orderByDesc('donated_at')
            ->paginate(15, ['*'], 'donations_page')
            ->withQueryString();

        $recentCollections = CollectionCase::query()
            ->where('assigned_collector_user_id', $user->id)
            ->with(['lead'])
            ->orderByDesc('due_at')
            ->paginate(15, ['*'], 'collections_page')
            ->withQueryString();

        // 6. Timeline Activity Breakdown for Chart
        $daysInPeriod = 7;
        $timelineLabels = [];
        $timelineActivity = [];
        for ($i = $daysInPeriod - 1; $i >= 0; $i--) {
            $day = now()->subDays($i);
            $timelineLabels[] = $day->format('m/d');
            $dayCount = LeadFollowup::query()
                ->where('user_id', $user->id)
                ->whereDate('followed_up_at', $day->toDateString())
                ->count();
            $timelineActivity[] = $dayCount;
        }

        return view('reports.employee-profile', [
            'viewer' => $viewer,
            'employee' => $user,
            'activeTab' => $activeTab,
            'kpis' => [
                'assigned_leads' => $assignedLeadsCount,
                'converted_donors' => $convertedDonorsCount,
                'conversion_rate' => $conversionRate,
                'total_followups' => $totalFollowups,
                'calls_count' => $callsCount,
                'meetings_count' => $meetingsCount,
                'whatsapp_count' => $whatsappCount,
                'donations_count' => $donationsCount,
                'donations_amount' => $donationsAmount,
                'assigned_cases' => $assignedCasesCount,
                'completed_cases' => $completedCasesCount,
                'collected_amount' => $collectedAmount,
                'total_generated_value' => $donationsAmount + $collectedAmount,
            ],
            'voipInfo' => $voipInfo,
            'assignedLeads' => $assignedLeads,
            'recentFollowups' => $recentFollowups,
            'recentDonations' => $recentDonations,
            'recentCollections' => $recentCollections,
            'timelineChart' => [
                'labels' => $timelineLabels,
                'data' => $timelineActivity,
            ],
            'channelsChart' => [
                'calls' => $callsCount,
                'meetings' => $meetingsCount,
                'whatsapp' => $whatsappCount,
            ],
            'filters' => [
                'period' => $period,
                'from' => $startDate?->format('Y-m-d') ?? ($validated['from'] ?? ''),
                'to' => $endDate?->format('Y-m-d') ?? ($validated['to'] ?? ''),
                'tab' => $activeTab,
            ],
        ]);
    }
}
