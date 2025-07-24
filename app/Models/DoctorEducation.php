<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorEducation extends BaseModel
{

    /**
     * İstifadə ediləcək cədvəl adı.
     * @var string
     */
    protected $table = 'doctor_education';

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'doctor_id',
        'university',
        'faculty',
        'degree',
        'specialization',
        'start_date',
        'end_date',
        'location',
        'description',
        'is_currently_studying',
        'document_path'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_currently_studying' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['duration', 'document_url'];

    /**
     * Təhsil müddətini qaytarır.
     * @return AttributeAlias
     */
    public function duration(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $startYear = $this->start_date->format('Y');

                if ($this->is_currently_studying) {
                    return $startYear . ' - İndiyə qədər';
                }

                $endYear = $this->end_date ? $this->end_date->format('Y') : 'İndiyə qədər';

                return $startYear . ' - ' . $endYear;
            }
        );
    }

    /**
     * Sənəd URL-i qaytarır.
     * @return AttributeAlias
     */
    public function documentUrl(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                return $this->document_path ? asset('storage/' . $this->document_path) : null;
            }
        );
    }

    /**
     * Təhsil qeydinə aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
