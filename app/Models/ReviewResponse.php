<?php

namespace App\Models;

use App\Enums\UserTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewResponse extends Model
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'review_id',
        'user_id',
        'response',
        'is_moderated',
        'is_active'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'is_moderated' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['status', 'author_type'];

    /**
     * Cavabın statusunu qaytarır.
     * @return AttributeAlias
     */
    public function status(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->is_active) {
                    return 'Deaktiv';
                }

                if (!$this->is_moderated) {
                    return 'Moderasiya gözləyir';
                }

                return 'Aktiv';
            }
        );
    }

    /**
     * Cavabı yazan istifadəçinin tipini qaytarır.
     * @return AttributeAlias
     */
    public function authorType(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if ($this->user->hasDoctor()) {
                    return 'Həkim';
                } elseif ($this->user->hasRole('admin')) {
                    return 'Administrator';
                } else {
                    return 'İstifadəçi';
                }
            }
        );
    }

    /**
     * Cavaba aid rəy əlaqəsi.
     * @return BelongsTo
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Cavabı yazan istifadəçi əlaqəsi.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Cavab moderasiyadan keçmiş kimi işarələnir.
     * @return bool
     */
    public function moderate(): bool
    {
        $this->is_moderated = true;
        return $this->save();
    }

    /**
     * Cavab deaktiv edilir.
     * @return bool
     */
    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }

    /**
     * Cavab aktiv edilir.
     * @return bool
     */
    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    /**
     * Aktiv cavabları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Moderasiyadan keçmiş cavabları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeModerated(Builder $query): Builder
    {
        return $query->where('is_moderated', true);
    }

    /**
     * Moderasiya gözləyən cavabları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeAwaitingModeration(Builder $query): Builder
    {
        return $query->where('is_moderated', false);
    }

    /**
     * Həkimlər tərəfindən yazılan cavabları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeByDoctors(Builder $query): Builder
    {
        return $query->whereRelation('user', 'user_type', UserTypeEnum::Doctor);
    }
}
