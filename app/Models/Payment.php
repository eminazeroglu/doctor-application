<?php

namespace App\Models;

use App\Enums\PaymentMethodEnum;
use App\Enums\PaymentStatusEnum;
use App\Traits\Model\HasCode;
use App\Traits\Model\HasLoggable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends BaseModel
{
    use HasCode, HasLoggable;

    protected $fillable = [
        'uuid',
        'user_id',
        'paymentable_id',
        'paymentable_type',
        'amount',
        'currency',
        'status',
        'payment_method',
        'transaction',
        'paid_at',
        'description',
        'custom_fields',
    ];

    /**
     * Avtomatik cast ediləcək sahələr
     */
    protected $casts = [
        'amount' => 'float',
        'transaction' => 'json',
        'custom_fields' => 'json',
        'paid_at' => 'datetime',
    ];

    /**
     * Virtual atributlar
     */
    protected $appends = ['status_text', 'payment_method_text', 'transaction_id', 'related_entity'];

    /**
     * Model yüklənərkən avtomatik eager loading
     */
    protected static function booted(): void
    {
        static::addGlobalScope('withPaymentableRelations', function (Builder $builder) {
            $builder->with(['paymentable' => function ($query) {
                $modelClass = $query->getModel()::class;
                $relations = static::getPaymentableRelations($modelClass);
                if (!empty($relations)) {
                    if ($modelClass === 'App\\Models\\ListingPaymentService') {
                        $query->with('listing:id,slug,code,category_id');
                    } else {
                        $query->with($relations);
                    }
                }
            }, 'user']);
        });
    }

    /*
    |--------------------------------------------------------------------------
    | ƏLAQƏLƏR (RELATIONSHIPS)
    |--------------------------------------------------------------------------
    */

    /**
     * Ödənişi edən istifadəçi
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Ödənişin hansı xidmətə aid olduğunu göstərir (polymorphic)
     */
    public function paymentable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Dinamik olaraq paymentable modelinə uyğun relationları təyin edir
     */
    protected static function getPaymentableRelations(string $modelClass): array
    {
        return match ($modelClass) {
            'App\\Models\\ListingPaymentService' => ['listing'],
            // Gələcəkdə başqa modellər üçün relationlar əlavə edilə bilər
            // 'App\\Models\\SubscriptionService' => ['subscription'],
            default => [],
        };
    }

    /*
    |--------------------------------------------------------------------------
    | VIRTUAL ATTRIBUTES
    |--------------------------------------------------------------------------
    */

    /**
     * Statusun təsvirini qaytarır
     */
    protected function statusText(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->status ? PaymentStatusEnum::getDescription($this->status) : null
        );
    }

    /**
     * Payment method təsvirini qaytarır
     */
    protected function paymentMethodText(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->payment_method ? PaymentMethodEnum::getDescription($this->payment_method) : null
        );
    }

    /**
     * Transaction ID atributu (tam formada)
     */
    protected function transactionId(): Attribute
    {
        return Attribute::make(
            get: fn() => $this->transaction->id ?? null, // JSON-dan ID-ni əldə edir
        );
    }

    /**
     * Related entity atributu (dinamik)
     */
    protected function relatedEntity(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->paymentable) {
                    return null;
                }

                return match ($this->paymentable_type) {
                    'App\\Models\\ListingPaymentService' => $this->paymentable->listing ? [
                        'type' => 'listing',
                        'id' => $this->paymentable->listing->id,
                        'code' => $this->paymentable->listing->code,
                    ] : null,
                   /* 'App\\Models\\SubscriptionService' => $this->paymentable->subscription ? [
                        'type' => 'subscription',
                        'id' => $this->paymentable->subscription->id,
                        'name' => $this->paymentable->subscription->name,
                    ] : null,*/
                    default => null,
                };
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS METODLARI
    |--------------------------------------------------------------------------
    */

    /**
     * Ödənişin tamamlanıb-tamamlanmadığını yoxlayır
     */
    public function isCompleted(): bool
    {
        return $this->status === PaymentStatusEnum::Completed;
    }

    /**
     * Ödənişin gözləmədə olub-olmadığını yoxlayır
     */
    public function isPending(): bool
    {
        return $this->status === PaymentStatusEnum::Pending;
    }

    /*
    |--------------------------------------------------------------------------
    | SCOPE METODLARI
    |--------------------------------------------------------------------------
    */

    /**
     * Tamamlanmış ödənişləri filtrləyir
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', PaymentStatusEnum::Completed);
    }

    /**
     * Gözləmədə olan ödənişləri filtrləyir
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentStatusEnum::Pending);
    }

    /**
     * Uğursuz ödənişləri filtrləyir
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', PaymentStatusEnum::Failed);
    }

    /**
     * Geri qaytarılmış ödənişləri filtrləyir
     */
    public function scopeRefunded(Builder $query): Builder
    {
        return $query->where('status', PaymentStatusEnum::Refunded);
    }
}
