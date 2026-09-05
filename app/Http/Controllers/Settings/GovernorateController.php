<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\Governorate;
use App\Models\GovernorateSubregion;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GovernorateController extends Controller
{
    public function index(Request $request): View
    {
        $query = Governorate::query()
            ->withCount(['subregions', 'leads', 'collectionCases'])
            ->with(['subregions' => function ($sq): void {
                $sq->withCount(['collectors', 'leads', 'collectionCases'])
                    ->orderBy('position')
                    ->orderBy('name_ar');
            }])
            ->orderBy('position')
            ->orderBy('name_ar');

        if ($request->filled('q')) {
            $search = trim((string) $request->input('q'));
            $query->where(function ($q) use ($search): void {
                $q->where('name_ar', 'like', "%{$search}%")
                    ->orWhere('name_en', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%");
            });
        }

        $governorates = $query->get();

        $selectedGovId = (int) $request->input('gov_id', $governorates->first()?->id ?? 0);
        $selectedGovernorate = $governorates->firstWhere('id', $selectedGovId) ?? $governorates->first();

        return view('settings.governorates.index', [
            'governorates' => $governorates,
            'selectedGovernorate' => $selectedGovernorate,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100', 'unique:governorates,name_ar'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'code' => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/i', 'unique:governorates,code'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $code = ! empty($validated['code'])
            ? Str::slug($validated['code'], '_')
            : Str::slug($validated['name_en'] ?? $validated['name_ar'], '_');

        if (Governorate::query()->where('code', $code)->exists()) {
            $code .= '_'.(Governorate::query()->max('id') + 1);
        }

        Governorate::query()->create([
            'name_ar' => trim($validated['name_ar']),
            'name_en' => ! empty($validated['name_en']) ? trim($validated['name_en']) : null,
            'code' => $code,
            'position' => (int) ($validated['position'] ?? (Governorate::query()->max('position') + 10)),
            'is_active' => true,
        ]);

        return redirect()->route('v2.settings.governorates.index')->with('success', __('crm.governorate_created_success'));
    }

    public function update(Request $request, Governorate $governorate): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100', Rule::unique('governorates', 'name_ar')->ignore($governorate->id)],
            'name_en' => ['nullable', 'string', 'max:100'],
            'code' => ['required', 'string', 'max:50', 'regex:/^[a-z0-9_-]+$/i', Rule::unique('governorates', 'code')->ignore($governorate->id)],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $governorate->update([
            'name_ar' => trim($validated['name_ar']),
            'name_en' => ! empty($validated['name_en']) ? trim($validated['name_en']) : null,
            'code' => Str::slug($validated['code'], '_'),
            'position' => (int) ($validated['position'] ?? $governorate->position),
        ]);

        return redirect()->route('v2.settings.governorates.index', ['gov_id' => $governorate->id])->with('success', __('crm.governorate_updated_success'));
    }

    public function toggleActive(Governorate $governorate): RedirectResponse
    {
        $governorate->update(['is_active' => ! $governorate->is_active]);

        $msg = $governorate->is_active ? __('crm.governorate_activated_success') : __('crm.governorate_deactivated_success');

        return redirect()->route('v2.settings.governorates.index', ['gov_id' => $governorate->id])->with('success', $msg);
    }

    public function toggle(Governorate $governorate): RedirectResponse
    {
        return $this->toggleActive($governorate);
    }

    public function destroy(Governorate $governorate): RedirectResponse
    {
        if ($governorate->subregions()->exists()) {
            return redirect()->route('v2.settings.governorates.index', ['gov_id' => $governorate->id])
                ->withErrors(['delete' => __('crm.cannot_delete_governorate_with_subregions')]);
        }

        if ($governorate->leads()->exists() || $governorate->collectionCases()->exists()) {
            return redirect()->route('v2.settings.governorates.index', ['gov_id' => $governorate->id])
                ->withErrors(['delete' => __('crm.cannot_delete_governorate_with_records')]);
        }

        $governorate->delete();

        return redirect()->route('v2.settings.governorates.index')->with('success', __('crm.governorate_deleted_success'));
    }

    // --- Subregions Actions ---

    public function storeSubregion(Request $request, Governorate $governorate): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'code' => [
                'nullable',
                'string',
                'max:50',
                'regex:/^[a-z0-9_-]+$/i',
                Rule::unique('governorate_subregions', 'code')->where('governorate_id', $governorate->id),
            ],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $code = ! empty($validated['code'])
            ? Str::slug($validated['code'], '_')
            : Str::slug($validated['name_en'] ?? $validated['name_ar'], '_');

        if (GovernorateSubregion::query()->where('governorate_id', $governorate->id)->where('code', $code)->exists()) {
            $code .= '_'.(GovernorateSubregion::query()->max('id') + 1);
        }

        GovernorateSubregion::query()->create([
            'governorate_id' => $governorate->id,
            'name_ar' => trim($validated['name_ar']),
            'name_en' => ! empty($validated['name_en']) ? trim($validated['name_en']) : null,
            'code' => $code,
            'position' => (int) ($validated['position'] ?? (GovernorateSubregion::query()->where('governorate_id', $governorate->id)->max('position') + 10)),
            'is_active' => true,
        ]);

        return redirect()->route('v2.settings.governorates.index', ['gov_id' => $governorate->id])->with('success', __('crm.subregion_created_success'));
    }

    public function updateSubregion(Request $request, GovernorateSubregion $subregion): RedirectResponse
    {
        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'name_en' => ['nullable', 'string', 'max:100'],
            'code' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_-]+$/i',
                Rule::unique('governorate_subregions', 'code')
                    ->where('governorate_id', $subregion->governorate_id)
                    ->ignore($subregion->id),
            ],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $subregion->update([
            'name_ar' => trim($validated['name_ar']),
            'name_en' => ! empty($validated['name_en']) ? trim($validated['name_en']) : null,
            'code' => Str::slug($validated['code'], '_'),
            'position' => (int) ($validated['position'] ?? $subregion->position),
        ]);

        return redirect()->route('v2.settings.governorates.index', ['gov_id' => $subregion->governorate_id])->with('success', __('crm.subregion_updated_success'));
    }

    public function toggleActiveSubregion(GovernorateSubregion $subregion): RedirectResponse
    {
        $subregion->update(['is_active' => ! $subregion->is_active]);

        $msg = $subregion->is_active ? __('crm.subregion_activated_success') : __('crm.subregion_deactivated_success');

        return redirect()->route('v2.settings.governorates.index', ['gov_id' => $subregion->governorate_id])->with('success', $msg);
    }

    public function toggleSubregion(GovernorateSubregion $subregion): RedirectResponse
    {
        return $this->toggleActiveSubregion($subregion);
    }

    public function destroySubregion(GovernorateSubregion $subregion): RedirectResponse
    {
        $govId = $subregion->governorate_id;

        if ($subregion->collectors()->exists() || $subregion->leads()->exists() || $subregion->collectionCases()->exists()) {
            return redirect()->route('v2.settings.governorates.index', ['gov_id' => $govId])
                ->withErrors(['delete' => __('crm.cannot_delete_subregion_with_records')]);
        }

        $subregion->delete();

        return redirect()->route('v2.settings.governorates.index', ['gov_id' => $govId])->with('success', __('crm.subregion_deleted_success'));
    }

    /**
     * API endpoint for dynamic subregion dropdowns in forms
     */
    public function apiSubregions(Governorate $governorate): JsonResponse
    {
        $subregions = $governorate->activeSubregions()
            ->get(['id', 'governorate_id', 'code', 'name_ar', 'name_en'])
            ->map(static fn (GovernorateSubregion $sub) => [
                'id' => $sub->id,
                'name' => $sub->name,
                'name_ar' => $sub->name_ar,
                'name_en' => $sub->name_en,
                'code' => $sub->code,
            ]);

        return response()->json(['subregions' => $subregions]);
    }

    public function subregionsApi(Governorate $governorate): JsonResponse
    {
        return $this->apiSubregions($governorate);
    }
}
