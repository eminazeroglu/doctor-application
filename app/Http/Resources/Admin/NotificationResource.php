<?php

namespace App\Http\Resources\Admin;

use App\Enums\NotificationTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type,
            'type_text' => NotificationTypeEnum::getDescription($this->type),
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

            // İstifadəçi məlumatları (əgər yüklənibsə)
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'surname' => $this->user->surname,
                    'fullname' => $this->user->fullname,
                    'email' => $this->user->email,
                    'photo' => $this->user->photo
                ];
            }),

            // Notification log-ları (əgər varsa)
            'logs_count' => $this->whenCounted('logs'),
            'delivery_status' => $this->when(
                $this->relationLoaded('logs'),
                function () {
                    if (!$this->logs) return null;

                    $total = $this->logs->count();
                    $successful = $this->logs->where('is_successful', true)->count();

                    return [
                        'total_attempts' => $total,
                        'successful' => $successful,
                        'failed' => $total - $successful,
                        'success_rate' => $total > 0 ? round(($successful / $total) * 100, 2) : 0,
                        'channels' => $this->logs->groupBy('channel')->map(function ($channelLogs, $channel) {
                            $channelTotal = $channelLogs->count();
                            $channelSuccessful = $channelLogs->where('is_successful', true)->count();

                            return [
                                'channel' => $channel,
                                'total' => $channelTotal,
                                'successful' => $channelSuccessful,
                                'failed' => $channelTotal - $channelSuccessful,
                                'success_rate' => $channelTotal > 0 ? round(($channelSuccessful / $channelTotal) * 100, 2) : 0
                            ];
                        })->values()
                    ];
                }
            ),

            // Priority (əgər data-da varsa)
            'priority' => $this->data['priority'] ?? 'normal',

            // Schedule status
            'is_scheduled' => $this->send_at > now(),
            'schedule_status' => $this->when($this->send_at, function () {
                if ($this->is_sent) {
                    return 'sent';
                } elseif ($this->send_at > now()) {
                    return 'scheduled';
                } else {
                    return 'pending';
                }
            }),

            // Əlavə məlumatlar
            'can_resend' => !$this->is_sent || $this->send_at > now(),
            'can_edit' => !$this->is_sent,
            'can_delete' => true,
        ];
    }
}
