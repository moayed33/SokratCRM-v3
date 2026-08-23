<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Settings\StoreGroupRequest;
use App\Http\Requests\Settings\UpdateGroupRequest;
use App\Models\Group;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GroupController extends Controller
{
    public function index(): View
    {
        return view('settings.groups.index', [
            'groups' => Group::query()
                ->withCount(['users', 'permissions'])
                ->orderByDesc('is_system')
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('settings.groups.create');
    }

    public function store(StoreGroupRequest $request): RedirectResponse
    {
        $group = Group::query()->create([
            ...$request->safe()->only([
                'name',
                'code',
                'description',
            ]),
            'is_system' => false,
        ]);

        return redirect()
            ->route('v2.settings.groups.edit', $group)
            ->with('success', 'تم إنشاء المجموعة بنجاح.');
    }

    public function edit(Request $request, Group $group): View
    {
        $this->ensureCanManageSystemGroup($request, $group);

        return view('settings.groups.edit', compact('group'));
    }

    public function update(
        UpdateGroupRequest $request,
        Group $group,
    ): RedirectResponse {
        $this->ensureCanManageSystemGroup($request, $group);
        $data = $request->safe()->only([
            'name',
            'code',
            'description',
        ]);

        if ($group->is_system) {
            unset($data['code']);
        }

        $group->update($data);

        return back()->with('success', 'تم تحديث المجموعة بنجاح.');
    }

    public function destroy(Request $request, Group $group): RedirectResponse
    {
        $this->ensureCanManageSystemGroup($request, $group);
        if ($group->is_system) {
            throw ValidationException::withMessages([
                'group' => 'لا يمكن حذف مجموعة نظام محمية.',
            ]);
        }

        if ($group->users()->exists()) {
            throw ValidationException::withMessages([
                'group' => 'انقل المستخدمين من المجموعة قبل حذفها.',
            ]);
        }

        $group->delete();

        return redirect()
            ->route('v2.settings.groups.index')
            ->with('success', 'تم حذف المجموعة.');
    }

    private function ensureCanManageSystemGroup(
        Request $request,
        Group $group,
    ): void {
        abort_if(
            $group->is_system && ! $request->user()->isSuperAdmin(),
            403,
            'Only a Super Admin may manage a system group.',
        );
    }
}
