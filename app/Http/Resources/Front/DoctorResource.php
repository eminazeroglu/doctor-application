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
        //return parent::toArray($request);

        $profession = $this?->category?->name;
        if ($this?->subcategoy?->name) {
            $profession .= ', ' . $this?->subcategoy?->name;
        }

        $main_workplace = $this->mainWorkplace();

        $doctor = Doctor::find($this->id);

        $services = [];

        foreach ($doctor->doctorClinics()->get() as $clinic) {
            foreach ($clinic->services()->get() as $item) {
                $services[] = [
                    'id' => $item->service->id,
                    'slug' => $item->service->slug,
                    'name' => $item->service->name,
                ];
            }
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
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
            'services' => collect($services)->unique('id')->values(),
            'nearest_appointments' => $this->formatNearestSlots(),
            'available_days_for_next' => app(DoctorService::class)->getAvailableDaysForNextDays($doctor),

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
