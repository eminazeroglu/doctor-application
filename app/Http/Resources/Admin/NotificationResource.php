<?php

namespace App\Http\Resources\Admin;

use App\Enums\NotificationPriorityEnum;
use App\Enums\NotificationTypeEnum;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Bildirişin əsas məlumatları
        $baseData = [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'type' => $this->type,
            'type_text' => NotificationTypeEnum::getDescription($this->type),
            'type_description' => NotificationTypeEnum::getLongDescription($this->type),
            'priority' => $this->priority,
            'priority_text' => NotificationPriorityEnum::getDescription($this->priority),

            // Notification məlumatları
            'title' => $this->data['title'] ?? null,
            'message' => $this->data['message'] ?? null,
            'action_url' => $this->data['action_url'] ?? null,
            'icon' => $this->data['icon'] ?? 'bell',
            'color' => $this->data['color'] ?? 'blue',

            // Status məlumatları
            'is_read' => !is_null($this->read_at),
            'is_scheduled' => !is_null($this->send_at) && $this->send_at->isFuture(),
            'read_at' => $this->read_at?->toIso8601String(),
            'send_at' => $this->send_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
        ];

        // Admin tərəfindən yaradılan bildirişlər üçün əlavə məlumatlar
        if ($this->type === NotificationTypeEnum::SYSTEM_ALERT) {
            $baseData['admin'] = [
                'id' => $this->data['admin_id'] ?? null,
                'name' => $this->data['admin_name'] ?? null,
                'created_at' => $this->data['created_at'] ?? null,
            ];
        }

        // Göndərmə kanalları üzrə status məlumatları
        // Bu məlumatlar bildirişin hansı kanallara və necə göndərildiyini göstərir
        if ($this->relationLoaded('deliveries')) {
            $baseData['delivery_status'] = [
                'email' => $this->formatDeliveryStatus('email'),
                'push' => $this->formatDeliveryStatus('push'),
                'telegram' => $this->formatDeliveryStatus('telegram'),
            ];
        }

        // Əgər notification bir modelə aiddirsə (məsələn, listing üçün bildirişdirsə)
        // həmin modelin məlumatlarını da əlavə edirik
        if (isset($this->data['model_type'], $this->data['model_id'])) {
            $baseData['related_to'] = [
                'type' => $this->data['model_type'],
                'id' => $this->data['model_id'],
                'title' => $this->data['model_title'] ?? null,
                'url' => $this->data['model_url'] ?? null,
            ];
        }

        // Əlavə metadata məlumatları
        if (isset($this->data['metadata'])) {
            $baseData['metadata'] = $this->data['metadata'];
        }

        return $baseData;
    }

    /**
     * Göndərmə kanalı üzrə statusu formatlaşdırır
     * Bu metod hər kanal üçün göndərmə statusu, tarixi və xəta məlumatını qaytarır
     */
    protected function formatDeliveryStatus(string $channel): ?array
    {
        $delivery = $this->deliveries->where('channel', $channel)->first();

        if (!$delivery) {
            return null;
        }

        return [
            'success' => $delivery->success,
            'delivered_at' => $delivery->delivered_at?->toIso8601String(),
            'error' => $delivery->error,
        ];
    }
}
