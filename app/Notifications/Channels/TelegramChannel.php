<?php

namespace App\Notifications\Channels;

use App\Services\App\Telegram\TelegramService;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class TelegramChannel
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    /**
     * Send the given notification.
     * @throws \Exception
     */
    public function send($notifiable, Notification $notification): void
    {
        try {
            if (!method_exists($notification, 'toTelegram')) {
                throw new \Exception('Notification class must implement toTelegram method');
            }

            $telegramId = $notifiable->routeNotificationFor('telegram');
            if (!$telegramId) {
                throw new \Exception('Notifiable does not have telegram_id');
            }

            $message = $notification->toTelegram($notifiable);

            $this->telegram
                ->chat($telegramId)
                ->sendMessage($message['message'], $message['params'] ?? []);

        } catch (\Exception $e) {
            Log::error('Telegram notification error: ' . $e->getMessage(), [
                'notifiable' => $notifiable,
                'notification' => get_class($notification)
            ]);

            throw $e;
        }
    }
}
