<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Pivot;

class ClinicService extends Pivot
{
    protected $table = 'clinic_services';

    protected $fillable = [
        'clinic_id',
        'service_id',
        'custom_price',
        'custom_duration',
        'is_featured',
        'custom_fields'
    ];

    protected $casts = [
        'custom_price' => 'decimal:2',
        'custom_duration' => 'integer',
        'is_featured' => 'boolean',
        'custom_fields' => 'json'
    ];

    /**
     * Bu əlaqənin aid olduğu klinika
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * Bu əlaqənin aid olduğu xidmət
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Qiyməti qaytarır - xüsusi qiymət varsa onu, yoxsa xidmətin standart qiymətini
     */
    public function price(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->custom_price ?? $this->service->price;
            }
        );
    }

    /**
     * Müddəti qaytarır - xüsusi müddət varsa onu, yoxsa xidmətin standart müddətini
     */
    public function duration(): Attribute
    {
        return new Attribute(
            get: function () {
                return $this->custom_duration ?? $this->service->duration;
            }
        );
    }
}
