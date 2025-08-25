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
 * Notification yaradıldığında işə düşən event
 */
class NotificationCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Notification $notification;
    public User $user;

    public function __construct(Notification $notification, User $user)
    {
        $this->notification = $notification;
        $this->user = $user;
    }

    /**
     * WebSocket broadcast data
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'uuid' => $this->notification->uuid,
            'type' => $this->notification->type,
            'title' => $this->notification->title,
            'content' => $this->notification->content,
            'icon' => $this->notification->icon,
            'action_url' => $this->notification->action_url,
            'action_text' => $this->notification->action_text,
            'created_at' => $this->notification->created_at->toIso8601String(),
            'time_ago' => $this->notification->created_at->diffForHumans()
        ];
    }

    /**
     * Broadcast kanalı
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('notifications.' . $this->user->id),
        ];
    }

    /**
     * Broadcast adı
     */
    public function broadcastAs(): string
    {
        return 'notification.created';
    }
}
