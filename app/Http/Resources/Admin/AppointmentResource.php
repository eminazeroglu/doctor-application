<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            // Xəstə məlumatları
            'patient' => $this->whenLoaded('patient', function () {
                return [
                    'id' => $this->patient->id,
                    'name' => $this->patient?->user?->name . ' ' . $this->patient?->user?->surname,
                    'email' => $this->patient?->user?->email,
                    'phone' => $this->patient?->user?->phone,
                ];
            }),

            // Həkim məlumatları
            'doctor' => $this->whenLoaded('doctor', function () {
                return [
                    'id' => $this->doctor->id,
                    'name' => $this->doctor->full_name_with_title,
                    'specialty' => $this->doctor->category->name ?? null,
                    'email' => $this->doctor->user->email,
                    'phone' => $this->doctor->user->phone,
                ];
            }),

            // Klinika məlumatları
            'clinic' => $this->when($this->clinic, [
                'id' => $this->clinic?->id,
                'name' => $this->clinic?->name,
                'address' => $this->clinic?->address,
                'phone' => $this->clinic?->phone,
            ]),

            // Xidmət məlumatları
            'service' => $this->when($this->service, [
                'id' => $this->service?->id,
                'name' => $this->service?->name,
            ]),

            // Randevu məlumatları
            'start_time' => $this->start_time?->format('Y-m-d H:i:s'),
            'end_time' => $this->end_time?->format('Y-m-d H:i:s'),
            'start_time_formatted' => $this->start_time?->format('d.m.Y H:i'),
            'end_time_formatted' => $this->end_time?->format('d.m.Y H:i'),
            'duration' => $this->duration,

            // Status məlumatları
            'appointment_status' => $this->appointment_status,
            'status_text' => $this->status_text,
            'status_color' => $this->status_color,
            'status_icon' => $this->status_icon,

            // Məzmun
            'complaint' => $this->complaint,
            'notes' => $this->notes,
            'cancel_reason' => $this->cancel_reason,

            // Ödəniş məlumatları
            'price' => $this->price,
            'is_paid' => $this->is_paid,
            'payment' => $this->when($this->payment, [
                'id' => $this->payment?->id,
                'amount' => $this->payment?->amount,
                'method' => $this->payment?->payment_method,
                'is_paid' => $this->payment?->is_paid,
            ]),

            // Konsultasiya məlumatları
            'consultation_type' => $this->consultation_type,
            'location' => $this->location,

            // Status yoxlamaları
            'is_active' => $this->is_active,
            'is_upcoming' => $this->is_upcoming,
            'is_completed' => $this->is_completed,

            // Əlavə məlumatlar
            'additional_info' => $this->additional_info,

            // Zaman məlumatları
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
