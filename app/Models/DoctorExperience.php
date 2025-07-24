<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute as AttributeAlias;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorExperience extends BaseModel
{
    /**
     * İstifadə ediləcək cədvəl adı.
     * @var string
     */
    protected $table = 'doctor_experience';

    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'uuid',
        'doctor_id',
        'workplace',
        'position',
        'start_date',
        'end_date',
        'location',
        'description',
        'is_current_job',
        'document_path'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_current_job' => 'boolean',
    ];

    /**
     * Avtomatik əlavə edilən atributlar.
     * @var array
     */
    protected $appends = ['duration', 'years', 'document_url'];

    /**
     * İş müddətini qaytarır.
     * @return AttributeAlias
     */
    public function duration(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $startYear = $this->start_date->format('Y');

                if ($this->is_current_job) {
                    return $startYear . ' - İndiyə qədər';
                }

                $endYear = $this->end_date ? $this->end_date->format('Y') : 'İndiyə qədər';

                return $startYear . ' - ' . $endYear;
            }
        );
    }

    /**
     * İş müddətini il olaraq qaytarır.
     * @return AttributeAlias
     */
    public function years(): AttributeAlias
    {
        return new AttributeAlias(
            get: function () {
                $endDate = $this->is_current_job ? now() : $this->end_date;

                if (!$endDate) {
                    $endDate = now();
                }

                return $endDate->diffInYears($this->start_date);
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
     * İş təcrübəsi qeydinə aid həkim əlaqəsi.
     * @return BelongsTo
     */
    public function doctor(): BelongsTo
    {
        return $this->belongsTo(Doctor::class);
    }
}
