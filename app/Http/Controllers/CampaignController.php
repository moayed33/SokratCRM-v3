<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreCampaignRequest;
use App\Models\Branch;
use App\Models\Campaign;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Models\User;
use App\Security\CrmPermission;
use App\Security\LeadAssignment;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CampaignController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $filters = [
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 150),
            'state' => (string) $request->query('state', ''),
            'user_id' => $request->integer('user_id'),
        ];

        if (! in_array($filters['state'], ['', 'active', 'upcoming', 'ended'], true)) {
            $filters['state'] = '';
        }

        $query = Campaign::query()
            ->with([
                'branch:id,name_ar,name_en,code',
                'creator:id,name',
                'users:id,name',
            ])
            ->withCount('leads');

        if (! $user->isSuperAdmin()) {
            $query->where(function ($campaignQuery) use ($user): void {
                $campaignQuery->whereHas(
                    'users',
                    static fn ($userQuery) => $userQuery->whereKey($user->id),
                );

                if ($user->hasPermission(CrmPermission::CAMPAIGNS_CREATE)) {
                    $campaignQuery->orWhere(
                        'created_by_user_id',
                        $user->id,
                    );
                }
            });
        }

        if ($filters['q'] !== '') {
            $query->where('name', 'like', '%'.$filters['q'].'%');
        }

        match ($filters['state']) {
            'active' => $query
                ->where('starts_at', '<=', now())
                ->where('ends_at', '>=', now()),
            'upcoming' => $query->where('starts_at', '>', now()),
            'ended' => $query->where('ends_at', '<', now()),
            default => null,
        };

        if ($filters['user_id'] > 0) {
            $query->whereHas(
                'users',
                static fn ($userQuery) => $userQuery->whereKey(
                    $filters['user_id'],
                ),
            );
        }

        $filterUsers = User::query()
            ->where('is_active', true)
            ->whereHas('campaigns', function ($campaignQuery) use ($user): void {
                if (! $user->isSuperAdmin()) {
                    $campaignQuery->where(function ($visibleQuery) use ($user): void {
                        $visibleQuery->whereHas(
                            'users',
                            static fn ($memberQuery) => $memberQuery->whereKey($user->id),
                        );

                        if ($user->hasPermission(CrmPermission::CAMPAIGNS_CREATE)) {
                            $visibleQuery->orWhere('created_by_user_id', $user->id);
                        }
                    });
                }
            })
            ->orderBy('name')
            ->get(['id', 'name']);

        $campaigns = $query
            ->orderByDesc('starts_at')
            ->paginate(20);

        $campaigns->getCollection()->each(
            fn (Campaign $campaign) => $campaign->setAttribute(
                'can_manage',
                $this->canManageCampaign($user, $campaign),
            ),
        );

        return view('campaigns.index', compact(
            'campaigns',
            'filters',
            'filterUsers',
        ));
    }

    public function create(): View
    {
        $users = User::query()
            ->where('is_active', true)
            ->with('groups:id,name')
            ->orderBy('name')
            ->get();

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get();

        return view('campaigns.create', compact('users', 'branches'));
    }

    public function store(StoreCampaignRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $imagePath = $request->file('image')?->store(
            'campaign-images',
            'public',
        );

        if ($request->hasFile('image') && ! $imagePath) {
            throw new \RuntimeException('Campaign image could not be stored.');
        }

        try {
            $campaign = DB::transaction(function () use (
                $imagePath,
                $request,
                $validated,
            ): Campaign {
                $branchId = ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : ($request->user()->branch_id ?? null);
                $campaign = Campaign::query()->create([
                    'branch_id' => $branchId,
                    'name' => trim($validated['name']),
                    'image_path' => $imagePath,
                    'cost' => $validated['cost'],
                    'starts_at' => $validated['starts_at'],
                    'ends_at' => $validated['ends_at'],
                    'created_by_user_id' => $request->user()->id,
                ]);
                $campaign->users()->sync($validated['user_ids']);

                return $campaign;
            });
        } catch (\Throwable $exception) {
            if ($imagePath !== null) {
                Storage::disk('public')->delete($imagePath);
            }

            throw $exception;
        }

        return redirect()
            ->route('v2.campaigns.index')
            ->with(
                'success',
                'تم إنشاء حملة "'.$campaign->name.'" وتعيين المستخدمين بنجاح.',
            );
    }

    public function edit(Request $request, Campaign $campaign): View
    {
        $this->ensureCanManageCampaign($request->user(), $campaign);

        $campaign->load('users:id');
        $users = User::query()
            ->where('is_active', true)
            ->with('groups:id,name')
            ->orderBy('name')
            ->get();

        $branches = Branch::query()
            ->where('is_active', true)
            ->orderBy('name_ar')
            ->get();
        return view('campaigns.create', [
            'campaign' => $campaign,
            'users' => $users,
            'branches' => $branches,
            'editing' => true,
        ]);
    }

    public function update(
        StoreCampaignRequest $request,
        Campaign $campaign,
    ): RedirectResponse {
        $this->ensureCanManageCampaign($request->user(), $campaign);
        $validated = $request->validated();
        $currentImagePath = $campaign->image_path;
        $newImagePath = $request->file('image')?->store(
            'campaign-images',
            'public',
        );

        if ($request->hasFile('image') && ! $newImagePath) {
            throw new \RuntimeException('Campaign image could not be stored.');
        }

        try {
            DB::transaction(function () use (
                $campaign,
                $newImagePath,
                $validated,
                $request,
            ): void {
                $branchId = array_key_exists('branch_id', $validated)
                    ? (! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null)
                    : ($request->user()->branch_id ?? $campaign->branch_id);

                $campaign->update([
                    'branch_id' => $branchId,
                    'name' => trim($validated['name']),
                    'image_path' => $newImagePath ?? $campaign->image_path,
                    'cost' => $validated['cost'],
                    'starts_at' => $validated['starts_at'],
                    'ends_at' => $validated['ends_at'],
                ]);
                $campaign->users()->sync($validated['user_ids']);
            });
        } catch (\Throwable $exception) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $exception;
        }
        if ($newImagePath !== null && $currentImagePath !== null) {
            Storage::disk('public')->delete($currentImagePath);
        }

        return redirect()
            ->route('v2.campaigns.show', $campaign)
            ->with('success', 'تم تحديث بيانات الحملة بنجاح.');
    }

    public function destroy(
        Request $request,
        Campaign $campaign,
    ): RedirectResponse {
        $this->ensureCanManageCampaign($request->user(), $campaign);
        $campaignName = $campaign->name;
        $imagePath = $campaign->image_path;

        $campaign->leads()->detach();
        $campaign->users()->detach();
        $campaign->delete();
        if ($imagePath !== null) {
            Storage::disk('public')->delete($imagePath);
        }

        return redirect()
            ->route('v2.campaigns.index')
            ->with('success', 'تم حذف حملة "'.$campaignName.'" بنجاح.');
    }

    public function show(Request $request, Campaign $campaign): View
    {
        $user = $request->user();
        $this->ensureCanViewCampaign($user, $campaign);

        $campaign->load([
            'creator:id,name',
            'users' => static fn ($query) => $query
                ->where('is_active', true)
                ->orderBy('name'),
        ]);

        $canManage = $this->canManageCampaign($user, $campaign);
        $filterUsers = collect([$user]);
        $selectedAssignee = $user;
        $showUnassigned = false;

        if ($canManage) {
            $filterUsers = collect($campaign->users->all());

            if (! $filterUsers->contains('id', $user->id)) {
                $filterUsers->push($user);
            }

            $filterUsers = $filterUsers
                ->sortBy('name')
                ->values();

            $requestedAssignee = (string) $request->query(
                'assigned_user_id',
                $user->id,
            );

            if ($requestedAssignee === 'unassigned') {
                $selectedAssignee = null;
                $showUnassigned = true;
            } elseif (ctype_digit($requestedAssignee)) {
                $selectedAssignee = $filterUsers->firstWhere(
                    'id',
                    (int) $requestedAssignee,
                ) ?? $user;
            }
        }

        $assignmentCounts = DB::table('campaign_lead')
            ->join('leads', 'leads.id', '=', 'campaign_lead.lead_id')
            ->where('campaign_lead.campaign_id', $campaign->id)
            ->selectRaw('leads.assigned_user_id, COUNT(*) as lead_count')
            ->groupBy('leads.assigned_user_id')
            ->get()
            ->mapWithKeys(static fn (object $row): array => [
                $row->assigned_user_id === null
                    ? 'unassigned'
                    : (string) $row->assigned_user_id => (int) $row->lead_count,
            ]);

        $pipelineStages = PipelineStage::activeOrdered()->load('statuses:id,pipeline_stage_id,name_ar,code');

        $baseCampaignLeadsQuery = DB::table('campaign_lead')
            ->join('leads', 'leads.id', '=', 'campaign_lead.lead_id')
            ->where('campaign_lead.campaign_id', $campaign->id);

        if ($showUnassigned) {
            $baseCampaignLeadsQuery->whereNull('leads.assigned_user_id');
        } elseif ($selectedAssignee !== null) {
            $baseCampaignLeadsQuery->where('leads.assigned_user_id', $selectedAssignee->id);
        }

        $stageLeadCounts = (clone $baseCampaignLeadsQuery)
            ->join('lead_statuses', 'lead_statuses.id', '=', 'leads.lead_status_id')
            ->selectRaw('lead_statuses.pipeline_stage_id, COUNT(*) as aggregate')
            ->groupBy('lead_statuses.pipeline_stage_id')
            ->pluck('aggregate', 'pipeline_stage_id')
            ->all();

        foreach ($pipelineStages as $stage) {
            $stage->campaign_leads_count = (int) ($stageLeadCounts[$stage->id] ?? 0);
        }

        $statuses = LeadStatus::query()
            ->withCount([
                'leads as campaign_leads_count' => static function (
                    $query,
                ) use (
                    $campaign,
                    $selectedAssignee,
                    $showUnassigned,
                ): void {
                    $query->whereHas(
                        'campaigns',
                        static fn ($campaignQuery) => $campaignQuery
                            ->whereKey($campaign->id),
                    );

                    if ($showUnassigned) {
                        $query->whereNull('assigned_user_id');
                    } else {
                        $query->where(
                            'assigned_user_id',
                            $selectedAssignee->id,
                        );
                    }
                },
            ])
            ->with('stage:id,name_ar')
            ->orderBy('position')
            ->get(['id', 'pipeline_stage_id', 'code', 'name_ar', 'color']);
        $statusParam = trim((string) $request->query('status', ''));
        $stageParam = trim((string) $request->query('stage', ''));

        $selectedStatus = $statusParam !== ''
            ? ($statuses->firstWhere('code', $statusParam) ?? $statuses->firstWhere('id', (int) $statusParam))
            : null;

        $selectedStage = $stageParam !== ''
            ? ($pipelineStages->firstWhere('code', $stageParam) ?? $pipelineStages->firstWhere('id', (int) $stageParam))
            : null;

        $leadsQuery = $campaign->leads()
            ->with([
                'status.stage:id,name_ar',
                'assignedUser:id,name',
            ])
            ->orderByDesc('leads.id');

        if ($showUnassigned) {
            $leadsQuery->whereNull('assigned_user_id');
        } else {
            $leadsQuery->where('assigned_user_id', $selectedAssignee->id);
        }

        if ($selectedStage !== null) {
            $leadsQuery->whereHas('status', function (Builder $sq) use ($selectedStage): void {
                $sq->where('pipeline_stage_id', $selectedStage->id);
            });
        } elseif ($selectedStatus !== null) {
            $leadsQuery->where('lead_status_id', $selectedStatus->id);
        }
        $leads = $leadsQuery
            ->paginate(30)
            ->withQueryString();

        $assignableUsers = $canManage
            ? $campaign->users
                ->filter(
                    static fn (User $target): bool => LeadAssignment::canAssignTo(
                        $user,
                        $target,
                    ),
                )
                ->values()
            : collect();

        return view('campaigns.show', compact(
            'campaign',
            'leads',
            'canManage',
            'assignableUsers',
            'filterUsers',
            'selectedAssignee',
            'showUnassigned',
            'assignmentCounts',
            'pipelineStages',
            'selectedStage',
            'statuses',
            'selectedStatus',
        ));
    }
    public function assignLeads(
        Request $request,
        Campaign $campaign,
    ): RedirectResponse {
        $actor = $request->user();
        $this->ensureCanManageCampaign($actor, $campaign);

        $validated = $request->validate(
            [
                'lead_ids' => ['required', 'array', 'min:1', 'max:1000'],
                'lead_ids.*' => [
                    'integer',
                    'distinct',
                    Rule::exists('campaign_lead', 'lead_id')
                        ->where('campaign_id', $campaign->id),
                ],
                'target_user_id' => [
                    'required',
                    'integer',
                    Rule::exists('campaign_user', 'user_id')
                        ->where('campaign_id', $campaign->id),
                ],
            ],
            [
                'lead_ids.required' => 'اختر عميلًا واحدًا على الأقل.',
                'target_user_id.required' => 'اختر المستخدم المسؤول.',
            ],
        );

        $target = User::query()->findOrFail(
            (int) $validated['target_user_id'],
        );

        if (! LeadAssignment::canAssignTo($actor, $target)) {
            throw ValidationException::withMessages([
                'target_user_id' => 'لا تملك صلاحية إسناد العملاء إلى هذا المستخدم.',
            ]);
        }

        $leadIds = array_map('intval', $validated['lead_ids']);

        $updated = DB::transaction(function () use (
            $campaign,
            $leadIds,
            $target,
        ): int {
            return Lead::query()
                ->whereIn('id', $leadIds)
                ->whereHas(
                    'campaigns',
                    static fn ($query) => $query->whereKey($campaign->id),
                )
                ->update([
                    'assigned_user_id' => $target->id,
                    'assigned_employee' => $target->name,
                ]);
        });

        return back()->with(
            'success',
            'تم إسناد '.number_format($updated).' عميل إلى '.$target->name.'.',
        );
    }

    private function ensureCanViewCampaign(
        User $user,
        Campaign $campaign,
    ): void {
        abort_unless(
            $this->canManageCampaign($user, $campaign)
            || $campaign->users()->whereKey($user->id)->exists(),
            403,
        );
    }

    private function ensureCanManageCampaign(
        User $user,
        Campaign $campaign,
    ): void {
        abort_unless($this->canManageCampaign($user, $campaign), 403);
    }

    private function canManageCampaign(
        User $user,
        Campaign $campaign,
    ): bool {
        return $user->isSuperAdmin()
            || (
                (int) $campaign->created_by_user_id === (int) $user->id
                && $user->hasPermission(CrmPermission::CAMPAIGNS_CREATE)
            );
    }
}
