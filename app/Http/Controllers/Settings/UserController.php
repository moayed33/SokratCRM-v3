<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreUserRequest;
use App\Http\Requests\Settings\UpdateUserRequest;
use App\Models\Group;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(
            trim((string) $request->query('q', '')),
            0,
            150,
        );

        $users = User::query()
            ->with('groups:id,name,code')
            ->when(
                $search !== '',
                static function ($query) use ($search): void {
                    $query->where(function ($query) use ($search): void {
                        $query->where('name', 'like', '%'.$search.'%')
                            ->orWhere('username', 'like', '%'.$search.'%')
                            ->orWhere('email', 'like', '%'.$search.'%');
                    });
                },
            )
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();
        $isVoipConnected = app(\App\Services\VoipService::class)->isConfigured();

        return view('settings.users.index', compact('users', 'search', 'isVoipConnected'));
    }

    public function create(Request $request): View
    {
        $isVoipConnected = app(\App\Services\VoipService::class)->isConfigured();

        return view('settings.users.create', [
            'groups' => $this->availableGroups($request->user()),
            'voipExtensions' => $this->getVoipExtensions(),
            'isVoipConnected' => $isVoipConnected,
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

        $user = DB::transaction(function () use ($validated, $email): User {
            $voipExt = ! empty($validated['voip_extension']) ? trim((string) $validated['voip_extension']) : null;
            $user = User::query()->create([
                'name' => trim($validated['name']),
                'username' => trim($validated['username']),
                'email' => $email !== '' ? $email : null,
                'voip_extension' => $voipExt,
                'password' => $validated['password'],
                'is_active' => true,
            ]);

            $user->groups()->sync($validated['group_ids']);

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
        if (! empty($user->voip_extension)) {
            /** @var \App\Services\VoipService $voip */
            $voip = app(\App\Services\VoipService::class);
            if ($voip->isConfigured()) {
                try {
                    $voipStats = $voip->getExtensionStats($user->voip_extension, $voipFilters);
                } catch (\Throwable) {
                    $voipStats = null;
                }
            }
        }

        $isVoipConnected = app(\App\Services\VoipService::class)->isConfigured();

        return view('settings.users.edit', [
            'managedUser' => $user,
            'groups' => $this->availableGroups($request->user()),
            'voipExtensions' => $this->getVoipExtensions(),
            'voipStats' => $voipStats,
            'voipFilters' => $voipFilters,
            'isVoipConnected' => $isVoipConnected,
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

        DB::transaction(function () use (
            $user,
            $validated,
            $email,
            $groupIds,
        ): void {
            $voipExt = ! empty($validated['voip_extension']) ? trim((string) $validated['voip_extension']) : null;
            $user->update([
                'name' => trim($validated['name']),
                'username' => trim($validated['username']),
                'email' => $email !== '' ? $email : null,
                'voip_extension' => $voipExt,
            ]);
            $user->groups()->sync($groupIds);
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
                'confirmed',
                Password::min(10),
            ],
        ]);

        $user->update(['password' => $validated['password']]);

        return back()->with('success', 'تم تحديث كلمة المرور.');
    }

    private function availableGroups(User $actor)
    {
        return Group::query()
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
        /** @var \App\Services\VoipService $voip */
        $voip = app(\App\Services\VoipService::class);
        if (! $voip->isConfigured()) {
            return [];
        }

        try {
            $res = $voip->getExtensions();
            return $res['extensions'] ?? [];
        } catch (\Throwable) {
            return [];
        }
    }
}
