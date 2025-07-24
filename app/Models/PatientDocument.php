<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientDocument extends BaseModel
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
        'document_type',
        'title',
        'description',
        'document_date',
        'file_path',
        'file_type',
        'file_size',
        'is_verified',
        'is_private'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'document_date' => 'date',
        'file_size' => 'integer',
        'is_verified' => 'boolean',
        'is_private' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['file_url', 'file_size_formatted', 'document_type_text', 'file_extension'];

    /**
     * Fayl URL-i qaytarır.
     * @return AttributeAlias
     */
    public function fileUrl(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return asset('storage/' . $this->file_path);
            }
        );
    }

    /**
     * Fayl ölçüsünü formatlaşdırılmış şəkildə qaytarır.
     * @return AttributeAlias
     */
    public function fileSizeFormatted(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $bytes = $this->file_size;

                if ($bytes < 1024) {
                    return $bytes . ' B';
                } elseif ($bytes < 1048576) {
                    return round($bytes / 1024, 2) . ' KB';
                } elseif ($bytes < 1073741824) {
                    return round($bytes / 1048576, 2) . ' MB';
                } else {
                    return round($bytes / 1073741824, 2) . ' GB';
                }
            }
        );
    }

    /**
     * Sənəd növünün mətn təsvirini qaytarır.
     * @return AttributeAlias
     */
    public function documentTypeText(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return match($this->document_type) {
                    'lab_result' => 'Laboratoriya nəticəsi',
                    'x_ray' => 'Rentgen',
                    'prescription' => 'Resept',
                    'mri' => 'MRT',
                    'ct_scan' => 'KT',
                    'ultrasound' => 'USM',
                    'ecg' => 'EKQ',
                    'referral' => 'Göndəriş',
                    'medical_certificate' => 'Tibbi arayış',
                    'other' => 'Digər',
                    default => $this->document_type
                };
            }
        );
    }

    /**
     * Faylın uzantısını qaytarır.
     * @return AttributeAlias
     */
    public function fileExtension(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                if (!$this->file_path) {
                    return null;
                }

                return pathinfo($this->file_path, PATHINFO_EXTENSION);
            }
        );
    }

    /**
     * Sənədə aid xəstə əlaqəsi.
     * @return BelongsTo
     */
    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    /**
     * Sənədə aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }

    /**
     * Sənədə aid randevu əlaqəsi.
     * @return BelongsTo
     */
    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    /**
     * Sənəd növünə görə axtarış.
     * @param Builder $query
     * @param string $type
     * @return Builder
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('document_type', $type);
    }

    /**
     * Müəyyən bir tarix aralığında olan sənədləri axtarış.
     * @param Builder $query
     * @param string $startDate
     * @param string $endDate
     * @return Builder
     */
    public function scopeDateBetween(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('document_date', [$startDate, $endDate]);
    }

    /**
     * Son dövrün sənədlərini axtarış.
     * @param Builder $query
     * @param int $days
     * @return Builder
     */
    public function scopeRecent(Builder $query, int $days = 30): Builder
    {
        return $query->where('document_date', '>=', now()->subDays($days)->toDateString());
    }

    /**
     * Təsdiqlənmiş sənədləri axtarış.
     * @param Builder $query
     * @return Builder
     */
    public function scopeVerified(Builder $query): Builder
    {
        return $query->where('is_verified', true);
    }
}
