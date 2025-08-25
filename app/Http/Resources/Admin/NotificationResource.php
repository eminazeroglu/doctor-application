<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type,
            'type_text' => $this->type_text,
            'user_id' => $this->user_id,
            'title' => $this->title,
            'content' => $this->content,
            'icon' => $this->icon,
            'action_url' => $this->action_url,
            'action_text' => $this->action_text,
            'data' => $this->data,
            'is_read' => $this->is_read,
            'read_at' => $this->read_at,
            'send_at' => $this->send_at,
            'is_sent' => $this->is_sent,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,

            // İlişkili məlumatlar (əgər yüklənibsə)
            'user' => $this->whenLoaded('user', function() {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'surname' => $this->user->surname,
                    'email' => $this->user->email,
                    'photo' => $this->user->photo
                ];
            }),

            // Əlavə məlumatlar frontend üçün
            'priority_class' => $this->getPriorityClass(),
            'type_icon' => $this->getTypeIcon(),
        ];
    }

    /**
     * Prioritet əsasında CSS class qaytarmaq
     */
    private function getPriorityClass(): string
    {
        return match($this->priority ?? 'normal') {
            'high' => 'notification-high-priority',
            'low' => 'notification-low-priority',
            default => 'notification-normal-priority'
        };
    }

    /**
     * Notification növünə görə ikon qaytarmaq
     */
    private function getTypeIcon(): string
    {
        return match($this->type) {
            'appointment_created',
            'appointment_confirmed',
            'appointment_cancelled',
            'appointment_reminder' => 'calendar',
            'review_received',
            'review_response' => 'star',
            'message_received' => 'message',
            'system' => 'info',
            default => 'bell'
        };
    }
}
