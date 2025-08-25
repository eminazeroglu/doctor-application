<?php

namespace App\Events\Notification;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Notification oxunduqda işə düşən event
 */
class NotificationRead implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notification $notification;
    public User $user;
    public int $unreadCount;

    public function __construct(Notification $notification, User $user, int $unreadCount)
    {
        $this->notification = $notification;
        $this->user = $user;
        $this->unreadCount = $unreadCount;
    }

    public function broadcastWith(): array
    {
        return [
            'notification_id' => $this->notification->id,
            'unread_count' => $this->unreadCount
        ];
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.' . $this->user->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'notification.read';
    }
}
