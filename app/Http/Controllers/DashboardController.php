<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\User;
use App\Services\VoipService;
use App\Support\CrmDatabaseGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private const STATUS_UI = [
        'new' => [
            'slug' => 'new',
            'icon' => '＋',
            'class' => '',
            'kanban_class' => 'new',
        ],
        'no_answer' => [
            'slug' => 'no-answer',
            'icon' => '☎',
            'class' => 'orange',
            'kanban_class' => 'no-answer',
        ],
        'interested' => [
            'slug' => 'interested',
            'icon' => '♥',
            'class' => 'green',
            'kanban_class' => 'interested',
        ],
        'not_interested' => [
            'slug' => 'not-interested',
            'icon' => '×',
            'class' => 'red',
            'kanban_class' => 'not-interested',
        ],
        'meeting' => [
            'slug' => 'meeting',
            'icon' => '□',
            'class' => 'purple',
            'kanban_class' => 'meeting',
        ],
        'quotation' => [
            'slug' => 'quotation',
            'icon' => '▤',
            'class' => 'orange',
            'kanban_class' => 'quotation',
        ],
        'discussion' => [
            'slug' => 'discussion',
            'icon' => '◇',
            'class' => '',
            'kanban_class' => 'discussion',
        ],
        'contract_closed' => [
            'slug' => 'contract-closing',
            'icon' => '✓',
            'class' => 'green',
            'kanban_class' => 'contract',
        ],
        'execution' => [
            'slug' => 'execution',
            'icon' => '⚙',
            'class' => 'purple',
            'kanban_class' => 'execution',
        ],
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

        $filters = $this->resolveFilters(
            $request
        );

        $statuses = LeadStatus::query()
            ->with('stage')
            ->orderBy('position')
            ->get();

        $stages = PipelineStage::query()
            ->with('statuses')
            ->where('is_active', true)
            ->orderBy('position')
            ->get();

        $leadBase = Lead::query()
            ->accessibleTo($user);

        $this->applyLeadFilters(
            $leadBase,
            $filters
        );

        $totalLeads = (clone $leadBase)
            ->count();

        $statusCounts = [];

        foreach ($statuses as $status) {
            $statusCounts[$status->code] =
                (clone $leadBase)
                    ->where(
                        'lead_status_id',
                        $status->id
                    )
                    ->count();
        }

        $statusCards = [];

        foreach ($statuses as $status) {
            $ui = self::STATUS_UI[
                $status->code
            ] ?? [
                'slug' => $status->code,
                'icon' => '•',
                'class' => '',
                'kanban_class' => '',
            ];

            $statusCards[] = [
                'id' => $status->id,
                'code' => $status->code,
                'name' => $status->name_ar,
                'count' => (int) (
                    $statusCounts[
                        $status->code
                    ] ?? 0
                ),
                'color' => (
                    $status->color
                    ?: '#3478f6'
                ),
                'stage_color' => (
                    $status->stage?->color
                    ?: $status->color
                    ?: '#3478f6'
                ),
                'slug' => $ui['slug'],
                'icon' => $ui['icon'],
                'class' => $ui['class'],
            ];
        }

        $stageCards = [];

        foreach ($stages as $stage) {
            $stageStatuses = [];

            $stageTotal = 0;

            foreach ($stage->statuses as $status) {
                $count = (int) (
                    $statusCounts[
                        $status->code
                    ] ?? 0
                );

                $stageTotal += $count;

                $stageStatuses[] = [
                    'name' => $status->name_ar,
                    'count' => $count,
                ];
            }

            $stageCards[] = [
                'code' => $stage->code,
                'name' => $stage->name_ar,
                'description' => (
                    $stage->description_ar
                    ?: ''
                ),
                'position' => $stage->position,
                'color' => (
                    $stage->color
                    ?: '#3478f6'
                ),
                'total' => $stageTotal,
                'statuses' => $stageStatuses,
                'class' => match (
                    $stage->code
                ) {
                    'interest' => 'interest',
                    'negotiation' => 'negotiation',
                    'closing_execution' => 'closing',
                    default => 'start',
                },
            ];
        }

        $contractClosed = (int) (
            $statusCounts[
                'contract_closed'
            ] ?? 0
        );

        $contractRate = $totalLeads > 0
            ? round(
                (
                    $contractClosed
                    / $totalLeads
                ) * 100,
                1
            )
            : 0.0;

        $todayStart = now()
            ->startOfDay();

        $todayEnd = now()
            ->endOfDay();

        $datedLeadBase = clone $leadBase;

        $followupCounts = [
            'today' => (
                clone $datedLeadBase
            )
                ->whereNotNull(
                    'next_follow_up_at'
                )
                ->whereBetween(
                    'next_follow_up_at',
                    [
                        $todayStart,
                        $todayEnd,
                    ]
                )
                ->count(),

            'overdue' => (
                clone $datedLeadBase
            )
                ->whereNotNull(
                    'next_follow_up_at'
                )
                ->where(
                    'next_follow_up_at',
                    '<',
                    $todayStart
                )
                ->count(),

            'upcoming' => (
                clone $datedLeadBase
            )
                ->whereNotNull(
                    'next_follow_up_at'
                )
                ->where(
                    'next_follow_up_at',
                    '>',
                    $todayEnd
                )
                ->count(),

            'no_date' => (
                clone $datedLeadBase
            )
                ->whereNull(
                    'next_follow_up_at'
                )
                ->count(),
        ];

        $meetingStatus = $statuses
            ->firstWhere(
                'code',
                'meeting'
            );

        $meetingBase = clone $leadBase;

        if ($meetingStatus) {
            $meetingBase->where(
                'lead_status_id',
                $meetingStatus->id
            );
        } else {
            $meetingBase->whereRaw(
                '1 = 0'
            );
        }

        $meetingCounts = [
            'today' => (
                clone $meetingBase
            )
                ->whereNotNull(
                    'next_follow_up_at'
                )
                ->whereBetween(
                    'next_follow_up_at',
                    [
                        $todayStart,
                        $todayEnd,
                    ]
                )
                ->count(),

            'overdue' => (
                clone $meetingBase
            )
                ->whereNotNull(
                    'next_follow_up_at'
                )
                ->where(
                    'next_follow_up_at',
                    '<',
                    $todayStart
                )
                ->count(),

            'upcoming' => (
                clone $meetingBase
            )
                ->whereNotNull(
                    'next_follow_up_at'
                )
                ->where(
                    'next_follow_up_at',
                    '>',
                    $todayEnd
                )
                ->count(),
        ];

        $latestFollowups =
            LeadFollowup::query()
                ->with([
                    'lead.status',
                    'toStatus',
                    'user:id,name',
                ])
                ->whereHas(
                    'lead',
                    function (
                        Builder $query
                    ) use ($filters, $user): void {
                        $query->accessibleTo($user);
                        $this->applyLeadFilters(
                            $query,
                            $filters
                        );
                    }
                )
                ->orderByDesc(
                    'followed_up_at'
                )
                ->orderByDesc('id')
                ->limit(8)
                ->get();

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

        $distribution = [];

        foreach ($statusCards as $card) {
            $percentage = $totalLeads > 0
                ? round(
                    (
                        $card['count']
                        / $totalLeads
                    ) * 100,
                    1
                )
                : 0.0;

            $distribution[] =
                $card + [
                    'percentage' => $percentage,
                ];
        }

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

        return view(
            'dashboard',
            [
                'totalLeads' => $totalLeads,

                'statusCards' => $statusCards,

                'stageCards' => $stageCards,

                'distribution' => $distribution,

                'contractRate' => $contractRate,

                'followupCounts' => $followupCounts,

                'meetingCounts' => $meetingCounts,

                'latestFollowups' => $latestFollowups,

                'communicationLabels' => self::COMMUNICATION_LABELS,

                'employees' => $employees,

                'filters' => $filters,

                'voipStatus' => $voipStatus,
            ]
        );
    }

    public function kanban(Request $request)
    {

        $this->assertCrmDatabase();
        $user = $request->user();

        $todayStart = now()
            ->startOfDay();

        $todayEnd = now()
            ->endOfDay();

        $statuses = LeadStatus::query()
            ->with('stage')
            ->orderBy('position')
            ->get();

        $kanbanColumns = [];

        $totalLeads = 0;

        foreach ($statuses as $status) {
            $ui = self::STATUS_UI[
                $status->code
            ] ?? [
                'slug' => $status->code,
                'icon' => '•',
                'class' => '',
                'kanban_class' => '',
            ];

            /*
             * One read-only query per status.
             * Customers are divided in memory into:
             * today / overdue / upcoming.
             */
            $leads = Lead::query()
                ->accessibleTo($user)
                ->with('assignedUser:id,name')
                ->where(
                    'lead_status_id',
                    $status->id
                )
                ->orderByRaw(
                    'next_follow_up_at IS NULL'
                )
                ->orderBy(
                    'next_follow_up_at'
                )
                ->orderByDesc(
                    'updated_at'
                )
                ->get();

            $totalCount =
                $leads->count();

            $totalLeads +=
                $totalCount;

            $datedLeads =
                $leads->filter(
                    static fn (
                        Lead $lead
                    ): bool => $lead
                        ->next_follow_up_at
                        !== null
                );

            $todayLeads =
                $datedLeads
                    ->filter(
                        static function (
                            Lead $lead
                        ) use (
                            $todayStart,
                            $todayEnd
                        ): bool {
                            $at =
                                $lead
                                    ->next_follow_up_at;

                            return $at !== null
                                && $at->gte(
                                    $todayStart
                                )
                                && $at->lte(
                                    $todayEnd
                                );
                        }
                    )
                    ->sortBy(
                        static fn (
                            Lead $lead
                        ): int => $lead
                            ->next_follow_up_at
                            ?->getTimestamp()
                            ?? 0
                    )
                    ->values();

            $overdueLeads =
                $datedLeads
                    ->filter(
                        static fn (
                            Lead $lead
                        ): bool => $lead
                            ->next_follow_up_at
                            ?->lt(
                                $todayStart
                            )
                            ?? false
                    )
                    ->sortByDesc(
                        static fn (
                            Lead $lead
                        ): int => $lead
                            ->next_follow_up_at
                            ?->getTimestamp()
                            ?? 0
                    )
                    ->values();

            $upcomingLeads =
                $datedLeads
                    ->filter(
                        static fn (
                            Lead $lead
                        ): bool => $lead
                            ->next_follow_up_at
                            ?->gt(
                                $todayEnd
                            )
                            ?? false
                    )
                    ->sortBy(
                        static fn (
                            Lead $lead
                        ): int => $lead
                            ->next_follow_up_at
                            ?->getTimestamp()
                            ?? 0
                    )
                    ->values();

            $scopeLeads = [
                'today' => $todayLeads,

                'overdue' => $overdueLeads,

                'upcoming' => $upcomingLeads,
            ];

            $scopeCounts = [
                'today' => $todayLeads->count(),

                'overdue' => $overdueLeads->count(),

                'upcoming' => $upcomingLeads->count(),
            ];

            $noDateCount =
                $leads->filter(
                    static fn (
                        Lead $lead
                    ): bool => $lead
                        ->next_follow_up_at
                        === null
                )->count();

            $kanbanColumns[] = [
                'status_id' => $status->id,

                'code' => $status->code,

                'name' => $status->name_ar,

                'slug' => $ui['slug'],

                'icon' => $ui['icon'],

                'class' => $ui[
                        'kanban_class'
                    ],

                'status_color' => (
                    $status->color
                    ?: '#3478f6'
                ),

                'stage_name' => (
                    $status
                        ->stage
                        ?->name_ar
                    ?: ''
                ),

                'stage_color' => (
                    $status
                        ->stage
                        ?->color
                    ?: $status->color
                    ?: '#3478f6'
                ),

                'total_count' => $totalCount,

                'no_date_count' => $noDateCount,

                'scope_counts' => $scopeCounts,

                'scope_leads' => $scopeLeads,
            ];
        }

        return view(
            'kanban',
            [
                'kanbanColumns' => $kanbanColumns,

                'totalLeads' => $totalLeads,
            ]
        );
    }

    private function assertCrmDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }

    private function resolveFilters(
        Request $request
    ): array {
        $employee = trim(
            (string) $request->query(
                'employee',
                ''
            )
        );

        $period = (string)
            $request->query(
                'period',
                'all'
            );

        if (
            ! in_array(
                $period,
                [
                    'all',
                    'today',
                    'week',
                    'month',
                ],
                true
            )
        ) {
            $period = 'all';
        }

        $fromInput = trim(
            (string) $request->query(
                'from',
                ''
            )
        );

        $toInput = trim(
            (string) $request->query(
                'to',
                ''
            )
        );

        $fromDate = $this->parseDate(
            $fromInput,
            false
        );

        $toDate = $this->parseDate(
            $toInput,
            true
        );

        if (
            ! $fromDate
            && ! $toDate
        ) {
            $now = now();

            if ($period === 'today') {
                $fromDate = $now
                    ->copy()
                    ->startOfDay();

                $toDate = $now
                    ->copy()
                    ->endOfDay();
            }

            if ($period === 'week') {
                $fromDate = $now
                    ->copy()
                    ->startOfWeek();

                $toDate = $now
                    ->copy()
                    ->endOfWeek();
            }

            if ($period === 'month') {
                $fromDate = $now
                    ->copy()
                    ->startOfMonth();

                $toDate = $now
                    ->copy()
                    ->endOfMonth();
            }
        }

        return [
            'employee' => $employee,
            'period' => $period,
            'from' => (
                $fromDate
                && $fromInput !== ''
                    ? $fromInput
                    : ''
            ),
            'to' => (
                $toDate
                && $toInput !== ''
                    ? $toInput
                    : ''
            ),
            'from_date' => $fromDate,
            'to_date' => $toDate,
        ];
    }

    private function parseDate(
        string $value,
        bool $endOfDay
    ): ?Carbon {
        if (
            $value === ''
            || ! preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $value
            )
        ) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat(
                'Y-m-d',
                $value,
                config('app.timezone')
            );

            return $endOfDay
                ? $date->endOfDay()
                : $date->startOfDay();
        } catch (\Throwable) {
            return null;
        }
    }

    private function applyLeadFilters(
        Builder $query,
        array $filters
    ): void {
        if (
            $filters['employee'] !== ''
        ) {
            $query->where(
                function (Builder $employeeQuery) use ($filters): void {
                    $employeeQuery
                        ->whereHas(
                            'assignedUser',
                            function (Builder $userQuery) use ($filters): void {
                                $userQuery->where(
                                    'name',
                                    $filters['employee']
                                );
                            }
                        )
                        ->orWhere(
                            function (Builder $legacyQuery) use ($filters): void {
                                $legacyQuery
                                    ->whereNull('assigned_user_id')
                                    ->where(
                                        'assigned_employee',
                                        $filters['employee']
                                    );
                            }
                        );
                }
            );
        }

        if (
            $filters['from_date']
            instanceof Carbon
        ) {
            $query->where(
                'created_at',
                '>=',
                $filters['from_date']
            );
        }

        if (
            $filters['to_date']
            instanceof Carbon
        ) {
            $query->where(
                'created_at',
                '<=',
                $filters['to_date']
            );
        }
    }
}
