<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorUnavailabilityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'doctor_id' => $this->doctor_id,
            'clinic_id' => $this->clinic_id,
            'start_datetime' => $this->start_datetime ? $this->start_datetime->format('Y-m-d H:i:s') : null,
            'end_datetime' => $this->end_datetime ? $this->end_datetime->format('Y-m-d H:i:s') : null,
            'reason' => $this->reason,
            'description' => $this->description,
            'is_recurring' => $this->is_recurring,
            'recurring_pattern' => $this->recurring_pattern,

            // Relations
            'clinic' => $this->whenLoaded('clinic', function () {
                return [
                    'id' => $this->clinic->id,
                    'name' => $this->clinic->name,
                ];
            }),

            // Computed attributes
            'datetime_range' => $this->datetime_range,
            'is_active' => $this->is_active,
            'is_expired' => $this->is_expired,
            'duration' => $this->duration,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
