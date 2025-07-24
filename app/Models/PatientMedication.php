<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedication extends BaseModel
{
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'patient_id',
        'doctor_id',
        'appointment_id',
        'medication_name',
        'dosage',
        'frequency',
        'instructions',
        'start_date',
        'end_date',
        'reason',
        'side_effects',
        'is_active',
        'notes'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['duration', 'is_expired', 'status'];

    /**
     * Dərman qəbulu müddətini qaytarır.
     * @return AttributeAlias
     */
    public function duration(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->end_date) {
                    return null;
                }

                $startDate = $this->start_date->format('d.m.Y');
                $endDate = $this->end_date->format('d.m.Y');

                return $startDate . ' - ' . $endDate;
            }
        );
    }

    /**
     * Dərman qəbulunun bitib-bitmədiyini yoxlayır.
     * @return AttributeAlias
     */
    public function isExpired(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->end_date) {
                    return false;
                }

                return $this->end_date->isPast();
            }
        );
    }

    /**
     * Dərman qəbulunun statusunu qaytarır.
     * @return AttributeAlias
     */
    public function status(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->is_active) {
                    return 'Dayandırılıb';
                }

                if ($this->is_expired) {
                    return 'Bitib';
                }

                if ($this->start_date->isFuture()) {
                    return 'Başlanmayıb';
                }

                return 'Aktiv';
            }
        );
    }

    /**
     * Dərman qeydlərinə aid xəstə əlaqəsi.
     * @return BelongsTo
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Dərman qeydlərinə aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Dərman qeydlərinə aid randevu əlaqəsi.
     * @return BelongsTo
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Aktiv dərman qeydlərini axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeActive($query): Builder
    {
        return $query->where('is_active', true)
            ->where(function($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now()->toDateString());
            });
    }

    /**
     * Müəyyən bir tarix aralığında olan dərman qeydlərini axtarış.
     * @param Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return Builder
     */
    public function scopeDateBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->where(function($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
                ->orWhereBetween('end_date', [$startDate, $endDate])
                ->orWhere(function($inner) use ($startDate, $endDate) {
                    $inner->where('start_date', '<=', $startDate)
                        ->where(function($deepInner) use ($endDate) {
                            $deepInner->whereNull('end_date')
                                ->orWhere('end_date', '>=', $endDate);
                        });
                });
        });
    }

    /**
     * Dərman adına görə axtarış.
     * @param Builder $query
     * @param string $medicationName
     * @return Builder
     */
    public function scopeWithMedicationName(Builder $query, string $medicationName): Builder
    {
        return $query->where('medication_name', 'like', "%{$medicationName}%");
    }
}
