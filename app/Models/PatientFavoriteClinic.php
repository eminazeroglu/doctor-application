<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class PatientFavoriteClinic extends Pivot
{
    protected $table = 'patient_favorite_clinics';
    /**
     * Kütləvi təyin edilə bilən atributlar.
     * @var array
     */
    protected $fillable = [
        'patient_id',
        'clinic_id',
        'note'
    ];

    /**
     * Verilənlər tipini çevrilməli olan atributlar.
     * @var array
     */
    protected $casts = [];
}
