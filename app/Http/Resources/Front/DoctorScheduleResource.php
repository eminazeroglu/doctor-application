<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorScheduleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'doctor_id' => $this->doctor_id,
            'clinic_id' => $this->clinic_id,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time ? $this->start_time->format('H:i') : null,
            'end_time' => $this->end_time ? $this->end_time->format('H:i') : null,
            'is_active' => $this->is_active,
            'max_appointments' => $this->max_appointments,
            'appointment_duration' => $this->appointment_duration,
            'note' => $this->note,

            // Relations
            'clinic' => $this->whenLoaded('clinic', function () {
                return [
                    'id' => $this->clinic->id,
                    'name' => $this->clinic->name,
                    'address' => $this->clinic->address,
                ];
            }),

            // Computed attributes
            'day_name' => $this->day_name,
            'time_range' => $this->time_range,
            'duration' => $this->duration,

            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
