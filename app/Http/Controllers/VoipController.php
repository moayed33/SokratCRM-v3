<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Services\VoipCallAnalytics;
use App\Services\VoipService;
use App\Support\CrmDatabaseGuard;
use App\Support\VoipCredentials;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class VoipController extends Controller
{
    public function settings(Request $request, VoipService $voip): View
    {
        $this->assertCrmDatabase();

        $isConfigured = $voip->isConfigured();
        $health = null;
        $capabilities = null;
        $error = null;
        $credentials = VoipCredentials::all();

        if ($isConfigured) {
            try {
                $health = $voip->health();
                $capabilities = $voip->capabilities();
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return view('settings.voip', [
            'isConfigured' => $isConfigured,
            'apiUrl' => $credentials['api_url'] ?? config('voip.api_url'),
            'clientId' => $credentials['client_id'] ?? config('voip.client_id'),
            'health' => $health,
            'capabilities' => $capabilities,
            'error' => $error,
        ]);
    }

    public function pair(Request $request, VoipService $voip): RedirectResponse
    {
        $this->assertCrmDatabase();

        $validated = $request->validate([
            'pairing_code' => ['required', 'string', 'max:50'],
            'api_url' => ['required', 'url', 'max:255'],
        ], [
            'pairing_code.required' => 'رمز الاقتران مطلوب.',
            'api_url.required' => 'رابط خادم VoIP مطلوب.',
            'api_url.url' => 'رابط خادم VoIP غير صحيح.',
        ]);

        $apiUrl = rtrim($validated['api_url'], '/');
        $pairingCode = trim($validated['pairing_code']);
        $origin = $request->schemeAndHttpHost();

        try {
            $tempVoip = new VoipService($apiUrl, '', 10);
            $result = $tempVoip->pair(
                $pairingCode,
                config('app.name', 'SOKRAT CRM V2'),
                $origin,
                '20'
            );

            Log::info('VoIP pairing attempt succeeded', [
                'api_url' => $apiUrl,
                'origin' => $origin,
                'response_keys' => array_keys(is_array($result) ? $result : []),
            ]);

            $clientId = $result['client_id'] ?? '';
            $clientSecret = $result['client_secret'] ?? '';

            if (empty($clientId) || empty($clientSecret)) {
                Log::warning('VoIP pairing response missing credentials', [
                    'api_url' => $apiUrl,
                    'response' => is_array($result) ? array_diff_key($result, array_flip(['client_secret'])) : $result,
                ]);

                return back()->with('error', 'فشل الاقتران: لم يتم إرجاع بيانات الاعتماد.');
            }

            VoipCredentials::update([
                'api_url' => $apiUrl,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
            ]);
            \Illuminate\Support\Facades\Cache::forget('voip.server.connected');

            return back()->with('success', 'تم الاقتران بنجاح مع خادم Sokrat VoIP!');
        } catch (Throwable $e) {
            Log::error('VoIP pairing attempt failed', [
                'api_url' => $apiUrl,
                'origin' => $origin,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطأ في الاقتران: '.$e->getMessage());
        }
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $this->assertCrmDatabase();

        try {
            VoipCredentials::update([
                'client_id' => '',
                'client_secret' => '',
            ]);
            \Illuminate\Support\Facades\Cache::forget('voip.server.connected');

            return back()->with('success', 'تم فصل الارتباط عن خادم Sokrat VoIP بنجاح.');
        } catch (Throwable $e) {
            return back()->with('error', 'تعذر فصل الارتباط: '.$e->getMessage());
        }
    }

    public function leadCalls(Lead $lead, Request $request, VoipCallAnalytics $analytics): JsonResponse
    {
        $this->assertCrmDatabase();
        abort_unless($lead->isAccessibleTo($request->user()), 403, 'غير مصرح بالاطلاع على اتصالات هذا العميل.');

        if (empty($lead->phone) && $lead->additionalPhones()->doesntExist()) {
            return response()->json([
                'success' => false,
                'error' => 'لا يوجد رقم هاتف للعميل.',
                'calls' => [],
            ], 400);
        }

        $filters = $request->validate([
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'direction' => ['nullable', 'in:inbound,outbound,internal'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:250'],
        ]);
        $filters = array_filter($filters, static fn ($value): bool => $value !== null && $value !== '');

        try {
            $insights = $analytics->forLead($lead, $filters);

            return response()->json([
                'success' => true,
                ...$insights,
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'calls' => [],
                'summary' => [
                    'total_calls' => 0,
                    'total_duration' => 0,
                    'total_duration_minutes' => 0,
                    'answered_calls' => 0,
                    'missed_calls' => 0,
                    'inbound_calls' => 0,
                    'outbound_calls' => 0,
                    'total_talk_seconds' => 0,
                    'answer_rate_percent' => 0,
                ],
                'charts' => [
                    'timeline' => ['labels' => [], 'calls' => [], 'talk_time' => []],
                    'dispositions' => ['labels' => [], 'data' => []],
                    'directions' => ['labels' => [], 'data' => []],
                    'duration' => ['under_30s' => 0, 'from_30s_to_2m' => 0, 'from_2m_to_5m' => 0, 'over_5m' => 0],
                ],
            ]);
        }
    }

    public function streamRecording(Request $request, string $mediaId, VoipService $voip): Response|JsonResponse
    {
        $this->assertCrmDatabase();

        try {
            $voipResponse = $voip->streamRecording($mediaId, $request->header('Range'));

            if ($voipResponse->failed()) {
                return response()->json([
                    'success' => false,
                    'error' => 'تعذر جلب ملف التسجيل الصوتي.',
                ], $voipResponse->status());
            }

            $headers = [
                'Content-Type' => $voipResponse->header('Content-Type', 'audio/wav'),
                'Content-Length' => $voipResponse->header('Content-Length'),
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'private, max-age=3600',
            ];

            if ($voipResponse->hasHeader('Content-Range')) {
                $headers['Content-Range'] = $voipResponse->header('Content-Range');
            }

            return response(
                $voipResponse->body(),
                $voipResponse->status(),
                array_filter($headers)
            );
        } catch (Throwable $e) {
            Log::error('Recording streaming error', ['mediaId' => $mediaId, 'error' => $e->getMessage()]);

            return response()->json([
                'success' => false,
                'error' => 'خطأ أثناء تحميل ملف التسجيل الصوتي.',
            ], 500);
        }
    }

    public function livePanel(Request $request, VoipService $voip): View|RedirectResponse
    {
        $this->assertCrmDatabase();

        if (! Auth::check()) {
            return redirect()->route('login');
        }

        return view('voip.live', $this->liveState($voip));
    }

    public function liveData(VoipService $voip): JsonResponse
    {
        $this->assertCrmDatabase();

        $state = $this->liveState($voip);

        if (! $state['isConfigured']) {
            return response()->json([
                'success' => false,
                'error' => 'VoIP PBX is not configured.',
            ], 409);
        }

        if ($state['errorMessage'] !== null) {
            return response()->json([
                'success' => false,
                'error' => 'Live PBX state is temporarily unavailable.',
            ], 502);
        }

        return response()->json([
            'success' => true,
            'html' => view('voip.partials.extension-grid', [
                'extensions' => $state['extensions'],
            ])->render(),
            'counts' => $state['counts'],
            'refreshed_at' => now()->toIso8601String(),
        ])->header('Cache-Control', 'private, no-store');
    }

    /**
     * @return array{
     *     isConfigured: bool,
     *     extensions: \Illuminate\Support\Collection<int, array<string, mixed>>,
     *     counts: array{total: int, online: int, in_call: int, offline: int},
     *     errorMessage: ?string
     * }
     */
    private function liveState(VoipService $voip): array
    {
        $isConfigured = $voip->isConfigured();
        $extensionsList = [];
        $errorMessage = null;

        $crmUsersByExtension = User::query()
            ->whereNotNull('voip_extension')
            ->where('is_active', true)
            ->get(['id', 'name', 'voip_extension'])
            ->keyBy(static fn (User $user): string => (string) $user->voip_extension);

        if ($isConfigured) {
            try {
                $rawExtensions = $voip->getExtensions();
                $extensionsList = $rawExtensions['extensions'] ?? $rawExtensions['data'] ?? $rawExtensions;
                if (! is_array($extensionsList)) {
                    $extensionsList = [];
                }
            } catch (Throwable $exception) {
                $errorMessage = $exception->getMessage();
            }
        }

        $extensions = collect($extensionsList)
            ->map(static function (mixed $extension) use ($crmUsersByExtension): array {
                $data = is_array($extension) ? $extension : [];
                $extensionNumber = is_array($extension)
                    ? (string) ($data['extension'] ?? $data['id'] ?? $data['ext'] ?? '')
                    : (string) $extension;
                $crmUser = $crmUsersByExtension->get($extensionNumber);
                $rawStatus = strtolower((string) ($data['status'] ?? $data['state'] ?? ''));
                $call = is_array($data['call'] ?? null) ? $data['call'] : [];
                $inCall = ! empty($data['in_call'])
                    || ! empty($data['incall'])
                    || $call !== []
                    || in_array($rawStatus, ['incall', 'in_call', 'in-call', 'busy', 'talking', 'active', 'ringing'], true);
                $online = $inCall
                    || ! empty($data['online'])
                    || ! empty($data['registered'])
                    || in_array($rawStatus, ['online', 'registered', 'available', 'idle', 'ready'], true);
                $callPartner = trim((string) ($call['partner'] ?? $data['partner'] ?? ''));
                $callState = trim((string) ($call['state'] ?? $data['call_state'] ?? ''));

                return [
                    'extension' => $extensionNumber,
                    'name' => $crmUser?->name
                        ?? (string) ($data['name'] ?? $data['display_name'] ?? $data['callerid'] ?? $extensionNumber),
                    'crm_user_name' => $crmUser?->name,
                    'online' => $online,
                    'in_call' => $inCall,
                    'status' => $inCall ? 'incall' : ($online ? 'online' : 'offline'),
                    'call_partner' => $callPartner !== '' ? $callPartner : null,
                    'call_state' => $callState !== '' ? $callState : null,
                    'call_started_at' => $call['started_at'] ?? null,
                    'call_duration_seconds' => max(0, (int) ($call['duration_seconds'] ?? 0)),
                ];
            })
            ->filter(static fn (array $extension): bool => $extension['extension'] !== '')
            ->values();

        if ($extensions->isEmpty() && $crmUsersByExtension->isNotEmpty()) {
            $extensions = $crmUsersByExtension
                ->map(static fn (User $crmUser): array => [
                    'extension' => (string) $crmUser->voip_extension,
                    'name' => $crmUser->name,
                    'crm_user_name' => $crmUser->name,
                    'online' => false,
                    'in_call' => false,
                    'status' => 'offline',
                    'call_partner' => null,
                    'call_state' => null,
                    'call_started_at' => null,
                    'call_duration_seconds' => 0,
                ])
                ->values();
        }

        $counts = [
            'total' => $extensions->count(),
            'online' => $extensions->where('online', true)->count(),
            'in_call' => $extensions->where('in_call', true)->count(),
            'offline' => $extensions
                ->filter(static fn (array $extension): bool => ! $extension['online'] && ! $extension['in_call'])
                ->count(),
        ];

        return compact('isConfigured', 'extensions', 'counts', 'errorMessage');
    }

    public function extensionStats(string $extension, Request $request, VoipService $voip): JsonResponse
    {
        $this->assertCrmDatabase();

        try {
            $filters = $request->only(['from', 'to', 'direction']);
            $stats = $voip->getExtensionStats($extension, $filters);

            return response()->json($stats);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    
    public function softphone(Request $request, VoipService $voip): RedirectResponse|View|JsonResponse
    {
        $this->assertCrmDatabase();

        $user = Auth::user();
        if (empty($user->voip_extension)) {
            return response()->json([
                'success' => false,
                'error' => 'No VoIP extension assigned to your account.',
            ], 422);
        }

        try {
            $ticketData = null;
            if ($voip->isConfigured()) {
                try {
                    $ticketData = $voip->createEmbedTicket(
                        $user->id,
                        $user->name,
                        (string) $user->voip_extension,
                        ['softphone:use']
                    );
                } catch (Throwable $e) {
                    Log::warning('VoIP ticket via VoipService failed: ' . $e->getMessage());
                }

            }

            if (empty($ticketData['ticket'])) {
                return response()->json([
                    'success' => false,
                    'error' => 'Could not acquire softphone ticket.',
                ], 502);
            }

            $maskParam = ! $user->can(\App\Security\CrmPermission::LEADS_VIEW_FULL_PHONE->value) ? '&mask_phone=1' : '';
            $embedUrl = '/phone/embed?ticket=' . urlencode((string) $ticketData['ticket']) . $maskParam;

            return redirect()->to($embedUrl);
        } catch (Throwable $e) {
            Log::error('Softphone embed ticket failed', [
                'user_id' => $user->id,
                'extension' => $user->voip_extension,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Could not initialize softphone session: ' . $e->getMessage(),
            ], 502);
        }
    }

    private function assertCrmDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }
}
