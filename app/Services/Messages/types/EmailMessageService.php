<?php

namespace App\Services\Messages\types;

use App\Services\Messages\MessageServiceInterface;
use Illuminate\Support\Facades\Mail;

class EmailMessageService implements MessageServiceInterface
{
    /*
     * Usage:
     *   MessageFactory::make('email')->send([
     *       'to'      => 'user@test.com',
     *       'subject' => 'Welcome',
     *       'view'    => 'emails.welcome',
     *       'body'    => ['name' => 'Ahmed'],
     *       'cc'      => ['manager@test.com'],       // optional
     *       'bcc'     => ['audit@test.com'],          // optional
     *       'from_email' => 'support@yourapp.com',    // optional
     *       'from_name'  => 'Support Team',           // optional
     *       'attachments' => [                        // optional
     *           ['path' => storage_path('app/invoices/inv.pdf'), 'name' => 'Invoice.pdf', 'mime' => 'application/pdf']
     *       ],
     *       'queue'   => true,                        // optional — send in background
     *   ]);
     */

    public function send(array $data): void
    {
        $callback = function ($message) use ($data) {
            $message->to($data['to']);
            $message->subject($data['subject'] ?? 'Notification');

            if (! empty($data['from_email'])) {
                $message->from(
                    $data['from_email'],
                    $data['from_name'] ?? config('mail.from.name')
                );
            }

            if (! empty($data['cc']) && is_array($data['cc'])) {
                $message->cc($data['cc']);
            }

            if (! empty($data['bcc']) && is_array($data['bcc'])) {
                $message->bcc($data['bcc']);
            }

            if (! empty($data['attachments']) && is_array($data['attachments'])) {
                foreach ($data['attachments'] as $file) {
                    $message->attach($file['path'], [
                        'as'   => $file['name'] ?? null,
                        'mime' => $file['mime'] ?? null,
                    ]);
                }
            }
        };

        // Queue or send immediately
        if (! empty($data['queue'])) {
            Mail::queue($data['view'], $data['body'] ?? [], $callback);
        } else {
            Mail::send($data['view'], $data['body'] ?? [], $callback);
        }
    }
}
