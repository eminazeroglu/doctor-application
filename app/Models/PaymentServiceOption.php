<?php

namespace App\Models;

use App\Enums\PaymentServiceOptionTypeEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentServiceOption extends Model
{
    protected $fillable = [
        'payment_service_id',
        'value',
        'amount',
        'custom_fields'
    ];

    protected $casts = [
        'amount' => 'float',
        'custom_fields' => 'json'
    ];

    public function paymentService(): BelongsTo
    {
        return $this->belongsTo(PaymentService::class);
    }

    /**
     * Seçilmiş option üçün saat dəyərini hesablayır.
     * Bu method xüsusilə VIP və Premium elanların müddətini hesablamaq üçün istifadə olunur.
     *
     * @return int Hesablanmış saat dəyəri
     */
    public function calculateHours(): int
    {
        // Option type-ı əldə edirik
        $optionType = $this->paymentService->option_type;

        // Value dəyərini option type-a görə saata çeviririk
        return match ($optionType) {
            PaymentServiceOptionTypeEnum::Hour => $this->value,           // Birbaşa saat
            PaymentServiceOptionTypeEnum::Day => $this->value * 24,       // Günü saata çeviririk
            PaymentServiceOptionTypeEnum::Week => $this->value * 24 * 7,  // Həftəni saata çeviririk
            PaymentServiceOptionTypeEnum::Month => $this->value * 24 * 30, // Ayı saata çeviririk
            default => 0
        };
    }
}
