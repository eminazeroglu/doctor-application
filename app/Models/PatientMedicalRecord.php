<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientMedicalRecord extends BaseModel
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
        'record_type',
        'diagnosis',
        'description',
        'treatment',
        'prescription',
        'notes',
        'record_date',
        'additional_info',
        'document_path',
        'is_private'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'record_date' => 'date',
        'additional_info' => 'json',
        'is_private' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['document_url', 'record_type_text'];

    /**
     * Sənəd URL-i qaytarır.
     * @return AttributeAlias
     */
    public function documentUrl(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->document_path ? url('uploads/files/' . $this->document_path) : null;
            }
        );
    }

    /**
     * Qeyd növünün mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function recordTypeText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return match($this->record_type) {
                    'diagnosis' => 'Diaqnoz',
                    'checkup' => 'Müayinə',
                    'test_result' => 'Test nəticəsi',
                    'consultation' => 'Konsultasiya',
                    'surgery' => 'Cərrahi əməliyyat',
                    'treatment' => 'Müalicə',
                    'other' => 'Digər',
                    default => $this->record_type
                };
            }
        );
    }

    /**
     * Tibbi qeydə aid xəstə əlaqəsi.
     * @return BelongsTo
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Tibbi qeydə aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Tibbi qeydə aid randevu əlaqəsi.
     * @return BelongsTo
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Qeyd növünə görə axtarış.
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('record_type', $type);
    }

    /**
     * Müəyyən bir tarix aralığında olan qeydləri axtarış.
     * @param Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return Builder
     */
    public function scopeDateBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('record_date', [$startDate, $endDate]);
    }

    /**
     * Son dövrün qeydlərini axtarış.
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('record_date', '>=', now()->subDays($days)->toDateString());
    }

    /**
     * Diaqnoza görə axtarış.
     * @param Builder $query
     * @param string $diagnosis
     * @return Builder
     */
    public function scopeWithDiagnosis(Builder $query, string $diagnosis): Builder
    {
        return $query->where('diagnosis', 'like', "%{$diagnosis}%");
    }
}
