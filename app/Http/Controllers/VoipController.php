<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\User;
use App\Services\VoipService;
use App\Support\CrmDatabaseGuard;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
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
            'apiUrl' => config('voip.api_url'),
            'clientId' => config('voip.client_id'),
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

            $this->updateEnvFile([
                'VOIP_API_URL' => $apiUrl,
                'VOIP_CLIENT_ID' => $clientId,
                'VOIP_CLIENT_SECRET' => $clientSecret,
            ]);

            return back()->with('success', 'تم الاقتران بنجاح مع خادم Sokrat VoIP!');
        } catch (Throwable $e) {
            Log::error('VoIP pairing attempt failed', [
                'api_url' => $apiUrl,
                'origin' => $origin,
                'exception' => get_class($e),
                'message' => $e->getMessage(),
            ]);

            return back()->with('error', 'خطأ في الاقتران: ' . $e->getMessage());
        }
    }

    public function disconnect(Request $request): RedirectResponse
    {
        $this->assertCrmDatabase();

        try {
            $this->updateEnvFile([
                'VOIP_CLIENT_ID' => '',
                'VOIP_CLIENT_SECRET' => '',
            ]);

            return back()->with('success', 'تم فصل الارتباط عن خادم Sokrat VoIP بنجاح.');
        } catch (Throwable $e) {
            return back()->with('error', 'تعذر فصل الارتباط: ' . $e->getMessage());
        }
    }

    public function leadCalls(Lead $lead, Request $request, VoipService $voip): JsonResponse
    {
        $this->assertCrmDatabase();

        if (empty($lead->phone)) {
            return response()->json([
                'success' => false,
                'error' => 'لا يوجد رقم هاتف للعميل.',
                'calls' => [],
            ], 400);
        }

        try {
            $filters = $request->only(['start_date', 'end_date', 'direction', 'limit']);
            $raw = $voip->getCustomerCallHistory($lead->phone, $filters);
            $rawCalls = $raw['calls'] ?? $raw['data'] ?? (is_array($raw) && array_is_list($raw) ? $raw : []);

            if (empty($rawCalls)) {
                $cleanPhone = preg_replace('/[^\d+]/', '', trim($lead->phone));
                if (! empty($cleanPhone) && $cleanPhone !== $lead->phone) {
                    $raw = $voip->getCustomerCallHistory($cleanPhone, $filters);
                    $rawCalls = $raw['calls'] ?? $raw['data'] ?? (is_array($raw) && array_is_list($raw) ? $raw : []);
                }
            }
            $userMap = User::query()
                ->whereNotNull('voip_extension')
                ->where('voip_extension', '!=', '')
                ->pluck('name', 'voip_extension')
                ->all();

            $calls = array_map(function ($c) use ($userMap) {
                $hasRec = ! empty($c['has_recording']) || ! empty($c['recording']['available']);
                $mediaId = $c['media_id'] ?? ($c['recording']['media_id'] ?? null);

                $ext = $c['agent_extension'] ?? null;
                $crmUserName = $ext !== null ? ($userMap[(string) $ext] ?? null) : null;
                $agentDisplayName = $crmUserName ?: ($c['agent_name'] ?? '');

                $agentInfo = $ext !== null && $ext !== ''
                    ? ($agentDisplayName !== '' ? "{$ext} ({$agentDisplayName})" : (string) $ext)
                    : ($c['agent_name'] ?? '—');

                $src = $c['direction'] === 'inbound'
                    ? ($c['customer_number'] ?? '—')
                    : $agentInfo;

                $dst = $c['direction'] === 'outbound'
                    ? ($c['customer_number'] ?? '—')
                    : $agentInfo;

                return [
                    'id' => $c['id'] ?? null,
                    'call_date' => $c['call_date'] ?? $c['started_at'] ?? '—',
                    'direction' => $c['direction'] ?? 'unknown',
                    'src' => $src,
                    'dst' => $dst,
                    'customer_number' => $c['customer_number'] ?? null,
                    'agent_extension' => $c['agent_extension'] ?? null,
                    'agent_name' => $crmUserName ?: ($c['agent_name'] ?? null),
                    'duration_formatted' => $c['duration_formatted'] ?? (isset($c['duration_seconds']) ? $c['duration_seconds'] . ' ثانية' : (isset($c['billsec']) ? $c['billsec'] . ' ثانية' : '0 ثانية')),
                    'disposition' => $c['disposition'] ?? '—',
                    'has_recording' => $hasRec,
                    'media_id' => $mediaId,
                ];
            }, $rawCalls);

            return response()->json([
                'success' => true,
                'calls' => $calls,
                'meta' => $raw['meta'] ?? [],
            ]);
        } catch (Throwable $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'calls' => [],
            ], 200);
        }
    }

    public function streamRecording(string $mediaId, VoipService $voip): Response|JsonResponse
    {
        $this->assertCrmDatabase();

        try {
            $voipResponse = $voip->streamRecording($mediaId);

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

        $user = Auth::user();
        if (! $user) {
            return redirect()->route('login');
        }

        try {
            $requestedScopes = [
                'live:read',
                'live:listen',
                'live:whisper',
                'live:barge',
                'live:hangup',
                'stats:read',
                'calls:read',
                'extensions:read',
            ];

            if ($user->hasPermission('voip.recordings')) {
                $requestedScopes[] = 'recordings:read';
            }

            $supervisorExt = ! empty($user->voip_extension) ? (string) $user->voip_extension : null;

            $ticketData = $voip->createEmbedTicket(
                $user->id,
                $user->name,
                $supervisorExt,
                $requestedScopes
            );

            $rawTicket = $ticketData['ticket'] ?? '';
            $voipHost = preg_replace('#/api/integrations/crm/v1/?$#', '', config('voip.api_url'));
            $embedUrl = "{$voipHost}/embed/crm/live?ticket=" . urlencode($rawTicket) . "&lang=ar";

            return view('voip.live', [
                'embedUrl' => $embedUrl,
                'expiresAt' => $ticketData['expires_at'] ?? null,
                'scopes' => $ticketData['effective_scopes'] ?? [],
            ]);
        } catch (Throwable $e) {
            return view('voip.live', [
                'embedUrl' => null,
                'error' => 'تعذر إنشاء تذكرة المراقبة المباشرة: ' . $e->getMessage(),
            ]);
        }
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

    private function assertCrmDatabase(): void
    {
        CrmDatabaseGuard::ensureConnected();
    }

    private function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');
        if (! file_exists($envPath)) {
            return;
        }

        $content = file_get_contents($envPath);
        foreach ($data as $key => $value) {
            $value = '"' . addcslashes($value, '"\\') . '"';
            if (preg_match("/^{$key}=.*/m", $content)) {
                $content = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $content);
            } else {
                $content .= "\n{$key}={$value}";
            }
        }

        file_put_contents($envPath, $content);

        if (file_exists(app()->getCachedConfigPath())) {
            @unlink(app()->getCachedConfigPath());
        }
        Artisan::call('config:clear');

        foreach ($data as $key => $value) {
            $configKey = 'voip.' . strtolower(str_replace('VOIP_', '', $key));
            config([$configKey => $value]);
        }
    }
}
