<?php

namespace App\Models;

use App\Enums\TransactionStatusEnum;
use App\Enums\TransactionTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentTransaction extends BaseModel
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'payment_id',
        'transaction_type',
        'transaction_id',
        'processor',
        'amount',
        'currency',
        'status',
        'response',
        'additional_info',
        'ip_address',
        'user_agent'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
        'additional_info' => 'json',
        'transaction_type' => 'string',
        'status' => 'string',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['type_text', 'status_text', 'formatted_amount', 'is_completed', 'is_failed'];

    /**
     * Əməliyyat növünün mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function typeText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return TransactionTypeEnum::getDescription($this->transaction_type);
            }
        );
    }

    /**
     * Əməliyyat statusunun mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function statusText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return TransactionStatusEnum::getDescription($this->status);
            }
        );
    }

    /**
     * Əməliyyat məbləğini formatlaşdırılmış şəkildə qaytarır.
     * @return AttributeAlias
     */
    public function formattedAmount(): AttributeAlias
    {
        return new AttributeAlias(
            get: function() {
                return number_format($this->amount, 2) . ' ' . $this->currency;
            }
        );
    }

    /**
     * Əməliyyatın tamamlanıb-tamamlanmadığını yoxlayır.
     * @return AttributeAlias
     */
    public function isCompleted(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->status === TransactionStatusEnum::Completed;
            }
        );
    }

    /**
     * Əməliyyatın uğursuz olub-olmadığını yoxlayır.
     * @return AttributeAlias
     */
    public function isFailed(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->status === TransactionStatusEnum::Failed;
            }
        );
    }

    /**
     * Əməliyyata aid ödəniş əlaqəsi.
     * @return BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Əməliyyatı tamamlanmış kimi işarələyir.
     * @return bool
     */
    public function markAsCompleted(): bool
    {
        $this->status = TransactionStatusEnum::Completed;
        return $this->save();
    }

    /**
     * Əməliyyatı uğursuz kimi işarələyir.
     * @return bool
     */
    public function markAsFailed(): bool
    {
        $this->status = TransactionStatusEnum::Failed;
        return $this->save();
    }

    /**
     * Əməliyyatı geri qaytarılmış kimi işarələyir.
     * @return bool
     */
    public function markAsReversed(): bool
    {
        $this->status = TransactionStatusEnum::Reversed;
        return $this->save();
    }

    /**
     * Ödəniş əməliyyatlarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopePayment(Builder $query): Builder
    {
        return $query->where('transaction_type', TransactionTypeEnum::Payment);
    }

    /**
     * Geri ödəmə əməliyyatlarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeRefund(Builder $query): Builder
    {
        return $query->where('transaction_type', TransactionTypeEnum::Refund);
    }

    /**
     * Ödəniş geri alması əməliyyatlarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeChargeback(Builder $query): Builder
    {
        return $query->where('transaction_type', TransactionTypeEnum::Chargeback);
    }

    /**
     * Komissiya əməliyyatlarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeFee(Builder $query): Builder
    {
        return $query->where('transaction_type', TransactionTypeEnum::Fee);
    }

    /**
     * Ödəmə əməliyyatlarını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopePayout(Builder $query): Builder
    {
        return $query->where('transaction_type', TransactionTypeEnum::Payout);
    }

    /**
     * Gözləmədə olan əməliyyatları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', TransactionStatusEnum::Pending);
    }

    /**
     * Tamamlanmış əməliyyatları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', TransactionStatusEnum::Completed);
    }

    /**
     * Uğursuz əməliyyatları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', TransactionStatusEnum::Failed);
    }

    /**
     * Geri qaytarılmış əməliyyatları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeReversed(Builder $query): Builder
    {
        return $query->where('status', TransactionStatusEnum::Reversed);
    }

    /**
     * Prosessora görə əməliyyatları axtarış.
     * @param Builder $query
     * @param string $processor
     * @return Builder
     */
    public function scopeByProcessor(Builder $query, string $processor): Builder
    {
        return $query->where('processor', $processor);
    }

    /**
     * Tranzaksiya ID-yə görə əməliyyatları axtarış.
     * @param Builder $query
     * @param string $transactionId
     * @return Builder
     */
    public function scopeWithTransactionId(Builder $query, string $transactionId): Builder
    {
        return $query->where('transaction_id', $transactionId);
    }

    /**
     * Son dövrün əməliyyatlarını axtarış.
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
}
