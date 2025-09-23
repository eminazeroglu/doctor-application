<?php

namespace App\Http\Resources\Front;

use App\Enums\AppointmentStatusEnum;
use App\Models\Doctor;
use App\Services\Module\DoctorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorViewResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        //return parent::toArray($request);

        $profession = $this?->category?->name;
        if ($this?->subcategoy?->name) {
            $profession .= ', ' . $this?->subcategoy?->name;
        }

        $main_workplace = $this->mainWorkplace();

        $doctor = Doctor::find($this->id);

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'photo' => $this->user->photo,
            'username' => $this->user->username,
            'fullname' => $this->full_name_with_title,
            'profession' => $profession,
            'rating_average' => $this->rating_average,
            'suggested_by_people' => $this->suggested_by_people,
            'total_patients' => $this->total_patients,
            'consultation_fee' => $this->consultation_fee,
            'is_verified' => $this->is_verified,
            'is_featured' => $this->is_featured,
            'reviews' => $this->whenLoaded('reviews', $this->reviews->map(fn($i) => [
                'photo' => $i->patient->photo,
                'fullname' => $i->patient->fullname,
                'rating' => $i->rating,
            ])),
            'languages' => $this->whenLoaded('languages', $this->languages->map(fn($i) => [
                'name' => $i->language,
                'proficiency' => $i->proficiency,
                'proficiency_text' => $i->proficiency_text
            ])),
            'certificates' => $this->whenLoaded('services', $this->certificates->map(fn($i) => [
                'name' => $i->name,
                'organization' => $i->issuing_organization,
                'issue_date' => $i->issue_date,
                'expiry_date' => $i->expiry_date,
                'description' => $i->description,
                'is_verified' => $i->is_verified,
            ])),
//            'experiences' => $this->whenLoaded('experiences', $this->experiences->map(fn($i) => [
//                'name' => $i->workplace,
//                'position' => $i->position,
//                'duration' => $i->duration,
//                'location' => $i->location,
//            ])),
            'educations' => $this->whenLoaded('educations', $this->educations->map(fn($i) => [
                'name' => $i->university,
                'faculty' => $i->faculty,
                'degree' => $i->degree,
                'duration' => $i->duration,
            ])),
            'clinics' => $this->whenLoaded('doctorClinics', $this->doctorClinics->map(function($i) {
                $clinic = $i->clinic;
                return [
                    'id' => $clinic->id,
                    'name' => $clinic->name,
                    'address' => $clinic->address,
                    'profession' => $i->profession,
                    'is_main_workplace' => $i->is_main_workplace,
                    'working_hours' => $clinic->workingHours->map(fn($w) => [
                        'day_of_week' => $w->day_of_week,
                        'open_time' => $w->open_time,
                        'close_time' => $w->close_time,
                        'is_closed' => $w->is_closed,
                        'day_name' => $w->day_name,
                        'time_range' => $w->time_range,
                    ]),
                    'services' => $i->services->map(function ($item) {
                        return [
                            'id' => $item->service->id,
                            'slug' => $item->service->slug,
                            'name' => $item->service->name,
                        ];
                    })
                ];
            })),
            'main_workplace' => $main_workplace ? [
                'slug' => $main_workplace['slug'],
                'name' => $main_workplace['name'],
                'address' => $main_workplace['address'],
                'latitude' => $main_workplace['latitude'],
                'longitude' => $main_workplace['longitude'],
            ] : [],
            'nearest_appointments' => $this->formatNearestSlots(),
            'available_days_for_next' => app(DoctorService::class)->getAvailableDaysForNextDays($doctor),
            'services' => $this->services->map(fn ($i) => [
                'id' => $i->id,
                'slug' => $i->slug,
                'name' => $i->name,
            ]),
        ];
    }

    private function formatNearestSlots(): array
    {
        if (!isset($this->nearest_slots)) {
            return [];
        }

        return $this->nearest_slots->map(function($slot) {
            return [
                'date' => $slot['date'],
                'day' => $slot['day_name'],
                'month' => $slot['month_name'],
                'time' => $slot['time'],
                'display' => $slot['day_name'] . "\n" .
                    Carbon::parse($slot['date'])->day . "\n" .
                    $slot['month_name']
            ];
        })->toArray();
    }
}
