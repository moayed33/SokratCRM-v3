<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DonationPurpose;
use App\Models\DonationType;
use App\Models\Lead;
use App\Models\LeadStatus;
use App\Models\PipelineStage;
use App\Support\CrmDatabaseGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PipelineStageController extends Controller
{
    private const MAX_STAGES = 3;

    public function index(): View
    {
        $this->assertCrmDatabase();

        $stages = PipelineStage::query()
            ->withCount([
                'statuses as statuses_count',
            ])
            ->orderBy('position')
            ->orderBy('id')
            ->get();

        // Calculate lead counts per stage
        foreach ($stages as $stage) {
            $stage->leads_count = Lead::query()
                ->whereHas('status', static fn ($q) => $q->where('pipeline_stage_id', $stage->id))
                ->count();
        }

        $totalStagesCount = $stages->count();
        $canAddStage = $totalStagesCount < self::MAX_STAGES;

        $donationTypes = DonationType::query()
            ->withCount('leads')
            ->orderBy('position')
            ->get();

        $donationPurposes = DonationPurpose::query()
            ->withCount('leads')
            ->orderBy('position')
            ->get();

        return view('settings.stages.index', [
            'stages' => $stages,
            'totalStagesCount' => $totalStagesCount,
            'maxStages' => self::MAX_STAGES,
            'canAddStage' => $canAddStage,
            'donationTypes' => $donationTypes,
            'donationPurposes' => $donationPurposes,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->assertCrmDatabase();

        $currentStagesCount = PipelineStage::query()->count();

        if ($currentStagesCount >= self::MAX_STAGES) {
            return redirect()
                ->route('v2.settings.stages.index')
                ->withErrors([
                    'stage' => 'تم الوصول إلى الحد الأقصى للمراحل (3 مراحل). لا يمكن إضافة مراحل أخرى.',
                ]);
        }

        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'description_ar' => ['nullable', 'string', 'max:255'],
        ], [
            'name_ar.required' => 'اسم المرحلة مطلوب.',
            'color.regex' => 'كود اللون يجب أن يكون بصيغة hex صحيحة مثل #3478f6.',
        ]);

        $nextPosition = (int) (PipelineStage::query()->max('position') ?? 0) + 1;
        $color = $validated['color'] ?? '#7b61df';

        DB::transaction(function () use ($validated, $nextPosition, $color): void {
            $code = 'stage_'.Str::lower(Str::random(8));

            $stage = PipelineStage::query()->create([
                'code' => $code,
                'name_ar' => trim($validated['name_ar']),
                'description_ar' => $validated['description_ar'] ?? null,
                'position' => $nextPosition,
                'color' => $color,
                'icon' => $validated['icon'] ?? null,
                'is_primary' => false,
                'is_active' => true,
            ]);

            // Create a matching primary status for this stage
            LeadStatus::query()->create([
                'pipeline_stage_id' => $stage->id,
                'code' => 'status_'.$stage->id.'_default',
                'name_ar' => $stage->name_ar,
                'position' => (int) (LeadStatus::query()->max('position') ?? 0) + 1,
                'color' => $color,
                'is_terminal' => false,
            ]);
        });

        PipelineStage::clearSidebarCache();

        return redirect()
            ->route('v2.settings.stages.index')
            ->with('success', 'تمت إضافة المرحلة الإضافية بنجاح.');
    }

    public function update(Request $request, PipelineStage $stage): RedirectResponse
    {
        $this->assertCrmDatabase();

        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'icon' => ['nullable', 'string', 'max:50'],
            'position' => ['required', 'integer', 'min:1', 'max:10'],
            'description_ar' => ['nullable', 'string', 'max:255'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'name_ar.required' => 'اسم المرحلة مطلوب.',
            'color.regex' => 'كود اللون يجب أن يكون بصيغة hex صحيحة.',
        ]);

        $isActive = $stage->isPrimary() ? true : (bool) ($validated['is_active'] ?? true);

        // Safety: If deactivating an optional stage that contains leads
        if (! $isActive && $stage->is_active && $stage->hasLeads()) {
            return redirect()
                ->route('v2.settings.stages.index')
                ->withErrors([
                    'stage' => 'لا يمكن تعطيل هذه المرحلة لوجود عملاء مرتبطين بها حالياً. يرجى نقل العملاء إلى مرحلة أخرى أولاً.',
                ]);
        }

        DB::transaction(function () use ($stage, $validated, $isActive): void {
            $stage->update([
                'name_ar' => trim($validated['name_ar']),
                'color' => $validated['color'] ?? $stage->color,
                'icon' => $validated['icon'] ?? $stage->icon,
                'position' => (int) $validated['position'],
                'description_ar' => $validated['description_ar'] ?? null,
                'is_active' => $isActive,
            ]);

            // Update matching default status color/name if applicable
            $defaultStatus = LeadStatus::query()
                ->where('pipeline_stage_id', $stage->id)
                ->first();

            if ($defaultStatus !== null && $stage->statuses()->count() === 1) {
                $defaultStatus->update([
                    'name_ar' => $stage->name_ar,
                    'color' => $stage->color,
                ]);
            }
        });

        PipelineStage::clearSidebarCache();

        return redirect()
            ->route('v2.settings.stages.index')
            ->with('success', 'تم حفظ تعديلات المرحلة بنجاح.');
    }

    public function destroy(PipelineStage $stage): RedirectResponse
    {
        $this->assertCrmDatabase();

        // 1. Primary stages cannot be deleted
        if ($stage->isPrimary()) {
            return redirect()
                ->route('v2.settings.stages.index')
                ->withErrors([
                    'stage' => 'المراحل الأساسية لا يمكن حذفها.',
                ]);
        }

        // 2. Safety Rule: Stage containing leads cannot be deleted
        if ($stage->hasLeads()) {
            return redirect()
                ->route('v2.settings.stages.index')
                ->withErrors([
                    'stage' => 'لا يمكن حذف هذه المرحلة لوجود عملاء مرتبطين بها حالياً. يرجى نقل العملاء إلى مرحلة أخرى أولاً.',
                ]);
        }

        DB::transaction(function () use ($stage): void {
            // Delete associated statuses with 0 leads
            LeadStatus::query()
                ->where('pipeline_stage_id', $stage->id)
                ->delete();

            $stage->delete();
        });

        PipelineStage::clearSidebarCache();

        return redirect()
            ->route('v2.settings.stages.index')
            ->with('success', 'تم حذف المرحلة بنجاح.');
    }

    // Donation types and purposes management
    public function storeDonationType(Request $request): RedirectResponse
    {
        $this->assertCrmDatabase();

        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:100'],
        ]);

        $nextPos = (int) (DonationType::query()->max('position') ?? 0) + 1;

        DonationType::query()->create([
            'name_ar' => trim($validated['name_ar']),
            'is_active' => true,
            'position' => $nextPos,
        ]);

        return redirect()
            ->route('v2.settings.stages.index')
            ->with('success', 'تمت إضافة نوع التبرع بنجاح.');
    }

    public function toggleDonationType(DonationType $type): RedirectResponse
    {
        $this->assertCrmDatabase();
        $type->update(['is_active' => ! $type->is_active]);

        return redirect()
            ->route('v2.settings.stages.index')
            ->with('success', 'تم تحديث حالة نوع التبرع.');
    }

    public function storeDonationPurpose(Request $request): RedirectResponse
    {
        $this->assertCrmDatabase();

        $validated = $request->validate([
            'name_ar' => ['required', 'string', 'max:150'],
        ]);

        $nextPos = (int) (DonationPurpose::query()->max('position') ?? 0) + 1;

        DonationPurpose::query()->create([
            'name_ar' => trim($validated['name_ar']),
            'is_active' => true,
            'position' => $nextPos,
        ]);

        return redirect()
            ->route('v2.settings.stages.index')
            ->with('success', 'تمت إضافة غرض التبرع بنجاح.');
    }

    public function toggleDonationPurpose(DonationPurpose $purpose): RedirectResponse
    {
        $this->assertCrmDatabase();
        $purpose->update(['is_active' => ! $purpose->is_active]);

        return redirect()
            ->route('v2.settings.stages.index')
            ->with('success', 'تم تحديث حالة غرض التبرع.');
    }

    private function assertCrmDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}
