<?php

namespace App\Services\Messages\types;

use App\Services\Messages\MessageServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService implements MessageServiceInterface
{
    /*
     * Usage:
     *   MessageFactory::make('push')->send([
     *       'to'    => 'device_fcm_token_here',     // single device token
     *       'title' => 'New Order',
     *       'body'  => 'You have a new order #1234',
     *       'data'  => ['order_id' => '1234'],       // optional — extra payload
     *       'image' => 'https://example.com/img.png', // optional
     *   ]);
     *
     * Config (config/services.php):
     *   'fcm' => [
     *       'url'        => env('FCM_API_URL', 'https://fcm.googleapis.com/fcm/send'),
     *       'server_key' => env('FCM_SERVER_KEY'),
     *   ],
     */

    private array $provider;

    public function __construct()
    {
        $this->provider = config('services.fcm', []);
    }

    public function send(array $data): void
    {
        $url = $this->provider['url'] ?? null;
        $serverKey = $this->provider['server_key'] ?? null;

        if (! $url || ! $serverKey) {
            throw new \RuntimeException('FCM provider is not configured. Set FCM_API_URL and FCM_SERVER_KEY in .env');
        }

        $payload = [
            'to' => $data['to'],
            'notification' => [
                'title' => $data['title'] ?? config('app.name'),
                'body'  => $data['body'] ?? '',
            ],
        ];

        if (! empty($data['image'])) {
            $payload['notification']['image'] = $data['image'];
        }

        if (! empty($data['data'])) {
            $payload['data'] = $data['data'];
        }

        $response = Http::withHeaders([
            'Authorization' => 'key=' . $serverKey,
            'Content-Type'  => 'application/json',
        ])->post($url, $payload);

        if ($response->failed()) {
            Log::error('Push notification failed', [
                'to'     => $data['to'],
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \RuntimeException('Failed to send push notification: ' . $response->body());
        }
    }
}
