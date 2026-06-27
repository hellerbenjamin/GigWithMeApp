<?php

namespace App\Notifications\Channels;

use App\Models\MobilePushToken;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Delivers notifications to mobile devices via Expo's push API.
 * Sends one request per token so each device is independently retried.
 * See https://docs.expo.dev/push-notifications/sending-notifications/
 */
class MobilePushChannel
{
    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toMobilePush')) {
            return;
        }

        $tokens = MobilePushToken::where('user_id', $notifiable->getKey())
            ->pluck('token');

        if ($tokens->isEmpty()) {
            return;
        }

        $message = $notification->toMobilePush($notifiable);

        $payloads = $tokens->map(fn (string $token) => array_merge(
            ['to' => $token],
            $message,
        ))->values()->all();

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'Accept-Encoding' => 'gzip, deflate',
            'Content-Type' => 'application/json',
        ])->post('https://exp.host/--/api/v2/push/send', $payloads);

        if (! $response->successful()) {
            Log::warning('Expo push failed', [
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);
        }
    }
}
