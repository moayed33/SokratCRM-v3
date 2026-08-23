<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificationPreference;
use App\Models\PushSubscription;
use App\Services\Notifications\WebPushEndpointValidator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PushSubscriptionController extends Controller
{
    public function store(
        Request $request,
        WebPushEndpointValidator $endpointValidator,
    ): JsonResponse {
        $validated = $request->validate([
            'endpoint' => [
                'required',
                'url',
                'max:5000',
                function (string $attribute, mixed $value, \Closure $fail) use ($endpointValidator): void {
                    if (! is_string($value) || ! $endpointValidator->isAllowed($value)) {
                        $fail(__('crm.browser_push_endpoint_invalid'));
                    }
                },
            ],
            'keys' => [
                'required',
                'array',
                function (string $attribute, mixed $value, \Closure $fail) use ($endpointValidator): void {
                    if (! is_array($value)
                        || ! $endpointValidator->hasValidKeys(
                            (string) ($value['p256dh'] ?? ''),
                            (string) ($value['auth'] ?? ''),
                        )) {
                        $fail(__('crm.browser_push_keys_invalid'));
                    }
                },
            ],
            'keys.p256dh' => ['required', 'string', 'max:255'],
            'keys.auth' => ['required', 'string', 'max:255'],
            'contentEncoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);
        $endpoint = $validated['endpoint'];
        $user = $request->user();

        PushSubscription::query()->updateOrCreate([
            'endpoint_hash' => hash('sha256', $endpoint),
        ], [
            'user_id' => $user->getKey(),
            'endpoint' => $endpoint,
            'public_key' => $validated['keys']['p256dh'],
            'auth_token' => $validated['keys']['auth'],
            'content_encoding' => $validated['contentEncoding'] ?? 'aes128gcm',
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ]);

        NotificationPreference::query()->updateOrCreate(
            ['user_id' => $user->getKey()],
            ['push_enabled' => true],
        );

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'endpoint' => ['required', 'url', 'max:5000'],
        ]);
        $user = $request->user();

        $user->pushSubscriptions()
            ->where('endpoint_hash', hash('sha256', $validated['endpoint']))
            ->delete();

        if (! $user->pushSubscriptions()->exists()) {
            NotificationPreference::query()
                ->where('user_id', $user->getKey())
                ->update(['push_enabled' => false]);
        }

        return response()->json(['success' => true]);
    }
}
