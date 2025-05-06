<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $sender = $this->resource->sender;

        return [
            'id' => $this->resource->id,
            'uuid' => $this->resource->uuid,
            'conversation_id' => $this->resource->conversation_id,
            'sender' => [
                'id' => $sender->id,
                'name' => $sender->name,
                'surname' => $sender->surname,
                'fullname' => $sender->name . ' ' . $sender->surname,
                'email' => $sender->email,
                'photo' => $sender->photo
            ],
            'type' => $this->resource->type,
            'content' => $this->resource->content,
            'attachments' => $this->resource->attachments,
            'status' => $this->resource->status,
            'status_text' => $this->resource->status_text,
            'is_edited' => (bool)$this->resource->is_edited,
            'is_system' => (bool)$this->resource->is_system,
            'created_at' => $this->resource->created_at,
            'edited_at' => $this->resource->edited_at,
            'delivered_at' => $this->resource->delivered_at,
            'read_at' => $this->resource->read_at,
            'meta_data' => $this->resource->meta_data,
            // Admin-lər üçün əlavə məlumatlar
            'edit_history' => $this->resource->meta_data['edit_history'] ?? null,
            'deleted_for' => $this->resource->meta_data['deleted_for'] ?? null
        ];
    }
}
