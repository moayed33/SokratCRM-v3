<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CollectionActivity;
use App\Models\CollectionCase;
use App\Models\Donation;
use App\Models\LeadFollowup;
use App\Models\LeadStatus;
use App\Models\User;
use App\Security\CrmPermission;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class CollectionController extends Controller
{
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', CollectionCase::class);

        $status = trim((string) $request->query('status'));
        $scope = trim((string) $request->query('scope'));
        $search = trim((string) $request->query('q'));
        $allowedStatuses = $this->statuses();

        $baseQuery = CollectionCase::query()->accessibleTo($request->user());
        $summary = [
            'open' => (clone $baseQuery)->open()->count(),
            'overdue' => (clone $baseQuery)->open()->where('due_at', '<', now())->count(),
            'today' => (clone $baseQuery)->open()->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()])->count(),
            'collected' => (clone $baseQuery)->where('status', CollectionCase::STATUS_COLLECTED)->count(),
        ];

        $collectionCases = $baseQuery
            ->with([
                'lead:id,name,phone,address,governorate,governorate_id,subregion_id,branch_id',
                'lead.governorate',
                'lead.subregion.governorate',
                'governorate',
                'subregion.governorate',
                'branch:id,name_ar,name_en',
                'assignedCollector:id,name,collection_zone,collection_subregion_id,mobile_phone',
                'assignedCollector.collectionSubregion.governorate',
            ])
            ->when(
                isset($allowedStatuses[$status]),
                static fn (Builder $query) => $query->where('status', $status),
            )
            ->when($scope === 'overdue', static fn (Builder $query) => $query->open()->where('due_at', '<', now()))
            ->when($scope === 'today', static fn (Builder $query) => $query->open()->whereBetween('due_at', [now()->startOfDay(), now()->endOfDay()]))
            ->when($scope === 'open', static fn (Builder $query) => $query->open())
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->whereHas('lead', static function (Builder $leadQuery) use ($search): void {
                    $leadQuery->where('name', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->orderByRaw("CASE WHEN status IN ('pending', 'assigned', 'scheduled', 'failed') THEN 0 ELSE 1 END")
            ->orderBy('due_at')
            ->paginate(20)
            ->withQueryString();

        return view('collections.index', [
            'collectionCases' => $collectionCases,
            'summary' => $summary,
            'statuses' => $allowedStatuses,
            'filters' => compact('status', 'scope', 'search'),
        ]);
    }

    public function show(Request $request, CollectionCase $collectionCase): View
    {
        Gate::authorize('view', $collectionCase);
        $collectionCase->load([
            'lead:id,name,phone,email,address,governorate,governorate_id,subregion_id,branch_id,assigned_user_id,assigned_employee',
            'lead.governorate',
            'lead.subregion.governorate',
            'lead.assignedUser:id,name,username,mobile_phone,voip_extension',
            'governorate',
            'subregion.governorate',
            'branch:id,name_ar,name_en',
            'donationType:id,name_ar,name_en',
            'assignedCollector:id,name,collection_zone,collection_subregion_id,mobile_phone,manager_id',
            'assignedCollector.collectionSubregion.governorate',
            'assignedCollector.manager:id,name,username,mobile_phone',
            'createdBy:id,name',
            'completedBy:id,name',
            'activities.actor:id,name',
            'activities.oldCollector:id,name',
            'activities.newCollector:id,name',
            'escalations.collector:id,name',
            'escalations.callCenterUser:id,name,username,mobile_phone',
            'escalations.collectionManager:id,name',
            'donation',
        ]);

        $collectors = collect();
        if (Gate::allows('assign', $collectionCase)) {
            $collectors = User::query()
                ->where('is_active', true)
                ->where('branch_id', $collectionCase->branch_id)
                ->whereHas('groups.permissions', static fn (Builder $query) => $query->where(
                    'permissions.code',
                    CrmPermission::COLLECTIONS_COLLECT->value,
                ))
                ->with(['collectionSubregion.governorate'])
                ->orderBy('name')
                ->get(['id', 'name', 'collection_zone', 'collection_subregion_id', 'mobile_phone']);
        }

        return view('collections.show', [
            'collectionCase' => $collectionCase,
            'collectors' => $collectors,
            'statuses' => $this->statuses(),
        ]);
    }

    public function assign(Request $request, CollectionCase $collectionCase): RedirectResponse
    {
        Gate::authorize('assign', $collectionCase);
        $validated = $request->validate([
            'assigned_collector_user_id' => ['required', 'integer', Rule::exists('users', 'id')->where('is_active', true)],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $collector = User::query()
            ->whereKey((int) $validated['assigned_collector_user_id'])
            ->where('is_active', true)
            ->where('branch_id', $collectionCase->branch_id)
            ->whereHas('groups.permissions', static fn (Builder $query) => $query->where(
                'permissions.code',
                CrmPermission::COLLECTIONS_COLLECT->value,
            ))
            ->firstOrFail();

        DB::transaction(function () use ($collectionCase, $collector, $request, $validated): void {
            $lockedCase = CollectionCase::query()->lockForUpdate()->findOrFail($collectionCase->id);
            $this->ensureOpen($lockedCase);
            $oldStatus = $lockedCase->status;
            $oldCollectorId = $lockedCase->assigned_collector_user_id;

            $lockedCase->update([
                'assigned_collector_user_id' => $collector->id,
                'assigned_by_user_id' => $request->user()->id,
                'status' => CollectionCase::STATUS_ASSIGNED,
            ]);

            CollectionActivity::query()->create([
                'collection_case_id' => $lockedCase->id,
                'actor_user_id' => $request->user()->id,
                'action' => $oldCollectorId === null ? 'assigned' : 'reassigned',
                'from_status' => $oldStatus,
                'to_status' => CollectionCase::STATUS_ASSIGNED,
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'old_collector_user_id' => $oldCollectorId,
                'new_collector_user_id' => $collector->id,
            ]);
        });

        return back()->with('success', __('crm.collection_assigned_success'));
    }

    public function reschedule(Request $request, CollectionCase $collectionCase): RedirectResponse
    {
        abort_unless(Gate::allows('manage', $collectionCase) || Gate::allows('collect', $collectionCase), 403);
        $validated = $request->validate([
            'due_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($collectionCase, $request, $validated): void {
            $lockedCase = CollectionCase::query()->lockForUpdate()->findOrFail($collectionCase->id);
            $this->ensureOpen($lockedCase);
            $this->ensureCollectorStillAssigned($request->user(), $lockedCase);
            $oldDueAt = $lockedCase->due_at;
            $oldStatus = $lockedCase->status;
            $lockedCase->update([
                'due_at' => Carbon::parse($validated['due_at']),
                'status' => CollectionCase::STATUS_SCHEDULED,
            ]);

            CollectionActivity::query()->create([
                'collection_case_id' => $lockedCase->id,
                'actor_user_id' => $request->user()->id,
                'action' => 'rescheduled',
                'from_status' => $oldStatus,
                'to_status' => CollectionCase::STATUS_SCHEDULED,
                'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                'old_due_at' => $oldDueAt,
                'new_due_at' => $lockedCase->due_at,
            ]);
        });

        return back()->with('success', __('crm.collection_rescheduled_success'));
    }

    public function fail(Request $request, CollectionCase $collectionCase): RedirectResponse
    {
        abort_unless(Gate::allows('manage', $collectionCase) || Gate::allows('collect', $collectionCase), 403);
        $validated = $request->validate([
            'notes' => ['required', 'string', 'max:5000'],
        ]);

        DB::transaction(function () use ($collectionCase, $request, $validated): void {
            $lockedCase = CollectionCase::query()->lockForUpdate()->findOrFail($collectionCase->id);
            $this->ensureOpen($lockedCase);
            $this->ensureCollectorStillAssigned($request->user(), $lockedCase);
            $oldStatus = $lockedCase->status;
            $lockedCase->update(['status' => CollectionCase::STATUS_FAILED]);
            CollectionActivity::query()->create([
                'collection_case_id' => $lockedCase->id,
                'actor_user_id' => $request->user()->id,
                'action' => 'failed',
                'from_status' => $oldStatus,
                'to_status' => CollectionCase::STATUS_FAILED,
                'notes' => trim((string) $validated['notes']),
            ]);
        });

        return back()->with('success', __('crm.collection_failed_success'));
    }

    public function complete(Request $request, CollectionCase $collectionCase): RedirectResponse
    {
        Gate::authorize('complete', $collectionCase);
        $validated = $request->validate([
            'donation_receipt' => ['required', 'file', 'mimes:png,jpg,jpeg,webp', 'max:5120'],
            'notes' => ['nullable', 'string', 'max:5000'],
        ]);

        $receipt = $request->file('donation_receipt');
        $originalName = mb_substr(trim((string) $receipt->getClientOriginalName()), 0, 255);
        $receiptPath = $receipt->store('crm-v2/donation-receipts/'.$collectionCase->lead_id, 'local');
        throw_unless(is_string($receiptPath) && $receiptPath !== '', \RuntimeException::class, 'Donation receipt storage failed.');

        try {
            DB::transaction(function () use ($collectionCase, $request, $validated, $receiptPath, $originalName): void {
                $lockedCase = CollectionCase::query()->lockForUpdate()->findOrFail($collectionCase->id);
                $this->ensureOpen($lockedCase);
                abort_unless(
                    $request->user()->isSuperAdmin()
                    || (int) $lockedCase->assigned_collector_user_id === (int) $request->user()->id,
                    403,
                );

                if (Donation::query()->where('collection_case_id', $lockedCase->id)->exists()) {
                    throw ValidationException::withMessages(['collection' => [__('crm.collection_already_completed')]]);
                }

                $collectorName = trim((string) $request->user()->name) ?: 'محصّل';
                $lead = $lockedCase->lead;
                $currentStatusId = $lead ? (int) $lead->lead_status_id : null;

                $oldStatus = $lockedCase->status;
                $lockedCase->update([
                    'status' => CollectionCase::STATUS_COLLECTED,
                    'completed_by_user_id' => $request->user()->id,
                    'completed_at' => now(),
                ]);

                $cycleMonths = match ($lockedCase->cycle) {
                    'monthly' => 1,
                    'quarterly' => 3,
                    'semi_annual' => 6,
                    'annual' => 12,
                    default => null,
                };

                $nextFollowUpAt = $cycleMonths === null
                    ? null
                    : now()->addMonthsNoOverflow($cycleMonths);

                $lockedCase->lead()->update([
                    'next_follow_up_at' => $nextFollowUpAt,
                ]);

                // Create a follow-up record so the lead timeline
                // reflects the collection event
                $followup = LeadFollowup::query()->create([
                    'branch_id' => $lockedCase->branch_id,
                    'lead_id' => $lockedCase->lead_id,
                    'from_status_id' => $currentStatusId,
                    'to_status_id' => $currentStatusId,
                    'employee_name' => $collectorName,
                    'user_id' => $request->user()->id,
                    'communication_type' => 'collection',
                    'outcome' => 'تم تحصيل '
                        .number_format((float) $lockedCase->expected_amount, 2)
                        .' — '
                        .($lockedCase->donation_type ?? 'تبرع'),
                    'next_follow_up_at' => $nextFollowUpAt,
                    'followed_up_at' => now(),
                ]);

                Donation::query()->create([
                    'lead_id' => $lockedCase->lead_id,
                    'lead_followup_id' => $followup->id,
                    'donation_type_id' => $lockedCase->donation_type_id,
                    'donation_type' => $lockedCase->donation_type,
                    'amount' => $lockedCase->expected_amount,
                    'cycle' => $lockedCase->cycle,
                    'donation_way' => 'collection',
                    'instant_donation_method_id' => null,
                    'collection_case_id' => $lockedCase->id,
                    'receipt_path' => $receiptPath,
                    'receipt_original_name' => $originalName,
                    'donated_at' => now(),
                    'recorded_by_user_id' => $request->user()->id,
                ]);

                CollectionActivity::query()->create([
                    'collection_case_id' => $lockedCase->id,
                    'actor_user_id' => $request->user()->id,
                    'action' => 'collected',
                    'from_status' => $oldStatus,
                    'to_status' => CollectionCase::STATUS_COLLECTED,
                    'notes' => trim((string) ($validated['notes'] ?? '')) ?: null,
                ]);
            });
        } catch (\Throwable $exception) {
            Storage::disk('local')->delete($receiptPath);
            throw $exception;
        }

        return back()->with('success', __('crm.collection_completed_success'));
    }

    public function cancel(Request $request, CollectionCase $collectionCase): RedirectResponse
    {
        Gate::authorize('cancel', $collectionCase);
        $validated = $request->validate(['notes' => ['required', 'string', 'max:5000']]);

        DB::transaction(function () use ($collectionCase, $request, $validated): void {
            $lockedCase = CollectionCase::query()->lockForUpdate()->findOrFail($collectionCase->id);
            $this->ensureOpen($lockedCase);
            $oldStatus = $lockedCase->status;
            $lockedCase->update(['status' => CollectionCase::STATUS_CANCELLED]);
            CollectionActivity::query()->create([
                'collection_case_id' => $lockedCase->id,
                'actor_user_id' => $request->user()->id,
                'action' => 'cancelled',
                'from_status' => $oldStatus,
                'to_status' => CollectionCase::STATUS_CANCELLED,
                'notes' => trim((string) $validated['notes']),
            ]);
        });

        return back()->with('success', __('crm.collection_cancelled_success'));
    }

    public function receipt(Request $request, CollectionCase $collectionCase, bool $download = false): BinaryFileResponse
    {
        Gate::authorize('view', $collectionCase);
        $donation = $collectionCase->donation()->firstOrFail();
        abort_if(blank($donation->receipt_path) || ! Storage::disk('local')->exists($donation->receipt_path), 404);

        $headers = [
            'Cache-Control' => 'private, no-store, max-age=0',
            'Pragma' => 'no-cache',
            'X-Content-Type-Options' => 'nosniff',
        ];

        $response = $download
            ? response()->download(Storage::disk('local')->path($donation->receipt_path), $donation->receipt_original_name ?: 'donation-receipt', $headers)
            : response()->file(Storage::disk('local')->path($donation->receipt_path), $headers);
        $response->headers->set('Cache-Control', 'private, no-store, max-age=0');

        return $response;
    }

    public function downloadReceipt(Request $request, CollectionCase $collectionCase): BinaryFileResponse
    {
        return $this->receipt($request, $collectionCase, true);
    }

    /** @return array<string, string> */
    private function statuses(): array
    {
        return [
            CollectionCase::STATUS_PENDING => __('crm.collection_status_pending'),
            CollectionCase::STATUS_ASSIGNED => __('crm.collection_status_assigned'),
            CollectionCase::STATUS_SCHEDULED => __('crm.collection_status_scheduled'),
            CollectionCase::STATUS_AWAITING_CALL_CENTER => __('crm.collection_status_awaiting_call_center'),
            CollectionCase::STATUS_FAILED => __('crm.collection_status_failed'),
            CollectionCase::STATUS_COLLECTED => __('crm.collection_status_collected'),
            CollectionCase::STATUS_CANCELLED => __('crm.collection_status_cancelled'),
        ];
    }

    private function ensureOpen(CollectionCase $collectionCase): void
    {
        if (! in_array($collectionCase->status, CollectionCase::OPEN_STATUSES, true)) {
            throw ValidationException::withMessages(['collection' => [__('crm.collection_closed')]]);
        }
    }

    private function ensureCollectorStillAssigned(User $user, CollectionCase $collectionCase): void
    {
        if ($user->isSuperAdmin() || Gate::forUser($user)->allows('manage', $collectionCase)) {
            return;
        }

        abort_unless(
            (int) $collectionCase->assigned_collector_user_id === (int) $user->getKey(),
            403,
        );
    }
}
