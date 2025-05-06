<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintMessageResource extends JsonResource
{
    /**
     * Metodun məqsədi: Şikayətə aid mesajları API cavabı üçün array formatına çevirir.
     * İstifadəçi detallarını əlavə edir.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'complaint_id' => $this->complaint_id,
            'user' => new UserResource($this->whenLoaded('user')),
            'message' => $this->message,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'is_staff_reply' => $this->is_staff_reply,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
