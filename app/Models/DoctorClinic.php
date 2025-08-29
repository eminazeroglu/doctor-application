<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DoctorClinic extends Pivot
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'doctor_id',
        'clinic_id',
        'start_date',
        'end_date',
        'is_main_workplace',
        'is_active',
        'note'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_main_workplace' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['duration', 'is_current'];


    /*
     * Services
     * */
    public function services(): HasMany
    {
        return $this->hasMany(DoctorClinicService::class, 'doctor_clinic_id');
    }

    /*
     * Doctor
     * */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /*
     * Clinic
     * */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(Clinic::class);
    }

    /**
     * İş müddətini qaytarır.
     * @return AttributeAlias
     */
    public function duration(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $startYear = $this->start_date ? $this->start_date->format('Y') : '';

                if (!$this->end_date) {
                    return $startYear . ' - İndiyə qədər';
                }

                $endYear = $this->end_date->format('Y');

                return $startYear . ' - ' . $endYear;
            }
        );
    }

    /**
     * Hal-hazırda iş münasibətlərinin aktiv olub-olmadığını yoxlayır.
     * @return AttributeAlias
     */
    public function isCurrent(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->is_active) {
                    return false;
                }

                if (!$this->end_date) {
                    return true;
                }

                return $this->end_date->isFuture();
            }
        );
    }
}
