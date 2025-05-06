<?php

namespace App\Notifications;

use App\Enums\NotificationPriorityEnum;
use App\Enums\NotificationStatusEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

abstract class BaseNotification extends Notification
{
    use Queueable;

    protected string $type;
    protected array $data;
    protected ?string $priority;

    public function __construct(array $data = [], ?string $priority = null)
    {
        $this->data = array_merge(
            NotificationTypeEnum::getDefaultData($this->type),
            $data
        );
        
        $this->priority = $priority ?? NotificationTypeEnum::getDefaultPriority($this->type);
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'priority' => $this->priority,
            'status' => NotificationStatusEnum::PENDING
        ];
    }

    public function via($notifiable): array
    {
        // Default kanalları qaytarır, override edilə bilər
        return ['database', 'broadcast'];
    }
} 