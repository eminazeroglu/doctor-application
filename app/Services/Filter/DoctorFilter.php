<?php

namespace App\Services\Filter;

use App\Enums\TimeOfDayEnum;
use App\Models\Attribute;
use Carbon\Carbon;
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
        'city_id',
        'region_id',
        'subway_id',
        'is_verified',
        'is_featured',
        'home_visit',
        'online_consultation',
        'experience_range',
        'fee_range',
        'rating_min',
        'language',
        'gender',
        'is_active',
        'date_range',
        'trashed',
        'attributes',
        'availability_date_range',
        'time_of_day',
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
        return $query->where(function (Builder $q) use ($value) {
            $q->whereHas('user', function ($userQuery) use ($value) {
                $userQuery->where('name', 'like', "%{$value}%")
                    ->orWhere('surname', 'like', "%{$value}%")
                    ->orWhereRaw("CONCAT(name, ' ', surname) LIKE ?", ["%{$value}%"]);
            })
                ->orWhere('biography', 'like', "%{$value}%")
                ->orWhere('title', 'like', "%{$value}%");
        });
    }

    /**
     * YENİ - Tarix aralığında müsait olan həkimləri tapır
     *
     * @param Builder $query
     * @param array $value ['start_date' => 'Y-m-d', 'end_date' => 'Y-m-d']
     * @return Builder
     */
    protected function filterAvailabilityDateRange(Builder $query, array $value): Builder
    {
        if (!isset($value['start_date']) || !isset($value['end_date'])) {
            return $query;
        }

        $startDate = Carbon::parse($value['start_date']);
        $endDate = Carbon::parse($value['end_date']);

        // Həkimin verilən tarix aralığında ən azından bir aktiv schedule-ı olmalıdır
        return $query->whereHas('schedules', function ($scheduleQuery) use ($startDate, $endDate) {
            $scheduleQuery->where('is_active', true)
                ->where(function ($q) use ($startDate, $endDate) {
                    // Schedule-in başlanğıc tarixi aralığa düşür
                    $q->whereBetween('start_date', [$startDate->toDateString(), $endDate->toDateString()])
                        // və ya schedule aralığı daxilində və end_date yoxdur ya da bitməyib
                        ->orWhere(function ($qq) use ($startDate, $endDate) {
                            $qq->where('start_date', '<=', $startDate->toDateString())
                                ->where(function ($qqq) use ($endDate) {
                                    $qqq->whereNull('end_date')
                                        ->orWhere('end_date', '>=', $endDate->toDateString());
                                });
                        });
                })
                // Həmçinin schedule-in frequency pattern-lərinə uyğun olmalıdır
                ->where(function ($freqQuery) use ($startDate, $endDate) {
                    $this->addFrequencyCheck($freqQuery, $startDate, $endDate);
                });
        });
    }

    /**
     * Frequency pattern-lərini yoxlamaq üçün helper metod
     */
    private function addFrequencyCheck(Builder $query, Carbon $startDate, Carbon $endDate): void
    {
        // Bu məntiq mürəkkəbdir, sadə halda weekly pattern üçün
        // Daha dəqiq implementation üçün DoctorService-dəki məntiq istifadə edilə bilər

        $query->where(function ($freqQuery) use ($startDate, $endDate) {
            // Daily frequency
            $freqQuery->where('frequency', 'daily')
                // Weekly frequency - gələcək günlər üçün days array-ini yoxla
                ->orWhere(function ($weeklyQuery) use ($startDate, $endDate) {
                    $weeklyQuery->where('frequency', 'weekly')
                        ->where(function ($daysQuery) use ($startDate, $endDate) {
                            // Aralıqdakı günlər üçün days array-ini yoxla
                            $currentDate = $startDate->copy();
                            while ($currentDate->lte($endDate)) {
                                $dayOfWeek = $currentDate->dayOfWeek; // 0=Sunday, 1=Monday...
                                $daysQuery->orWhereJsonContains('days', $dayOfWeek);
                                $currentDate->addDay();
                            }
                        });
                })
                // Monthly frequency
                ->orWhere('frequency', 'monthly');
        });
    }

    /**
     * YENİ - Günün müəyyən hissəsində işləyən həkimləri tapır
     *
     * @param Builder $query
     * @param string|array $value TimeOfDayEnum dəyərləri
     * @return Builder
     */
    protected function filterTimeOfDay(Builder $query, $value): Builder
    {
        // Çoxlu seçim ola bilər
        $timeRanges = is_array($value) ? $value : [$value];

        return $query->whereHas('schedules', function ($scheduleQuery) use ($timeRanges) {
            $scheduleQuery->where('is_active', true)
                ->where(function ($timeQuery) use ($timeRanges) {
                    foreach ($timeRanges as $timeRange) {
                        // Enum-dan vaxt aralığını əldə et
                        $range = TimeOfDayEnum::getTimeRange($timeRange);

                        $timeQuery->orWhere(function ($rangeQuery) use ($range) {
                            // Schedule-in from_time və to_time-ı seçilən aralığa uyğun olmalıdır
                            $rangeQuery->where(function ($q) use ($range) {
                                // Schedule başlanğıc vaxtı seçilən aralığa düşür
                                $q->where('from_time', '>=', $range['start'])
                                    ->where('from_time', '<', $range['end']);
                            })
                                ->orWhere(function ($q) use ($range) {
                                    // Schedule bitiş vaxtı seçilən aralığa düşür
                                    $q->where('to_time', '>', $range['start'])
                                        ->where('to_time', '<=', $range['end']);
                                })
                                ->orWhere(function ($q) use ($range) {
                                    // Schedule seçilən aralığı tamamilə əhatə edir
                                    $q->where('from_time', '<=', $range['start'])
                                        ->where('to_time', '>=', $range['end']);
                                });
                        });
                    }
                });
        });
    }

    /**
     * İxtisas üzrə filtrasiya
     */
    protected function filterCategoryId($query, $value): Builder
    {
        return $query->where('category_id', $value);
    }

    /**
     * Alt ixtisas üzrə filtrasiya
     */
    protected function filterSubcategoryId($query, $value): Builder
    {
        return $query->where('sub_category_id', $value);
    }

    /**
     * Klinika üzrə filtrasiya
     */
    protected function filterClinicId($query, $value): Builder
    {
        return $query->whereHas('clinics', function ($clinicQuery) use ($value) {
            $clinicQuery->where('clinic_id', $value);
        });
    }

    /**
     * Şəhər üzrə filtrasiya
     */
    protected function filterCityId($query, $value): Builder
    {
        return $query->whereHas('clinics', function ($clinicQuery) use ($value) {
            $clinicQuery->where('clinics.city_id', $value);
        });
    }

    /**
     * Region üzrə filtrasiya
     */
    protected function filterRegionId($query, $value): Builder
    {
        return $query->whereHas('clinics', function ($clinicQuery) use ($value) {
            $clinicQuery->where('clinics.region_id', $value);
        });
    }

    /**
     * Metro üzrə filtrasiya
     */
    protected function filterSubwayId($query, $value): Builder
    {
        return $query->whereHas('clinics', function ($clinicQuery) use ($value) {
            $clinicQuery->where('clinics.subway_id', $value);
        });
    }

    /**
     * Xidmət üzrə filtrasiya
     */
    protected function filterServiceId(Builder $query, $value): Builder
    {
        return $query->whereHas('services', function ($serviceQuery) use ($value) {
            $serviceQuery->where('service_id', $value);
        });
    }

    /**
     * Təsdiqlənmiş həkimlər filtri
     */
    protected function filterIsVerified(Builder $query, $value): Builder
    {
        return $query->where('is_verified', (bool)$value);
    }

    /**
     * Populyar həkimlər filtri
     */
    protected function filterIsFeatured(Builder $query, $value): Builder
    {
        return $query->where('is_featured', (bool)$value);
    }

    /**
     * Ev ziyarəti edən həkimlər filtri
     */
    protected function filterHomeVisit(Builder $query, $value): Builder
    {
        if ((bool)$value) {
            $query->where('available_for_home_visit', true);
        }
        return $query;
    }

    /**
     * Online konsultasiya edən həkimlər filtri
     */
    protected function filterOnlineConsultation(Builder $query, $value): Builder
    {
        if ((bool)$value) {
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
        $minRating = (float)$value;
        return $query->whereRaw('(average_rating / NULLIF(total_ratings, 0)) >= ?', [$minRating]);
    }

    /**
     * Dil filtri
     */
    protected function filterLanguage(Builder $query, string $value): Builder
    {
        return $query->whereHas('languages', function ($langQuery) use ($value) {
            $langQuery->where('language', $value);
        });
    }

    /**
     * Cins filtri
     */
    protected function filterGender(Builder $query, string $value): Builder
    {
        return $query->whereHas('user', function ($userQuery) use ($value) {
            $userQuery->where('gender', $value);
        });
    }

    /**
     * 'attributes' parametri üçün filter
     * Dinamik atribut filtrlərini idarə edir
     *
     * Format: attributes[1]=5&attributes[2][]=3&attributes[2][]=4
     * 1, 2 - attribute_id, 3, 4, 5 - attribute_option_id və ya dəyər
     */
    protected function filterAttributes(Builder $query, array $attributes): Builder
    {
        foreach ($attributes as $attributeId => $value) {
            // Atributu yoxlayırıq
            $attribute = Attribute::find($attributeId);
            if (!$attribute) continue;

            // Attributun tipindən asılı olaraq fərqli davranırıq
            if (is_array($value)) {
                // Çoxlu seçim üçün
                $query->whereHas('attributes', function ($q) use ($attributeId, $value) {
                    $q->where('attribute_id', $attributeId)
                        ->where(function ($subQ) use ($value) {
                            foreach ($value as $optionId) {
                                // Əgər rəqəmdirsə, option_id kimi baxırıq
                                if (is_numeric($optionId)) {
                                    $subQ->orWhere('attribute_option_id', $optionId);
                                } else {
                                    // Əgər mətn isə, dəyər kimi baxırıq
                                    $subQ->orWhere('value', 'like', "%{$optionId}%");
                                }
                            }
                        });
                });
            } else {
                // Tək dəyər üçün
                $query->whereHas('attributes', function ($q) use ($attributeId, $value) {
                    $q->where('attribute_id', $attributeId);

                    // Əgər rəqəmdirsə, option_id kimi baxırıq
                    if (is_numeric($value)) {
                        $q->where('attribute_option_id', $value);
                    } else {
                        // Range atributları üçün xüsusi davranış (min-max)
                        if (str_contains($value, '-') && preg_match('/^[\d\.]+-[\d\.]+$/', $value)) {
                            list($min, $max) = explode('-', $value);
                            $q->where(function ($rangeQ) use ($min, $max) {
                                $rangeQ->where('value', '>=', $min)
                                    ->where('value', '<=', $max);
                            });
                        } else {
                            // Digər mətn dəyərləri üçün
                            $q->where('value', 'like', "%{$value}%");
                        }
                    }
                });
            }
        }

        return $query;
    }
}
