<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorEducationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'doctor_id' => $this->doctor_id,
            'university' => $this->university,
            'faculty' => $this->faculty,
            'degree' => $this->degree,
            'specialization' => $this->specialization,
            'start_date' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            'location' => $this->location,
            'description' => $this->description,
            'is_currently_studying' => $this->is_currently_studying,
            'document_path' => $this->document_path,

            // Computed attributes
            'duration' => $this->duration,
            'document_url' => $this->document_url,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
