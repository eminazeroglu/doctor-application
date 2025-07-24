<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewReport extends Model
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'review_id',
        'user_id',
        'reason',
        'is_resolved',
        'resolution_notes',
        'resolved_by',
        'resolved_at'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'is_resolved' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['status', 'reason_text'];

    /**
     * Şikayətin statusunu qaytarır.
     * @return AttributeAlias
     */
    public function status(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->is_resolved ? 'Həll olunub' : 'Gözləmədə';
            }
        );
    }

    /**
     * Şikayət səbəbinin mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function reasonText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return match($this->reason) {
                    'spam' => 'Spam',
                    'offensive' => 'Təhqiredici məzmun',
                    'inappropriate' => 'Uyğunsuz məzmun',
                    'false_information' => 'Yanlış məlumat',
                    'duplicate' => 'Təkrar rəy',
                    'other' => 'Digər',
                    default => $this->reason
                };
            }
        );
    }

    /**
     * Şikayətə aid rəy əlaqəsi.
     * @return BelongsTo
     */
    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }

    /**
     * Şikayəti yazan istifadəçi əlaqəsi.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Şikayəti həll edən istifadəçi əlaqəsi.
     * @return BelongsTo
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    /**
     * Şikayəti həll edilmiş kimi işarələyir.
     * @param int $resolvedBy Həll edən istifadəçi ID-si
     * @param string|null $notes Həll qeydləri
     * @return bool
     */
    public function resolve(int $resolvedBy, ?string $notes = null): bool
    {
        $this->is_resolved = true;
        $this->resolved_by = $resolvedBy;
        $this->resolved_at = now();

        if ($notes) {
            $this->resolution_notes = $notes;
        }

        return $this->save();
    }

    /**
     * Həll olunmuş şikayətləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeResolved(Builder $query): Builder
    {
        return $query->where('is_resolved', true);
    }

    /**
     * Həll edilməmiş şikayətləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeUnresolved(Builder $query): Builder
    {
        return $query->where('is_resolved', false);
    }

    /**
     * Şikayət səbəbinə görə axtarış.
     * @param Builder $query
     * @param string $reason
     * @return Builder
     */
    public function scopeWithReason(Builder $query, string $reason): Builder
    {
        return $query->where('reason', $reason);
    }
}
