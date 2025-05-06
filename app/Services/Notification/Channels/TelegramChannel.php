<?php

namespace App\Services\Notification\Channels;

use App\Models\Notification;
use App\Services\App\Telegram\TelegramService;

class TelegramChannel extends BaseChannel
{
    protected TelegramService $telegram;

    public function __construct(TelegramService $telegram)
    {
        $this->telegram = $telegram;
    }

    protected function getChannelName(): string
    {
        return 'telegram';
    }

    public function send($notifiable, Notification $notification): bool
    {
        try {
            $telegramId = $notifiable->telegram_id;
            if (!$telegramId) {
                return false;
            }

            $data = $notification->data;

            $this->telegram
                ->chat($telegramId)
                ->sendMessage($data['message'], [
                    'parse_mode' => 'HTML',
                    'disable_web_page_preview' => true
                ]);

            return true;
        } catch (\Exception $e) {
            report($e);
            return false;
        }
    }
}
