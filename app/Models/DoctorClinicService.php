<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorClinicService extends BaseModel
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'doctor_clinic_id',
        'service_id',
        'price',
        'duration',
        'description',
        'is_active'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'price' => 'float',
        'duration' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Xidmətə aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctorClinic(): BelongsTo
    {
        return $this->belongsTo(DoctorClinic::class);
    }

    /**
     * Xidmətə aid servis əlaqəsi.
     * @return BelongsTo
     */
    public function service(): BelongsTo
    {
        return $this->belongsTo(Service::class);
    }
}
