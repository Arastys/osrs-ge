<?php

namespace App\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WebhookChannel
{
    public function send($notifiable, Notification $notification)
    {
        Log::info('WebhookChannel: Attempting to send notification', [
            'notifiable' => get_class($notifiable),
            'notification' => get_class($notification),
        ]);

        $response = $notification->toWebhook($notifiable);

        if ($response) {
            Log::info('WebhookChannel: Webhook response', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        } else {
            Log::warning('WebhookChannel: No response returned from toWebhook');
        }
    }
}
