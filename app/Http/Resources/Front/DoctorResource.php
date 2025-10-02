<?php

namespace App\Http\Resources\Front;

use App\Enums\AppointmentStatusEnum;
use App\Models\Doctor;
use App\Services\Module\DoctorService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DoctorResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $profession = $this?->category?->name;
        if ($this?->subcategory?->name) { // subcategoy -> subcategory düzəltdim
            $profession .= ', ' . $this?->subcategory?->name;
        }

        $main_workplace = $this->mainWorkplace();

        // Services-i daha səmərəli şəkildə əldə edirik
        $services = $this->doctorClinics->flatMap(function ($clinic) {
            return $clinic->services->map(function ($item) use ($clinic) {
                return [
                    'id' => $item->service->id,
                    'slug' => $item->service->slug,
                    'name' => $item->service->name,
                ];
            });
        })->unique('id')->values();

        $clinics = $this->doctorClinics->map(function ($clinic) {
            return [
                'id' => $clinic->clinic?->id,
                'name' => $clinic->clinic?->name,
                'services' => $clinic->services->map(function ($item) {
                    return [
                        'id' => $item->service->id,
                        'slug' => $item->service->slug,
                        'name' => $item->service->name,
                    ];
                }),
            ];
        })->values();

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'username' => $this->user->username,
            'photo' => $this->user->photo,
            'fullname' => $this->full_name_with_title,
            'profession' => $profession,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
            'biography' => $this->biography,
            'rating_average' => $this->rating_average,
            'suggested_by_people' => $this->suggested_by_people,
            'total_patients' => $this->total_patients,
            'consultation_fee' => $this->consultation_fee,
            'is_verified' => $this->is_verified,
            'is_featured' => $this->is_featured,
            'main_workplace' => $main_workplace ? [
                'slug' => $main_workplace['slug'],
                'name' => $main_workplace['name'],
                'address' => $main_workplace['address'],
                'latitude' => $main_workplace['latitude'],
                'longitude' => $main_workplace['longitude'],
            ] : [],
            'services' => $services,
            'clinics' => $clinics,

            // Yalnız nearest_slots property-si varsa göstər
            'nearest_appointments' => $this->when(
                isset($this->nearest_days),
                fn() => $this->formatNearestDays()
            ),

            // ✅ Modal view üçün: Tam kalendar
            'available_days_for_next' => $this->when(
                isset($this->available_days),
                fn() => $this->formatAvailableDays()
            ),
        ];
    }

    /**
     * List view format: 3 gün, hər gündə 3 slot
     */
    private function formatNearestDays(): array
    {
        if (!isset($this->nearest_days) || empty($this->nearest_days)) {
            return [];
        }

        return collect($this->nearest_days)->map(function($day) {
            return [
                'date' => $day['date'],
                'day_name' => $day['day_name'],
                'month_name' => $day['month_name'],
                'display_date' => $day['display_date'],
                'slots' => collect($day['slots'])->map(fn($slot) => [
                    'time' => $slot['time'],
                    'clinic_id' => $slot['clinic_id']
                ])->toArray()
            ];
        })->toArray();
    }

    /**
     * Modal view format: Tam kalendar
     */
    private function formatAvailableDays(): array
    {
        if (!isset($this->available_days) || empty($this->available_days)) {
            return [];
        }

        return collect($this->available_days)->map(function($day) {
            return [
                'date' => $day['date'],
                'day_name' => $day['day_name'] ?? Carbon::parse($day['date'])->format('D'),
                'display_date' => $day['display_date'] ?? $day['date'],
                'slots' => collect($day['slots'])->map(fn($slot) => [
                    'time' => $slot['time'],
                    'clinic_id' => $slot['clinic_id']
                ])->toArray()
            ];
        })->toArray();
    }
}
