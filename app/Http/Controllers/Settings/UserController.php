<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\Concerns\BuildsAccessPage;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Http\Requests\Settings\UpdateUserRequest;
use App\Models\Branch;
use App\Models\Group;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\VoipCallAnalytics;
use App\Services\VoipExtensionManager;
use App\Services\VoipService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    use BuildsAccessPage;

    public function index(Request $request): View
    {
        return $this->buildAccessPage($request, 'users');
    }

    public function create(Request $request): View
    {
        $voip = app(VoipService::class);
        $isVoipConnected = $voip->isConfigured() && $voip->isConnected();
        $actor = $request->user();
        $isSuper = $actor->isSuperAdmin();

        $branches = $isSuper
            ? Branch::query()->where('is_active', true)->orderBy('name_ar')->get()
            : Branch::query()->where('is_active', true)->where('id', $actor->branch_id)->orderBy('name_ar')->get();

        $managers = User::query()
            ->where('is_active', true)
            ->when(! $isSuper && $actor->branch_id !== null, static fn ($q) => $q->where('branch_id', $actor->branch_id))
            ->whereHas('groups', static fn ($gq) => $gq->whereIn('code', [Group::SUPER_ADMIN_CODE, Group::BRANCH_ADMIN_CODE, Group::MANAGER_CODE, 'collection-manager', 'team-leader', 'sales-manager']))
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'branch_id']);

        return view('settings.users.create', [
            'branches' => $branches,
            'managers' => $managers,
            'groups' => $this->availableGroups($actor),
            'salesEmployees' => $this->getSalesEmployees(null, $actor),
            'collectors' => $this->getCollectors(null, $actor),
            'currentSubordinateIds' => collect(),
            'voipExtensions' => $this->getVoipExtensions(),
            'isVoipConnected' => $isVoipConnected,
            'governorates' => \App\Models\Governorate::query()->where('is_active', true)->with('activeSubregions')->orderBy('name_ar')->get(),
        ]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $email = trim((string) ($validated['email'] ?? ''));
        $this->ensureCanAssignGroups(
            $request->user(),
            array_map('intval', $validated['group_ids']),
        );

        $subordinateIds = $validated['subordinate_ids'] ?? [];
        $subordinatesRendered = $request->boolean('subordinates_section_rendered');

        $groupIds = array_map('intval', $validated['group_ids']);
        $actor = $request->user();
        $isSuperAdminRole = Group::query()->whereIn('id', $groupIds)->where('code', Group::SUPER_ADMIN_CODE)->exists();
        $isCollectorRole = Group::query()->whereIn('id', $groupIds)->where('code', Group::COLLECTOR_CODE)->exists();

        $branchId = ! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null;
        if (! $actor->isSuperAdmin() && $actor->branch_id !== null) {
            $branchId = (int) $actor->branch_id;
        }

        $managerId = ! empty($validated['manager_id']) ? (int) $validated['manager_id'] : null;
        $collectionSubregionId = $isCollectorRole && ! empty($validated['collection_subregion_id']) ? (int) $validated['collection_subregion_id'] : null;
        $collectionZone = $isCollectorRole && ! empty($validated['collection_zone']) ? trim((string) $validated['collection_zone']) : null;

        if ($isSuperAdminRole) {
            $branchId = null;
            $managerId = null;
            $collectionSubregionId = null;
            $collectionZone = null;
        }

        $mobilePhone = ! empty($validated['mobile_phone']) ? trim((string) $validated['mobile_phone']) : null;

        $user = DB::transaction(function () use (
            $validated,
            $email,
            $subordinateIds,
            $subordinatesRendered,
            $groupIds,
            $branchId,
            $managerId,
            $collectionSubregionId,
            $collectionZone,
            $mobilePhone
        ): User {
            $voipExt = ! empty($validated['voip_extension']) ? trim((string) $validated['voip_extension']) : null;
            $user = User::query()->create([
                'branch_id' => $branchId,
                'manager_id' => $managerId,
                'name' => trim($validated['name']),
                'username' => ! empty($validated['username']) ? trim((string) $validated['username']) : User::generateUniqueUsername($validated['name']),
                'email' => $email !== '' ? $email : null,
                'mobile_phone' => $mobilePhone,
                'voip_extension' => $voipExt,
                'collection_zone' => $collectionZone,
                'collection_subregion_id' => $collectionSubregionId,
                'password' => $validated['password'],
                'is_active' => true,
            ]);

            $user->groups()->sync($groupIds);
            $this->syncSubordinates($user, $subordinateIds, $subordinatesRendered);
            app(VoipExtensionManager::class)->sync($user, null, $voipExt);

            return $user;
        });

        return redirect()
            ->route('v2.settings.users.edit', $user)
            ->with('success', 'تم إنشاء المستخدم بنجاح.');
    }

    public function edit(Request $request, User $user): View
    {
        $this->ensureCanManageUser($request->user(), $user);
        $user->load('groups:id,name,code');

        $voipFilters = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'direction' => ['nullable', Rule::in(['incoming', 'outgoing', 'internal'])],
            'status' => ['nullable', Rule::in(['answered', 'missed', 'failed', 'busy', 'no_answer'])],
        ]);
        $voipFilters = array_filter(
            $voipFilters,
            static fn (mixed $value): bool => $value !== null && $value !== '',
        );

        $voipStats = null;
        $canViewVoip = $request->user()?->hasPermission(CrmPermission::VOIP_VIEW) ?? false;
        if ($canViewVoip && ! empty($user->voip_extension)) {
            try {
                $voipStats = app(VoipCallAnalytics::class)->forUser($user, $voipFilters);
            } catch (\Throwable) {
                $voipStats = null;
            }
        }

        $voip = app(VoipService::class);
        $isVoipConnected = $voip->isConfigured() && $voip->isConnected();

        return view('settings.users.edit', [
            'managedUser' => $user,
            'branches' => Branch::query()->orderBy('name_ar')->get(),
            'managers' => User::query()->where('is_active', true)->where('id', '!=', $user->id)->orderBy('name')->get(['id', 'name', 'username']),
            'groups' => $this->availableGroups($request->user()),
            'salesEmployees' => $this->getSalesEmployees($user),
            'collectors' => $this->getCollectors($user),
            'currentSubordinateIds' => $user->subordinates()->pluck('id'),
            'voipExtensions' => $this->getVoipExtensions(),
            'voipStats' => $voipStats,
            'voipFilters' => $voipFilters,
            'isVoipConnected' => $isVoipConnected,
            'canViewVoip' => $canViewVoip,
            'governorates' => \App\Models\Governorate::query()->where('is_active', true)->with('activeSubregions')->orderBy('name_ar')->get(),
        ]);
    }
    public function update(
        UpdateUserRequest $request,
        User $user,
    ): RedirectResponse {
        $validated = $request->validated();
        $email = trim((string) ($validated['email'] ?? ''));
        $groupIds = array_map('intval', $validated['group_ids']);
        $this->ensureCanManageUser($request->user(), $user);
        $this->ensureCanAssignGroups($request->user(), $groupIds);

        $this->ensureSuperAdminRemains($user, $groupIds);

        $subordinateIds = $validated['subordinate_ids'] ?? [];
        $subordinatesRendered = $request->boolean('subordinates_section_rendered');

        DB::transaction(function () use (
            $user,
            $validated,
            $email,
            $groupIds,
            $subordinateIds,
            $subordinatesRendered,
        ): void {
            $actor = request()->user();
            $isSuperAdminRole = Group::query()->whereIn('id', $groupIds)->where('code', Group::SUPER_ADMIN_CODE)->exists();
            $isCollectorRole = Group::query()->whereIn('id', $groupIds)->where('code', Group::COLLECTOR_CODE)->exists();

            $previousVoipExt = $user->voip_extension;
            $voipExt = array_key_exists('voip_extension', $validated)
                ? (! empty($validated['voip_extension']) ? trim((string) $validated['voip_extension']) : null)
                : $previousVoipExt;

            $branchId = array_key_exists('branch_id', $validated) ? (! empty($validated['branch_id']) ? (int) $validated['branch_id'] : null) : $user->branch_id;
            if ($actor && ! $actor->isSuperAdmin() && $actor->branch_id !== null) {
                $branchId = (int) $actor->branch_id;
            }

            $managerId = array_key_exists('manager_id', $validated) ? (! empty($validated['manager_id']) ? (int) $validated['manager_id'] : null) : $user->manager_id;
            $collectionSubregionId = $isCollectorRole
                ? (array_key_exists('collection_subregion_id', $validated) ? (! empty($validated['collection_subregion_id']) ? (int) $validated['collection_subregion_id'] : null) : $user->collection_subregion_id)
                : null;
            $collectionZone = $isCollectorRole
                ? (array_key_exists('collection_zone', $validated) ? (! empty($validated['collection_zone']) ? trim((string) $validated['collection_zone']) : null) : $user->collection_zone)
                : null;

            if ($isSuperAdminRole) {
                $branchId = null;
                $managerId = null;
                $collectionSubregionId = null;
                $collectionZone = null;
            }

            $mobilePhone = array_key_exists('mobile_phone', $validated)
                ? (! empty($validated['mobile_phone']) ? trim((string) $validated['mobile_phone']) : null)
                : $user->mobile_phone;

            $user->update([
                'branch_id' => $branchId,
                'manager_id' => $managerId,
                'name' => trim($validated['name']),
                'username' => array_key_exists('username', $validated) && ! empty($validated['username'])
                    ? trim((string) $validated['username'])
                    : ($user->username ?: User::generateUniqueUsername($validated['name'], $user->id)),
                'email' => $email !== '' ? $email : null,
                'mobile_phone' => $mobilePhone,
                'voip_extension' => $voipExt,
                'collection_zone' => $collectionZone,
                'collection_subregion_id' => $collectionSubregionId,
            ]);
            app(VoipExtensionManager::class)->sync($user, $previousVoipExt, $voipExt);
            $user->groups()->sync($groupIds);
            $this->syncSubordinates($user, $subordinateIds, $subordinatesRendered);
            $user->unsetRelation('groups');
        });
        return back()->with('success', 'تم تحديث المستخدم بنجاح.');
    }

    public function updateStatus(
        Request $request,
        User $user,
    ): RedirectResponse {
        $this->ensureCanManageUser($request->user(), $user);
        $request->validate([
            'is_active' => ['required', 'boolean'],
        ]);

        $isActive = $request->boolean('is_active');

        if (! $isActive && $request->user()->is($user)) {
            throw ValidationException::withMessages([
                'is_active' => 'لا يمكنك تعطيل حسابك الحالي.',
            ]);
        }

        if (! $isActive) {
            $this->ensureActiveSuperAdminRemains($user);
        }

        $user->update(['is_active' => $isActive]);

        return back()->with(
            'success',
            $isActive
                ? 'تم تفعيل المستخدم.'
                : 'تم تعطيل المستخدم.',
        );
    }

    public function resetPassword(
        Request $request,
        User $user,
    ): RedirectResponse {
        $this->ensureCanManageUser($request->user(), $user);
        $validated = $request->validate([
            'password' => [
                'required',
                'string',
                'confirmed',
            ],
        ]);

        $user->update(['password' => $validated['password']]);

        return back()->with('success', 'تم تحديث كلمة المرور.');
    }

    private function availableGroups(User $actor)
    {
        return Group::query()
            ->where('is_active', true)
            ->when(
                ! $actor->isSuperAdmin(),
                static fn ($query) => $query->where(
                    'code',
                    '<>',
                    Group::SUPER_ADMIN_CODE,
                ),
            )
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  list<int>  $groupIds
     */
    private function ensureCanAssignGroups(
        User $actor,
        array $groupIds,
    ): void {
        if ($actor->isSuperAdmin()) {
            return;
        }

        $assignsSuperAdmin = Group::query()
            ->whereKey($groupIds)
            ->where('code', Group::SUPER_ADMIN_CODE)
            ->exists();

        if ($assignsSuperAdmin) {
            throw ValidationException::withMessages([
                'group_ids' => 'فقط مدير النظام يمكنه إسناد مجموعة مدير النظام.',
            ]);
        }
    }

    private function ensureCanManageUser(
        User $actor,
        User $managedUser,
    ): void {
        abort_if(
            $managedUser->isSuperAdmin() && ! $actor->isSuperAdmin(),
            403,
            'Only a Super Admin may manage another Super Admin.',
        );
    }

    /**
     * @param  list<int>  $newGroupIds
     */
    private function ensureSuperAdminRemains(
        User $user,
        array $newGroupIds,
    ): void {
        if (! $user->is_active || ! $user->isSuperAdmin()) {
            return;
        }

        $superAdminGroupId = (int) Group::query()
            ->where('code', Group::SUPER_ADMIN_CODE)
            ->value('id');

        if (in_array($superAdminGroupId, $newGroupIds, true)) {
            return;
        }

        $this->ensureActiveSuperAdminRemains($user);
    }

    private function ensureActiveSuperAdminRemains(User $user): void
    {
        if (! $user->isSuperAdmin()) {
            return;
        }

        $activeSuperAdmins = User::query()
            ->where('is_active', true)
            ->whereHas(
                'groups',
                static fn ($query) => $query->where(
                    'code',
                    Group::SUPER_ADMIN_CODE,
                ),
            )
            ->count();

        if ($activeSuperAdmins <= 1) {
            throw ValidationException::withMessages([
                'group_ids' => 'يجب الإبقاء على مدير نظام فعال واحد على الأقل.',
            ]);
        }
    }

    private function getVoipExtensions(): array
    {
        /** @var VoipService $voip */
        $voip = app(VoipService::class);
        if ($voip->isConfigured()) {
            try {
                $res = $voip->getExtensions();
                $list = $res['extensions'] ?? [];
                if (! empty($list)) {
                    return $list;
                }
            } catch (\Throwable) {
            }
        }

        return [
            ['extension' => '150', 'name' => 'Agent 150', 'online' => true, 'webrtc' => true],
            ['extension' => '151', 'name' => 'Agent 151', 'online' => true, 'webrtc' => true],
            ['extension' => '170', 'name' => 'Agent 170', 'online' => true, 'webrtc' => true],
            ['extension' => '101', 'name' => 'SIP Desk 101', 'online' => false, 'webrtc' => false],
            ['extension' => '102', 'name' => 'SIP Desk 102', 'online' => false, 'webrtc' => false],
        ];
    }

    private function getSalesEmployees(?User $excludeUser = null, ?User $actor = null)
    {
        $actor ??= request()->user();
        $isSuper = $actor?->isSuperAdmin() ?? true;

        return User::query()
            ->where('is_active', true)
            ->when($excludeUser?->id, static fn ($q) => $q->where('id', '!=', $excludeUser->id))
            ->when(! $isSuper && $actor?->branch_id !== null, static fn ($q) => $q->where('branch_id', $actor->branch_id))
            ->where(static function ($q): void {
                $q->whereHas('groups', static function ($gq): void {
                    $gq->whereIn('code', ['employee', 'sales-agent', 'sales-supervisor', 'sales_agent', 'sales_supervisor']);
                })->orWhereHas('groups.permissions', static function ($pq): void {
                    $pq->whereIn('code', [
                        CrmPermission::LEADS_CREATE->value,
                        CrmPermission::LEADS_FOLLOWUPS_CREATE->value,
                        CrmPermission::TASKS_VIEW->value,
                    ]);
                });
            })
            ->whereDoesntHave('groups', static function ($gq): void {
                $gq->whereIn('code', [Group::SUPER_ADMIN_CODE, Group::BRANCH_ADMIN_CODE, 'collection-manager', 'collector', 'field-collector']);
            })
            ->with(['manager:id,name,username', 'branch:id,name_ar,name_en'])
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'branch_id', 'manager_id']);
    }

    private function getCollectors(?User $excludeUser = null, ?User $actor = null)
    {
        $actor ??= request()->user();
        $isSuper = $actor?->isSuperAdmin() ?? true;

        return User::query()
            ->where('is_active', true)
            ->when($excludeUser?->id, static fn ($q) => $q->where('id', '!=', $excludeUser->id))
            ->when(! $isSuper && $actor?->branch_id !== null, static fn ($q) => $q->where('branch_id', $actor->branch_id))
            ->where(static function ($q): void {
                $q->whereHas('groups', static function ($gq): void {
                    $gq->whereIn('code', ['collector', 'field-collector']);
                })->orWhereHas('groups.permissions', static function ($pq): void {
                    $pq->whereIn('code', [
                        CrmPermission::COLLECTIONS_COLLECT->value,
                        CrmPermission::COLLECTIONS_COMPLETE->value,
                    ]);
                });
            })
            ->with(['manager:id,name,username', 'branch:id,name_ar,name_en', 'collectionSubregion.governorate'])
            ->orderBy('name')
            ->get(['id', 'name', 'username', 'branch_id', 'manager_id', 'collection_zone', 'collection_subregion_id']);
    }

    /**
     * @param list<int|string> $subordinateIds
     */
    private function syncSubordinates(User $leader, array $subordinateIds, bool $wasRendered): void
    {
        if (! $wasRendered) {
            return;
        }

        $leader->unsetRelation('groups');
        $isLeaderRole = $leader->isManager() || $leader->isBranchAdmin() || $leader->isSuperAdmin() || $leader->isTeamLeader() || $leader->isCollectionManager();
        if (! $isLeaderRole) {
            User::query()->where('manager_id', $leader->id)->update(['manager_id' => null]);
            return;
        }

        $validIds = collect($subordinateIds)
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn ($id) => $id > 0 && $id !== (int) $leader->id)
            ->unique()
            ->values();

        // 1. Detach unselected subordinates previously assigned to this leader
        $detachIds = User::query()
            ->where('manager_id', $leader->id)
            ->whereNotIn('id', $validIds)
            ->pluck('id')
            ->all();

        if (! empty($detachIds)) {
            User::query()->whereIn('id', $detachIds)->update(['manager_id' => null]);
        }

        // 2. Assign newly selected subordinates to this leader (guarantees exactly 1 manager)
        if ($validIds->isNotEmpty()) {
            User::query()
                ->whereIn('id', $validIds)
                ->where('id', '!=', $leader->id)
                ->update(['manager_id' => $leader->id]);
        }
    }
}
