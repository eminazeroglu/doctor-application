<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorLanguageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'doctor_id' => $this->doctor_id,
            'language' => $this->language,
            'proficiency' => $this->proficiency,

            // Computed attributes
            'proficiency_text' => $this->proficiency_text,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
