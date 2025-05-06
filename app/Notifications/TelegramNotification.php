<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TelegramNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected string $message;
    protected ?string $parseMode;
    protected ?array $keyboard;
    protected ?array $extra;

    /**
     * Create a new notification instance.
     */
    public function __construct(
        string $message,
        ?string $parseMode = 'HTML',
        ?array $keyboard = null,
        ?array $extra = []
    ) {
        $this->message = $message;
        $this->parseMode = $parseMode;
        $this->keyboard = $keyboard;
        $this->extra = $extra;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via($notifiable): array
    {
        return ['telegram'];
    }

    /**
     * Get the Telegram representation of the notification.
     */
    public function toTelegram($notifiable): array
    {
        $params = array_merge([
            'parse_mode' => $this->parseMode
        ], $this->extra);

        if ($this->keyboard) {
            $params['reply_markup'] = $this->keyboard;
        }

        return [
            'message' => $this->message,
            'params' => $params
        ];
    }
}
