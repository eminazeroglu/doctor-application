<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ComplaintResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);
        return [
            'uuid' => $this->uuid,
            'code' => $this->code,
            'type' => $this->complaintable_type_text,
            'title' => $this->title,
            'description' => $this->description,
            'status' => $this->status,
            'status_text' => $this->status_text,
            'resolution_note' => $this->resolution_note,
            'resolved_at' => $this->resolved_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'messages_count' => $this->whenCounted('messages'),
        ];
    }
}
