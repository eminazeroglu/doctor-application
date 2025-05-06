<?php

namespace App\Contracts;

use App\Models\Notification;

interface NotificationChannel
{
    /**
     * Notification-ı göndərmək
     *
     * @param mixed $notifiable Notification alan model
     * @param Notification $notification Notification obyekti
     * @return bool Göndərmənin uğurlu olub-olmadığı
     */
    public function send(mixed $notifiable, Notification $notification): bool;

    /**
     * Bu kanal ilə notification göndərilməli olub-olmadığını yoxlayır
     */
    public function shouldSend($notifiable, Notification $notification): bool;
}
