<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;

class ClinicFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'city_id',
        'region_id',
        'category_id',
        'is_verified',
        'is_featured',
        'is_active',
        'rating_min',
        'rating_max',
        'has_appointments',
        'distance',
        'coordinates',
        'working_day',
        'date_range',
        'trashed'
    ];

    protected function getSearchableFields(): array
    {
        return ['name', 'description', 'address', 'phone', 'email'];
    }

    /**
     * Şəhərə görə filtrasiya
     */
    protected function filterCityId(Builder $query, $value): Builder
    {
        return $query->where('city_id', $value);
    }

    /**
     * Rayona görə filtrasiya
     */
    protected function filterRegionId(Builder $query, $value): Builder
    {
        return $query->where('region_id', $value);
    }

    /**
     * Kateqoriyaya görə filtrasiya
     */
    protected function filterCategoryId(Builder $query, $value): Builder
    {
        return $query->whereHas('categories', function ($q) use ($value) {
            $q->where('categories.id', $value);
        });
    }

    /**
     * Təsdiqlənmiş klinikalar
     */
    protected function filterIsVerified(Builder $query, $value): Builder
    {
        return $query->where('is_verified', $value);
    }

    /**
     * Önə çıxarılmış klinikalar
     */
    protected function filterIsFeatured(Builder $query, $value): Builder
    {
        return $query->where('is_featured', $value);
    }

    /**
     * Minimum reytinq
     */
    protected function filterRatingMin(Builder $query, $value): Builder
    {
        return $query->where('rating', '>=', $value);
    }

    /**
     * Maksimum reytinq
     */
    protected function filterRatingMax(Builder $query, $value): Builder
    {
        return $query->where('rating', '<=', $value);
    }

    /**
     * Aktiv randevuları olan klinikalar
     */
    protected function filterHasAppointments(Builder $query, $value): Builder
    {
        if ($value) {
            return $query->whereHas('appointments', function ($q) {
                $q->whereIn('status', ['pending', 'confirmed'])
                    ->where('appointment_date', '>=', now());
            });
        }

        return $query->whereDoesntHave('appointments', function ($q) {
            $q->whereIn('status', ['pending', 'confirmed'])
                ->where('appointment_date', '>=', now());
        });
    }

    /**
     * Məsafəyə görə filtrasiya (koordinatlar tələb olunur)
     */
    protected function filterDistance(Builder $query, $value): Builder
    {
        $coordinates = $this->request->get('coordinates');

        if (!$coordinates || !isset($coordinates['lat']) || !isset($coordinates['lng'])) {
            return $query;
        }

        $lat = $coordinates['lat'];
        $lng = $coordinates['lng'];
        $radius = $value; // km-lə

        return $query->selectRaw('*,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
            [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance');
    }

    /**
     * Koordinatlara görə yaxın klinikalar
     */
    protected function filterCoordinates(Builder $query, $value): Builder
    {
        if (!isset($value['lat']) || !isset($value['lng'])) {
            return $query;
        }

        $lat = $value['lat'];
        $lng = $value['lng'];
        $radius = $value['radius'] ?? 10; // default 10km

        return $query->selectRaw('*,
            (6371 * acos(cos(radians(?)) * cos(radians(latitude)) * cos(radians(longitude) - radians(?)) + sin(radians(?)) * sin(radians(latitude)))) AS distance',
            [$lat, $lng, $lat])
            ->having('distance', '<=', $radius)
            ->orderBy('distance');
    }

    /**
     * Müəyyən gündə işləyən klinikalar
     */
    protected function filterWorkingDay(Builder $query, $value): Builder
    {
        return $query->whereHas('workingHours', function ($q) use ($value) {
            $q->where('day_of_week', $value)
                ->where('is_closed', false);
        });
    }

    /**
     * Çoxdilli məzmunda axtarış
     */
    protected function filterSearch(Builder $query, string $value): Builder
    {
        return $query->where(function (Builder $q) use ($value) {
            // Əsas sahələrdə axtarış
            $q->where('name', 'like', "%{$value}%")
                ->orWhere('description', 'like', "%{$value}%")
                ->orWhere('address', 'like', "%{$value}%")
                ->orWhere('phone', 'like', "%{$value}%")
                ->orWhere('email', 'like', "%{$value}%");
        });
    }
}
