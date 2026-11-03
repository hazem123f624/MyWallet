<?php

namespace App\Services\Messages;

use App\Services\Messages\types\EmailMessageService;
use App\Services\Messages\types\PushNotificationService;
use App\Services\Messages\types\SmsMessageService;
use App\Services\Messages\types\WhatsAppMessageService;

class MessageFactory
{
    /*
     * ─── How to Use ──────────────────────────────────────────────────────
     *
     *  Email:
     *    MessageFactory::make('email')->send([
     *        'to' => 'user@test.com', 'subject' => 'Welcome',
     *        'view' => 'emails.welcome', 'body' => ['name' => 'Ahmed'],
     *        'queue' => true,  // optional — send in background
     *    ]);
     *
     *  SMS:
     *    MessageFactory::make('sms')->send([
     *        'to' => '+201234567890', 'body' => 'Your OTP is 1234',
     *    ]);
     *
     *  WhatsApp:
     *    MessageFactory::make('whatsapp')->send([
     *        'to' => '+201234567890', 'body' => 'Order ready!',
     *        'template' => 'order_ready', 'params' => ['12345'],  // optional
     *    ]);
     *
     *  Push Notification (FCM):
     *    MessageFactory::make('push')->send([
     *        'to' => 'device_fcm_token',
     *        'title' => 'New Order', 'body' => 'You have a new order',
     *        'data' => ['order_id' => '1234'],  // optional
     *    ]);
     *
     * ─────────────────────────────────────────────────────────────────────
     */

    const EMAIL = 'email';

    const SMS = 'sms';

    const WHATSAPP = 'whatsapp';

    const PUSH = 'push';

    public static function make(string $type): MessageServiceInterface
    {
        return match ($type) {
            static::EMAIL    => new EmailMessageService,
            static::SMS      => new SmsMessageService,
            static::WHATSAPP => new WhatsAppMessageService,
            static::PUSH     => new PushNotificationService,
            default          => throw new \Exception("Unsupported message type: {$type}"),
        };
    }
}
