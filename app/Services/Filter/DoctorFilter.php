<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;

class DoctorFilter extends BaseFilter
{
    /**
     * Aktiv filterlərin siyahısı
     */
    protected array $filters = [
        'search',
        'category_id',
        'subcategory_id',
        'clinic_id',
        'service_id',
        'is_verified',
        'is_featured',
        'home_visit',
        'online_consultation',
        'experience_range',
        'fee_range',
        'rating_min',
        'language',
        'gender',
        'status',
        'is_active',
        'date_range',
        'trashed'
    ];

    /**
     * Default sıralama sütunu
     */
    protected string $defaultSortColumn = 'created_at';

    /**
     * Default sıralama istiqaməti
     */
    protected string $defaultSortDirection = 'desc';

    /**
     * Həkim adı və ya istifadəçi adı üzrə axtarış
     */
    protected function filterSearch(Builder $query, string $value): Builder
    {
        return $query->where(function(Builder $q) use ($value) {
            $q->whereHas('user', function($userQuery) use ($value) {
                $userQuery->where('name', 'like', "%{$value}%")
                    ->orWhere('surname', 'like', "%{$value}%")
                    ->orWhereRaw("CONCAT(name, ' ', surname) LIKE ?", ["%{$value}%"]);
            })
                ->orWhere('biography', 'like', "%{$value}%")
                ->orWhere('title', 'like', "%{$value}%");
        });
    }

    /**
     * İxtisas üzrə filtrasiya
     */
    protected function filterCategoryId(Builder $query, $value): Builder
    {
        return $query->where('category', $value);
    }

    /**
     * Alt ixtisas üzrə filtrasiya
     */
    protected function filterSubcategoryId(Builder $query, $value): Builder
    {
        return $query->where('sub_category', $value);
    }

    /**
     * Klinika üzrə filtrasiya
     */
    protected function filterClinicId(Builder $query, $value): Builder
    {
        return $query->whereHas('clinics', function($clinicQuery) use ($value) {
            $clinicQuery->where('clinic_id', $value);
        });
    }

    /**
     * Xidmət üzrə filtrasiya
     */
    protected function filterServiceId(Builder $query, $value): Builder
    {
        return $query->whereHas('services', function($serviceQuery) use ($value) {
            $serviceQuery->where('service_id', $value)
                ->where('is_active', true);
        });
    }

    /**
     * Təsdiqlənmiş həkimlər filtri
     */
    protected function filterIsVerified(Builder $query, $value): Builder
    {
        return $query->where('is_verified', (bool) $value);
    }

    /**
     * Populyar həkimlər filtri
     */
    protected function filterIsFeatured(Builder $query, $value): Builder
    {
        return $query->where('is_featured', (bool) $value);
    }

    /**
     * Ev ziyarəti edən həkimlər filtri
     */
    protected function filterHomeVisit(Builder $query, $value): Builder
    {
        if ((bool) $value) {
            $query->where('available_for_home_visit', true);
        }
        return $query;
    }

    /**
     * Online konsultasiya edən həkimlər filtri
     */
    protected function filterOnlineConsultation(Builder $query, $value): Builder
    {
        if ((bool) $value) {
            $query->where('available_for_online_consultation', true);
        }
        return $query;
    }

    /**
     * Təcrübə aralığı filtri
     */
    protected function filterExperienceRange(Builder $query, string $value): Builder
    {
        switch ($value) {
            case '0-2':
                return $query->where('years_of_experience', '<=', 2);
            case '3-5':
                return $query->whereBetween('years_of_experience', [3, 5]);
            case '6-10':
                return $query->whereBetween('years_of_experience', [6, 10]);
            case '11-15':
                return $query->whereBetween('years_of_experience', [11, 15]);
            case '16+':
                return $query->where('years_of_experience', '>=', 16);
            default:
                return $query;
        }
    }

    /**
     * Qiymət aralığı filtri
     */
    protected function filterFeeRange(Builder $query, string $value): Builder
    {
        switch ($value) {
            case '0-50':
                return $query->where('consultation_fee', '<=', 50);
            case '51-100':
                return $query->whereBetween('consultation_fee', [51, 100]);
            case '101-200':
                return $query->whereBetween('consultation_fee', [101, 200]);
            case '201+':
                return $query->where('consultation_fee', '>=', 201);
            default:
                return $query;
        }
    }

    /**
     * Minimum reytinq filtri
     */
    protected function filterRatingMin(Builder $query, $value): Builder
    {
        $minRating = (float) $value;
        return $query->whereRaw('(average_rating / NULLIF(total_ratings, 0)) >= ?', [$minRating]);
    }

    /**
     * Dil filtri
     */
    protected function filterLanguage(Builder $query, string $value): Builder
    {
        return $query->whereHas('languages', function($langQuery) use ($value) {
            $langQuery->where('language', $value);
        });
    }

    /**
     * Cins filtri
     */
    protected function filterGender(Builder $query, string $value): Builder
    {
        return $query->whereHas('user', function($userQuery) use ($value) {
            $userQuery->where('gender', $value);
        });
    }
}
