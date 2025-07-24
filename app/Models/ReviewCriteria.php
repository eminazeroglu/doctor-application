<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ReviewCriteria extends Model
{
    /**
     * İstifadə ediləcək cədvəl adı.
     * @var string
     */
    protected $table = 'review_criteria';

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'name',
        'type',
        'description',
        'weight',
        'is_active'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'weight' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['average_rating'];

    /**
     * Kriteriya üzrə orta qiymətləndirməni hesablayır.
     * @return AttributeAlias
     */
    public function averageRating(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $ratings = $this->ratings;

                if ($ratings->isEmpty()) {
                    return 0;
                }

                return round($ratings->avg('rating'), 1);
            }
        );
    }

    /**
     * Kriteriyaya aid qiymətləndirmələr əlaqəsi.
     * @return HasMany
     */
    public function ratings(): HasMany
    {
        return $this->hasMany(ReviewRating::class, 'criteria_id');
    }

    /**
     * Kriteriyaya aid rəylər əlaqəsi.
     * @return BelongsToMany
     */
    public function reviews(): BelongsToMany
    {
        return $this->belongsToMany(Review::class, 'review_ratings', 'criteria_id', 'review_id')
            ->withPivot(['rating'])
            ->withTimestamps();
    }

    /**
     * Həkimlər üçün olan kriteriyaları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeForDoctors(Builder $query): Builder
    {
        return $query->where('type', 'doctor');
    }

    /**
     * Klinikalar üçün olan kriteriyaları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeForClinics(Builder $query): Builder
    {
        return $query->where('type', 'clinic');
    }

    /**
     * Aktiv kriteriyaları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Çəkiyə görə sıralanmış kriteriyaları axtarış.
     * @param Builder $query
     * @param string $direction
     * @return Builder
     */
    public function scopeOrderByWeight(Builder $query, string $direction = 'desc'): Builder
    {
        return $query->orderBy('weight', $direction);
    }
}
