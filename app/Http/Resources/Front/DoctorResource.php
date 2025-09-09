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
            return $clinic->services->map(function ($item) {
                return [
                    'id' => $item->service->id,
                    'slug' => $item->service->slug,
                    'name' => $item->service->name,
                ];
            });
        })->unique('id')->values();

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'username' => $this->user->username,
            'photo' => $this->user->photo,
            'fullname' => $this->full_name_with_title,
            'profession' => $profession,
            'category_id' => $this->category_id,
            'sub_category_id' => $this->sub_category_id,
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

            // Yalnız nearest_slots property-si varsa göstər
            'nearest_appointments' => $this->when(
                isset($this->nearest_slots),
                fn() => $this->formatNearestSlots()
            ),

            // available_days yalnız nearest_slots ilə birlikdə lazım olduqda əlavə edilir
            'available_days_for_next' => $this->when(
                isset($this->available_days),
                $this->available_days ?? []
            ),
        ];
    }

    private function formatNearestSlots(): array
    {
        if (!isset($this->nearest_slots) || !$this->nearest_slots) {
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
