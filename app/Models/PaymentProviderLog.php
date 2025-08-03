<?php

namespace App\Models;

use App\Enums\ProviderRequestTypeEnum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentProviderLog extends BaseModel
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'payment_provider_id',
        'transaction_id',
        'payment_id',
        'request_type',
        'request_data',
        'response_data',
        'response_code',
        'status',
        'error_message',
        'ip_address'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'request_type' => 'string',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['request_type_text', 'is_successful'];

    /**
     * Sorğu növünün mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function requestTypeText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return ProviderRequestTypeEnum::getDescription($this->request_type);
            }
        );
    }

    /**
     * Sorğunun uğurlu olub-olmadığını yoxlayır.
     * @return AttributeAlias
     */
    public function isSuccessful(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->status === 'success';
            }
        );
    }

    /**
     * Loga aid təchizatçı əlaqəsi.
     * @return BelongsTo
     */
    public function provider(): BelongsTo
    {
        return $this->belongsTo(PaymentProvider::class, 'payment_provider_id');
    }

    /**
     * Loga aid ödəniş əlaqəsi.
     * @return BelongsTo
     */
    public function payment(): BelongsTo
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Uğurlu sorğuları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        return $query->where('status', 'success');
    }

    /**
     * Uğursuz sorğuları axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', 'error');
    }

    /**
     * Sorğu növünə görə axtarış.
     * @param Builder $query
     * @param string $requestType
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $requestType): Builder
    {
        return $query->where('request_type', $requestType);
    }

    /**
     * Ödəniş sorğularını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopePayment(Builder $query): Builder
    {
        return $query->where('request_type', ProviderRequestTypeEnum::Payment);
    }

    /**
     * Geri ödəmə sorğularını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeRefund(Builder $query): Builder
    {
        return $query->where('request_type', ProviderRequestTypeEnum::Refund);
    }

    /**
     * Status yoxlaması sorğularını axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeStatusCheck(Builder $query): Builder
    {
        return $query->where('request_type', ProviderRequestTypeEnum::StatusCheck);
    }

    /**
     * Təchizatçıya görə axtarış.
     * @param Builder $query
     * @param int $providerId
     * @return Builder
     */
    public function scopeByProvider(Builder $query, int $providerId): Builder
    {
        return $query->where('payment_provider_id', $providerId);
    }

    /**
     * Tranzaksiya ID-yə görə axtarış.
     * @param Builder $query
     * @param string $transactionId
     * @return Builder
     */
    public function scopeWithTransactionId(Builder $query, string $transactionId): Builder
    {
        return $query->where('transaction_id', $transactionId);
    }

    /**
     * Cavab koduna görə axtarış.
     * @param Builder $query
     * @param string $responseCode
     * @return Builder
     */
    public function scopeWithResponseCode(Builder $query, string $responseCode): Builder
    {
        return $query->where('response_code', $responseCode);
    }

    /**
     * Son dövrün loglarını axtarış.
     * @param Builder $query
     * @param int $hours
     * @return Builder
     */
    public function scopeRecent(Builder $query, int $hours = 24): Builder
    {
        return $query->where('created_at', '>=', now()->subHours($hours));
    }
}
