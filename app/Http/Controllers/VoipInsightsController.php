<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\VoipCallAnalytics;
use App\Services\VoipService;
use App\Support\CrmDatabaseGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VoipInsightsController extends Controller
{
    public function profile(Request $request, VoipCallAnalytics $analytics, VoipService $voip): View
    {
        CrmDatabaseGuard::ensureConnected();
        $user = $request->user();
        abort_if($user === null, 401);

        $filters = $this->filters($request);
        $insights = null;
        $error = null;
        if ($voip->isConfigured() && ! empty($user->voip_extension)) {
            try {
                $insights = $analytics->forUser($user, $filters);
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        return view('voip.profile', compact('user', 'filters', 'insights', 'error'));
    }

    public function team(Request $request, VoipCallAnalytics $analytics, VoipService $voip): View
    {
        CrmDatabaseGuard::ensureConnected();
        $viewer = $request->user();
        abort_if($viewer === null, 401);

        $filters = $this->filters($request, true);
        $users = User::query()
            ->with('branch:id,name_ar,name_en')
            ->whereNotNull('voip_extension')
            ->where('voip_extension', '!=', '')
            ->when(! $viewer->isSuperAdmin(), static fn ($query) => $query->where('branch_id', $viewer->branch_id))
            ->orderBy('name')
            ->get();
        $report = null;
        $error = null;
        $isConfigured = $voip->isConfigured();
        $isConnected = $isConfigured ? $voip->isConnected() : false;

        if ($isConfigured) {
            try {
                $report = $analytics->forTeam($users, $filters);
            } catch (\Throwable $exception) {
                $error = $exception->getMessage();
            }
        }

        return view('reports.voip', compact('filters', 'report', 'error', 'users', 'isConfigured', 'isConnected'));
    }

    private function filters(Request $request, bool $withDefaults = false): array
    {
        $validated = $request->validate([
            'from_date' => ['nullable', 'date_format:Y-m-d'],
            'to_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            'direction' => ['nullable', Rule::in(['incoming', 'outgoing', 'internal'])],
            'status' => ['nullable', Rule::in(['answered', 'missed', 'failed', 'busy', 'no_answer'])],
        ]);

        if ($withDefaults && ! $request->hasAny(['from_date', 'to_date', 'direction', 'status'])) {
            $validated['from_date'] = now()->subDays(29)->format('Y-m-d');
            $validated['to_date'] = now()->format('Y-m-d');
        }

        return array_filter($validated, static fn ($value): bool => $value !== null && $value !== '');
    }
}
