<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends BaseModel
{
    use SoftDeletes;

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'user_id',
        'amount',
        'currency',
        'payment_method',
        'payment_status',
        'transaction_id',
        'description',
        'paid_at',
        'expires_at',
        'additional_info'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'additional_info' => 'json',
        'payment_method' => 'string',
        'payment_status' => 'string',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['status_text', 'method_text', 'is_paid', 'is_expired', 'formatted_amount'];

    /**
     * Ödəniş statusunun mətn təsvirini qaytarır.
     * @return string
     */
    public function statusText(): string
    {
        return PaymentStatusEnum::getDescription($this->payment_status);
    }

    /**
     * Ödəniş metodunun mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function methodText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return PaymentMethodEnum::getDescription($this->payment_method);
            }
        );
    }

    /**
     * Ödənişin edilib-edilmədiyini yoxlayır.
     * @return AttributeAlias
     */
    public function isPaid(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->payment_status === PaymentStatusEnum::Completed && $this->paid_at !== null;
            }
        );
    }

    /**
     * Ödəniş müddətinin bitib-bitmədiyini yoxlayır.
     * @return AttributeAlias
     */
    public function isExpired(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->expires_at) {
                    return false;
                }

                return $this->expires_at->isPast() && $this->payment_status === PaymentStatusEnum::Pending;
            }
        );
    }

    /**
     * Ödəniş məbləğini formatlaşdırılmış şəkildə qaytarır.
     * @return AttributeAlias
     */
    public function formattedAmount(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return number_format($this->amount, 2) . ' ' . $this->currency;
            }
        );
    }

    /**
     * Ödənişə aid istifadəçi əlaqəsi.
     * @return BelongsTo
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ödənişə aid faktura əlaqəsi.
     * @return HasOne
     */
    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    /**
     * Ödənişə aid əməliyyatlar əlaqəsi.
     * @return HasMany
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    /**
     * Ödənişə aid geri ödəmələr əlaqəsi.
     * @return HasMany
     */
    public function refunds(): HasMany
    {
        return $this->hasMany(Refund::class);
    }

    /**
     * Ödənişə aid randevu əlaqəsi.
     * @return HasOne
     */
    public function appointment(): HasOne
    {
        return $this->hasOne(Appointment::class);
    }

    /**
     * Ödənişə aid təchizatçı logları əlaqəsi.
     * @return HasMany
     */
    public function providerLogs(): HasMany
    {
        return $this->hasMany(PaymentProviderLog::class);
    }

    /**
     * Ödənişi tamamlanmış kimi işarələyir.
     * @param string|null $transactionId
     * @return bool
     */
    public function markAsCompleted(?string $transactionId = null): bool
    {
        $this->payment_status = PaymentStatusEnum::Completed;
        $this->paid_at = now();

        if ($transactionId) {
            $this->transaction_id = $transactionId;
        }

        return $this->save();
    }

    /**
     * Ödənişi uğursuz kimi işarələyir.
     * @return bool
     */
    public function markAsFailed(): bool
    {
        $this->payment_status = PaymentStatusEnum::Failed;
        return $this->save();
    }

    /**
     * Ödənişi ləğv edilmiş kimi işarələyir.
     * @return bool
     */
    public function markAsCancelled(): bool
    {
        $this->payment_status = PaymentStatusEnum::Cancelled;
        return $this->save();
    }

    /**
     * Ödənişi geri ödənilmiş kimi işarələyir.
     * @return bool
     */
    public function markAsRefunded(): bool
    {
        $this->payment_status = PaymentStatusEnum::Refunded;
        return $this->save();
    }

    /**
     * Ödənişi qismən geri ödənilmiş kimi işarələyir.
     * @return bool
     */
    public function markAsPartiallyRefunded(): bool
    {
        $this->payment_status = PaymentStatusEnum::PartiallyRefunded;
        return $this->save();
    }

    /**
     * Ödənişi vaxtı bitmiş kimi işarələyir.
     * @return bool
     */
    public function markAsExpired(): bool
    {
        $this->payment_status = PaymentStatusEnum::Expired;
        return $this->save();
    }

    /**
     * Ödəniş üçün faktura yaradır.
     * @param array $data
     * @return Invoice
     */
    public function createInvoice(array $data): Invoice
    {
        $invoiceData = array_merge([
            'uuid' => \Str::uuid(),
            'payment_id' => $this->id,
            'invoice_number' => 'INV-' . str_pad($this->id, 6, '0', STR_PAD_LEFT),
            'invoice_date' => now()->toDateString(),
            'status' => 'draft',
        ], $data);

        return Invoice::create($invoiceData);
    }

    /**
     * Ödəniş üçün əməliyyat yaradır.
     * @param array $data
     * @return PaymentTransaction
     */
    public function createTransaction(array $data): PaymentTransaction
    {
        $transactionData = array_merge([
            'uuid' => \Str::uuid(),
            'payment_id' => $this->id,
            'currency' => $this->currency,
        ], $data);

        return PaymentTransaction::create($transactionData);
    }

    /**
     * Ödəniş üçün geri ödəmə yaradır.
     * @param array $data
     * @return Refund
     */
    public function createRefund(array $data): Refund
    {
        $refundData = array_merge([
            'uuid' => \Str::uuid(),
            'payment_id' => $this->id,
        ], $data);

        return Refund::create($refundData);
    }

    /**
     * Gözləmədə olan ödənişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatusEnum::Pending);
    }

    /**
     * Tamamlanmış ödənişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatusEnum::Completed);
    }

    /**
     * Uğursuz ödənişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatusEnum::Failed);
    }

    /**
     * Geri ödənilmiş ödənişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeRefunded(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatusEnum::Refunded);
    }

    /**
     * Qismən geri ödənilmiş ödənişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopePartiallyRefunded(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatusEnum::PartiallyRefunded);
    }

    /**
     * Ləğv edilmiş ödənişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeCancelled(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatusEnum::Cancelled);
    }

    /**
     * Vaxtı bitmiş ödənişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeExpired(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('payment_status', PaymentStatusEnum::Pending)
                ->whereNotNull('expires_at')
                ->where('expires_at', '<', now());
        })->orWhere('payment_status', PaymentStatusEnum::Expired);
    }

    /**
     * Ödəniş metoduna görə axtarış.
     * @param Builder $query
     * @param string $method
     * @return Builder
     */
    public function scopeByMethod(Builder $query, string $method): Builder
    {
        return $query->where('payment_method', $method);
    }

    /**
     * Valyutaya görə axtarış.
     * @param Builder $query
     * @param string $currency
     * @return Builder
     */
    public function scopeByCurrency(Builder $query, string $currency): Builder
    {
        return $query->where('currency', $currency);
    }

    /**
     * Minimum məbləğə görə axtarış.
     * @param Builder $query
     * @param float $amount
     * @return Builder
     */
    public function scopeMinAmount(Builder $query, float $amount): Builder
    {
        return $query->where('amount', '>=', $amount);
    }

    /**
     * Maksimum məbləğə görə axtarış.
     * @param Builder $query
     * @param float $amount
     * @return Builder
     */
    public function scopeMaxAmount(Builder $query, float $amount): Builder
    {
        return $query->where('amount', '<=', $amount);
    }

    /**
     * Müəyyən bir tarix aralığında olan ödənişləri axtarış.
     * @param Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return Builder
     */
    public function scopeDateBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Son dövrün ödənişlərini axtarış.
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Geri ödənilə bilən ödənişləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeRefundable(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatusEnum::Completed)
            ->where('created_at', '>=', now()->subDays(30));
    }
}
