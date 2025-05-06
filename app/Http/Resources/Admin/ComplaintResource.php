<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

// İstifadəçi resursu (əgər yoxdursa, ayrıca yaza bilərik)

class ComplaintResource extends JsonResource
{
    /**
     * Metodun məqsədi: Şikayət məlumatlarını API cavabı üçün array formatına çevirir.
     * İstifadəçi və complaintable detallarını əlavə edir.
     */
    public function toArray(Request $request): array
    {
        $isAdmin = auth()->user()->hasPermission('referral_read');

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'code' => $this->code,
            'user' => new UserResource($this->whenLoaded('user')), // İstifadəçi detalları
            'complaintable_id' => $this->when($isAdmin, $this->complaintable_id),
            'complaintable_type' => $this->when($isAdmin, $this->complaintable_type),
            'complaintable_type_text' => $this->when($isAdmin, $this->complaintable_type_text),
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'resolution_note' => $this->resolution_note,
            'resolved_at' => $this->resolved_at,
            'resolver' => $this->when($isAdmin, new UserResource($this->whenLoaded('resolver'))),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'messages_count' => $this->whenCounted('messages'),
            'messages' => ComplaintMessageResource::collection($this->whenLoaded('messages'))
        ];
    }
}
