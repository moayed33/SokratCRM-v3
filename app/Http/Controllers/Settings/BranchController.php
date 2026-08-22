<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Branch;
use App\Models\Lead;
use App\Models\User;
use App\Security\CrmPermission;
use App\Support\BranchContext;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BranchController extends Controller
{
    public function index(Request $request): View
    {
        $search = mb_substr(
            trim((string) $request->query('q', '')),
            0,
            150,
        );

        $branches = Branch::query()
            ->withCount(['users', 'leads', 'campaigns'])
            ->when(
                $search !== '',
                static function ($query) use ($search): void {
                    $query->where(function ($q) use ($search): void {
                        $q->where('name_ar', 'like', '%'.$search.'%')
                            ->orWhere('name_en', 'like', '%'.$search.'%')
                            ->orWhere('code', 'like', '%'.$search.'%')
                            ->orWhere('phone', 'like', '%'.$search.'%')
                            ->orWhere('address', 'like', '%'.$search.'%');
                    });
                },
            )
            ->orderByDesc('is_active')
            ->orderBy('name_ar')
            ->paginate(20)
            ->withQueryString();

        $stats = [
            'total' => Branch::count(),
            'active' => Branch::where('is_active', true)->count(),
            'total_users' => User::whereNotNull('branch_id')->count(),
            'total_leads' => Lead::whereNotNull('branch_id')->count(),
        ];

        return view('settings.branches.index', [
            'branches' => $branches,
            'search' => $search,
            'stats' => $stats,
        ]);
    }

    public function create(): View
    {
        return view('settings.branches.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:150', 'unique:branches,name_ar'],
            'name_en' => ['nullable', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash:ascii', 'unique:branches,code'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $branch = Branch::query()->create([
            'name_ar' => trim($validated['name_ar']),
            'name_en' => ! empty($validated['name_en']) ? trim($validated['name_en']) : null,
            'code' => Str::lower(trim($validated['code'])),
            'phone' => ! empty($validated['phone']) ? trim($validated['phone']) : null,
            'address' => ! empty($validated['address']) ? trim($validated['address']) : null,
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()
            ->route('v2.settings.branches.index')
            ->with('success', 'تم إنشاء الفرع بنجاح.');
    }

    public function edit(Branch $branch): View
    {
        $branch->loadCount(['users', 'leads', 'campaigns']);

        return view('settings.branches.edit', [
            'branch' => $branch,
        ]);
    }

    public function update(Request $request, Branch $branch): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:150', Rule::unique('branches', 'name_ar')->ignore($branch->id)],
            'name_en' => ['nullable', 'string', 'max:150'],
            'code' => ['required', 'string', 'max:50', 'alpha_dash:ascii', Rule::unique('branches', 'code')->ignore($branch->id)],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $branch->update([
            'name_ar' => trim($validated['name_ar']),
            'name_en' => ! empty($validated['name_en']) ? trim($validated['name_en']) : null,
            'code' => Str::lower(trim($validated['code'])),
            'phone' => ! empty($validated['phone']) ? trim($validated['phone']) : null,
            'address' => ! empty($validated['address']) ? trim($validated['address']) : null,
            'is_active' => $request->boolean('is_active', $branch->is_active),
        ]);

        return redirect()
            ->route('v2.settings.branches.index')
            ->with('success', 'تم تحديث بيانات الفرع بنجاح.');
    }

    public function toggleActive(Branch $branch): RedirectResponse
    {
        $branch->update([
            'is_active' => ! $branch->is_active,
        ]);

        $statusText = $branch->is_active ? 'تفعيل' : 'تعطيل';

        return redirect()
            ->back()
            ->with('success', "تم {$statusText} الفرع بنجاح.");
    }

    public function destroy(Branch $branch): RedirectResponse
    {
        if ($branch->leads()->exists()) {
            return redirect()
                ->back()
                ->withErrors(['delete' => 'لا يمكن حذف الفرع لوجود عملاء وتبرعات مرتبطة به. يمكنك تعطيل الفرع بدلاً من ذلك.']);
        }

        if ($branch->users()->exists()) {
            return redirect()
                ->back()
                ->withErrors(['delete' => 'لا يمكن حذف الفرع لوجود مستخدمين معينين عليه. يرجى نقل المستخدمين لفرع آخر أولاً.']);
        }

        if ($branch->campaigns()->exists()) {
            return redirect()
                ->back()
                ->withErrors(['delete' => 'لا يمكن حذف الفرع لوجود حملات مرتبطة به. يرجى تعديل ارتباط الحملات أولاً.']);
        }

        $branch->delete();

        return redirect()
            ->route('v2.settings.branches.index')
            ->with('success', 'تم حذف الفرع بنجاح.');
    }

    public function switchBranch(Request $request): RedirectResponse|JsonResponse
    {
        $user = $request->user();

        if (! $user || ! $user->isSuperAdmin()) {
            abort(403, 'غير مصرح لك بتبديل الفروع.');
        }

        $branchId = $request->input('branch_id', $request->query('branch_id'));

        if ($branchId === 'all' || empty($branchId)) {
            BranchContext::setSelectedBranch('all');
        } else {
            $branch = Branch::query()->where('id', (int) $branchId)->firstOrFail();
            BranchContext::setSelectedBranch($branch->id);
        }

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'selected_branch_id' => session(BranchContext::SESSION_KEY),
                'label' => BranchContext::getActiveBranchLabel($user),
            ]);
        }

        return redirect()->back()->with('success', 'تم تبديل الفرع النشط بنجاح.');
    }
}
