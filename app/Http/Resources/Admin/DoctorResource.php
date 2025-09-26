<?php

namespace App\Http\Resources\Admin;

use App\Enums\AttributeTypeEnum;
use App\Http\Resources\Front\DoctorScheduleResource;
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
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,

            // İstifadəçi məlumatları
            'user' => $this->whenLoaded('user', function () {
                return [
                    'id' => $this->user->id,
                    'name' => $this->user->name,
                    'surname' => $this->user->surname,
                    'fullname' => $this->user->fullname,
                    'email' => $this->user->email,
                    'phone' => $this->user->phone,
                    'gender' => $this->user->gender,
                    'photo' => $this->user->photo,
                    'birthdate' => $this->user->birthdate,
                ];
            }),

            // Əsas məlumatlar
            'title' => $this->title,
            'full_name_with_title' => $this->full_name_with_title,
            'biography' => $this->biography,
            'years_of_experience' => $this->years_of_experience,
            'practice_license_number' => $this->practice_license_number,


            'attributes' => $this->whenLoaded('attributes', function () {
                return $this->attributes
                    ->groupBy('attribute_id')
                    ->map(function ($attributeGroup, $attributeId) {
                        $optionIds = $attributeGroup->pluck('attribute_option_id')->filter()->values();
                        $values = $attributeGroup->whereNull('attribute_option_id')->pluck('value')->filter()->values();

                        // İlk item-dan attribute type-ını al
                        $firstItem = $attributeGroup->first();
                        $type = $firstItem?->attribute?->type;

                        $isMultiSelect = $type === AttributeTypeEnum::MultiSelect || $optionIds->count() > 1;

                        return [
                            'attribute_id' => $attributeId,
                            ...($isMultiSelect) ? ['attribute_option_id' => $optionIds->toArray()] : ['attribute_option_id' => $optionIds->first()],
                            'value' => $values->first(),
                        ];
                    })
                    ->values();
            }),

            // İxtisaslar
            'category' => $this->whenLoaded('category', function() {
                return [
                    'id' => $this->category->id,
                    'name' => $this->category->name,
                    'slug' => $this->category->slug,
                ];
            }),
            'subcategory' => $this->whenLoaded('subcategory', function() {
                return [
                    'id' => $this->subcategory->id,
                    'name' => $this->subcategory->name,
                    'slug' => $this->subcategory->slug,
                ];
            }),

            // Qiymətlər və müddətlər
            'consultation_fee' => $this->consultation_fee,
            'consultation_duration' => $this->consultation_duration,
            'home_visit_fee' => $this->home_visit_fee,
            'online_consultation_fee' => $this->online_consultation_fee,

            // Xidmət növləri
            'available_for_home_visit' => $this->available_for_home_visit,
            'available_for_online_consultation' => $this->available_for_online_consultation,

            // Status
            'is_verified' => $this->is_verified,
            'is_featured' => $this->is_featured,

            // İş yeri məlumatları
            'workplace_name' => $this->workplace_name,
            'workplace_address' => $this->workplace_address,
            'workplace_phone' => $this->workplace_phone,

            // Sosial media
            'social_media_links' => $this->social_media_links,

            // Reytinq və statistika
            'average_rating' => $this->average_rating,
            'total_ratings' => $this->total_ratings,
            'rating_average' => $this->rating_average,
            'total_patients' => $this->total_patients,

            // İş günləri
            'working_days' => $this->working_days,

            // Əlaqələr
            'educations' => $this->whenLoaded('educations'),
            'experiences' => $this->whenLoaded('experiences'),
            'certificates' => $this->whenLoaded('certificates'),
            'languages' => $this->whenLoaded('languages'),
            'clinics' => $this->whenLoaded('clinics'),
            'schedules' => $this->whenLoaded('schedules', function() {
                return DoctorScheduleResource::collection($this->schedules);
            }),
            'services' => $this->whenLoaded('services'),

            // Tarixlər
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
