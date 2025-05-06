<?php

namespace App\Services\Notification\Channels;

use App\Contracts\NotificationChannel;
use App\Models\Notification;

abstract class BaseChannel implements NotificationChannel
{
    public function shouldSend($notifiable, Notification $notification): bool
    {
        // İstifadəçinin bu kanal üçün tənzimləmələrini yoxlayırıq
        if (method_exists($notifiable, 'shouldReceiveNotificationVia')) {
            return $notifiable->shouldReceiveNotificationVia($this->getChannelName(), $notification);
        }

        return true;
    }

    /**
     * Kanalın adını qaytarır
     */
    abstract protected function getChannelName(): string;
}
