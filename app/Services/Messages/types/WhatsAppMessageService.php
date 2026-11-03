<?php

namespace App\Services\Messages\types;

use App\Services\Messages\MessageServiceInterface;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppMessageService implements MessageServiceInterface
{
    /*
     * Usage:
     *   MessageFactory::make('whatsapp')->send([
     *       'to'       => '+201234567890',
     *       'body'     => 'Hello! Your order is ready.',
     *       'template' => 'order_ready',            // optional — WhatsApp template name
     *       'params'   => ['order_id' => '12345'],  // optional — template parameters
     *   ]);
     *
     * Config (config/services.php):
     *   'whatsapp' => [
     *       'url'      => env('WHATSAPP_API_URL', 'https://graph.facebook.com/v17.0'),
     *       'token'    => env('WHATSAPP_TOKEN'),
     *       'phone_id' => env('WHATSAPP_PHONE_ID'),
     *   ],
     */

    private array $provider;

    public function __construct()
    {
        $this->provider = config('services.whatsapp', []);
    }

    public function send(array $data): void
    {
        $url = $this->provider['url'] ?? null;
        $token = $this->provider['token'] ?? null;
        $phoneId = $this->provider['phone_id'] ?? null;

        if (! $url || ! $token || ! $phoneId) {
            throw new \RuntimeException('WhatsApp provider is not configured. Set WHATSAPP_API_URL, WHATSAPP_TOKEN, WHATSAPP_PHONE_ID in .env');
        }

        $payload = $this->buildPayload($data);

        $response = Http::withToken($token)
            ->post("{$url}/{$phoneId}/messages", $payload);

        if ($response->failed()) {
            Log::error('WhatsApp send failed', [
                'to'     => $data['to'],
                'status' => $response->status(),
                'body'   => $response->body(),
            ]);

            throw new \RuntimeException('Failed to send WhatsApp message: ' . $response->body());
        }
    }

    private function buildPayload(array $data): array
    {
        // Template message
        if (! empty($data['template'])) {
            $components = [];
            if (! empty($data['params'])) {
                $parameters = [];
                foreach ($data['params'] as $value) {
                    $parameters[] = ['type' => 'text', 'text' => $value];
                }
                $components[] = ['type' => 'body', 'parameters' => $parameters];
            }

            return [
                'messaging_product' => 'whatsapp',
                'to'                => $data['to'],
                'type'              => 'template',
                'template'          => [
                    'name'       => $data['template'],
                    'language'   => ['code' => $data['language'] ?? 'en'],
                    'components' => $components,
                ],
            ];
        }

        // Plain text message
        return [
            'messaging_product' => 'whatsapp',
            'to'                => $data['to'],
            'type'              => 'text',
            'text'              => ['body' => $data['body'] ?? ''],
        ];
    }
}
