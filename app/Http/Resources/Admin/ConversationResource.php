<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ConversationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $creator = $this->resource->creator;
        $receiver = $this->resource->receiver;

        // Son mesajın məlumatları
        $lastMessage = null;
        if ($this->whenLoaded('lastMessage') && $this->lastMessage) {
            $lastMessage = [
                'id' => $this->lastMessage->id,
                'uuid' => $this->lastMessage->uuid,
                'content' => $this->lastMessage->content,
                'type' => $this->lastMessage->type,
                'created_at' => $this->lastMessage->created_at,
                'sender_id' => $this->lastMessage->sender_id,
                'sender_name' => $this->lastMessage->sender->name . ' ' . $this->lastMessage->sender->surname
            ];
        }

        return [
            'id' => $this->resource->id,
            'uuid' => $this->resource->uuid,
            'type' => $this->resource->type,
            'status' => $this->resource->status,
            'status_text' => $this->resource->status_text,
            'is_pinned' => (bool)$this->resource->is_pinned,
            'creator' => [
                'id' => $creator->id,
                'name' => $creator->name,
                'surname' => $creator->surname,
                'fullname' => $creator->name . ' ' . $creator->surname,
                'email' => $creator->email,
                'photo' => $creator->photo
            ],
            'receiver' => [
                'id' => $receiver->id,
                'name' => $receiver->name,
                'surname' => $receiver->surname,
                'fullname' => $receiver->name . ' ' . $receiver->surname,
                'email' => $receiver->email,
                'photo' => $receiver->photo
            ],
            'last_message' => $lastMessage,
            'last_activity_at' => $this->resource->last_activity_at,
            'created_at' => $this->resource->created_at,
            'archived_at' => $this->resource->archived_at,
            // Blok məlumatları adminlər üçün
            'block_info' => isset($this->resource->meta_data['blocked_reason']) ? [
                'reason' => $this->resource->meta_data['blocked_reason'],
                'blocked_at' => $this->resource->meta_data['blocked_at'] ?? null,
                'blocked_by' => $this->resource->meta_data['blocked_by'] ?? null,
                'unblocked_at' => $this->resource->meta_data['unblocked_at'] ?? null,
                'unblocked_by' => $this->resource->meta_data['unblocked_by'] ?? null,
            ] : null
        ];
    }
}
