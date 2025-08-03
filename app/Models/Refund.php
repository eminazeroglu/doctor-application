<?php

namespace App\Models;

use App\Enums\RefundReasonEnum;
use App\Enums\RefundStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Refund extends BaseModel
{

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'payment_id',
        'amount',
        'reason',
        'notes',
        'status',
        'refund_id',
        'processed_by',
        'processed_at'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
        'processed_at' => 'datetime',
        'reason' => 'string',
        'status' => 'string',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['reason_text', 'status_text', 'formatted_amount', 'is_completed', 'is_pending'];

    /**
     * Geri ödəmə səbəbinin mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function reasonText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return RefundReasonEnum::getDescription($this->reason);
            }
        );
    }

    /**
     * Geri ödəmə statusunun mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function statusText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return RefundStatusEnum::getDescription($this->status);
            }
        );
    }

    /**
     * Geri ödəmə məbləğini formatlaşdırılmış şəkildə qaytarır.
     * @return AttributeAlias
     */
    public function formattedAmount(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return number_format($this->amount, 2) . ' ' . $this->payment->currency;
            }
        );
    }

    /**
     * Geri ödəmənin tamamlanıb-tamamlanmadığını yoxlayır.
     * @return AttributeAlias
     */
    public function isCompleted(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->status === RefundStatusEnum::Completed;
            }
        );
    }

    /**
     * Geri ödəmənin gözləmədə olub-olmadığını yoxlayır.
     * @return AttributeAlias
     */
    public function isPending(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->status === RefundStatusEnum::Pending;
            }
        );
    }

    /**
     * Geri ödəməyə aid ödəniş əlaqəsi.
     * @return BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Geri ödəməyə aid icra edən istifadəçi əlaqəsi.
     * @return BelongsTo
     */
    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Geri ödəməni tamamlanmış kimi işarələyir.
     * @param string|null $refundId Geri ödəmə ID-si
     * @return bool
     */
    public function markAsCompleted(?string $refundId = null): bool
    {
        $this->status = RefundStatusEnum::Completed;
        $this->processed_at = now();

        if ($refundId) {
            $this->refund_id = $refundId;
        }

        // Ödənişin statusunu da yeniləyək
        $payment = $this->payment;

        // Əgər geri ödəmə məbləği ödəniş məbləğinə bərabərdirsə, ödənişi tam geri ödənilmiş kimi işarələyək
        if ($this->amount >= $payment->amount) {
            $payment->markAsRefunded();
        } else {
            // Əks halda, qismən geri ödənilmiş kimi işarələyək
            $payment->markAsPartiallyRefunded();
        }

        return $this->save();
    }

    /**
     * Geri ödəməni uğursuz kimi işarələyir.
     * @return bool
     */
    public function markAsFailed(): bool
    {
        $this->status = RefundStatusEnum::Failed;
        $this->processed_at = now();
        return $this->save();
    }

    /**
     * Gözləmədə olan geri ödəmələri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', RefundStatusEnum::Pending);
    }

    /**
     * Tamamlanmış geri ödəmələri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', RefundStatusEnum::Completed);
    }

    /**
     * Uğursuz geri ödəmələri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', RefundStatusEnum::Failed);
    }

    /**
     * Səbəbə görə geri ödəmələri axtarış.
     * @param Builder $query
     * @param string $reason
     * @return Builder
     */
    public function scopeByReason(Builder $query, string $reason): Builder
    {
        return $query->where('reason', $reason);
    }

    /**
     * İcra edən istifadəçiyə görə geri ödəmələri axtarış.
     * @param Builder $query
     * @param int $userId
     * @return Builder
     */
    public function scopeProcessedBy(Builder $query, int $userId): Builder
    {
        return $query->where('processed_by', $userId);
    }

    /**
     * İcra tarixinə görə geri ödəmələri axtarış.
     * @param Builder $query
     * @param string $date
     * @return Builder
     */
    public function scopeProcessedOn(Builder $query, string $date): Builder
    {
        return $query->whereDate('processed_at', $date);
    }

    /**
     * Son dövrün geri ödəmələrini axtarış.
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
