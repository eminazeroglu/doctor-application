<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewRating extends Model
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'review_id',
        'criteria_id',
        'rating'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'rating' => 'integer',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['rating_stars'];

    /**
     * Qiymətləndirməni ulduz şəklində qaytarır.
     * @return AttributeAlias
     */
    public function ratingStars(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $stars = str_repeat('★', $this->rating);
                $emptyStars = str_repeat('☆', 5 - $this->rating);
                return $stars . $emptyStars;
            }
        );
    }

    /**
     * Qiymətləndirməyə aid rəy əlaqəsi.
     * @return BelongsTo
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Qiymətləndirməyə aid kriteriya əlaqəsi.
     * @return BelongsTo
     */
    public function criteria(): BelongsTo
    {
        return $this->belongsTo(ReviewCriteria::class, 'criteria_id');
    }

    /**
     * Qiymətləndirməyə görə axtarış.
     * @param Builder $query
     * @param int $rating
     * @return Builder
     */
    public function scopeWithRating(Builder $query, int $rating): Builder
    {
        return $query->where('rating', $rating);
    }

    /**
     * Minimum qiymətləndirməyə görə axtarış.
     * @param Builder $query
     * @param int $minRating
     * @return Builder
     */
    public function scopeMinRating(Builder $query, int $minRating): Builder
    {
        return $query->where('rating', '>=', $minRating);
    }

    /**
     * Kriteriyaya görə axtarış.
     * @param Builder $query
     * @param int $criteriaId
     * @return Builder
     */
    public function scopeForCriteria(Builder $query, int $criteriaId): Builder
    {
        return $query->where('criteria_id', $criteriaId);
    }

    /**
     * Rəyə görə axtarış.
     * @param Builder $query
     * @param int $reviewId
     * @return Builder
     */
    public function scopeForReview(Builder $query, int $reviewId): Builder
    {
        return $query->where('review_id', $reviewId);
    }
}
