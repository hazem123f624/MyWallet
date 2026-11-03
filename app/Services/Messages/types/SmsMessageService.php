<?php

namespace App\Services\Messages\types;

use App\Services\Messages\MessageServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SmsMessageService implements MessageServiceInterface
{
    /*
     * Usage:
     *   MessageFactory::make('sms')->send([
     *       'to'   => '+201234567890',
     *       'body' => 'Your OTP is 1234',
     *   ]);
     *
     * Config (config/services.php):
     *   'sms' => [
     *       'url'    => env('SMS_API_URL', 'https://sms-provider.com/api/send'),
     *       'key'    => env('SMS_API_KEY'),
     *       'sender' => env('SMS_SENDER_NAME', 'MyApp'),
     *   ],
     */

    private array $provider;

    public function __construct()
    {
        $this->provider = config('services.sms', []);
    }

    public function send(array $data): void
    {
        $url = $this->provider['url'] ?? null;
        if (! $url) {
            if (config('app.env') === 'production') {
                throw new \RuntimeException('SMS provider URL is not configured. Set SMS_API_URL in .env');
            }
            Log::info('SMS send skipped (not in production and URL not configured)', ['to' => $data['to'], 'body' => $data['body'] ?? '']);
            return;
        }

        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . ($this->provider['key'] ?? ''),
        ])->post($url, [
            'to'      => $data['to'],
            'message' => $data['body'] ?? '',
            'sender'  => $data['sender'] ?? $this->provider['sender'] ?? config('app.name'),
        ]);

        if ($response->failed()) {
            Log::error('SMS send failed', [
                'to'     => $data['to'],
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \RuntimeException('Failed to send SMS: ' . $response->body());
        }
    }
}
