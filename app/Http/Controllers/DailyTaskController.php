<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\CrmDatabaseGuard;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DailyTaskController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request): View
    {
        $this->assertCrmDatabase();
        $user = $request->user();

        abort_unless(
            $user !== null && $user->hasPermission(CrmPermission::TASKS_VIEW),
            403
        );

        $now = now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $next7Days = $now->copy()->addDays(7)->endOfDay();

        // Extract and sanitize filter params
        $scope = trim((string) $request->query('scope', 'all'));
        $validScopes = ['all', 'overdue', 'today', 'upcoming', 'no_date', 'completed'];
        if (!in_array($scope, $validScopes, true)) {
            $scope = 'all';
        }

        $search = trim((string) $request->query('search', ''));
        $statusId = $request->filled('status_id') ? (int) $request->query('status_id') : null;
        $stageId = $request->filled('stage_id') ? (int) $request->query('stage_id') : null;
        $employeeId = $request->filled('employee_id') ? (int) $request->query('employee_id') : null;
        $sort = trim((string) $request->query('sort', 'followup_asc'));
        $viewMode = trim((string) $request->query('view', 'cards'));
        if (!in_array($viewMode, ['cards', 'timeline', 'table'], true)) {
            $viewMode = 'cards';
        }

        // Apply shared filters builder
        $applySharedFilters = static function (Builder $query) use ($search, $statusId, $stageId, $employeeId): void {
            if ($search !== '') {
                $query->where(static function (Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhereHas('phones', static fn ($pq) => $pq->where('phone', 'like', "%{$search}%"));
                });
            }

            if ($statusId !== null && $statusId > 0) {
                $query->where('lead_status_id', $statusId);
            }

            if ($stageId !== null && $stageId > 0) {
                $query->whereHas('status', static fn (Builder $q): Builder => $q->where('pipeline_stage_id', $stageId));
            }

            if ($employeeId !== null && $employeeId > 0) {
                $query->where('assigned_user_id', $employeeId);
            }
        };

        // Base query for accessible leads
        $baseAccessibleQuery = Lead::query()->accessibleTo($user);
        $filteredCountBase = clone $baseAccessibleQuery;
        $applySharedFilters($filteredCountBase);

        // Calculate accurate KPI metrics for today respecting active filters
        $overdueCount = (clone $filteredCountBase)
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '<', $todayStart)
            ->count();

        $todayCount = (clone $filteredCountBase)
            ->whereNotNull('next_follow_up_at')
            ->whereBetween('next_follow_up_at', [$todayStart, $todayEnd])
            ->count();

        $upcomingCount = (clone $filteredCountBase)
            ->whereNotNull('next_follow_up_at')
            ->where('next_follow_up_at', '>', $todayEnd)
            ->count();

        $noDateCount = (clone $filteredCountBase)
            ->whereNull('next_follow_up_at')
            ->count();

        $completedTodayQuery = LeadFollowup::query()
            ->whereBetween('followed_up_at', [$todayStart, $todayEnd])
            ->whereHas('lead', static fn (Builder $q): Builder => $q->accessibleTo($user));

        if ($search !== '') {
            $completedTodayQuery->whereHas('lead', static function (Builder $q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%")
                    ->orWhere('company_name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhereHas('phones', static fn ($pq) => $pq->where('phone', 'like', "%{$search}%"));
            });
        }

        if ($statusId !== null && $statusId > 0) {
            $completedTodayQuery->where(static function (Builder $q) use ($statusId): void {
                $q->where('to_status_id', $statusId)
                    ->orWhereHas('lead', static fn (Builder $lq): Builder => $lq->where('lead_status_id', $statusId));
            });
        }

        if ($stageId !== null && $stageId > 0) {
            $completedTodayQuery->where(static function (Builder $q) use ($stageId): void {
                $q->whereHas('toStatus', static fn (Builder $sq): Builder => $sq->where('pipeline_stage_id', $stageId))
                    ->orWhereHas('lead.status', static fn (Builder $lq): Builder => $lq->where('pipeline_stage_id', $stageId));
            });
        }
        if ($employeeId !== null && $employeeId > 0) {
            $completedTodayQuery->where(static function (Builder $q) use ($employeeId): void {
                $q->where('user_id', $employeeId)
                    ->orWhereHas('lead', static fn (Builder $lq): Builder => $lq->where('assigned_user_id', $employeeId));
            });
        }

        $completedTodayCount = $completedTodayQuery->count();

        $totalDueToday = $overdueCount + $todayCount;
        $progressTotal = $completedTodayCount + $totalDueToday;
        $completionRate = $progressTotal > 0
            ? (int) round(($completedTodayCount / $progressTotal) * 100)
            : 0;

        // Query leads with all necessary relationships
        $leadsBase = Lead::query()
            ->accessibleTo($user)
            ->with([
                'status.stage',
                'assignedUser:id,name',
                'creator:id,name',
                'followups' => static fn ($q) => $q->with(['user:id,name', 'fromStatus', 'toStatus'])
                    ->orderByDesc('followed_up_at')
                    ->orderByDesc('id')
                    ->limit(1),
            ]);

        $applySharedFilters($leadsBase);

        // Sorting logic
        $applySorting = static function (Builder $query) use ($sort): void {
            match ($sort) {
                'followup_desc' => $query->orderByDesc('next_follow_up_at')->orderByDesc('id'),
                'name_asc' => $query->orderBy('name')->orderByDesc('id'),
                'created_desc' => $query->orderByDesc('created_at')->orderByDesc('id'),
                default => $query->orderByRaw('next_follow_up_at IS NULL')
                    ->orderBy('next_follow_up_at')
                    ->orderBy('id'),
            };
        };

        // Build unified paginated query based on selected scope
        $query = clone $leadsBase;
        $paginatedTasks = null;
        $completedTodayFollowups = null;

        if ($scope === 'overdue') {
            $query->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '<', $todayStart);
            $applySorting($query);
            $paginatedTasks = $query->paginate(self::PER_PAGE)->withQueryString();
        } elseif ($scope === 'today') {
            $query->whereNotNull('next_follow_up_at')
                ->whereBetween('next_follow_up_at', [$todayStart, $todayEnd]);
            $applySorting($query);
            $paginatedTasks = $query->paginate(self::PER_PAGE)->withQueryString();
        } elseif ($scope === 'upcoming') {
            $query->whereNotNull('next_follow_up_at')
                ->where('next_follow_up_at', '>', $todayEnd);
            $applySorting($query);
            $paginatedTasks = $query->paginate(self::PER_PAGE)->withQueryString();
        } elseif ($scope === 'no_date') {
            $query->whereNull('next_follow_up_at');
            $query->orderByDesc('created_at');
            $paginatedTasks = $query->paginate(self::PER_PAGE)->withQueryString();
        } elseif ($scope === 'all') {
            // Default scope: paginate all actionable tasks sorted by urgency or custom sort
            $applySorting($query);
            $paginatedTasks = $query->paginate(self::PER_PAGE)->withQueryString();
        } elseif ($scope === 'completed') {
            $completedQuery = LeadFollowup::query()
                ->whereBetween('followed_up_at', [$todayStart, $todayEnd])
                ->whereHas('lead', static fn (Builder $q): Builder => $q->accessibleTo($user))
                ->with([
                    'lead.status.stage',
                    'lead.assignedUser:id,name',
                    'user:id,name',
                    'fromStatus',
                    'toStatus',
                ])
                ->orderByDesc('followed_up_at')
                ->orderByDesc('id');

            if ($search !== '') {
                $completedQuery->whereHas('lead', static function (Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%")
                        ->orWhere('company_name', 'like', "%{$search}%");
                });
            }

            if ($statusId !== null && $statusId > 0) {
                $completedQuery->where(static function (Builder $q) use ($statusId): void {
                    $q->where('to_status_id', $statusId)
                        ->orWhereHas('lead', static fn (Builder $lq): Builder => $lq->where('lead_status_id', $statusId));
                });
            }

            if ($stageId !== null && $stageId > 0) {
                $completedQuery->where(static function (Builder $q) use ($stageId): void {
                    $q->whereHas('toStatus', static fn (Builder $sq): Builder => $sq->where('pipeline_stage_id', $stageId))
                        ->orWhereHas('lead.status', static fn (Builder $lq): Builder => $lq->where('pipeline_stage_id', $stageId));
                });
            }

            if ($employeeId !== null && $employeeId > 0) {
                $completedQuery->where('user_id', $employeeId);
            }

            $completedTodayFollowups = $completedQuery->paginate(self::PER_PAGE)->withQueryString();
        }

        // Data for filters and modals
        $statuses = LeadStatus::query()
            ->with('stage')
            ->whereHas('stage', static fn (Builder $q): Builder => $q->where('is_active', true))
            ->orderBy('position')
            ->get();

        $stages = PipelineStage::activeOrdered();

        $assignableUsers = collect();
        if ($user->isSuperAdmin() || $user->hasPermission(CrmPermission::LEADS_SCOPE_ALL) || $user->hasPermission(CrmPermission::LEADS_SCOPE_GROUP)) {
            $assignableUsers = User::query()
                ->where('is_active', true)
                ->select(['id', 'name'])
                ->orderBy('name')
                ->get();
        } else {
            $assignableUsers = collect([$user]);
        }

        $communicationTypes = [
            'call' => __('crm.communication_call'),
            'whatsapp' => __('crm.communication_whatsapp'),
            'email' => __('crm.communication_email'),
            'meeting' => __('crm.communication_meeting'),
            'other' => __('crm.communication_other'),
        ];

        return view('tasks.daily', [
            'scope' => $scope,
            'search' => $search,
            'statusId' => $statusId,
            'stageId' => $stageId,
            'employeeId' => $employeeId,
            'sort' => $sort,
            'viewMode' => $viewMode,
            'overdueCount' => $overdueCount,
            'todayCount' => $todayCount,
            'upcomingCount' => $upcomingCount,
            'noDateCount' => $noDateCount,
            'completedTodayCount' => $completedTodayCount,
            'totalDueToday' => $totalDueToday,
            'completionRate' => $completionRate,
            'paginatedTasks' => $paginatedTasks,
            'completedTodayFollowups' => $completedTodayFollowups,
            'statuses' => $statuses,
            'stages' => $stages,
            'assignableUsers' => $assignableUsers,
            'communicationTypes' => $communicationTypes,
            'todayDateFormatted' => app()->getLocale() === 'ar'
                ? $now->locale('ar')->translatedFormat('l، d F Y')
                : $now->locale('en')->isoFormat('dddd, MMMM D, YYYY'),
        ]);
    }

    public function reschedule(Request $request, Lead $lead): JsonResponse|RedirectResponse
    {
        $this->assertCrmDatabase();
        $user = $request->user();

        abort_unless(
            $user !== null && $lead->isAccessibleTo($user) && ($user->hasPermission(CrmPermission::LEADS_UPDATE) || $user->hasPermission(CrmPermission::TASKS_VIEW)),
            403
        );

        $validated = $request->validate([
            'next_follow_up_at' => ['required', 'date'],
            'reschedule_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $nextDate = Carbon::parse($validated['next_follow_up_at']);
        $lead->update([
            'next_follow_up_at' => $nextDate,
        ]);

        if (!empty($validated['reschedule_reason'])) {
            LeadFollowup::create([
                'lead_id' => $lead->id,
                'from_status_id' => $lead->lead_status_id,
                'to_status_id' => $lead->lead_status_id,
                'employee_name' => $user->name ?? 'System',
                'user_id' => $user->id,
                'communication_type' => 'other',
                'outcome' => __('crm.reschedule_task') . ': ' . $validated['reschedule_reason'],
                'next_follow_up_at' => $nextDate,
                'followed_up_at' => now(),
            ]);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => __('crm.task_rescheduled_success'),
                'lead_id' => $lead->id,
                'next_follow_up_at' => $nextDate->format('Y-m-d H:i'),
            ]);
        }

        return back()->with('status', __('crm.task_rescheduled_success'));
    }


    private function assertCrmDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}
