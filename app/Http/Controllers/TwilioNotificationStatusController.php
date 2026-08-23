<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\NotificationDelivery;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class TwilioNotificationStatusController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $token = trim((string) config('crm_notifications.twilio.auth_token'));
        abort_if($token === '', 503, 'Twilio status callbacks are not configured.');
        abort_unless($this->validSignature($request, $token), 403);

        $sid = (string) $request->input('MessageSid', '');
        $providerStatus = (string) $request->input('MessageStatus', '');
        $status = match ($providerStatus) {
            'delivered', 'read' => NotificationDelivery::STATUS_DELIVERED,
            'failed', 'undelivered' => NotificationDelivery::STATUS_FAILED,
            'queued', 'accepted', 'sending', 'sent' => NotificationDelivery::STATUS_SENT,
            default => null,
        };

        if ($sid === '' || $status === null) {
            return response()->noContent();
        }

        $delivery = NotificationDelivery::query()
            ->where('provider_message_id', $sid)
            ->first();
        $deliveryId = $request->integer('delivery');

        if (! $delivery && $deliveryId > 0) {
            $delivery = NotificationDelivery::query()
                ->whereKey($deliveryId)
                ->whereIn('channel', ['sms', 'whatsapp'])
                ->where(function ($query) use ($sid): void {
                    $query->whereNull('provider_message_id')
                        ->orWhere('provider_message_id', $sid);
                })
                ->first();
        }

        if (! $delivery) {
            return response()->noContent();
        }

        $terminal = [
            NotificationDelivery::STATUS_DELIVERED,
            NotificationDelivery::STATUS_FAILED,
        ];
        if (in_array($delivery->status, $terminal, true)
            && $delivery->status !== $status) {
            return response()->noContent();
        }

        $delivery->update([
            'status' => $status,
            'provider_message_id' => $sid,
            'sent_at' => $delivery->sent_at ?? now(),
            'delivered_at' => $status === NotificationDelivery::STATUS_DELIVERED
                ? ($delivery->delivered_at ?? now())
                : $delivery->delivered_at,
            'last_error' => $status === NotificationDelivery::STATUS_FAILED
                ? mb_substr((string) $request->input('ErrorMessage', 'Provider delivery failed.'), 0, 4000)
                : null,
            'claim_token' => null,
            'claim_type' => null,
            'processing_started_at' => null,
        ]);

        return response()->noContent();
    }

    private function validSignature(Request $request, string $token): bool
    {
        $signature = (string) $request->header('X-Twilio-Signature', '');
        $url = trim((string) config('crm_notifications.twilio.status_callback'));
        if ($url === '') {
            $url = $request->fullUrl();
        } else {
            $url = strtok($url, '?') ?: $url;
            $queryString = (string) $request->server->get('QUERY_STRING', '');
            if ($queryString !== '') {
                $url .= '?'.$queryString;
            }
        }

        $data = $request->post();
        ksort($data, SORT_STRING);
        foreach ($data as $key => $value) {
            if (is_scalar($value)) {
                $url .= $key.(string) $value;
            }
        }

        $expected = base64_encode(hash_hmac('sha1', $url, $token, true));

        return $signature !== '' && hash_equals($expected, $signature);
    }
}
