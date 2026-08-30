<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings\Concerns;

use App\Models\Group;
use App\Models\Permission;
use App\Models\User;
use App\Security\CrmPermission;
use App\Services\VoipService;
use Illuminate\Http\Request;
use Illuminate\View\View;

trait BuildsAccessPage
{
    /**
     * Loads everything needed by the combined Users / Groups / Permissions
     * settings page so any entry route can render the same view.
     */
    protected function buildAccessPage(Request $request, string $defaultTab): View
    {
        $search = mb_substr(
            trim((string) $request->query('q', '')),
            0,
            150,
        );

        $requestedTab = mb_substr(trim((string) $request->query('tab', '')), 0, 20);
        $activeTab = in_array($requestedTab, ['users', 'groups', 'permissions'], true)
            ? $requestedTab
            : $defaultTab;

        $users = User::query()
            ->with(['groups:id,name,code', 'branch:id,name_ar,name_en,code'])
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

        $groups = Group::query()
            ->where('is_active', true)
            ->with('permissions:id,code')
            ->withCount(['users', 'permissions'])
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get();

        $permissionsByModule = Permission::query()
            ->orderBy('module')
            ->orderBy('code')
            ->get()
            ->groupBy('module');

        return view('settings.access.index', [
            'activeTab' => $activeTab,
            'users' => $users,
            'search' => $search,
            'isVoipConnected' => app(VoipService::class)->isConfigured(),
            'groups' => $groups,
            'permissionsCount' => $permissionsByModule->flatten()->count(),
            'permissionsByModule' => $permissionsByModule,
            'moduleLabels' => CrmPermission::moduleLabels(),
        ]);
    }
}
