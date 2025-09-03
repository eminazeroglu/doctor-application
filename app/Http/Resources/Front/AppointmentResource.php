<?php

namespace App\Http\Resources\Front;

use Illuminate\Http\Resources\Json\JsonResource;

class AppointmentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray($request): array
    {
        return [
            'uuid' => $this->uuid,
            'id' => $this->id,

            // Həkim məlumatları (Screen 1 - Həkim kolonu)
            'doctor' => [
                'id' => $this->doctor->id,
                'uuid' => $this->doctor->uuid,
                'name' => $this->doctor->user->fullname,
                'title' => $this->doctor->title,
                'full_name_with_title' => $this->doctor->full_name_with_title,
                'photo' => $this->doctor->user->photo,
                'specialization' => $this->doctor->category?->name,
                'rating' => $this->doctor->rating_average,
                'experience_years' => $this->doctor->years_of_experience,
            ],

            // Klinika məlumatları
            'clinic' => $this->when($this->clinic, [
                'id' => $this->clinic?->id,
                'uuid' => $this->clinic?->uuid,
                'name' => $this->clinic?->name,
                'address' => $this->clinic?->address,
                'phone' => $this->clinic?->phone,
                'logo' => $this->clinic?->logo,
            ]),

            // Xidmət məlumatları
            'service' => $this->when($this->service, [
                'id' => $this->service?->id,
                'name' => $this->service?->name,
                'duration' => $this->service?->duration,
            ]),

            // Randevu vaxtı (Screen 1 - Randevu vaxtı kolonu)
            'appointment_time' => [
                'start_time' => $this->start_time?->format('H:i'),
                'end_time' => $this->end_time?->format('H:i'),
                'date' => $this->start_time?->format('d M, D'), // 07 May, Mon
                'full_datetime' => $this->start_time?->toISOString(),
                'duration' => $this->duration, // dəqiqə
                'is_today' => $this->start_time?->isToday() ?? false,
                'is_past' => $this->start_time?->isPast() ?? false,
                'is_upcoming' => $this->is_upcoming,
            ],

            // Müraciət səbəbi (Screen 1 - Müraciət səbəbi kolonu)
            'complaint' => $this->complaint,
            'notes' => $this->notes,

            // Status (Screen 1 - Status kolonu)
            'status' => [
                'code' => $this->appointment_status,
                'text' => $this->status_text,
                'color' => $this->status_color,
                'icon' => $this->status_icon,
            ],

            // Qiymət məlumatları
            'payment' => [
                'price' => $this->price,
                'is_paid' => $this->is_paid,
                'payment_method' => $this->payment?->method,
                'payment_status' => $this->payment?->status,
            ],

            // Konsultasiya növü
            'consultation_type' => [
                'type' => $this->consultation_type,
                'location' => $this->location,
            ],

            // Ləğv məlumatları (əgər ləğv edilmişdirsə)
            'cancellation' => $this->when($this->appointment_status === 'cancelled', [
                'reason' => $this->cancel_reason,
                'cancelled_at' => $this->cancelled_at?->format('Y-m-d H:i:s'),
                'cancelled_by' => $this->cancelled_by ?? 'patient',
            ]),

            // Qiymətləndir düyməsi üçün
            'can_review' => $this->canReview(),
            'has_review' => $this->reviews->isNotEmpty(),

            // Ləğv etmə üçün
            'can_cancel' => $this->canCancel(),
            'can_reschedule' => $this->canReschedule(),

            // Xatırlatma məlumatları
            'reminders' => $this->when($this->reminders,
                $this->reminders->map(function ($reminder) {
                    return [
                        'type' => $reminder->type,
                        'send_at' => $reminder->send_at->format('Y-m-d H:i:s'),
                        'is_sent' => $reminder->is_sent,
                        'sent_at' => $reminder->sent_at?->format('Y-m-d H:i:s'),
                    ];
                })
            ),

            // Vaxt məlumatları
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Randevuya rəy yazıla bilər mi?
     */
    private function canReview(): bool
    {
        // Yalnız tamamlanmış randevular üçün rəy yazıla bilər
        if ($this->appointment_status !== 'completed') {
            return false;
        }

        // Artıq rəy yazılıbsa, yenidən yazıla bilməz
        if ($this->reviews->isNotEmpty()) {
            return false;
        }

        // Randevu bitdikdən sonra 30 gün ərzində rəy yazıla bilər
        if ($this->end_time && $this->end_time->diffInDays(now()) > 30) {
            return false;
        }

        return true;
    }

    /**
     * Randevu ləğv edilə bilər mi?
     */
    private function canCancel(): bool
    {
        // Artıq ləğv edilmiş və ya tamamlanmış randevular ləğv edilə bilməz
        if (in_array($this->appointment_status, ['cancelled', 'completed', 'no-show'])) {
            return false;
        }

        // Randevu vaxtından ən azı 2 saat əvvəl ləğv edilə bilər
        if ($this->start_time && $this->start_time->diffInHours(now()) < 2) {
            return false;
        }

        return true;
    }

    /**
     * Randevu yenidən planlaşdırıla bilər mi?
     */
    private function canReschedule(): bool
    {
        // Yalnız pending və confirmed statuslarda yenidən planlaşdırıla bilər
        if (!in_array($this->appointment_status, ['pending', 'confirmed'])) {
            return false;
        }

        // Randevu vaxtından ən azı 4 saat əvvəl yenidən planlaşdırıla bilər
        if ($this->start_time && $this->start_time->diffInHours(now()) < 4) {
            return false;
        }

        return true;
    }
}
