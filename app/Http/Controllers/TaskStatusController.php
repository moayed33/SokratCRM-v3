<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadStatus;
use App\Support\CrmDatabaseGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TaskStatusController extends Controller
{
    private const PER_PAGE = 30;

    private const STATUS_MAP = [
        'new' => 'new',
        'no-answer' => 'no_answer',
        'no_answer' => 'no_answer',
        'not-interested' => 'not_interested',
        'not_interested' => 'not_interested',
        'donor' => 'donor',
    ];

    private const STATUS_LABELS = [
        'new' => 'جديد',
        'no_answer' => 'لم يتم الرد',
        'not_interested' => 'غير مهتم',
        'donor' => 'متبرع',
    ];

    private const SCOPE_LABELS = [
        'today' => 'يجب التواصل معهم اليوم',

        'overdue' => 'المتابعات المتأخرة',

        'upcoming' => 'المتابعات القادمة',
    ];

    private const SCOPE_DESCRIPTIONS = [
        'today' => 'العملاء المطلوب التواصل معهم اليوم.',

        'overdue' => 'العملاء الذين مر موعد متابعتهم.',

        'upcoming' => 'العملاء الذين لديهم متابعة قادمة.',
    ];

    public function show(
        Request $request,
        string $status
    ): View|RedirectResponse {

        $this->assertCrmV2Database();
        $user = $request->user();

        abort_unless(
            array_key_exists(
                $status,
                self::STATUS_MAP
            ),
            404
        );

        $scope = trim(
            (string) $request->query(
                'scope',
                'today'
            )
        );

        abort_unless(
            array_key_exists(
                $scope,
                self::SCOPE_LABELS
            ),
            404
        );
        $canonicalSlug = self::STATUS_MAP[$status];

        $statusRecord =
            LeadStatus::query()
                ->with('stage')
                ->where(
                    'code',
                    $canonicalSlug
                )
                ->firstOrFail();

        $todayStart =
            now()->startOfDay();

        $todayEnd =
            now()->endOfDay();

        $baseQuery =
            Lead::query()
                ->accessibleTo($user)
                ->with([
                    'status.stage',
                    'assignedUser:id,name',
                ])
                ->where(
                    'lead_status_id',
                    $statusRecord->id
                )
                ->whereNotNull(
                    'next_follow_up_at'
                );

        $todayCount =
            (clone $baseQuery)
                ->whereBetween(
                    'next_follow_up_at',
                    [
                        $todayStart,
                        $todayEnd,
                    ]
                )
                ->count();

        $overdueCount =
            (clone $baseQuery)
                ->where(
                    'next_follow_up_at',
                    '<',
                    $todayStart
                )
                ->count();

        $upcomingCount =
            (clone $baseQuery)
                ->where(
                    'next_follow_up_at',
                    '>',
                    $todayEnd
                )
                ->count();

        $activeQuery =
            clone $baseQuery;

        if ($scope === 'today') {
            $activeQuery->whereBetween(
                'next_follow_up_at',
                [
                    $todayStart,
                    $todayEnd,
                ]
            );
        } elseif ($scope === 'overdue') {
            $activeQuery->where(
                'next_follow_up_at',
                '<',
                $todayStart
            );
        } else {
            $activeQuery->where(
                'next_follow_up_at',
                '>',
                $todayEnd
            );
        }

        $activeLeads =
            $activeQuery
                ->orderBy(
                    'next_follow_up_at'
                )
                ->orderBy('id')
                ->paginate(
                    self::PER_PAGE,
                    ['*'],
                    'page'
                )
                ->withQueryString();

        $totalStatusLeads =
            Lead::query()
                ->accessibleTo($user)
                ->where(
                    'lead_status_id',
                    $statusRecord->id
                )
                ->count();

        $withoutFollowUpCount =
            Lead::query()
                ->accessibleTo($user)
                ->where(
                    'lead_status_id',
                    $statusRecord->id
                )
                ->whereNull(
                    'next_follow_up_at'
                )
                ->count();

        $totalLeads =
            Lead::query()
                ->accessibleTo($user)
                ->count();

        return view(
            'tasks.status',
            [
                'statusSlug' => $canonicalSlug,
                'statusRecord' => $statusRecord,

                'statusLinks' => self::STATUS_LABELS,

                'scopeLinks' => self::SCOPE_LABELS,

                'activeScope' => $scope,

                'activeScopeLabel' => self::SCOPE_LABELS[
                        $scope
                    ],

                'activeScopeDescription' => self::SCOPE_DESCRIPTIONS[
                        $scope
                    ],

                'scopeCounts' => [
                    'today' => $todayCount,

                    'overdue' => $overdueCount,

                    'upcoming' => $upcomingCount,
                ],

                'activeLeads' => $activeLeads,

                'statusColor' => $this->safeColor(
                    (string)
                        $statusRecord->color
                ),

                'stageColor' => $this->safeColor(
                    (string) (
                        $statusRecord
                            ->stage
                            ?->color
                        ?? ''
                    )
                ),

                'todayLabel' => now()->format(
                    'Y-m-d'
                ),

                'totalStatusLeads' => $totalStatusLeads,

                'withoutFollowUpCount' => $withoutFollowUpCount,

                'totalLeads' => $totalLeads,
            ]
        );
    }

    private function safeColor(
        string $color
    ): string {
        $color = trim($color);

        return preg_match(
            '/^#[0-9a-fA-F]{6}$/',
            $color
        ) === 1
            ? $color
            : '#64748b';
    }

    private function assertCrmV2Database(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}
