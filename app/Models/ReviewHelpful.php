<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewHelpful extends Model
{
    /**
     * İstifadə ediləcək cədvəl adı.
     * @var string
     */
    protected $table = 'review_helpful';

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'review_id',
        'user_id',
        'is_helpful'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'is_helpful' => 'boolean',
    ];

    /**
     * Faydalılıq qeydinə aid rəy əlaqəsi.
     * @return BelongsTo
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Faydalılıq qeydini yazan istifadəçi əlaqəsi.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Faydalı hesab edilən qeydləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeHelpful(Builder $query): Builder
    {
        return $query->where('is_helpful', true);
    }

    /**
     * Faydalı hesab edilməyən qeydləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeNotHelpful(Builder $query): Builder
    {
        return $query->where('is_helpful', false);
    }
}
