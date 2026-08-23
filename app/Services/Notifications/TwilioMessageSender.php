<?php

declare(strict_types=1);

namespace App\Services\Notifications;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class TwilioMessageSender
{
    public function sendSms(string $to, string $body, int $deliveryId): ChannelResult
    {
        $payload = $this->basePayload($to, $body);
        $messagingServiceSid = trim((string) config('crm_notifications.twilio.messaging_service_sid'));
        $from = trim((string) config('crm_notifications.twilio.sms_from'));

        if ($messagingServiceSid !== '') {
            $payload['MessagingServiceSid'] = $messagingServiceSid;
        } elseif ($from !== '') {
            $payload['From'] = $from;
        } else {
            return ChannelResult::suppressed('Twilio SMS sender is not configured.');
        }

        return $this->send($payload, $deliveryId);
    }

    /**
     * @param  array<string, string>  $variables
     */
    public function sendWhatsApp(
        string $to,
        string $locale,
        array $variables,
        int $deliveryId,
    ): ChannelResult {
        $from = trim((string) config('crm_notifications.twilio.whatsapp_from'));
        $contentSid = trim((string) config(
            $locale === 'ar'
                ? 'crm_notifications.twilio.whatsapp_content_sid_ar'
                : 'crm_notifications.twilio.whatsapp_content_sid_en',
        ));

        if ($from === '' || $contentSid === '') {
            return ChannelResult::suppressed('Twilio WhatsApp sender or approved content template is not configured.');
        }

        $payload = [
            'To' => 'whatsapp:'.$to,
            'From' => str_starts_with($from, 'whatsapp:') ? $from : 'whatsapp:'.$from,
            'ContentSid' => $contentSid,
            'ContentVariables' => json_encode($variables, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
        ];

        return $this->send($payload, $deliveryId);
    }

    /** @return array<string, string> */
    private function basePayload(string $to, string $body): array
    {
        return ['To' => $to, 'Body' => $body];
    }

    /** @param array<string, string> $payload */
    private function send(array $payload, int $deliveryId): ChannelResult
    {
        $sid = trim((string) config('crm_notifications.twilio.account_sid'));
        $token = trim((string) config('crm_notifications.twilio.auth_token'));

        if ($sid === '' || $token === '') {
            return ChannelResult::suppressed('Twilio credentials are not configured.');
        }

        $callback = $this->statusCallback($deliveryId);
        if ($callback !== null) {
            $payload['StatusCallback'] = $callback;
        }

        try {
            $response = $this->client($sid, $token)->post(
                sprintf('https://api.twilio.com/2010-04-01/Accounts/%s/Messages.json', rawurlencode($sid)),
                $payload,
            );
        } catch (ConnectionException $exception) {
            return ChannelResult::failed(
                'Twilio request outcome is unknown and was not retried: '
                .mb_substr($exception->getMessage(), 0, 900),
            );
        }

        if (! $response->successful()) {
            return ChannelResult::failed(sprintf(
                'Twilio request failed with HTTP %d: %s',
                $response->status(),
                mb_substr($response->body(), 0, 1000),
            ));
        }

        return ChannelResult::sent((string) $response->json('sid'));
    }

    private function client(string $sid, string $token): PendingRequest
    {
        return Http::asForm()
            ->acceptJson()
            ->withBasicAuth($sid, $token)
            ->timeout(15);
    }

    private function statusCallback(int $deliveryId): ?string
    {
        $callback = trim((string) config('crm_notifications.twilio.status_callback'));
        if ($callback === '') {
            return null;
        }

        return $callback
            .(str_contains($callback, '?') ? '&' : '?')
            .http_build_query(['delivery' => $deliveryId]);
    }
}
