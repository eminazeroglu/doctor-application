<?php

namespace App\Services\Notification\Channels;

use App\Models\Notification;
use Illuminate\Support\Facades\Http;

class PushChannel extends BaseChannel
{
    protected function getChannelName(): string
    {
        return 'push';
    }

    public function send($notifiable, Notification $notification): bool
    {
        try {
            $token = $notifiable->push_token;
            if (!$token) {
                return false;
            }

            $data = $notification->data;

            $response = Http::withToken(config('services.fcm.key'))
                ->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $token,
                    'notification' => [
                        'title' => $data['title'],
                        'body' => $data['message'],
                        'click_action' => 'FLUTTER_NOTIFICATION_CLICK',
                    ],
                    'data' => $data
                ]);

            return $response->successful();
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }
}
