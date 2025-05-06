<?php

namespace App\Services\Notification\Channels;

use App\Models\Notification;
use Illuminate\Support\Facades\Mail;

class EmailChannel extends BaseChannel
{
    protected function getChannelName(): string
    {
        return 'email';
    }

    public function send($notifiable, Notification $notification): bool
    {
        try {
            if (!$notifiable->email) {
                return false;
            }

            $data = $notification->data;

            Mail::send([], [], function ($message) use ($notifiable, $data) {
                $message->to($notifiable->email)
                    ->subject($data['title'])
                    ->html($data['message']);
            });

            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }
}
