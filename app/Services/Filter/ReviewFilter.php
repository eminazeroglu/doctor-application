<?php

namespace App\Services\Filter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class ReviewFilter extends BaseFilter
{
    protected array $filters = [
        'search',
        'doctor_id',
        'clinic_id',
        'patient_id',
        'rating',
        'min_rating',
        'max_rating',
        'status',
        'is_active',
        'is_moderated',
        'is_verified',
        'is_anonymous',
        'type',
        'date_range',
        'has_comment',
        'has_responses',
        'is_reported',
        'helpful_count',
        'sort_by_helpful',
        'sort_by_rating'
    ];

    protected string $defaultSortColumn = 'created_at';
    protected string $defaultSortDirection = 'desc';

    /**
     * Axtarış sahələri
     */
    protected function getSearchableFields(): array
    {
        return ['comment'];
    }

    /**
     * Axtarış filtri - rəy mətnində axtarış
     */
    protected function filterSearch(Builder $query, string $value): Builder
    {
        return $query->where(function(Builder $q) use ($value) {
            $q->where('comment', 'like', "%{$value}%")
                ->orWhereHas('patient.user', function($query) use ($value) {
                    $query->where('name', 'like', "%{$value}%")
                        ->orWhere('surname', 'like', "%{$value}%");
                })
                ->orWhereHas('doctor.user', function($query) use ($value) {
                    $query->where('name', 'like', "%{$value}%")
                        ->orWhere('surname', 'like', "%{$value}%");
                });
        });
    }

    /**
     * Həkimə görə filtr
     */
    protected function filterDoctorId(Builder $query, $value): Builder
    {
        return $query->where('doctor_id', $value);
    }

    /**
     * Klinikaya görə filtr
     */
    protected function filterClinicId(Builder $query, $value): Builder
    {
        return $query->where('clinic_id', $value);
    }

    /**
     * Xəstəyə görə filtr
     */
    protected function filterPatientId(Builder $query, $value): Builder
    {
        return $query->where('patient_id', $value);
    }

    /**
     * Dəqiq qiymətləndirməyə görə filtr
     */
    protected function filterRating(Builder $query, $value): Builder
    {
        return $query->where('rating', $value);
    }

    /**
     * Minimum qiymətləndirməyə görə filtr
     */
    protected function filterMinRating(Builder $query, $value): Builder
    {
        return $query->where('rating', '>=', $value);
    }

    /**
     * Maksimum qiymətləndirməyə görə filtr
     */
    protected function filterMaxRating(Builder $query, $value): Builder
    {
        return $query->where('rating', '<=', $value);
    }

    /**
     * Status filtri (xüsusi statuslar)
     */
    protected function filterStatus(Builder $query, $value): Builder
    {
        return match($value) {
            'active' => $query->where('is_active', true)
                ->where('is_moderated', true),
            'inactive' => $query->where('is_active', false),
            'awaiting_moderation' => $query->where('is_moderated', false)
                ->where('is_active', true),
            'reported' => $query->whereHas('reports', function($q) {
                $q->where('is_resolved', false);
            }),
            'verified' => $query->where('is_verified', true),
            'unverified' => $query->where('is_verified', false),
            default => $query
        };
    }

    /**
     * Aktivlik filtri
     */
    protected function filterIsActive(Builder $query, $value): Builder
    {
        return $query->where('is_active', $value);
    }

    /**
     * Moderasiya filtri
     */
    protected function filterIsModerated(Builder $query, $value): Builder
    {
        return $query->where('is_moderated', $value);
    }

    /**
     * Təsdiq filtri
     */
    protected function filterIsVerified(Builder $query, $value): Builder
    {
        return $query->where('is_verified', $value);
    }

    /**
     * Anonimlik filtri
     */
    protected function filterIsAnonymous(Builder $query, $value): Builder
    {
        return $query->where('is_anonymous', $value);
    }

    /**
     * Tip filtri (həkim/klinika)
     */
    protected function filterType(Builder $query, $value): Builder
    {
        return match($value) {
            'doctor' => $query->whereNotNull('doctor_id'),
            'clinic' => $query->whereNotNull('clinic_id'),
            default => $query
        };
    }

    /**
     * Şərh mövcudluğu filtri
     */
    protected function filterHasComment(Builder $query, $value): Builder
    {
        if ($value) {
            return $query->whereNotNull('comment')
                ->where('comment', '!=', '');
        }

        return $query->where(function($q) {
            $q->whereNull('comment')
                ->orWhere('comment', '');
        });
    }

    /**
     * Cavab mövcudluğu filtri
     */
    protected function filterHasResponses(Builder $query, $value): Builder
    {
        if ($value) {
            return $query->whereHas('responses');
        }

        return $query->whereDoesntHave('responses');
    }

    /**
     * Şikayət edilmə filtri
     */
    protected function filterIsReported(Builder $query, $value): Builder
    {
        if ($value) {
            return $query->whereHas('reports', function($q) {
                $q->where('is_resolved', false);
            });
        }

        return $query->whereDoesntHave('reports', function($q) {
            $q->where('is_resolved', false);
        });
    }

    /**
     * Faydalılıq sayına görə filtr
     */
    protected function filterHelpfulCount(Builder $query, $value): Builder
    {
        return $query->withCount(['helpful' => function($q) {
            $q->where('is_helpful', true);
        }])->having('helpful_count', '>=', $value);
    }

    /**
     * Faydalılığa görə sıralama
     */
    protected function filterSortByHelpful(Builder $query, $value): Builder
    {
        return $query->withCount(['helpful' => function($q) {
            $q->where('is_helpful', true);
        }])->orderBy('helpful_count', $value === 'asc' ? 'asc' : 'desc');
    }

    /**
     * Qiymətləndirməyə görə sıralama
     */
    protected function filterSortByRating(Builder $query, $value): Builder
    {
        return $query->orderBy('rating', $value === 'asc' ? 'asc' : 'desc');
    }

    /**
     * Sıralama üçün icazə verilən sütunlar
     */
    protected function getSortableColumns(): array
    {
        return [
            'id',
            'rating',
            'created_at',
            'updated_at',
            'is_active',
            'is_moderated',
            'is_verified'
        ];
    }
}
