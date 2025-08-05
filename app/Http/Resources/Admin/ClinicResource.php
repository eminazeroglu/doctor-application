<?php

namespace App\Http\Resources\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClinicResource extends JsonResource
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
            'slug' => $this->slug,

            // Əsas məlumatlar
            'name' => $this->name,
            'description' => $this->description,
            'translates' => $this->translates,

            // Əlaqə məlumatları
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,

            // Ünvan
            'address' => $this->address,
            'country_id' => $this->country_id,
            'country_name' => $this?->country?->name,
            'city_id' => $this->city_id,
            'city_name' => $this?->city?->name,
            'region_id' => $this->region_id,
            'region_name' => $this?->region?->name,
            'postal_code' => $this->postal_code,

            // Koordinatlar
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'coordinates' => $this->when(
                $this->latitude && $this->longitude,
                [
                    'lat' => (float) $this->latitude,
                    'lng' => (float) $this->longitude
                ]
            ),

            // Media
            'logo' => $this->logo,
            'logo_path' => $this->logo_path,
            'gallery' => $this->gallery,

            // İş saatları və imkanlar
            'working_hours' => $this->working_hours,
            'facilities' => $this->facilities,

            // Qiymətləndirmə
            'rating' => (float) $this->rating,
            'reviews_count' => (int) $this->reviews_count,
            'average_rating' => $this->when(
                $this->reviews_count > 0,
                round($this->rating, 1)
            ),

            // Status sahələri
            'is_verified' => (bool) $this->is_verified,
            'is_featured' => (bool) $this->is_featured,
            'is_active' => (bool) $this->is_active,
            'order' => (int) $this->order,

            // Əlaqələr
            'parent_id' => $this->parent_id,
            'parent' => $this->whenLoaded('parent', function () {
                return [
                    'id' => $this->parent->id,
                    'name' => $this->parent->name,
                    'slug' => $this->parent->slug,
                ];
            }),

            'city' => $this->whenLoaded('city', function () {
                return [
                    'id' => $this->city->id,
                    'name' => $this->city->name,
                ];
            }),

            'region' => $this->whenLoaded('region', function () {
                return [
                    'id' => $this->region->id,
                    'name' => $this->region->name,
                ];
            }),

            'categories' => $this->whenLoaded('categories', function () {
                return $this->categories->map(function ($category) {
                    return [
                        'id' => $category->id,
                        'name' => $category->name,
                        'slug' => $category->slug,
                        'description' => $category->pivot->description ?? null,
                        'is_active' => (bool) $category->pivot->is_active,
                    ];
                });
            }),

            'services' => $this->whenLoaded('services', function () {
                return $this->services->map(function ($service) {
                    return [
                        'id' => $service->id,
                        'name' => $service->name,
                        'slug' => $service->slug,
                        'price' => $service->pivot->price ? (float) $service->pivot->price : null,
                        'duration' => $service->pivot->duration ? (int) $service->pivot->duration : null,
                        'description' => $service->pivot->description,
                        'is_active' => (bool) $service->pivot->is_active,
                        'formatted_price' => $service->pivot->price
                            ? number_format($service->pivot->price, 2) . ' AZN'
                            : null,
                        'formatted_duration' => $service->pivot->duration
                            ? $this->formatDuration($service->pivot->duration)
                            : null,
                    ];
                });
            }),

            'working_hours_formatted' => $this->whenLoaded('workingHours', function () {
                return $this->workingHours->map(function ($hour) {
                    return [
                        'day_of_week' => $hour->day_of_week,
                        'day_name' => $hour->day_name,
                        'open_time' => $hour->open_time?->format('H:i'),
                        'close_time' => $hour->close_time?->format('H:i'),
                        'time_range' => $hour->time_range,
                        'is_closed' => (bool) $hour->is_closed,
                        'note' => $hour->note,
                    ];
                });
            }),

            'doctors_count' => $this->whenCounted('doctors'),
            'active_doctors_count' => $this->when(
                $this->relationLoaded('doctors'),
                $this->doctors->where('is_active', true)->count()
            ),

            'appointments_count' => $this->whenCounted('appointments'),
            'reviews' => $this->whenLoaded('reviews', function () {
                return ReviewResource::collection($this->reviews);
            }),

            // Məsafə (nearby axtarışında)
            'distance' => $this->when(
                isset($this->distance),
                round($this->distance, 2)
            ),
            'distance_formatted' => $this->when(
                isset($this->distance),
                round($this->distance, 2) . ' km'
            ),

            // SEO
            'meta_tags' => $this->meta_tags,
            'custom_fields' => $this->custom_fields,

            // Tarixlər
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
        ];
    }

    /**
     * Müddəti format edir
     */
    private function formatDuration(?int $duration): ?string
    {
        if (!$duration) return null;

        if ($duration < 60) {
            return $duration . ' dəqiqə';
        }

        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        if ($minutes > 0) {
            return $hours . ' saat ' . $minutes . ' dəqiqə';
        }

        return $hours . ' saat';
    }
}
